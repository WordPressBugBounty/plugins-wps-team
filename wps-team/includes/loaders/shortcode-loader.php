<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Shortcode_Loader extends Attribute_Manager {
    use Setting_Methods, AJAX_Template_Methods;
    public $settings = [];

    public $mode;

    public $id;

    public $query_args = [];

    public $query;

    public $is_ajax = false;

    public $ajax_query_args = [];

    function __construct( $args ) {
        $args = shortcode_atts( [
            'id'         => '',
            'settings'   => [],
            'mode'       => 'public',
            'is_ajax'    => false,
            'query_args' => [],
        ], $args );
        $this->id = $args['id'];
        $this->mode = $args['mode'];
        $this->settings = $args['settings'];
        $this->is_ajax = $args['is_ajax'];
        $this->ajax_query_args = $args['query_args'];
        // Required for Assets Building
        $this->settings['id'] = $this->id;
        $this->set_query_args();
        $this->query = Utils::get_posts( $this->query_args );
        $this->set_attributes();
    }

    public function load_template() {
        if ( $this->mode == 'preview' ) {
            $this->load_editor_template();
            $assets_model = plugin()->assets->set_settings( $this->settings );
            $assets_model->load_fonts_on_preview( $this->settings );
            $css = $assets_model->get_custom_css( $this->id );
            if ( !empty( $css ) ) {
                printf( '<style class="%s">%s</style>', 'wpspeedo-team--customized', $css );
            }
            // phpcs:ignore WordPress.Security.EscapeOutput
        } else {
            if ( $this->mode == 'builder' ) {
                $this->load_public_template();
                $assets_model = plugin()->assets->set_settings( $this->settings );
                $assets_model->load_fonts_on_preview( $this->settings );
                $css = $assets_model->get_custom_css( $this->id );
                if ( !empty( $css ) ) {
                    printf( '<style class="%s">%s</style>', 'wpspeedo-team--customized', $css );
                }
                // phpcs:ignore WordPress.Security.EscapeOutput
            } else {
                $this->load_public_template();
                if ( empty( plugin()->assets->get_data() ) ) {
                    plugin()->assets->build( $this->settings )->force_enqueue();
                }
            }
        }
    }

    public function set_attributes() {
        $theme = $this->get_setting( 'theme' );
        $display_type = $this->get_setting( 'display_type' );
        $card_action = $this->get_setting( 'card_action' );
        $layout_mode = $this->get_setting( 'layout_mode' );
        $enable_sticky_filter = $this->get_setting( 'enable_sticky_filter' );
        $enable_mobile_sticky_filter = $this->get_setting( 'enable_mobile_sticky_filter' );
        $sticky_top_gap = $this->get_setting( 'filter_sticky_top_gap' );
        $sticky_top_gap_tablet = $this->get_setting( 'filter_sticky_top_gap_tablet' );
        $sticky_top_gap_small_tablet = $this->get_setting( 'filter_sticky_top_gap_small_tablet' );
        $sticky_top_gap_mobile = $this->get_setting( 'filter_sticky_top_gap_mobile' );
        $sticky_bottom_gap = $this->get_setting( 'filter_sticky_bottom_gap' );
        $sticky_bottom_gap_tablet = $this->get_setting( 'filter_sticky_bottom_gap_tablet' );
        $sticky_bottom_gap_small_tablet = $this->get_setting( 'filter_sticky_bottom_gap_small_tablet' );
        $sticky_bottom_gap_mobile = $this->get_setting( 'filter_sticky_bottom_gap_mobile' );
        $widget_custom_class = $this->get_setting( 'container_custom_class' );
        if ( !empty( $widget_custom_class = trim( (string) $widget_custom_class ) ) ) {
            $this->add_attribute( 'wrapper', 'class', $widget_custom_class );
        }
        $this->add_attribute( 'wrapper', 'data-widget-id', $this->id );
        $this->add_attribute( 'wrapper', 'id', $this->get_shortcode_id( $this->id ) );
        $this->add_attribute( 'wrapper', 'style', 'visibility: hidden; opacity: 0' );
        if ( !$this->get_posts()->have_posts() ) {
            $this->add_attribute( 'wrapper', 'class', 'wps-team--no-posts' );
        }
        if ( !in_array( $theme, Utils::get_active_themes() ) ) {
            $theme = 'square-01';
            $this->set_setting( 'theme', $theme );
        }
        if ( !in_array( $theme, Utils::get_group_themes( 'table' ) ) ) {
            $this->add_attribute( 'single_item_row', 'class', 'wps-row' );
            $this->add_attribute( 'single_item_col', 'class', 'wps-col' );
        }
        if ( $display_type === 'ticker' && !wps_team_fs()->can_use_premium_code__premium_only() ) {
            $display_type = 'grid';
            $this->set_setting( 'display_type', $display_type );
        }
        if ( in_array( $theme, Utils::get_group_themes( 'table' ) ) ) {
            $this->add_attribute( 'single_item_row', 'class', 'wps-row wps-table' );
            $this->add_attribute( 'single_item_col', 'class', 'wps-col' );
            if ( in_array( $display_type, ['carousel', 'masonry', 'ticker'] ) ) {
                $display_type = 'grid';
                $this->set_setting( 'display_type', $display_type );
            }
        }
        if ( $card_action == 'expand' && in_array( $display_type, ['carousel', 'ticker'] ) ) {
            $card_action = 'none';
            $this->set_setting( 'card_action', $card_action );
        }
        $this->add_attribute( 'wrapper', 'class', [
            'wps-container wps-widget wps-widget--team',
            'wps-team-theme--' . $theme,
            'wps-team--type-' . $display_type,
            'wps-team-card-action--' . $card_action
        ] );
        $this->add_attribute( 'wrapper_inner', 'class', ['wps-container--inner'] );
        if ( $display_type === 'carousel' ) {
            $this->add_attribute( 'wrapper_inner', 'class', 'swiper' );
            $this->add_attribute( 'single_item_row', 'class', 'swiper-wrapper' );
            $this->add_attribute( 'single_item_col', 'class', 'swiper-slide' );
            $carousel_layout = $this->get_setting( 'carousel_layout' );
            if ( !is_string( $carousel_layout ) || !in_array( $carousel_layout, Utils::get_active_carousel_layouts(), true ) ) {
                $carousel_layout = 'layout-01';
            }
            $this->add_attribute( 'wrapper', 'class', 'wps-team--carousel-layout-' . substr( $carousel_layout, -2 ) );
            if ( wp_validate_boolean( $this->get_setting( 'dots' ) ) ) {
                $this->add_attribute( 'wrapper', 'class', 'wps-team--carousel-has-dots' );
            }
            if ( wp_validate_boolean( $this->get_setting( 'navs' ) ) ) {
                $this->add_attribute( 'wrapper', 'class', 'wps-team--carousel-has-navs' );
            }
            $bp_client = Utils::get_breakpoints_for_client();
            $carousel_settings = [
                'columns'              => (int) $this->get_setting( 'columns' ),
                'columns_tablet'       => (int) $this->get_setting( 'columns_tablet' ),
                'columns_small_tablet' => (int) $this->get_setting( 'columns_small_tablet' ),
                'columns_mobile'       => (int) $this->get_setting( 'columns_mobile' ),
                'gap'                  => ( $this->get_setting( 'gap' ) === '' ? '' : (int) $this->get_setting( 'gap' ) ),
                'gap_tablet'           => ( $this->get_setting( 'gap_tablet' ) === '' ? '' : (int) $this->get_setting( 'gap_tablet' ) ),
                'gap_small_tablet'     => ( $this->get_setting( 'gap_small_tablet' ) === '' ? '' : (int) $this->get_setting( 'gap_small_tablet' ) ),
                'gap_mobile'           => ( $this->get_setting( 'gap_mobile' ) === '' ? '' : (int) $this->get_setting( 'gap_mobile' ) ),
                'speed'                => (int) $this->get_setting( 'speed' ),
                'dots'                 => wp_validate_boolean( $this->get_setting( 'dots' ) ),
                'navs'                 => wp_validate_boolean( $this->get_setting( 'navs' ) ),
                'loop'                 => wp_validate_boolean( $this->get_setting( 'loop' ) ),
                'breakpoints'          => [
                    'swiper_sm' => (int) $bp_client['swiper_sm'],
                    'swiper_md' => (int) $bp_client['swiper_md'],
                    'swiper_lg' => (int) $bp_client['swiper_lg'],
                ],
            ];
            $this->add_attribute( 'wrapper', 'data-carousel-settings', json_encode( $carousel_settings ) );
        }
        if ( $display_type === 'ticker' ) {
            $this->add_attribute( 'wrapper_inner', 'class', 'wps-ticker wps-ticker--viewport' );
            $this->add_attribute( 'single_item_row', 'class', 'wps-ticker__track' );
            $this->add_attribute( 'single_item_col', 'class', 'wps-ticker__item' );
            $ticker_speed = $this->get_setting( 'ticker_speed' );
            if ( is_array( $ticker_speed ) && isset( $ticker_speed['size'] ) ) {
                $ticker_speed = (int) $ticker_speed['size'];
            } else {
                $ticker_speed = (int) $ticker_speed;
            }
            if ( $ticker_speed < 5 ) {
                $ticker_speed = 55;
            }
            $t_pause = $this->get_setting( 'ticker_pause_on_hover' );
            $t_drag = $this->get_setting( 'ticker_drag' );
            $t_loop = $this->get_setting( 'ticker_loop' );
            $t_rev = $this->get_setting( 'ticker_reverse' );
            $ticker_settings = [
                'speed'              => $ticker_speed,
                'loop'               => ( null === $t_loop ? false : wp_validate_boolean( $t_loop ) ),
                'reverse'            => ( null === $t_rev ? false : wp_validate_boolean( $t_rev ) ),
                'pauseOnHover'       => ( null === $t_pause ? true : wp_validate_boolean( $t_pause ) ),
                'drag'               => ( null === $t_drag ? true : wp_validate_boolean( $t_drag ) ),
                'columns'            => (int) $this->get_setting( 'columns' ),
                'columnsTablet'      => (int) $this->get_setting( 'columns_tablet' ),
                'columnsSmallTablet' => (int) $this->get_setting( 'columns_small_tablet' ),
                'columnsMobile'      => (int) $this->get_setting( 'columns_mobile' ),
                'gap'                => ( $this->get_setting( 'gap' ) === '' || null === $this->get_setting( 'gap' ) ? 24 : (int) $this->get_setting( 'gap' ) ),
                'gapTablet'          => ( $this->get_setting( 'gap_tablet' ) === '' || null === $this->get_setting( 'gap_tablet' ) ? 24 : (int) $this->get_setting( 'gap_tablet' ) ),
                'gapSmallTablet'     => ( $this->get_setting( 'gap_small_tablet' ) === '' || null === $this->get_setting( 'gap_small_tablet' ) ? 16 : (int) $this->get_setting( 'gap_small_tablet' ) ),
                'gapMobile'          => ( $this->get_setting( 'gap_mobile' ) === '' || null === $this->get_setting( 'gap_mobile' ) ? 16 : (int) $this->get_setting( 'gap_mobile' ) ),
                'breakpoints'        => Utils::get_breakpoints_for_client(),
            ];
            $this->add_attribute( 'wrapper_inner', 'data-wps-ticker', wp_json_encode( $ticker_settings ) );
        }
        $this->set_social_attributes();
    }

    public function set_social_attributes() {
        $theme = $this->get_setting( 'theme' );
        $theme_defaults = [];
        if ( in_array( $theme, [
            'square-02',
            'square-03',
            'square-05',
            'square-08'
        ] ) ) {
            $theme_defaults['shape'] = 'radius';
        }
        if ( in_array( $theme, [
            'square-02',
            'square-03',
            'square-05',
            'circle-01'
        ] ) ) {
            $theme_defaults['bg_color_type'] = 'custom';
            $theme_defaults['bg_color_type_hover'] = 'brand';
            $theme_defaults['br_color_type'] = 'custom';
            $theme_defaults['br_color_type_hover'] = 'brand';
            $theme_defaults['color_type'] = 'brand';
            $theme_defaults['color_type_hover'] = 'custom';
        }
        if ( in_array( $theme, ['square-08', 'square-09'] ) ) {
            $theme_defaults['bg_color_type'] = 'brand';
            $theme_defaults['bg_color_type_hover'] = 'brand';
            $theme_defaults['br_color_type'] = 'brand';
            $theme_defaults['br_color_type_hover'] = 'brand';
            $theme_defaults['color_type'] = 'custom';
            $theme_defaults['color_type_hover'] = 'custom';
        }
        if ( in_array( $theme, ['square-13'] ) ) {
            $theme_defaults['shape'] = 'square';
            $theme_defaults['bg_color_type'] = 'custom';
            $theme_defaults['bg_color_type_hover'] = 'custom';
            $theme_defaults['br_color_type'] = 'custom';
            $theme_defaults['br_color_type_hover'] = 'custom';
            $theme_defaults['color_type'] = 'custom';
            $theme_defaults['color_type_hover'] = 'custom';
        }
        $social_classes = Utils::get_social_classes( $this, $theme_defaults );
        $this->add_attribute( 'social', 'class', $social_classes );
    }

    private function load_editor_template() {
        ?>
        <!DOCTYPE html>
        <html <?php 
        language_attributes();
        ?>>
            <head>
                <meta charset="<?php 
        bloginfo( 'charset' );
        ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <?php 
        wp_head();
        ?>
            </head>
            <body <?php 
        body_class();
        ?>>
                <div class='wps-widget--preview_wrapper'>
                    <?php 
        $this->init_template();
        ?>
                </div>
                <?php 
        wp_footer();
        ?>
            </body>
        </html>
        <?php 
    }

    private function load_public_template() {
        $this->init_template();
    }

    public function get_tax_queries_include_exclude() {
        $tax_query = [];
        $taxonomies = Utils::get_active_taxonomies();
        foreach ( $taxonomies as $taxonomy ) {
            $tax_root_key = Utils::get_taxonomy_root( $taxonomy, true );
            $include = Utils::normalize_term_ids( $this->get_setting( 'include_by_' . $tax_root_key ) );
            if ( !empty( $include ) ) {
                $tax_query[] = [
                    'taxonomy'         => $taxonomy,
                    'field'            => 'term_id',
                    'terms'            => $include,
                    'include_children' => true,
                ];
            }
            $exclude = Utils::normalize_term_ids( $this->get_setting( 'exclude_by_' . $tax_root_key ) );
            $exclude = array_values( array_diff( $exclude, $include ) );
            if ( !empty( $exclude ) ) {
                $tax_query[] = [
                    'taxonomy'         => $taxonomy,
                    'field'            => 'term_id',
                    'terms'            => $exclude,
                    'operator'         => 'NOT IN',
                    'include_children' => true,
                ];
            }
        }
        return $tax_query;
    }

    public function get_tax_queries_initial() {
        $tax_query = [];
        return $tax_query;
    }

    public function set_query_args() {
        $paged_var = Utils::get_paged_var( $this->id );
        // phpcs:ignore WordPress.Security.NonceVerification
        $paged = ( isset( $_GET[$paged_var] ) ? (int) $_GET[$paged_var] : 1 );
        $query_args = [
            'posts_per_page' => -1,
            'orderby'        => $this->get_setting( 'orderby' ),
            'order'          => $this->get_setting( 'order' ),
            'paged'          => $paged,
            'tax_query'      => [],
        ];
        if ( !empty( $this->ajax_query_args['search_by_name'] ) ) {
            $query_args['s'] = $this->ajax_query_args['search_by_name'];
        }
        if ( isset( $this->ajax_query_args['offset'] ) ) {
            $query_args['offset'] = $this->ajax_query_args['offset'];
        }
        if ( isset( $this->ajax_query_args['paged'] ) ) {
            $query_args['paged'] = $this->ajax_query_args['paged'];
        }
        if ( $query_args['paged'] > 1 && isset( $query_args['offset'] ) ) {
            unset($query_args['offset']);
        }
        // Set posts_per_page
        if ( !$this->get_setting( 'show_all' ) ) {
            $query_args['posts_per_page'] = (int) $this->get_setting( 'limit' );
            if ( isset( $this->ajax_query_args['posts_per_page'] ) ) {
                $query_args['posts_per_page'] = (int) $this->ajax_query_args['posts_per_page'];
            }
        }
        // Set Include/Exclude Tax Queries
        $tax_queries_include_exclude = $this->get_tax_queries_include_exclude();
        if ( !empty( $tax_queries_include_exclude ) ) {
            $query_args['tax_query'] = array_merge( $query_args['tax_query'] ?? [], $tax_queries_include_exclude );
        }
        if ( !empty( $query_args['orderby'] ) && $query_args['orderby'] === 'menu_order' ) {
            $query_args['orderby'] = 'date';
        }
        // admin-ajax.php sets is_admin() true, so WP_Query would otherwise include draft/pending
        // (show_in_admin_all_list). Mirror front-end visibility for public/preview AJAX loads.
        if ( $this->is_ajax && in_array( $this->mode, ['public', 'preview'], true ) ) {
            $post_status = get_post_stati( [
                'public' => true,
            ], 'names' );
            if ( is_user_logged_in() ) {
                $post_type_object = get_post_type_object( Utils::post_type_name() );
                if ( $post_type_object instanceof \WP_Post_Type ) {
                    $post_status = array_merge( $post_status, get_post_stati( [
                        'private' => true,
                    ], 'names' ) );
                    $query_args['perm'] = 'readable';
                }
            }
            $query_args['post_status'] = array_unique( array_map( 'sanitize_key', $post_status ) );
        }
        $this->apply_filter_ordering_to_query_args( $query_args );
        $this->query_args = $query_args;
    }

    /**
     * When display type is "filter" and custom filter ordering is enabled,
     * swap in filter_orderby / filter_order while a taxonomy filter is active.
     * Otherwise the main orderby / order fields continue to apply.
     *
     * @param array $query_args Query args passed to WP_Query (by reference).
     */
    protected function apply_filter_ordering_to_query_args( array &$query_args ) {
        return;
        if ( $this->get_setting( 'display_type' ) !== 'filter' ) {
            return;
        }
        $tax_query = ( isset( $query_args['tax_query'] ) ? $query_args['tax_query'] : [] );
        if ( !$this->has_active_filter_tax_query( $tax_query ) ) {
            return;
        }
        if ( !$this->get_setting( 'enable_filter_custom_order' ) ) {
            // Explicitly block Term_Order_Resolver while a filter is active but
            // the toggle is off — keep the main orderby / order unchanged.
            $query_args['_wps_filter_custom_order'] = false;
            return;
        }
        $filter_orderby = $this->get_setting( 'filter_orderby' );
        if ( empty( $filter_orderby ) ) {
            $filter_orderby = 'menu_order';
        }
        $filter_order = $this->get_setting( 'filter_order' );
        if ( empty( $filter_order ) ) {
            $filter_order = 'ASC';
        }
        $query_args['orderby'] = $filter_orderby;
        $query_args['order'] = $filter_order;
        if ( $filter_orderby === 'menu_order' ) {
            $query_args['_wps_filter_custom_order'] = true;
        } else {
            $query_args['_wps_filter_custom_order'] = false;
        }
    }

    /**
     * True when the tax_query contains rows from the live filter UI (not
     * include / exclude constraints from the shortcode query settings).
     *
     * @param array $tax_query
     * @return bool
     */
    protected function has_active_filter_tax_query( array $tax_query ) : bool {
        if ( empty( $tax_query ) ) {
            return false;
        }
        $active_taxonomies = Utils::get_active_taxonomies();
        foreach ( $tax_query as $row ) {
            if ( !is_array( $row ) || empty( $row['taxonomy'] ) ) {
                continue;
            }
            if ( !in_array( $row['taxonomy'], $active_taxonomies, true ) ) {
                continue;
            }
            if ( isset( $row['operator'] ) && 'NOT IN' === strtoupper( (string) $row['operator'] ) ) {
                continue;
            }
            // Initial group filter on first paint (AJAX filter mode).
            if ( !empty( $row['is_initial'] ) ) {
                return true;
            }
            // Rows pushed by filter.js during AJAX filtering.
            if ( array_key_exists( 'include_children', $row ) && false === $row['include_children'] ) {
                return true;
            }
            // Include / exclude rows always set field => term_id.
            if ( !isset( $row['field'] ) ) {
                return true;
            }
        }
        return false;
    }

    public function get_posts() {
        return $this->query;
    }

    public function modify_excerpt_length() {
        return Utils::get_description_length();
    }

    public function init_template() {
        $theme = $this->get_setting( 'theme' );
        $card_action = $this->get_setting( 'card_action' );
        $display_type = $this->get_setting( 'display_type' );
        $thumbnail_type = $this->get_setting( 'thumbnail_type' );
        $thumbnail_size = $this->get_setting( 'thumbnail_size' );
        $thumbnail_size_custom = $this->get_setting( 'thumbnail_size_custom' );
        $description_length = Utils::get_description_length();
        $detail_thumbnail_type = $this->get_setting( 'detail_thumbnail_type' );
        $show_read_more_link = $this->get_setting( 'show_read_more_link' );
        if ( $show_read_more_link === null || $show_read_more_link === '' ) {
            $show_read_more_link = $this->get_setting( 'add_read_more' );
        }
        $show_read_more_link = wp_validate_boolean( $show_read_more_link );
        add_filter( 'excerpt_length', [$this, 'modify_excerpt_length'], 999 );
        $shortcode_loader = $this;
        $template_path = Utils::load_template( sprintf( 'template-%s.php', sanitize_key( $theme ) ) );
        if ( $template_path instanceof \WP_Error ) {
            return;
        }
        include $template_path;
        remove_filter( 'excerpt_length', [$this, 'modify_excerpt_length'], 999 );
        wp_reset_postdata();
    }

}
