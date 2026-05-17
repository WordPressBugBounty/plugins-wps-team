<?php

/**
 * Term Order Resolver
 *
 * Resolves the effective sort order for every team-member query (shortcode,
 * AJAX filter, archive) that targets one or more taxonomy terms.
 *
 * ── RESOLUTION RULES ──────────────────────────────────────────────────────
 *
 * 1. No taxonomy filter applied
 *      → no-op.  Global menu_order is applied by Hooks::posts_orderby__premium_only.
 *
 * 2. Single active taxonomy term selected – saved order exists
 *      → FIELD()-based positioning for in-order posts; menu_order fallback.
 *
 * 3. Single active taxonomy term selected – no saved order
 *      → no-op.  Falls back to global menu_order.
 *
 * 4. Multiple terms selected (same or different taxonomies)
 *      effective_order = LEAST(
 *          COALESCE( NULLIF(FIELD(ID, <term1_order>), 0), 999999 ),
 *          COALESCE( NULLIF(FIELD(ID, <term2_order>), 0), 999999 ),
 *          …
 *      )
 *      Posts absent from every term order resolve to 999999 and are then
 *      ranked by their global menu_order as a secondary key.
 *
 * ── WHY FIELD() INSTEAD OF JOINs ──────────────────────────────────────────
 *
 * Per-term order is stored by Admin_Sortable as a single serialised PHP array
 * in term meta (meta_key = wps_team_term_post_order). Reading individual row
 * positions out of a serialised blob via SQL is not practical. Instead we
 * read the array in PHP once per term (one cheap get_term_meta() call), then
 * embed the ordered post-ID list directly into a SQL FIELD() expression.
 * FIELD() evaluates in the DB engine in a single table scan — no extra JOINs,
 * no subqueries, and no per-post PHP sorting.
 *
 * ── HOOK INTERACTION ──────────────────────────────────────────────────────
 *
 * WordPress's WP_Query fires hooks in this order:
 *   1. posts_orderby  (individual clause filter — Hooks::posts_orderby__premium_only)
 *   2. posts_clauses  (full clause bundle — Term_Order_Resolver::filter_posts_clauses)
 *
 * posts_clauses fires AFTER posts_orderby; its result is FINAL. So our
 * filter_posts_clauses completely replaces whatever posts_orderby produced
 * for the tagged queries. The effective_order SQL already embeds menu_order
 * as the secondary sort key, preserving deterministic ordering for posts that
 * fall outside every saved term order.
 *
 * @package WPSpeedo_Team
 */
namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Term_Order_Resolver {
    /**
     * Term-meta key used to store per-term post-order arrays.
     * Must match Admin_Sortable::TERM_POST_ORDER_META_KEY.
     */
    const META_KEY = 'wps_team_term_post_order';

    /**
     * Queue of pre-computed ORDER BY expressions indexed by a monotone slot
     * ID.  Populated by filter_query_params() and consumed (once) by
     * filter_posts_clauses() for the matching WP_Query instance.
     *
     * @var array<int,string>
     */
    private static $pending_sql = [];

    /** @var int  Counter used to generate unique slot IDs. */
    private static $slot = 0;

    public function __construct() {
        return;
        // Priority 25: runs after Admin_Sortable's deprecated frontend filter
        // (priority 20, now unregistered) so we get the last word in query_params.
        add_filter( 'wpspeedo_team/query_params', [$this, 'filter_query_params'], 25 );
        // posts_clauses fires after posts_orderby; our override is therefore final.
        add_filter(
            'posts_clauses',
            [$this, 'filter_posts_clauses'],
            25,
            2
        );
    }

    /* =====================================================================
     * 1.  DETECT QUERIED TAXONOMY TERMS
     * ===================================================================== */
    /**
     * Extract every active-team-taxonomy term referenced by a flat (or
     * one-level) tax_query inside the supplied WP_Query args array.
     *
     * Only IN / = operators are considered. Terms from native WP taxonomies
     * (e.g. `category`, `post_tag`) that are not in the plugin's active list
     * are ignored.
     *
     * @param array $args  WP_Query args (after wpspeedo_team/query_params filters).
     * @return array[]     Each element: ['taxonomy' => string, 'term_id' => int]
     */
    public function get_selected_terms( array $args ) : array {
        if ( empty( $args['tax_query'] ) || !is_array( $args['tax_query'] ) ) {
            return [];
        }
        $active = Utils::get_active_taxonomies();
        if ( empty( $active ) ) {
            return [];
        }
        $found = [];
        $seen = [];
        foreach ( $args['tax_query'] as $row ) {
            // Skip 'relation' key and malformed / nested rows.
            if ( !is_array( $row ) || empty( $row['taxonomy'] ) || !isset( $row['terms'] ) ) {
                continue;
            }
            if ( !in_array( $row['taxonomy'], $active, true ) ) {
                continue;
            }
            $operator = ( isset( $row['operator'] ) ? strtoupper( $row['operator'] ) : 'IN' );
            if ( !in_array( $operator, ['IN', '='], true ) ) {
                continue;
            }
            $field = ( isset( $row['field'] ) ? (string) $row['field'] : 'term_id' );
            foreach ( (array) $row['terms'] as $value ) {
                $term_id = $this->resolve_term_id( $value, $field, $row['taxonomy'] );
                if ( $term_id <= 0 ) {
                    continue;
                }
                $dedup_key = $row['taxonomy'] . ':' . $term_id;
                if ( isset( $seen[$dedup_key] ) ) {
                    continue;
                }
                $seen[$dedup_key] = true;
                $found[] = [
                    'taxonomy' => $row['taxonomy'],
                    'term_id'  => $term_id,
                ];
            }
        }
        return $found;
    }

    /* =====================================================================
     * 2.  BUILD ORDER JOIN (per-term saved ID list)
     * ===================================================================== */
    /**
     * Retrieve the saved per-term post display order for one taxonomy term.
     *
     * The method name follows the requested API contract. Internally it reads
     * a single term-meta row (no SQL JOIN needed because ordering is applied
     * via FIELD() in the main query's ORDER BY — see class docblock).
     *
     * @param string $taxonomy
     * @param int    $term_id
     * @return int[]  Ordered post IDs, or empty array when no order is saved.
     */
    public function build_order_join( string $taxonomy, int $term_id ) : array {
        $saved = get_term_meta( $term_id, self::META_KEY, true );
        if ( !is_array( $saved ) || empty( $saved ) ) {
            return [];
        }
        return array_values( array_filter( array_map( 'intval', $saved ) ) );
    }

    /* =====================================================================
     * 3.  RESOLVE EFFECTIVE ORDER SQL
     * ===================================================================== */
    /**
     * Build the complete ORDER BY expression for the given term scopes.
     *
     * SQL CONCEPT
     * ───────────
     *   effective_order =
     *     LEAST(
     *       COALESCE( NULLIF( FIELD(posts.ID, <ids for term-1>), 0 ), 999999 ),
     *       COALESCE( NULLIF( FIELD(posts.ID, <ids for term-2>), 0 ), 999999 ),
     *       …
     *     )
     *
     * • FIELD(ID, …) returns the 1-based position of ID in the list, or 0 if
     *   the ID is not present.
     * • NULLIF(…, 0) converts the "not found" sentinel 0 to NULL.
     * • COALESCE(…, 999999) replaces NULL with a large fallback so that posts
     *   absent from every term order sink to the bottom of the result set.
     * • LEAST(…) picks the smallest position across all selected terms.
     * • A secondary ORDER BY menu_order ensures stable ordering for posts that
     *   do not appear in any saved term order (those receive effective_order
     *   = 999999).
     *
     * Returns an empty string when NO term has a saved custom order, which
     * signals to the caller that the default menu_order sort should be kept.
     *
     * @param array[] $term_scopes  Output of get_selected_terms().
     * @param string  $dir          'ASC' (default) | 'DESC'
     * @return string               Full ORDER BY expression, or '' if nothing to do.
     */
    public function resolve_effective_order_sql( array $term_scopes, string $dir = 'ASC' ) : string {
        global $wpdb;
        $dir = ( strtoupper( $dir ) === 'DESC' ? 'DESC' : 'ASC' );
        $exprs = [];
        foreach ( $term_scopes as $scope ) {
            $ids = $this->build_order_join( $scope['taxonomy'], (int) $scope['term_id'] );
            if ( empty( $ids ) ) {
                // No saved order for this term — it does not contribute to
                // effective_order; global menu_order serves as implicit fallback.
                continue;
            }
            $ids_csv = implode( ',', $ids );
            $exprs[] = "COALESCE(NULLIF(FIELD({$wpdb->posts}.ID, {$ids_csv}), 0), 999999)";
        }
        if ( empty( $exprs ) ) {
            // All selected terms lack a saved order → keep default ordering.
            return '';
        }
        // Single term: LEAST() with one argument is redundant.
        $effective = ( count( $exprs ) === 1 ? $exprs[0] : 'LEAST(' . implode( ', ', $exprs ) . ')' );
        // Primary: effective position across all selected terms.
        // Secondary: global menu_order for posts at the fallback 999999 tier.
        // Tertiary: post ID for fully stable output.
        return "{$effective} {$dir}, {$wpdb->posts}.menu_order {$dir}, {$wpdb->posts}.ID ASC";
    }

    /* =====================================================================
     * 4.  HOOK: wpspeedo_team/query_params  (priority 25)
     * ===================================================================== */
    /**
     * Inspect the query args coming out of the shortcode / AJAX layer.
     *
     * When term-specific ordering is applicable (at least one term has a saved
     * custom order) this method:
     *   1. Pre-computes the effective_order ORDER BY SQL expression in PHP.
     *   2. Stores it in the static $pending_sql queue under a unique slot ID.
     *   3. Injects that slot ID into the args so filter_posts_clauses() can
     *      apply it to exactly the right WP_Query instance.
     *
     * When no term has a saved order the args are returned unchanged, allowing
     * the global menu_order fallback in Hooks::posts_orderby__premium_only to
     * take effect as normal.
     *
     * @param  array $args  WP_Query args.
     * @return array        Potentially modified args.
     */
    public function filter_query_params( array $args ) : array {
        // Admin main-query listings are handled by Admin_Sortable; leave them.
        if ( is_admin() && !wp_doing_ajax() ) {
            return $args;
        }
        // Shortcode filter mode: only run when "Enable custom order when filtering"
        // is on (Shortcode_Loader sets _wps_filter_custom_order = true).
        if ( array_key_exists( '_wps_filter_custom_order', $args ) && !$args['_wps_filter_custom_order'] ) {
            return $args;
        }
        // Only when ordering by custom / menu order.
        $orderby = ( isset( $args['orderby'] ) ? (string) $args['orderby'] : '' );
        if ( $orderby !== '' && $orderby !== 'menu_order' ) {
            return $args;
        }
        // Respect any explicit post__in already set (e.g. shortcode "include" option).
        if ( !empty( $args['post__in'] ) ) {
            return $args;
        }
        $terms = $this->get_selected_terms( $args );
        if ( empty( $terms ) ) {
            return $args;
        }
        // When the shortcode filter toggle is enabled, require at least one
        // filter-UI tax_query row (not include/exclude-only constraints).
        if ( !empty( $args['_wps_filter_custom_order'] ) && !$this->tax_query_has_filter_rows( $args ) ) {
            return $args;
        }
        $dir = ( isset( $args['order'] ) ? strtoupper( (string) $args['order'] ) : 'ASC' );
        $sql = $this->resolve_effective_order_sql( $terms, $dir );
        // All selected terms lack a saved order — keep global ordering.
        if ( $sql === '' ) {
            return $args;
        }
        // Stash the SQL and inject a slot ID into the query args.
        // WP_Query stores every key from the args array into query_vars,
        // so filter_posts_clauses() can retrieve it via $query->query_vars.
        $slot = ++self::$slot;
        self::$pending_sql[$slot] = $sql;
        $args['_wps_order_slot'] = $slot;
        return $args;
    }

    /* =====================================================================
     * 5.  HOOK: posts_clauses  (priority 25)
     * ===================================================================== */
    /**
     * Replace the ORDER BY clause of a WP_Query that was tagged by
     * filter_query_params() with the pre-computed effective_order SQL.
     *
     * posts_clauses fires AFTER posts_orderby in WP_Query::get_posts(), so
     * whatever this method sets for clauses['orderby'] is the final value
     * used to build the SQL query.
     *
     * @param  array     $clauses  SQL clause bundle.
     * @param  \WP_Query $query    Current WP_Query instance.
     * @return array               Modified clauses.
     */
    public function filter_posts_clauses( array $clauses, $query ) : array {
        if ( !$query instanceof \WP_Query ) {
            return $clauses;
        }
        // Only act on queries we previously tagged with a slot ID.
        $slot = ( isset( $query->query_vars['_wps_order_slot'] ) ? (int) $query->query_vars['_wps_order_slot'] : 0 );
        if ( $slot <= 0 || !isset( self::$pending_sql[$slot] ) ) {
            return $clauses;
        }
        $clauses['orderby'] = self::$pending_sql[$slot];
        // Consume the slot so it can't be accidentally applied to a second query.
        unset(self::$pending_sql[$slot]);
        return $clauses;
    }

    /* =====================================================================
     * PRIVATE HELPERS
     * ===================================================================== */
    /**
     * Resolve a raw term-field value to an integer term_id.
     *
     * @param mixed  $value     Raw value (term_id integer, slug string, etc.)
     * @param string $field     WP tax_query 'field' value.
     * @param string $taxonomy
     * @return int              0 on failure.
     */
    private function resolve_term_id( $value, string $field, string $taxonomy ) : int {
        if ( in_array( $field, ['term_id', 'id', 'ID'], true ) ) {
            return (int) $value;
        }
        $term = get_term_by( $field, $value, $taxonomy );
        return ( $term && !is_wp_error( $term ) ? (int) $term->term_id : 0 );
    }

    /**
     * Whether tax_query contains rows from the live filter UI (mirrors
     * Shortcode_Loader::has_active_filter_tax_query()).
     *
     * @param array $args WP_Query args.
     * @return bool
     */
    private function tax_query_has_filter_rows( array $args ) : bool {
        if ( empty( $args['tax_query'] ) || !is_array( $args['tax_query'] ) ) {
            return false;
        }
        $active = Utils::get_active_taxonomies();
        foreach ( $args['tax_query'] as $row ) {
            if ( !is_array( $row ) || empty( $row['taxonomy'] ) ) {
                continue;
            }
            if ( !in_array( $row['taxonomy'], $active, true ) ) {
                continue;
            }
            if ( isset( $row['operator'] ) && 'NOT IN' === strtoupper( (string) $row['operator'] ) ) {
                continue;
            }
            if ( !empty( $row['is_initial'] ) ) {
                return true;
            }
            if ( array_key_exists( 'include_children', $row ) && false === $row['include_children'] ) {
                return true;
            }
            if ( !isset( $row['field'] ) ) {
                return true;
            }
        }
        return false;
    }

}
