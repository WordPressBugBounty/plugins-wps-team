<?php
/**
 * Admin Sortable
 *
 * Adds a hybrid drag-and-drop + editable numeric "Order" column directly to:
 *   - wp-admin/edit.php?post_type={post_type}              (CPT listing)
 *   - wp-admin/edit-tags.php?taxonomy={taxonomy}           (Taxonomy listing)
 *
 * Replaces the dedicated "Team → Sort Order" admin page while reusing the
 * existing storage system (menu_order for posts, term_order for terms) so
 * existing saved ordering, frontend ordering, shortcode ordering, slider
 * ordering and filter ordering all continue to work exactly as before.
 *
 * GLOBAL POSITIONING SYSTEM
 * -------------------------
 * On every save the ENTIRE ordered set of items in scope (post type or
 * taxonomy) is renumbered to a gapless 1..N sequence using the relative
 * order users have already set. The moved item is inserted at the requested
 * global position and surrounding items shift by ±1. This guarantees:
 *   - unique ordering values (1..N, no duplicates)
 *   - gapless sequence
 *   - stable ordering for items outside the affected range
 *   - the same menu_order/term_order column is used as before, so no
 *     downstream query path needs to change.
 *
 * Pro feature only. Free version shows the column with a locked icon and an
 * upgrade tooltip but never modifies any data.
 *
 * @package WPSpeedo_Team
 */

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin_Sortable {

    /** AJAX action used by the drag handle and the inline number input. */
    const AJAX_ACTION = 'wpspeedo_team_save_drag_order';

    /** Nonce action shared by the AJAX endpoint. */
    const NONCE_ACTION = 'wpspeedo_team_drag_order';

    /** Default per-page fallback when the user has no screen option saved. */
    const DEFAULT_PER_PAGE = 20;

    /**
     * Term meta key storing the per-term post order as an array of post IDs
     * in display order. Activated when the listing is filtered by exactly
     * one active team taxonomy term (e.g. ?wps-team-group=project-manager).
     */
    const TERM_POST_ORDER_META_KEY = 'wps_team_term_post_order';

    /**
     * Per-request rank cache populated by {@see compute_post_ranks()} /
     * {@see compute_term_ranks()}. Avoids issuing one query per row.
     *
     * Shape: [
     *   'post' => [ post_id => rank, ... ] | null,
     *   'term' => [ taxonomy => [ term_id => rank, ... ], ... ],
     * ]
     */
    private $rank_cache = [
        'post' => null,
        'term' => [],
    ];

    /**
     * Memoized term-scope detection result for the current request.
     * Sentinel `false` means "not yet computed"; `null` means "no scope".
     *
     * @var array|null|false
     */
    private $term_scope_cache = false;

    /**
     * Per-request cache of [taxonomy:term_id => int[]] member ID lists.
     *
     * @var array<string,int[]>
     */
    private static $term_member_cache = [];

    public function __construct() {

        // Column registration is performed immediately (matches the pattern
        // used by Data class). Active taxonomies are read from saved options
        // which are always available at this point.
        $this->register_post_column_hooks();
        $this->register_term_column_hooks();

        // Lightweight sortable JS/CSS only on relevant screens.
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );

        // Single AJAX endpoint for both modes: 'single_move' (new global
        // positioning, used by drag and inline-edit) and 'slot_reshuffle'
        // (legacy block save kept for backward compatibility).
        add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'ajax_save_order' ] );

        // ── Native-style default ordering ────────────────────────────────
        // When the user visits the CPT or taxonomy listing page without an
        // explicit orderby/order in the URL we redirect once to apply the
        // plugin's default ordering key. This mirrors how core handles
        // column-header sorts and avoids permanently hijacking WP_Query.
        add_action( 'load-edit.php',      [ $this, 'maybe_redirect_to_default_post_order' ] );
        add_action( 'load-edit-tags.php', [ $this, 'maybe_redirect_to_default_term_order' ] );

        // Register the "Order" column as a native sortable column so clicking
        // its header toggles ASC/DESC like any core column (Name, Date, etc.).
        // The value 'menu_order' is a native WP_Query orderby, so no
        // pre_get_posts hook is required — WordPress handles it automatically.
        $post_type = Utils::post_type_name();
        add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'register_post_sortable_columns' ] );

        /*
         * Per-term post ordering. Activates only when the listing is
         * filtered by exactly one team taxonomy term in the URL, e.g.
         *   edit.php?post_type=wps-team-members&wps-team-group=project-manager
         * In that case:
         *   - the Order column shows term-specific positions (1..N)
         *   - drag / numeric input saves the order into term meta
         *   - the listing query sorts by the saved per-term order
         *   - frontend shortcodes filtered by that same term pick it up too
         * When no term filter is active, all behaviour is unchanged
         * (global menu_order).
         */
        add_filter( 'posts_orderby', [ $this, 'maybe_apply_term_scoped_orderby' ], 20, 2 );
        // Frontend single/multi-term ordering is now handled by Term_Order_Resolver
        // (includes/admin/term-order-resolver.php) which uses a SQL FIELD()+LEAST()
        // approach that covers multi-term queries correctly. The hook below is
        // intentionally NOT registered here to avoid conflicts.
        // apply_term_scoped_order_to_frontend is kept as a method for back-compat.
        add_action( 'admin_notices', [ $this, 'print_term_scope_notice' ] );
    }

    /* =================================================================
     * COLUMN REGISTRATION
     * ================================================================= */

    public function register_post_column_hooks() {

        $post_type = Utils::post_type_name();

        add_filter( "manage_edit-{$post_type}_columns",        [ $this, 'add_post_columns' ], 5 );
        add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_post_column' ], 10, 2 );
    }

    public function register_term_column_hooks() {

        $is_pro     = $this->is_pro();
        $taxonomies = Utils::get_active_taxonomies();

        foreach ( $taxonomies as $taxonomy ) {

            // Drag handle column for everyone (added by us, not by Data).
            add_filter( "manage_edit-{$taxonomy}_columns", [ $this, 'add_term_columns' ], 5 );

            if ( $is_pro ) {

                // PRO: Data class already registered "term_order" column.
                // We hijack its content (priority 20, after Data's 10) and
                // replace the static numeric value with an editable input.
                add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'render_term_columns_pro' ], 20, 3 );

            } else {

                // Free: Data does not register the "term_order" column. We
                // expose our own "wps_order_pos" column with a read-only
                // locked input (the drag handle column is the same).
                add_filter( "manage_{$taxonomy}_custom_column", [ $this, 'render_term_columns_free' ], 10, 3 );
            }
        }
    }

    /* =================================================================
     * POST LIST COLUMNS
     * ================================================================= */

    /**
     * Insert the "Order" column into the CPT list table immediately before
     * the "Date" column. The drag handle is no longer a separate PHP column;
     * it is injected by JS into td.check-column (see admin-sortable.js
     * injectDragHandles()), keeping the table one column narrower.
     *
     * Fallback: if 'date' is absent the Order column is appended at the end.
     */
    public function add_post_columns( $columns ) {

        if ( ! is_array( $columns ) || empty( $columns ) ) return $columns;

        $order_label = esc_html_x( 'Order', 'Admin column', 'wps-team' );

        $new = [];
        foreach ( $columns as $key => $label ) {

            // Order (number input) is inserted BEFORE the Date column.
            if ( 'date' === $key ) {
                $new['wps_order_pos'] = $order_label;
            }

            $new[ $key ] = $label;
        }

        // Fallback: 'date' column was not present — append at the end.
        if ( ! isset( $new['wps_order_pos'] ) ) {
            $new['wps_order_pos'] = $order_label;
        }

        return $new;
    }

    /**
     * Render the Order column for a post row.
     * The drag handle is no longer a PHP column; it is injected by JS.
     */
    public function render_post_column( $column, $post_id ) {

        if ( 'wps_order_pos' !== $column ) return;

        $post_id = (int) $post_id;
        $rank    = $this->get_post_rank( $post_id );
        $total   = $this->get_total_posts();

        echo $this->build_order_input_html( 'post', $post_id, $rank, $total ); // phpcs:ignore WordPress.Security.EscapeOutput
    }

    /* =================================================================
     * TERM LIST COLUMNS
     * ================================================================= */

    /**
     * Insert columns on the taxonomy listing screen.
     *
     * PRO:  Data class already registers and renders "term_order".
     *       render_term_columns_pro() converts it to an editable input.
     *       No extra column is needed; the drag handle is injected by JS.
     *
     * Free: Add a locked "wps_order_pos" column before "posts". The drag
     *       handle is also injected by JS (locked, muted icon).
     */
    public function add_term_columns( $columns ) {

        if ( ! is_array( $columns ) || empty( $columns ) ) return $columns;

        // PRO: Data class already adds "term_order"; we only enhance its
        // content in render_term_columns_pro(). Nothing to add here.
        if ( $this->is_pro() ) return $columns;

        // Free: add a locked read-only "Order" column before "posts".
        $order_label = esc_html_x( 'Order', 'Admin column', 'wps-team' );
        $new         = [];
        $inserted    = false;

        foreach ( $columns as $key => $label ) {
            if ( ! $inserted && 'posts' === $key ) {
                $new['wps_order_pos'] = $order_label;
                $inserted = true;
            }
            $new[ $key ] = $label;
        }

        if ( ! $inserted ) {
            $new['wps_order_pos'] = $order_label;
        }

        return $new;
    }

    /**
     * Render the drag-handle column AND replace the existing term_order
     * column content with the editable number input (PRO).
     *
     * Runs at priority 20 so the static numeric output written by
     * Data::show_term_order_column__premium_only (priority 10) is replaced
     * here, but the column registration / sortable header / Quick Edit
     * input registered by Data class continue to work as before.
     */
    /**
     * Replace the static term_order cell with an editable input (PRO).
     * The drag handle is no longer a PHP column; injected by JS instead.
     */
    public function render_term_columns_pro( $content, $column_name, $term_id ) {

        if ( 'term_order' !== $column_name ) return $content;

        $term_id  = (int) $term_id;
        $taxonomy = $this->current_taxonomy();
        $rank     = $this->get_term_rank( $term_id, $taxonomy );
        $total    = $this->get_total_terms( $taxonomy );

        return $this->build_order_input_html( 'term', $term_id, $rank, $total );
    }

    /**
     * Free path: read-only locked number input. Drag handle is injected by JS.
     */
    public function render_term_columns_free( $content, $column_name, $term_id ) {

        if ( 'wps_order_pos' !== $column_name ) return $content;

        return $this->build_order_input_html( 'term', (int) $term_id, 0, 0 );
    }

    /* =================================================================
     * MARKUP BUILDERS
     * ================================================================= */

    /**
     * Build the drag-handle markup for a row. Hidden span carries the
     * row metadata (parent_id / current rank) so the JS can read it
     * without touching the <tr> attributes.
     *
     * @param string $type      'post' or 'term'.
     * @param int    $object_id Post ID or term ID.
     * @param array  $meta      Optional extra metadata.
     */
    protected function build_drag_handle_html( $type, $object_id, $meta = [] ) {

        $is_pro = $this->is_pro();
        $type   = ( $type === 'term' ) ? 'term' : 'post';

        $classes = [ 'wps-team--drag-handle', 'wps-team--drag-handle-' . $type ];
        if ( ! $is_pro ) $classes[] = 'wps-team--drag-locked';

        $title = $is_pro
            ? __( 'Drag to reorder', 'wps-team' )
            : __( 'Drag to reorder is a Pro feature', 'wps-team' );

        $icon = $is_pro ? 'dashicons-menu' : 'dashicons-lock';

        $data_attrs = [
            'data-wps-id'     => (int) $object_id,
            'data-wps-type'   => $type,
            'data-wps-parent' => isset( $meta['parent_id'] )     ? (int) $meta['parent_id']     : 0,
            'data-wps-order'  => isset( $meta['current_order'] ) ? (int) $meta['current_order'] : 0,
        ];

        $attrs = '';
        foreach ( $data_attrs as $key => $value ) {
            $attrs .= sprintf( ' %s="%s"', esc_attr( $key ), esc_attr( $value ) );
        }

        return sprintf(
            '<span class="%1$s" title="%2$s"%3$s><span class="dashicons %4$s" aria-hidden="true"></span><span class="screen-reader-text">%2$s</span></span>',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $title ),
            $attrs,
            esc_attr( $icon )
        );
    }

    /**
     * Build the editable number input that drives the global positioning
     * system. The input is wired up by the JS via the wps-team--order-input
     * class.
     *
     * Free users get a disabled input with a lock icon and click handler
     * that opens an upgrade prompt.
     *
     * @param string $type      'post' or 'term'.
     * @param int    $object_id Post ID or term ID.
     * @param int    $rank      Current global rank (1..N).
     * @param int    $total     Total number of items in scope.
     */
    protected function build_order_input_html( $type, $object_id, $rank, $total ) {

        $is_pro = $this->is_pro();
        $type   = ( $type === 'term' ) ? 'term' : 'post';

        $rank  = max( 0, (int) $rank );
        $total = max( 0, (int) $total );

        // For terms: expose the parent_id so the JS hierarchy guard can
        // read it from the input's data attribute after the drag-handle
        // column was merged into td.check-column via JS injection.
        $parent_id = 0;
        if ( 'term' === $type ) {
            $term_obj  = get_term( (int) $object_id );
            $parent_id = ( $term_obj && ! is_wp_error( $term_obj ) ) ? (int) $term_obj->parent : 0;
        }

        $input_attrs = [
            'type'              => 'number',
            'class'             => 'wps-team--order-input',
            'min'               => 1,
            'max'               => $total > 0 ? $total : 1,
            'step'              => 1,
            'value'             => $rank > 0 ? $rank : '',
            'data-wps-id'       => (int) $object_id,
            'data-wps-type'     => $type,
            'data-wps-parent'   => $parent_id,
            'data-wps-current'  => $rank,
            'data-wps-total'    => $total,
            'aria-label'        => $is_pro
                ? __( 'Global order position', 'wps-team' )
                : __( 'Global order position (Pro feature)', 'wps-team' ),
            'inputmode'         => 'numeric',
            'autocomplete'      => 'off',
        ];

        if ( ! $is_pro ) {
            $input_attrs['readonly'] = 'readonly';
            $input_attrs['class']   .= ' wps-team--order-input-locked';
            $input_attrs['title']    = __( 'Editing the order is a Pro feature', 'wps-team' );
        } else {
            $input_attrs['title'] = __( 'Type a number and press Enter to move this item to that global position', 'wps-team' );
        }

        $attr_html = '';
        foreach ( $input_attrs as $k => $v ) {
            if ( $v === '' || $v === null ) continue;
            $attr_html .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $v ) );
        }

        $spinner = '<span class="wps-team--order-spinner spinner" aria-hidden="true"></span>';

        return sprintf(
            '<span class="wps-team--order-cell">%1$s<input%2$s />%3$s</span>',
            $is_pro ? '' : '<span class="dashicons dashicons-lock wps-team--order-lock-icon" aria-hidden="true"></span>',
            $attr_html,
            $spinner
        );
    }

    /* =================================================================
     * RANK COMPUTATION
     * ================================================================= */

    /**
     * Get the global 1-indexed rank of a post in the team CPT, ordered by
     * menu_order ASC, ID ASC. Result is cached per request.
     */
    public function get_post_rank( $post_id ) {

        if ( null === $this->rank_cache['post'] ) {
            $this->rank_cache['post'] = $this->compute_post_ranks();
        }

        return isset( $this->rank_cache['post'][ (int) $post_id ] )
            ? (int) $this->rank_cache['post'][ (int) $post_id ]
            : 0;
    }

    /**
     * Total number of posts in scope (excludes auto-draft / trash).
     */
    public function get_total_posts() {

        if ( null === $this->rank_cache['post'] ) {
            $this->rank_cache['post'] = $this->compute_post_ranks();
        }

        return count( $this->rank_cache['post'] );
    }

    /**
     * Get the global 1-indexed rank of a term in the given taxonomy,
     * ordered by term_order ASC, term_id ASC. Cached per request.
     */
    public function get_term_rank( $term_id, $taxonomy ) {

        if ( ! $taxonomy ) return 0;

        if ( ! isset( $this->rank_cache['term'][ $taxonomy ] ) ) {
            $this->rank_cache['term'][ $taxonomy ] = $this->compute_term_ranks( $taxonomy );
        }

        return isset( $this->rank_cache['term'][ $taxonomy ][ (int) $term_id ] )
            ? (int) $this->rank_cache['term'][ $taxonomy ][ (int) $term_id ]
            : 0;
    }

    /**
     * Total number of terms in scope.
     */
    public function get_total_terms( $taxonomy ) {

        if ( ! $taxonomy ) return 0;

        if ( ! isset( $this->rank_cache['term'][ $taxonomy ] ) ) {
            $this->rank_cache['term'][ $taxonomy ] = $this->compute_term_ranks( $taxonomy );
        }

        return count( $this->rank_cache['term'][ $taxonomy ] );
    }

    /**
     * One query that returns every post ID in the team CPT in canonical
     * order. Returns [ post_id => rank ].
     *
     * When the request is term-scoped (filtering the listing by one
     * taxonomy term), this delegates to compute_term_scoped_post_ranks()
     * so the Order column shows positions WITHIN that term and the move
     * algorithm operates only on the term's members.
     */
    protected function compute_post_ranks() {

        $term_scope = $this->get_current_term_scope();
        if ( $term_scope ) {
            return $this->compute_term_scoped_post_ranks( $term_scope['taxonomy'], $term_scope['term_id'] );
        }

        global $wpdb;

        $post_type = Utils::post_type_name();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_type = %s
               AND post_status NOT IN ('auto-draft', 'trash')
             ORDER BY menu_order ASC, ID ASC",
            $post_type
        ) );
        // phpcs:enable

        $ranks = [];
        foreach ( (array) $ids as $i => $id ) {
            $ranks[ (int) $id ] = $i + 1;
        }
        return $ranks;
    }

    /**
     * Same as compute_post_ranks() but for terms in a single taxonomy.
     */
    protected function compute_term_ranks( $taxonomy ) {

        global $wpdb;

        // phpcs:disable WordPress.DB.DirectDatabaseQuery
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT t.term_id
             FROM {$wpdb->terms} AS t
             INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
             WHERE tt.taxonomy = %s
             ORDER BY t.term_order ASC, t.term_id ASC",
            $taxonomy
        ) );
        // phpcs:enable

        $ranks = [];
        foreach ( (array) $ids as $i => $id ) {
            $ranks[ (int) $id ] = $i + 1;
        }
        return $ranks;
    }

    /* =================================================================
     * ENQUEUE ASSETS
     * ================================================================= */

    public function enqueue_assets( $hook ) {

        $context = $this->get_screen_context( $hook );
        if ( false === $context ) return;

        wp_register_style(
            'wps-team-admin-sortable',
            WPS_TEAM_ADMIN_ASSET_URL . 'css/admin-sortable.min.css',
            [ 'dashicons' ],
            WPS_TEAM_VERSION
        );
        wp_enqueue_style( 'wps-team-admin-sortable' );

        wp_register_script(
            'wps-team-admin-sortable',
            WPS_TEAM_ADMIN_ASSET_URL . 'js/admin-sortable.min.js',
            [ 'jquery', 'jquery-ui-sortable' ],
            WPS_TEAM_VERSION,
            true
        );

        $upgrade_url = 'https://wpspeedo.com/wps-team-pro/?utm_source=wp-plugins&utm_campaign=admin-sortable&utm_medium=wp-dash';
        if ( function_exists( 'wps_team_fs' ) && method_exists( wps_team_fs(), 'get_upgrade_url' ) ) {
            $upgrade_url = wps_team_fs()->get_upgrade_url();
        }

        // Per-page count is needed to compute "page where the moved item
        // now lives" so we can redirect the user there after a move.
        $context['per_page'] = $this->get_per_page_for_context( $context );
        $context['total']   = $this->get_total_for_context( $context );

        // When the CPT listing is filtered by a single taxonomy term, the
        // Order column behaves as a TERM-SCOPED order. The JS forwards
        // this payload on every AJAX save so the server can route the
        // write to term meta instead of the global menu_order column.
        if ( 'post' === $context['type'] ) {
            $term_scope = $this->get_current_term_scope();
            if ( $term_scope ) {
                $context['term_scope'] = [
                    'taxonomy'  => $term_scope['taxonomy'],
                    'term_id'   => $term_scope['term_id'],
                    'term_name' => $term_scope['term_name'],
                ];
            }
        }

        wp_localize_script( 'wps-team-admin-sortable', 'wpsTeamSortable', [
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'action'     => self::AJAX_ACTION,
            'nonce'      => wp_create_nonce( self::NONCE_ACTION ),
            'context'    => $context,
            'isPro'      => $this->is_pro(),
            'upgradeUrl' => $upgrade_url,
            'i18n'       => [
                'saving'        => __( 'Saving order…', 'wps-team' ),
                'saved'         => __( 'Order saved successfully.', 'wps-team' ),
                'failed'        => __( 'Could not save order. Please try again.', 'wps-team' ),
                'dragToReorder' => __( 'Drag to reorder', 'wps-team' ),
                'lockedNotice'  => __( 'Drag-and-drop ordering is a Pro feature.', 'wps-team' ),
                'customSort'    => __( 'Custom Sort', 'wps-team' ),
                'crossParent'   => __( 'Child terms can only be reordered within their own parent.', 'wps-team' ),
                'invalidNumber' => __( 'Please enter a valid order number.', 'wps-team' ),
                'upgrade'       => __( 'Upgrade to Pro', 'wps-team' ),
            ],
        ] );

        wp_enqueue_script( 'wps-team-admin-sortable' );
    }

    /**
     * Decide whether the current admin screen needs the sortable assets
     * and what context (post / term) to pass to the script.
     *
     * @return array|false Context array or false to skip enqueue.
     */
    protected function get_screen_context( $hook ) {

        $post_type = Utils::post_type_name();

        // CPT listing screen: edit.php?post_type=wps-team-members
        if ( 'edit.php' === $hook ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $current_pt = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
            if ( $current_pt === $post_type ) {
                return [ 'type' => 'post', 'post_type' => $post_type ];
            }
        }

        // Taxonomy listing screen: edit-tags.php?taxonomy=...&post_type=...
        if ( 'edit-tags.php' === $hook ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $current_tax = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
            $active_taxes = Utils::get_active_taxonomies();
            if ( $current_tax && in_array( $current_tax, $active_taxes, true ) ) {

                $taxonomy_obj = get_taxonomy( $current_tax );
                $hierarchical = $taxonomy_obj ? (bool) $taxonomy_obj->hierarchical : false;

                return [
                    'type'         => 'term',
                    'taxonomy'     => $current_tax,
                    'hierarchical' => $hierarchical,
                ];
            }
        }

        return false;
    }

    /**
     * Resolve user's per-page screen option for the current screen.
     */
    protected function get_per_page_for_context( $context ) {

        $user_id = get_current_user_id();

        if ( 'post' === $context['type'] ) {
            $option_key = 'edit_' . $context['post_type'] . '_per_page';
        } else {
            $option_key = 'edit_' . $context['taxonomy'] . '_per_page';
        }

        $per_page = (int) get_user_option( $option_key, $user_id );
        return $per_page > 0 ? $per_page : self::DEFAULT_PER_PAGE;
    }

    /**
     * Total count for the current scope.
     */
    protected function get_total_for_context( $context ) {

        if ( 'post' === $context['type'] ) {
            return $this->get_total_posts();
        }
        if ( 'term' === $context['type'] ) {
            return $this->get_total_terms( $context['taxonomy'] );
        }
        return 0;
    }

    /**
     * Resolve the active taxonomy from the current request. Used by the
     * column rendering callbacks which only get the term_id, not the
     * taxonomy.
     */
    protected function current_taxonomy() {
        // phpcs:ignore WordPress.Security.NonceVerification
        $tax = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
        return $tax;
    }

    /* =================================================================
     * AJAX HANDLER
     * ================================================================= */

    /**
     * Single endpoint for both new (single_move) and legacy (slot_reshuffle)
     * payloads.
     *
     * Single-move payload:
     *   action       = wpspeedo_team_save_drag_order
     *   nonce        = ...
     *   mode         = single_move
     *   type         = post | term
     *   id           = object id
     *   new_position = 1..N target rank
     *   post_type    = required when type=post
     *   taxonomy     = required when type=term
     *
     * Slot-reshuffle payload (legacy, kept for backward compatibility):
     *   mode         = slot_reshuffle  (or omitted for older clients)
     *   type, items[], post_type / taxonomy
     */
    public function ajax_save_order() {

        if ( ! check_ajax_referer( self::NONCE_ACTION, 'nonce', false ) ) {
            wp_send_json_error( [ 'message' => __( 'Security check failed.', 'wps-team' ) ], 403 );
        }

        if ( ! current_user_can( 'edit_others_posts' ) ) {
            wp_send_json_error( [ 'message' => __( 'You are not allowed to perform this action.', 'wps-team' ) ], 403 );
        }

        if ( ! $this->is_pro() ) {
            wp_send_json_error( [ 'message' => __( 'Drag-and-drop ordering is a Pro feature.', 'wps-team' ) ], 403 );
        }

        // phpcs:ignore WordPress.Security.NonceVerification
        $mode = isset( $_POST['mode'] ) ? sanitize_key( wp_unslash( $_POST['mode'] ) ) : 'single_move';
        // phpcs:ignore WordPress.Security.NonceVerification
        $type = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

        if ( ! in_array( $type, [ 'post', 'term' ], true ) ) {
            wp_send_json_error( [ 'message' => __( 'Unsupported sort type.', 'wps-team' ) ], 400 );
        }

        // Resolve scope (post_type or taxonomy) and validate.
        if ( 'post' === $type ) {
            // phpcs:ignore WordPress.Security.NonceVerification
            $post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
            if ( $post_type !== Utils::post_type_name() ) {
                wp_send_json_error( [ 'message' => __( 'Unsupported post type.', 'wps-team' ) ], 400 );
            }
            $scope = [ 'type' => 'post', 'post_type' => $post_type ];
        } else {
            // phpcs:ignore WordPress.Security.NonceVerification
            $taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
            if ( ! in_array( $taxonomy, Utils::get_active_taxonomies(), true ) ) {
                wp_send_json_error( [ 'message' => __( 'Unsupported taxonomy.', 'wps-team' ) ], 400 );
            }
            $scope = [ 'type' => 'term', 'taxonomy' => $taxonomy ];
        }

        if ( 'single_move' === $mode ) {
            $this->dispatch_single_move( $scope );
            return;
        }

        // Legacy slot_reshuffle path - kept for backward compatibility.
        $this->dispatch_slot_reshuffle( $scope );
    }

    /**
     * Handle the new global-positioning single-item move.
     */
    protected function dispatch_single_move( array $scope ) {

        // phpcs:ignore WordPress.Security.NonceVerification
        $object_id = isset( $_POST['id'] ) ? absint( wp_unslash( $_POST['id'] ) ) : 0;
        // phpcs:ignore WordPress.Security.NonceVerification
        $new_pos   = isset( $_POST['new_position'] ) ? (int) wp_unslash( $_POST['new_position'] ) : 0;

        if ( $object_id <= 0 || $new_pos <= 0 ) {
            wp_send_json_error( [ 'message' => __( 'Invalid move payload.', 'wps-team' ) ], 400 );
        }

        $result = $this->move_to_global_position( $object_id, $new_pos, $scope );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
        }

        $per_page    = $this->get_per_page_for_context( $scope );
        $target_page = ( $per_page > 0 ) ? (int) ceil( $result['new_position'] / $per_page ) : 1;

        wp_send_json_success( [
            'message'      => __( 'Order saved successfully.', 'wps-team' ),
            'new_position' => $result['new_position'],
            'old_position' => $result['old_position'],
            'total'        => $result['total'],
            'target_page'  => max( 1, $target_page ),
        ] );
    }

    /**
     * Legacy slot-reshuffle: re-shuffle the menu_order/term_order slots of
     * a visible block of items. Preserves the old contract used by older
     * clients that may still be cached.
     */
    protected function dispatch_slot_reshuffle( array $scope ) {

        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $items = isset( $_POST['items'] ) ? wp_unslash( $_POST['items'] ) : [];
        $items = is_array( $items ) ? array_map( 'absint', $items ) : [];
        $items = array_values( array_filter( $items ) );

        if ( empty( $items ) ) {
            wp_send_json_error( [ 'message' => __( 'Invalid sort payload.', 'wps-team' ) ], 400 );
        }

        if ( 'post' === $scope['type'] ) {
            $this->slot_reshuffle_posts( $items );
        } else {
            $this->slot_reshuffle_terms( $items, $scope['taxonomy'] );
        }

        wp_send_json_success( [ 'message' => __( 'Order saved successfully.', 'wps-team' ) ] );
    }

    /* =================================================================
     * GLOBAL POSITIONING ALGORITHM
     * ================================================================= */

    /**
     * Move a single item to a new GLOBAL ordinal position (1..N) within its
     * scope. Surrounding items shift by ±1, the entire scope is renumbered
     * to a gapless 1..N sequence and the affected rows are persisted in a
     * single batched query.
     *
     * @param int   $object_id   Post ID or term ID being moved.
     * @param int   $new_pos     Target global position (1..N).
     * @param array $scope       [ 'type' => 'post'|'term', 'post_type'|'taxonomy' => ... ]
     *
     * @return array|\WP_Error   On success: [ 'old_position', 'new_position', 'total' ]
     */
    protected function move_to_global_position( $object_id, $new_pos, array $scope ) {

        $object_id = (int) $object_id;

        // 1) Build the canonical ordered list of all IDs in scope.
        if ( 'post' === $scope['type'] ) {
            $rank_map = $this->compute_post_ranks();
        } else {
            $rank_map = $this->compute_term_ranks( $scope['taxonomy'] );
        }

        $total = count( $rank_map );

        if ( $total === 0 ) {
            return new \WP_Error( 'wps_team_empty_scope', __( 'There is nothing to reorder yet.', 'wps-team' ) );
        }

        if ( ! isset( $rank_map[ $object_id ] ) ) {
            return new \WP_Error( 'wps_team_not_in_scope', __( 'The selected item is not part of this list.', 'wps-team' ) );
        }

        // 2) Clamp target position into [1, N].
        $new_pos = max( 1, min( $new_pos, $total ) );

        $old_pos = (int) $rank_map[ $object_id ];

        // No-op short-circuit.
        if ( $old_pos === $new_pos ) {
            return [
                'old_position' => $old_pos,
                'new_position' => $new_pos,
                'total'        => $total,
            ];
        }

        // 3) Build the new ordered ID list using the relative order users
        //    have already established. We take the existing rank_map (which
        //    is already sorted by current rank), remove the moved item, and
        //    insert it at the requested 0-indexed position.
        $ids_in_order = array_keys( $rank_map ); // sorted by current rank ASC
        $current_idx  = (int) array_search( $object_id, $ids_in_order, true );
        array_splice( $ids_in_order, $current_idx, 1 );
        array_splice( $ids_in_order, $new_pos - 1, 0, [ $object_id ] );

        // 4) Persist. Renumbers the entire scope to 1..N.
        //
        // For the term-scoped path the saved order is the full ordered ID
        // list stored as a single term-meta value — no per-row writes to
        // wp_posts.menu_order happen, so the global ordering is preserved.
        //
        // For the global path we compute the minimal set of rows whose
        // new rank differs from the previous rank and persist them with
        // a single batched UPDATE per chunk.
        $previous_ranks = $rank_map;
        $changed        = $this->build_changed_set( $ids_in_order, $previous_ranks );

        if ( 'post' === $scope['type'] ) {
            $term_scope = $this->get_current_term_scope();
            if ( $term_scope ) {
                $this->persist_term_scoped_post_ranks( $ids_in_order, $term_scope );
            } else {
                $this->persist_post_ranks( $changed );
            }
        } else {
            $this->persist_term_ranks( $changed, $scope['taxonomy'] );
        }

        // Invalidate per-request rank cache so subsequent calls in the
        // same request see the fresh data.
        $this->rank_cache['post'] = null;
        $this->rank_cache['term'] = [];

        do_action( 'wpspeedo_team/sortable/global_move', $scope, $object_id, $old_pos, $new_pos );

        return [
            'old_position' => $old_pos,
            'new_position' => $new_pos,
            'total'        => $total,
        ];
    }

    /**
     * Compute the minimal set of (id => new_rank) pairs that need to be
     * written to the database.
     *
     * @param int[] $ids_in_order   IDs in their NEW order (0-indexed).
     * @param int[] $previous_ranks Map of id => old 1-indexed rank.
     * @return array<int,int>       Map of id => new_rank for changed rows.
     */
    protected function build_changed_set( array $ids_in_order, array $previous_ranks ) {
        $changed = [];
        foreach ( $ids_in_order as $i => $id ) {
            $new_rank = $i + 1;
            if ( ! isset( $previous_ranks[ $id ] ) || (int) $previous_ranks[ $id ] !== $new_rank ) {
                $changed[ (int) $id ] = $new_rank;
            }
        }
        return $changed;
    }

    /**
     * Bulk write menu_order for a set of post IDs using a single
     * "UPDATE ... CASE WHEN ..." query per chunk. Chunks of 500 keep the
     * generated SQL well below the default max_allowed_packet.
     */
    protected function persist_post_ranks( array $id_to_rank ) {

        if ( empty( $id_to_rank ) ) return;

        global $wpdb;

        $chunks = array_chunk( $id_to_rank, 500, true );

        foreach ( $chunks as $chunk ) {
            $cases     = '';
            $in_clause = [];
            foreach ( $chunk as $id => $rank ) {
                $cases     .= sprintf( ' WHEN %d THEN %d', (int) $id, (int) $rank );
                $in_clause[] = (int) $id;
            }

            $sql = sprintf(
                "UPDATE {$wpdb->posts} SET menu_order = CASE ID%s END WHERE ID IN (%s)",
                $cases,
                implode( ',', $in_clause )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( $sql );
        }

        // Cache invalidation - bounded by the affected range, not by total.
        foreach ( array_keys( $id_to_rank ) as $pid ) {
            clean_post_cache( (int) $pid );
        }

        do_action( 'wpspeedo_team/sortable/posts_global_saved', array_keys( $id_to_rank ) );
    }

    /**
     * Bulk write term_order for a set of term IDs using a single
     * "UPDATE ... CASE WHEN ..." query per chunk.
     */
    protected function persist_term_ranks( array $id_to_rank, $taxonomy ) {

        if ( empty( $id_to_rank ) ) return;

        global $wpdb;

        $chunks = array_chunk( $id_to_rank, 500, true );

        foreach ( $chunks as $chunk ) {
            $cases     = '';
            $in_clause = [];
            foreach ( $chunk as $id => $rank ) {
                $cases     .= sprintf( ' WHEN %d THEN %d', (int) $id, (int) $rank );
                $in_clause[] = (int) $id;
            }

            $sql = sprintf(
                "UPDATE {$wpdb->terms} SET term_order = CASE term_id%s END WHERE term_id IN (%s)",
                $cases,
                implode( ',', $in_clause )
            );

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->query( $sql );
        }

        clean_term_cache( array_keys( $id_to_rank ), $taxonomy );

        do_action( 'wpspeedo_team/sortable/terms_global_saved', array_keys( $id_to_rank ), $taxonomy );
    }

    /* =================================================================
     * LEGACY SLOT-RESHUFFLE (backward compatibility only)
     * ================================================================= */

    protected function slot_reshuffle_posts( array $post_ids ) {

        global $wpdb;

        $post_ids = array_values( array_unique( array_map( 'intval', $post_ids ) ) );
        if ( empty( $post_ids ) ) return;

        $slots = [];
        foreach ( $post_ids as $pid ) {
            $slots[] = (int) get_post_field( 'menu_order', $pid );
        }
        sort( $slots, SORT_NUMERIC );

        foreach ( $post_ids as $i => $pid ) {
            $new_order = isset( $slots[ $i ] ) ? (int) $slots[ $i ] : $i;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $wpdb->posts, [ 'menu_order' => $new_order ], [ 'ID' => $pid ], [ '%d' ], [ '%d' ] );
            clean_post_cache( $pid );
        }

        do_action( 'wpspeedo_team/sortable/posts_saved', $post_ids );
    }

    protected function slot_reshuffle_terms( array $term_ids, $taxonomy ) {

        global $wpdb;

        $term_ids = array_values( array_unique( array_map( 'intval', $term_ids ) ) );
        if ( empty( $term_ids ) ) return;

        $slots = [];
        foreach ( $term_ids as $tid ) {
            $term = get_term( $tid );
            $slots[] = ( $term && ! is_wp_error( $term ) && isset( $term->term_order ) ) ? (int) $term->term_order : 0;
        }
        sort( $slots, SORT_NUMERIC );

        foreach ( $term_ids as $i => $tid ) {
            $new_order = isset( $slots[ $i ] ) ? (int) $slots[ $i ] : $i;
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->update( $wpdb->terms, [ 'term_order' => $new_order ], [ 'term_id' => $tid ], [ '%d' ], [ '%d' ] );
        }

        clean_term_cache( $term_ids, $taxonomy );

        do_action( 'wpspeedo_team/sortable/terms_saved', $term_ids, $taxonomy );
    }

    /* =================================================================
     * NATIVE DEFAULT ORDERING — REDIRECT HELPERS
     * ================================================================= */

    /**
     * When a PRO user visits the CPT listing screen without any explicit
     * orderby/order in the URL, redirect once to apply the plugin's default
     * ordering key (menu_order ASC). All other URL parameters (paged, s,
     * post_status, …) are preserved.
     *
     * The "once only" guarantee is the isset( $_GET['orderby'] ) check:
     * after the redirect the URL always contains orderby, so this method
     * becomes a no-op for every subsequent request — including pagination
     * and search — preventing any infinite-redirect scenario.
     */
    public function maybe_redirect_to_default_post_order() {

        // Only run on the actual CPT listing screen.
        global $pagenow;
        if ( 'edit.php' !== $pagenow ) return;

        // PRO-only feature.
        if ( ! $this->is_pro() ) return;

        // Only on our CPT listing screen.
        // phpcs:ignore WordPress.Security.NonceVerification
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
        if ( $post_type !== Utils::post_type_name() ) return;

        // Already sorted by the user — respect their choice.
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_GET['orderby'] ) ) return;

        if ( ! current_user_can( 'edit_others_posts' ) ) return;

        // Parse the current query string so every existing arg is preserved.
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        parse_str( isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : '', $args );

        // Drop any stale `wp_http_referer` / `_wp_http_referer` so we don't
        // accumulate nested referer chains across successive redirects.
        unset( $args['wp_http_referer'], $args['_wp_http_referer'] );

        $args['orderby'] = 'menu_order';
        $args['order']   = 'asc';

        wp_safe_redirect( add_query_arg( $args, admin_url( 'edit.php' ) ) );
        exit;
    }

    /**
     * Same redirect logic for taxonomy listing pages.
     * Redirects to orderby=term_order&order=asc, which is handled by the
     * existing get_terms_orderby__premium_only and
     * modify_terms_order_in_admin__premium_only hooks in Data / taxonomy
     * traits — no new query logic is needed.
     *
     * IMPORTANT: WordPress core fires `load-edit-tags.php` on BOTH the term
     * listing (edit-tags.php) AND the single-term edit screen (term.php),
     * the latter strictly for backward compatibility (see wp-admin/admin.php).
     * Without the $pagenow guard below this callback would also fire on
     * term.php, redirecting users away from the term edit form back to the
     * listing — which is exactly the symptom: clicking "Edit" appears to
     * "stay" on the listing because we bounce term.php → edit-tags.php.
     */
    public function maybe_redirect_to_default_term_order() {

        // Only run on the actual taxonomy listing screen — never on term.php
        // (WP fires load-edit-tags.php on term.php for backwards-compat).
        global $pagenow;
        if ( 'edit-tags.php' !== $pagenow ) return;

        // PRO-only feature.
        if ( ! $this->is_pro() ) return;

        // Only on our taxonomy listing screens.
        // phpcs:ignore WordPress.Security.NonceVerification
        $taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
        if ( ! in_array( $taxonomy, Utils::get_active_taxonomies(), true ) ) return;

        // Already sorted by the user — respect their choice.
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( isset( $_GET['orderby'] ) ) return;

        if ( ! current_user_can( 'manage_categories' ) ) return;

        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        parse_str( isset( $_SERVER['QUERY_STRING'] ) ? wp_unslash( $_SERVER['QUERY_STRING'] ) : '', $args );

        // Drop any stale `wp_http_referer` / `_wp_http_referer` so we don't
        // accumulate nested referer chains across successive redirects.
        unset( $args['wp_http_referer'], $args['_wp_http_referer'] );

        $args['orderby'] = 'term_order';
        $args['order']   = 'asc';

        wp_safe_redirect( add_query_arg( $args, admin_url( 'edit-tags.php' ) ) );
        exit;
    }

    /* =================================================================
     * NATIVE SORTABLE COLUMN REGISTRATION
     * ================================================================= */

    /**
     * Register the "Order" column (wps_order_pos) as a native WordPress
     * sortable column. The second element in the tuple maps the column to
     * the WP_Query orderby key 'menu_order', which WordPress supports
     * natively — no pre_get_posts hook is needed.
     *
     * Behaviour:
     *   First click  → ?orderby=menu_order&order=asc
     *   Second click → ?orderby=menu_order&order=desc
     *
     * WordPress will also highlight the column header whenever the URL
     * contains orderby=menu_order, so the default redirect (which adds
     * orderby=menu_order) causes the header to appear active on page load,
     * exactly like a core column would.
     *
     * Available to all users (column-header click is read-only). The
     * PRO-gated redirect and drag/input features are separate concerns.
     */
    public function register_post_sortable_columns( $sortable ) {
        // [ column_key => [ orderby_value, desc_first ] ]
        // desc_first = false → first click is ASC (natural / "lowest number first").
        $sortable['wps_order_pos'] = [ 'menu_order', false ];
        return $sortable;
    }

    /* =================================================================
     * PER-TERM POST ORDERING (term-scoped layer)
     * ================================================================= */

    /**
     * Detect whether the current admin / AJAX request operates inside a
     * single-term filter for the team CPT.
     *
     * Page-view detection reads $_GET for the native WP taxonomy filter
     * URL pattern (?{taxonomy_slug}={term_slug_or_id}). AJAX detection
     * reads $_POST['term_scope'] which the JS forwards from the page's
     * localized context.
     *
     * @return array{taxonomy:string,term_id:int,term_slug:string,term_name:string}|null
     */
    public function get_current_term_scope() {

        if ( false !== $this->term_scope_cache ) return $this->term_scope_cache;

        $this->term_scope_cache = wp_doing_ajax()
            ? $this->parse_term_scope_from_post()
            : $this->parse_term_scope_from_get();

        return $this->term_scope_cache;
    }

    protected function parse_term_scope_from_get() {

        // phpcs:ignore WordPress.Security.NonceVerification
        $post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
        if ( $post_type !== Utils::post_type_name() ) return null;

        foreach ( Utils::get_active_taxonomies() as $taxonomy ) {

            // phpcs:ignore WordPress.Security.NonceVerification
            if ( empty( $_GET[ $taxonomy ] ) ) continue;

            // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            $raw = sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) );
            if ( $raw === '' || $raw === '0' ) continue;

            $term = is_numeric( $raw )
                ? get_term( (int) $raw, $taxonomy )
                : get_term_by( 'slug', $raw, $taxonomy );

            if ( $term && ! is_wp_error( $term ) ) {
                return [
                    'taxonomy'  => $taxonomy,
                    'term_id'   => (int) $term->term_id,
                    'term_slug' => $term->slug,
                    'term_name' => $term->name,
                ];
            }
        }

        return null;
    }

    protected function parse_term_scope_from_post() {

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( empty( $_POST['term_scope'] ) || ! is_array( $_POST['term_scope'] ) ) return null;

        // phpcs:ignore WordPress.Security.NonceVerification
        $payload = wp_unslash( $_POST['term_scope'] );

        $taxonomy = isset( $payload['taxonomy'] ) ? sanitize_key( $payload['taxonomy'] ) : '';
        $term_id  = isset( $payload['term_id'] )  ? (int) $payload['term_id'] : 0;

        if ( $term_id <= 0 ) return null;
        if ( ! in_array( $taxonomy, Utils::get_active_taxonomies(), true ) ) return null;

        $term = get_term( $term_id, $taxonomy );
        if ( ! $term || is_wp_error( $term ) ) return null;

        return [
            'taxonomy'  => $taxonomy,
            'term_id'   => $term_id,
            'term_slug' => $term->slug,
            'term_name' => $term->name,
        ];
    }

    /**
     * All published / private team-member post IDs that belong to a given
     * term, ordered by global menu_order. Memoized per request.
     *
     * @return int[]
     */
    public function get_term_member_ids( $taxonomy, $term_id ) {

        $key = $taxonomy . ':' . (int) $term_id;
        if ( isset( self::$term_member_cache[ $key ] ) ) {
            return self::$term_member_cache[ $key ];
        }

        global $wpdb;

        $post_type = Utils::post_type_name();

        // phpcs:disable WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared
        $ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID
             FROM {$wpdb->posts} AS p
             INNER JOIN {$wpdb->term_relationships} AS tr ON tr.object_id = p.ID
             INNER JOIN {$wpdb->term_taxonomy}     AS tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
             WHERE p.post_type   = %s
               AND p.post_status NOT IN ('auto-draft','trash')
               AND tt.taxonomy   = %s
               AND tt.term_id    = %d
             ORDER BY p.menu_order ASC, p.ID ASC",
            $post_type,
            $taxonomy,
            (int) $term_id
        ) );
        // phpcs:enable

        self::$term_member_cache[ $key ] = array_map( 'intval', (array) $ids );
        return self::$term_member_cache[ $key ];
    }

    /**
     * Build the 1..N rank map for a single term (saved order first, then
     * any newly-added member appended by global menu_order).
     *
     * @return array<int,int>  post_id => rank
     */
    protected function compute_term_scoped_post_ranks( $taxonomy, $term_id ) {

        $members = $this->get_term_member_ids( $taxonomy, $term_id );
        if ( empty( $members ) ) return [];

        $saved = get_term_meta( (int) $term_id, self::TERM_POST_ORDER_META_KEY, true );
        $saved = is_array( $saved ) ? array_values( array_filter( array_map( 'intval', $saved ) ) ) : [];

        $valid   = array_values( array_intersect( $saved, $members ) );
        $missing = array_values( array_diff( $members, $valid ) );

        $final = array_merge( $valid, $missing );

        $ranks = [];
        foreach ( $final as $i => $id ) {
            $ranks[ (int) $id ] = $i + 1;
        }
        return $ranks;
    }

    /**
     * Persist a new term-specific post order into term meta. Replaces any
     * existing order; existing global menu_order is left untouched.
     *
     * @param int[] $ids_in_order Sanitized list of post IDs in display order.
     * @param array $term_scope   ['taxonomy' => ..., 'term_id' => ...]
     */
    protected function persist_term_scoped_post_ranks( array $ids_in_order, array $term_scope ) {

        $clean = [];
        foreach ( $ids_in_order as $id ) {
            $id = (int) $id;
            if ( $id > 0 && ! in_array( $id, $clean, true ) ) {
                $clean[] = $id;
            }
        }

        update_term_meta( (int) $term_scope['term_id'], self::TERM_POST_ORDER_META_KEY, $clean );
        clean_term_cache( (int) $term_scope['term_id'], $term_scope['taxonomy'] );

        // Invalidate our per-request caches so subsequent reads see the
        // freshly-saved order.
        $key = $term_scope['taxonomy'] . ':' . (int) $term_scope['term_id'];
        unset( self::$term_member_cache[ $key ] );

        do_action( 'wpspeedo_team/sortable/term_scoped_posts_saved', $clean, $term_scope );
    }

    /* =================================================================
     * QUERY FILTERS (admin listing + frontend shortcode)
     * ================================================================= */

    /**
     * Rewrite ORDER BY on the admin listing when a term filter is active
     * AND a per-term order has been saved. Uses MySQL FIELD() to project
     * the saved sequence onto the result set; members not in the saved
     * sequence fall to the end ordered by global menu_order.
     */
    public function maybe_apply_term_scoped_orderby( $orderby, $query ) {

        if ( ! is_admin() ) return $orderby;
        if ( wp_doing_ajax() ) return $orderby;
        if ( ! $query instanceof \WP_Query ) return $orderby;
        if ( ! $query->is_main_query() ) return $orderby;

        if ( $query->get( 'post_type' ) !== Utils::post_type_name() ) return $orderby;

        $scope = $this->get_current_term_scope();
        if ( ! $scope ) return $orderby;

        $saved = get_term_meta( $scope['term_id'], self::TERM_POST_ORDER_META_KEY, true );
        $saved = is_array( $saved ) ? array_values( array_filter( array_map( 'intval', $saved ) ) ) : [];
        if ( empty( $saved ) ) return $orderby;

        global $wpdb;

        $ids_csv = implode( ',', $saved );
        $dir     = strtoupper( (string) $query->get( 'order' ) ) === 'DESC' ? 'DESC' : 'ASC';

        // Matched items (FIELD > 0) first, then by their position in the
        // saved sequence; unmatched items follow, ordered by global
        // menu_order so newly-added members slot in predictably.
        return "FIELD({$wpdb->posts}.ID, {$ids_csv}) = 0 ASC, "
             . "FIELD({$wpdb->posts}.ID, {$ids_csv}) {$dir}, "
             . "{$wpdb->posts}.menu_order ASC, {$wpdb->posts}.ID ASC";
    }

    /**
     * Frontend integration: when a shortcode / AJAX filter / archive query
     * scopes to exactly one team taxonomy term and uses custom ordering,
     * inject post__in with the saved per-term order.
     */
    public function apply_term_scoped_order_to_frontend( $args ) {

        if ( ! is_array( $args ) ) return $args;

        // Admin main-query listings are handled by maybe_apply_term_scoped_orderby.
        if ( is_admin() && ! wp_doing_ajax() ) return $args;

        $orderby = isset( $args['orderby'] ) ? $args['orderby'] : '';
        if ( ! empty( $orderby ) && $orderby !== 'menu_order' ) return $args;

        if ( ! empty( $args['post__in'] ) ) return $args;

        $scope = $this->detect_single_term_in_tax_query( $args );
        if ( ! $scope ) return $args;

        $saved = get_term_meta( $scope['term_id'], self::TERM_POST_ORDER_META_KEY, true );
        $saved = is_array( $saved ) ? array_values( array_filter( array_map( 'intval', $saved ) ) ) : [];
        if ( empty( $saved ) ) return $args;

        $members = $this->get_term_member_ids( $scope['taxonomy'], $scope['term_id'] );
        if ( empty( $members ) ) return $args;

        $valid   = array_values( array_intersect( $saved, $members ) );
        $missing = array_values( array_diff( $members, $valid ) );

        $final = array_merge( $valid, $missing );
        if ( empty( $final ) ) return $args;

        $args['post__in'] = $final;
        $args['orderby']  = 'post__in';
        unset( $args['order'] );

        return $args;
    }

    /**
     * Detect whether a query args tax_query targets exactly one term in
     * an active team taxonomy.
     *
     * @return array{taxonomy:string,term_id:int}|null
     */
    protected function detect_single_term_in_tax_query( array $args ) {

        if ( empty( $args['tax_query'] ) || ! is_array( $args['tax_query'] ) ) return null;

        $tax_query = $args['tax_query'];
        unset( $tax_query['relation'] );

        $rows = [];
        foreach ( $tax_query as $row ) {
            if ( is_array( $row ) && ! empty( $row['taxonomy'] ) && isset( $row['terms'] ) ) {
                $rows[] = $row;
            }
        }

        if ( count( $rows ) !== 1 ) return null;

        $row = $rows[0];
        if ( ! in_array( $row['taxonomy'], Utils::get_active_taxonomies(), true ) ) return null;

        $operator = isset( $row['operator'] ) ? strtoupper( $row['operator'] ) : 'IN';
        if ( ! in_array( $operator, [ 'IN', '=' ], true ) ) return null;

        $terms = (array) $row['terms'];
        if ( count( $terms ) !== 1 ) return null;

        $field = isset( $row['field'] ) ? $row['field'] : 'term_id';
        $value = reset( $terms );

        if ( in_array( $field, [ 'term_id', 'id', 'ID' ], true ) ) {
            $term_id = (int) $value;
        } else {
            $term = get_term_by( $field, $value, $row['taxonomy'] );
            $term_id = ( $term && ! is_wp_error( $term ) ) ? (int) $term->term_id : 0;
        }

        if ( $term_id <= 0 ) return null;

        return [ 'taxonomy' => $row['taxonomy'], 'term_id' => $term_id ];
    }

    /**
     * Print an info banner above the listing while a term-scoped ordering
     * session is active, so the user clearly understands the scope.
     */
    public function print_term_scope_notice() {

        if ( ! is_admin() ) return;

        global $pagenow;
        if ( 'edit.php' !== $pagenow ) return;

        $scope = $this->get_current_term_scope();
        if ( ! $scope ) return;

        $tax_obj   = get_taxonomy( $scope['taxonomy'] );
        $tax_label = ( $tax_obj && isset( $tax_obj->labels->singular_name ) )
            ? $tax_obj->labels->singular_name
            : $scope['taxonomy'];

        $message = sprintf(
            /* translators: 1: term name 2: taxonomy singular label */
            _x( 'You are ordering members <strong>within "%1$s"</strong> (%2$s). Drag or edit the position to save a custom order for this term only. Global ordering is not affected.', 'Admin notice', 'wps-team' ),
            esc_html( $scope['term_name'] ),
            esc_html( $tax_label )
        );

        printf(
            '<div class="notice notice-info wps-team--term-scope-notice"><p>%s</p></div>',
            wp_kses( $message, [ 'strong' => [] ] )
        );
    }

    /* =================================================================
     * HELPERS
     * ================================================================= */

    protected function is_pro() {
        return (bool) wps_team_fs()->can_use_premium_code__premium_only();
    }
}
