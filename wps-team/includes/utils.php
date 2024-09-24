<?php

namespace WPSpeedo_Team;

use WP_Query, WP_Error;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Utils {
    static function get_posts_meta_cache_key( $meta_key, $post_type = null ) {
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        return sprintf( 'wps--meta-vals--%s_%s', $post_type, $meta_key );
    }

    static function is_external_url( $url ) {
        $self_data = wp_parse_url( home_url() );
        $url_data = wp_parse_url( $url );
        if ( $self_data['host'] == $url_data['host'] ) {
            return false;
        }
        return true;
    }

    static function get_ext_url_params() {
        return ' rel="nofollow noopener noreferrer" target="_blank"';
    }

    static function update_posts_meta_vals( $meta_key, $post_type = null ) {
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        $cache_key = self::get_posts_meta_cache_key( $meta_key, $post_type );
        delete_transient( $cache_key );
        return self::get_posts_meta_vals( $meta_key, $post_type );
    }

    static function update_all_posts_meta_vals( $meta_fields = [], $post_type = null ) {
        $meta_fields = ( !empty( $meta_fields ) ? $meta_fields : ['_ribbon'] );
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        foreach ( $meta_fields as $meta_key ) {
            self::update_posts_meta_vals( $meta_key, $post_type );
        }
    }

    static function get_posts_meta_vals( $meta_key, $post_type = null ) {
        global $wpdb;
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        $cache_key = self::get_posts_meta_cache_key( $meta_key, $post_type );
        $cache_data = get_transient( $cache_key );
        if ( $cache_data !== false ) {
            return $cache_data;
        }
        $results = $wpdb->get_results( $wpdb->prepare( "\n\t\t\tselect META.meta_value\n\t\t\tfrom {$wpdb->postmeta} AS META\n\t\t\tINNER JOIN {$wpdb->posts} AS POST\n\t\t\tON META.post_id = POST.ID\n\t\t\twhere POST.post_type = %s AND\n\t\t\tPOST.post_status = 'publish' AND\n\t\t\tMETA.meta_key = %s;\n\t\t", $post_type, $meta_key ) );
        if ( !empty( $results ) ) {
            $results = wp_list_pluck( $results, 'meta_value' );
            $results = array_values( array_unique( $results ) );
            set_transient( $cache_key, $results, MINUTE_IN_SECONDS * 10 );
            return $results;
        }
        return [];
    }

    static function get_posts( $query_args = [] ) {
        $args = [
            'posts_per_page' => -1,
            'paged'          => 1,
        ];
        $args = array_merge( $args, $query_args );
        $args = (array) apply_filters( 'wpspeedo_team/query_params', $args );
        $args['post_type'] = Utils::post_type_name();
        return new WP_Query($args);
    }

    static function search_by_custom_criteria( $where, $wp_query ) {
        global $wpdb;
        if ( $search_term = $wp_query->get( 'search_by_name' ) ) {
            // Escaping the search term for safety
            $search_term = $wpdb->esc_like( $search_term );
            $search_term = '%' . $search_term . '%';
            // Modify the WHERE clause to search only in post titles
            $where = $where . $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", $search_term );
        }
        return $where;
    }

    static function paginate_links( $args ) {
        global $wp;
        $args = array_merge( [
            'query'           => null,
            'ajax'            => false,
            'shortcode_id'    => null,
            'edge_page_links' => 2,
        ], $args );
        if ( $args['query'] == null ) {
            return;
        }
        $query = (object) $args['query'];
        $is_ajax = $args['ajax'];
        $shortcode_id = $args['shortcode_id'];
        $extra_links = $args['edge_page_links'];
        $paged_var = self::get_paged_var( $shortcode_id );
        $total = $query->max_num_pages;
        $current = ( isset( $_GET[$paged_var] ) ? (int) $_GET[$paged_var] : 1 );
        if ( $current < 1 ) {
            $current = 1;
        }
        if ( $current > $total ) {
            $current = $total;
        }
        if ( $total < 2 ) {
            return;
        }
        $current_url = home_url( add_query_arg( array($_GET), $wp->request ) );
        printf( '<div class="wps-pagination--wrap"><nav class="wps-team--navigation"><ul class="wps-team--pagination %s">', ( $is_ajax ? 'wps-team--pagination-ajax' : '' ) );
        $prev_limit = $current - $extra_links + min( $total - $current - $extra_links, 0 );
        $next_limit = $current + $extra_links + max( $extra_links + 1 - $current, 0 );
        for ($n = 1; $n <= $total; $n++) {
            $ajax_hidden_class = '';
            $url = ( $n == 1 ? remove_query_arg( $paged_var, $current_url ) : ($url = add_query_arg( $paged_var, $n, $current_url )) );
            if ( $n < $prev_limit || $n > $next_limit ) {
                if ( !$is_ajax ) {
                    continue;
                }
                $ajax_hidden_class = 'wps-page-item--hidden';
            }
            printf(
                '<li class="%s"><a class="wps--page-numbers %s" href="%s" data-page="%d">%s</a></li>',
                $ajax_hidden_class,
                ( $n == $current ? 'wps--current' : '' ),
                esc_url( $url ),
                abs( $n ),
                number_format_i18n( $n )
            );
        }
        echo '</ul></nav></div>';
    }

    static function get_paged_var( $id ) {
        return 'paged' . $id;
    }

    static function get_meta_field_keys() {
        $field_keys = [
            '_experience',
            '_company',
            '_skills',
            '_designation',
            '_telephone',
            '_email',
            '_website',
            '_social_links',
            '_ribbon',
            '_mobile',
            '_color',
            '_education'
        ];
        return $field_keys;
    }

    static function get_item_data( $data_key, $post_id = null, $shortcode_id = null ) {
        if ( empty( $post_id ) ) {
            $post_id = get_the_ID();
        }
        $meta_fields = self::get_meta_field_keys();
        $taxonomies = self::get_active_taxonomies( true );
        $value = '';
        if ( in_array( $data_key, $meta_fields ) ) {
            $value = get_post_meta( $post_id, $data_key, true );
        } else {
            if ( in_array( $data_key, $taxonomies ) ) {
                $value = get_the_terms( $post_id, str_replace( '_', '-', $data_key ) );
            }
        }
        global $wps_team_id;
        if ( isset( $wps_team_id ) ) {
            $data_key_filter = ltrim( $data_key, '_' );
            $value = apply_filters(
                "wpspeedo_team/{$data_key_filter}",
                $value,
                $post_id,
                $wps_team_id
            );
        }
        if ( !empty( $value ) ) {
            return $value;
        }
        return false;
    }

    static function load_template( $template_name ) {
        $template_folder = (string) apply_filters( 'wpspeedo_team/template/folder', 'wpspeedo-team' );
        $template_folder = '/' . trailingslashit( ltrim( $template_folder, '/\\' ) );
        // Load from child theme if template exists
        if ( is_child_theme() ) {
            $template_path = get_template_directory() . $template_folder . $template_name;
            if ( file_exists( $template_path ) ) {
                return $template_path;
            }
            $template_path = get_template_directory() . $template_folder . 'pro/' . $template_name;
            if ( file_exists( $template_path ) ) {
                return $template_path;
            }
        }
        // Load from parent theme if template exists
        $template_path = get_stylesheet_directory() . $template_folder . $template_name;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
        $template_path = get_stylesheet_directory() . $template_folder . 'pro/' . $template_name;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
        // Load templates from plugin
        $template_path = WPS_TEAM_PATH . 'templates/' . $template_name;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
        return new WP_Error('wpspeedo_team/template/not_found', _x( 'Template file is not found', 'Dashboard', 'wpspeedo-team' ));
    }

    static function get_temp_settings() {
        $temp_key = self::get_shortcode_preview_key();
        if ( $temp_key ) {
            return get_transient( $temp_key );
        }
    }

    static function is_shortcode_preview() {
        return (bool) (!empty( $_REQUEST['wps_team_sh_preview'] ));
    }

    static function get_shortcode_preview_key() {
        return ( self::is_shortcode_preview() ? sanitize_text_field( $_REQUEST['wps_team_sh_preview'] ) : null );
    }

    public static function render_html_attributes( array $attributes ) {
        $rendered_attributes = [];
        foreach ( $attributes as $attribute_key => $attribute_values ) {
            if ( is_array( $attribute_values ) ) {
                $attribute_values = implode( ' ', $attribute_values );
            }
            $rendered_attributes[] = sprintf( '%1$s="%2$s"', $attribute_key, esc_attr( $attribute_values ) );
        }
        return implode( ' ', $rendered_attributes );
    }

    public static function max_letters( $text, $limit ) {
        if ( mb_strlen( $text ) > $limit ) {
            $text = mb_substr( $text, 0, $limit ) . '...';
        }
        return $text;
    }

    public static function get_brnad_name( $icon ) {
        return str_replace( ['fab fa-', 'far fa-', 'fas fa-'], '', $icon );
    }

    public static function sanitize_phone_number( $phone ) {
        return preg_replace( '/[^0-9\\-\\_\\+]*/', '', $phone );
    }

    public static function default_settings() {
        return [
            'desig_label'                  => 'Designation',
            'email_label'                  => 'Email Address',
            'mobile_label'                 => 'Mobile (Personal)',
            'telephone_label'              => 'Telephone (Office)',
            'experience_label'             => 'Years of Experience',
            'website_label'                => 'Website',
            'company_label'                => 'Company',
            'ribbon_label'                 => 'Ribbon / Tag',
            'link_1_label'                 => 'Resume Link',
            'link_2_label'                 => 'Hire Link',
            'color_label'                  => 'Color',
            'read_more_text'               => 'Read More',
            'filter_search_text'           => 'Search',
            'filter_all_group_text'        => 'All',
            'filter_all_location_text'     => 'All Locations',
            'filter_all_language_text'     => 'All Languages',
            'filter_all_specialty_text'    => 'All Specialties',
            'filter_all_gender_text'       => 'All Genders',
            'filter_all_extra_one_text'    => 'All Extra One',
            'filter_all_extra_two_text'    => 'All Extra Two',
            'filter_all_extra_three_text'  => 'All Extra Three',
            'filter_all_extra_four_text'   => 'All Extra Four',
            'filter_all_extra_five_text'   => 'All Extra Five',
            'link_1_text'                  => 'My Resume',
            'link_2_text'                  => 'Hire Me',
            'social_links_title'           => 'Connect With Me:',
            'skills_title'                 => 'Skills:',
            'education_title'              => 'Education:',
            'mobile_meta_label'            => 'Mobile:',
            'phone_meta_label'             => 'Phone:',
            'email_meta_label'             => 'Email:',
            'website_meta_label'           => 'Website:',
            'experience_meta_label'        => 'Experience:',
            'company_meta_label'           => 'Company:',
            'group_meta_label'             => 'Group:',
            'location_meta_label'          => 'Location:',
            'language_meta_label'          => 'Language:',
            'specialty_meta_label'         => 'Specialty:',
            'gender_meta_label'            => 'Gender:',
            'extra_one_meta_label'         => 'Extra One:',
            'extra_two_meta_label'         => 'Extra Two:',
            'extra_three_meta_label'       => 'Extra Three:',
            'extra_four_meta_label'        => 'Extra Four:',
            'extra_five_meta_label'        => 'Extra Five:',
            'load_more_text'               => 'Load More',
            'return_to_archive_text'       => 'Back to Team Page',
            'no_results_found_text'        => 'No Results Found',
            'enable_multilingual'          => false,
            'disable_google_fonts_loading' => false,
            'single_link_1'                => false,
            'single_link_2'                => false,
            'archive_page'                 => false,
            'archive_page_link'            => get_post_type_archive_link( Utils::post_type_name() ),
            'thumbnail_size'               => 'full',
            'thumbnail_size_custom'        => [],
            'detail_thumbnail_size'        => 'full',
            'detail_thumbnail_size_custom' => [],
            'detail_thumbnail_type'        => 'image',
            'enable_archive'               => true,
            'post_type_slug'               => 'wps-members',
            'member_plural_name'           => 'Members',
            'member_single_name'           => 'Member',
            'enable_group_taxonomy'        => true,
            'enable_group_archive'         => false,
            'group_slug'                   => 'wps-members-group',
            'group_plural_name'            => 'Groups',
            'group_single_name'            => 'Group',
            'enable_location_taxonomy'     => false,
            'enable_location_archive'      => false,
            'location_slug'                => 'wps-members-location',
            'location_plural_name'         => 'Locations',
            'location_single_name'         => 'Location',
            'enable_language_taxonomy'     => false,
            'enable_language_archive'      => false,
            'language_slug'                => 'wps-members-language',
            'language_plural_name'         => 'Languages',
            'language_single_name'         => 'Language',
            'enable_specialty_taxonomy'    => false,
            'enable_specialty_archive'     => false,
            'specialty_slug'               => 'wps-members-specialty',
            'specialty_plural_name'        => 'Specialties',
            'specialty_single_name'        => 'Specialty',
            'enable_gender_taxonomy'       => false,
            'enable_gender_archive'        => false,
            'gender_slug'                  => 'wps-members-gender',
            'gender_plural_name'           => 'Genders',
            'gender_single_name'           => 'Gender',
            'enable_extra_one_taxonomy'    => false,
            'enable_extra_one_archive'     => false,
            'extra_one_slug'               => 'wps-members-extra-one',
            'extra_one_plural_name'        => 'Extra One',
            'extra_one_single_name'        => 'Extra One',
            'enable_extra_two_taxonomy'    => false,
            'enable_extra_two_archive'     => false,
            'extra_two_slug'               => 'wps-members-extra-two',
            'extra_two_plural_name'        => 'Extra Two',
            'extra_two_single_name'        => 'Extra Two',
            'enable_extra_three_taxonomy'  => false,
            'enable_extra_three_archive'   => false,
            'extra_three_slug'             => 'wps-members-extra-three',
            'extra_three_plural_name'      => 'Extra Three',
            'extra_three_single_name'      => 'Extra Three',
            'enable_extra_four_taxonomy'   => false,
            'enable_extra_four_archive'    => false,
            'extra_four_slug'              => 'wps-members-extra-four',
            'extra_four_plural_name'       => 'Extra Four',
            'extra_four_single_name'       => 'Extra Four',
            'enable_extra_five_taxonomy'   => false,
            'enable_extra_five_archive'    => false,
            'extra_five_slug'              => 'wps-members-extra-five',
            'extra_five_plural_name'       => 'Extra Five',
            'extra_five_single_name'       => 'Extra Five',
        ];
    }

    public static function get_default( $key = '' ) {
        $default_settings = self::default_settings();
        if ( array_key_exists( $key, $default_settings ) ) {
            return $default_settings[$key];
        }
        return null;
    }

    static function get_registered_image_sizes() {
        $sizes = get_intermediate_image_sizes();
        if ( empty( $sizes ) ) {
            return [];
        }
        $_sizes = [];
        foreach ( $sizes as $size ) {
            $_sizes[] = [
                'label' => ucwords( preg_replace( '/_|-/', ' ', $size ) ),
                'value' => $size,
            ];
        }
        $_sizes = array_merge( $_sizes, [[
            'label' => _x( 'Full', 'Editor', 'wpspeedo-team' ),
            'value' => 'full',
        ]] );
        $custom_size = [
            'label' => _x( 'Custom', 'Editor', 'wpspeedo-team' ),
            'value' => 'custom',
        ];
        $custom_size['label'] = self::get_pro_label() . $custom_size['label'];
        $custom_size['disabled'] = true;
        $_sizes[] = $custom_size;
        return $_sizes;
    }

    static function get_thumbnail_position() {
        return [
            [
                'label' => _x( 'Top Left', 'Editor', 'wpspeedo-team' ),
                'value' => 'left top',
            ],
            [
                'label' => _x( 'Top Center', 'Editor', 'wpspeedo-team' ),
                'value' => 'center top',
            ],
            [
                'label' => _x( 'Top Right', 'Editor', 'wpspeedo-team' ),
                'value' => 'right top',
            ],
            [
                'label' => _x( 'Middle Left', 'Editor', 'wpspeedo-team' ),
                'value' => 'left center',
            ],
            [
                'label' => _x( 'Middle Center', 'Editor', 'wpspeedo-team' ),
                'value' => 'center center',
            ],
            [
                'label' => _x( 'Middle Right', 'Editor', 'wpspeedo-team' ),
                'value' => 'right center',
            ],
            [
                'label' => _x( 'Bottom Left', 'Editor', 'wpspeedo-team' ),
                'value' => 'left bottom',
            ],
            [
                'label' => _x( 'Bottom Center', 'Editor', 'wpspeedo-team' ),
                'value' => 'center bottom',
            ],
            [
                'label' => _x( 'Bottom Right', 'Editor', 'wpspeedo-team' ),
                'value' => 'right bottom',
            ]
        ];
    }

    static function get_options_thumbnail_type( $excludes = [] ) {
        $options = [[
            'label' => _x( 'Image', 'Editor', 'wpspeedo-team' ),
            'value' => 'image',
        ], [
            'label'    => _x( 'Carousel', 'Editor', 'wpspeedo-team' ),
            'disabled' => true,
            'value'    => 'carousel',
        ], [
            'label'    => _x( 'Flip Image', 'Editor', 'wpspeedo-team' ),
            'disabled' => true,
            'value'    => 'flip',
        ]];
        if ( !empty( $excludes ) ) {
            foreach ( $excludes as $exclude_item ) {
                $key = array_search( $exclude_item, array_column( $options, 'value' ) );
                unset($options[$key]);
            }
            $options = array_values( $options );
        }
        return $options;
    }

    public static function get_settings() {
        // Settings
        $defaults = self::default_settings();
        $settings = (array) get_option( self::get_option_name(), $defaults );
        $settings = array_merge( $defaults, $settings );
        // Taxonomy Settings
        $taxonomy_settings = self::get_taxonomies_settings();
        $settings = array_merge( $settings, $taxonomy_settings );
        // Set Essential Settings
        $fields = ['post_type_slug', 'member_plural_name', 'member_single_name'];
        foreach ( $fields as $field ) {
            if ( empty( $settings[$field] ) ) {
                $settings[$field] = $defaults[$field];
            }
        }
        return $settings;
    }

    public static function get_setting( $key, $default = '' ) {
        $settings = self::get_settings();
        if ( array_key_exists( $key, $settings ) ) {
            $val = $settings[$key];
            if ( $val === null && !empty( $default ) ) {
                return $default;
            }
            return $val;
        }
        if ( !empty( $default ) ) {
            return $default;
        }
        return null;
    }

    public static function has_archive( $taxonomy = null ) {
        if ( $taxonomy ) {
            return wp_validate_boolean( self::get_setting( 'enable_' . self::to_field_key( $taxonomy ) . '_archive' ) );
        }
        return wp_validate_boolean( self::get_setting( 'enable_archive' ) );
    }

    public static function get_taxonomy_roots( $with_pro_taxonomies = false ) {
        $taxonomies = [
            'group',
            'location',
            'language',
            'specialty',
            'gender',
            'extra-one',
            'extra-two',
            'extra-three',
            'extra-four',
            'extra-five'
        ];
        if ( $with_pro_taxonomies || wps_team_fs()->can_use_premium_code__premium_only() ) {
            return $taxonomies;
        }
        return ['group'];
    }

    public static function get_taxonomy_name( $tax_root, $is_field = false ) {
        $name = 'wps-team-' . $tax_root;
        return ( $is_field ? self::to_field_key( $name ) : $name );
    }

    public static function get_taxonomy_root( $taxonomy, $is_field = false ) {
        $tax_root = str_replace( 'wps-team-', '', $taxonomy );
        return ( $is_field ? self::to_field_key( $tax_root ) : $tax_root );
    }

    public static function get_taxonomy_key( $taxonomy ) {
        return self::get_taxonomy_root( $taxonomy, true );
    }

    public static function get_taxonomies( $is_field = false ) {
        $taxonomies = array_map( get_called_class() . '::get_taxonomy_name', self::get_taxonomy_roots() );
        if ( $is_field ) {
            return array_map( get_called_class() . '::to_field_key', $taxonomies );
        }
        return $taxonomies;
    }

    public static function get_active_taxonomies( $is_field = false ) {
        $roots = self::get_taxonomy_roots();
        $taxonomies = [];
        foreach ( $roots as $tax_root ) {
            if ( self::get_setting( 'enable_' . Utils::to_field_key( $tax_root ) . '_taxonomy' ) ) {
                $taxonomies[] = self::get_taxonomy_name( $tax_root );
            }
        }
        if ( $is_field ) {
            return array_map( get_called_class() . '::to_field_key', $taxonomies );
        }
        return $taxonomies;
    }

    public static function archive_enabled_taxonomies() {
        $taxonomies = self::get_active_taxonomies();
        if ( empty( $taxonomies ) ) {
            return [];
        }
        $_taxonomies = [];
        foreach ( $taxonomies as $taxonomy ) {
            if ( self::has_archive( str_replace( 'wps-team-', '', $taxonomy ) ) ) {
                $_taxonomies[] = $taxonomy;
            }
        }
        return $_taxonomies;
    }

    public static function post_type_name() {
        return 'wps-team-members';
    }

    public static function to_field_key( $str ) {
        return str_replace( '-', '_', $str );
    }

    public static function get_option_name() {
        return 'wps_team_members';
    }

    public static function get_taxonomies_option_name() {
        return 'wps_team_members_taxonomies';
    }

    public static function taxonomies_settings_keys() {
        $taxonomy_roots = self::get_taxonomy_roots( true );
        $_tax_roots = [];
        foreach ( $taxonomy_roots as $tax_root ) {
            $tax_root = self::to_field_key( $tax_root );
            $_tax_roots[] = 'enable_' . $tax_root . '_taxonomy';
            $_tax_roots[] = 'enable_' . $tax_root . '_archive';
            $_tax_roots[] = $tax_root . '_plural_name';
            $_tax_roots[] = $tax_root . '_single_name';
            $_tax_roots[] = $tax_root . '_slug';
        }
        return $_tax_roots;
    }

    public static function get_taxonomies_settings() {
        $taxonomy_keys = self::taxonomies_settings_keys();
        $default_settings = array_intersect_key( Utils::default_settings(), array_flip( $taxonomy_keys ) );
        $settings = get_option( self::get_taxonomies_option_name(), $default_settings );
        foreach ( $settings as $key => $val ) {
            if ( empty( $val ) ) {
                $settings[$key] = $default_settings[$key];
            }
        }
        return $settings;
    }

    public static function get_archive_slug( $taxonomy = null ) {
        if ( $taxonomy ) {
            return self::get_setting( $taxonomy . '_slug' );
        }
        return self::get_setting( 'post_type_slug' );
    }

    public static function flush_rewrite_rules() {
        delete_option( self::rewrite_flush_key() );
    }

    public static function rewrite_flush_key() {
        return 'wps-rewrite--flushed';
    }

    public static function get_plugin_icon() {
        return WPS_TEAM_URL . 'images/icon.svg';
    }

    public static function get_pro_label() {
        return _x( '(Pro) - ', 'Editor', 'wpspeedo-team' );
    }

    public static function get_options_display_type() {
        $options = [[
            'label' => _x( 'Grid', 'Editor', 'wpspeedo-team' ),
            'value' => 'grid',
        ], [
            'label' => _x( 'Carousel', 'Editor', 'wpspeedo-team' ),
            'value' => 'carousel',
        ], [
            'disabled' => true,
            'label'    => _x( 'Filter', 'Editor', 'wpspeedo-team' ),
            'value'    => 'filter',
        ]];
        return $options;
    }

    public static function get_options_filters_theme() {
        $options = [[
            'disabled' => true,
            'label'    => _x( 'Style 01', 'Editor', 'wpspeedo-team' ),
            'value'    => 'style-01',
        ], [
            'disabled' => true,
            'label'    => _x( 'Style 02', 'Editor', 'wpspeedo-team' ),
            'value'    => 'style-02',
        ]];
        return $options;
    }

    public static function get_options_aspect_ratio() {
        $options = [
            [
                'label' => _x( 'Default', 'Editor', 'wpspeedo-team' ),
                'value' => 'default',
            ],
            [
                'label' => _x( 'Square - 1:1', 'Editor', 'wpspeedo-team' ),
                'value' => '1/1',
            ],
            [
                'label' => _x( 'Portrait - 6:7', 'Editor', 'wpspeedo-team' ),
                'value' => '6/7',
            ],
            [
                'label' => _x( 'Portrait - 5:6', 'Editor', 'wpspeedo-team' ),
                'value' => '5/6',
            ],
            [
                'label' => _x( 'Portrait - 4:5', 'Editor', 'wpspeedo-team' ),
                'value' => '4/5',
            ],
            [
                'label' => _x( 'Portrait - 8.5:11', 'Editor', 'wpspeedo-team' ),
                'value' => '8.5/11',
            ],
            [
                'label' => _x( 'Portrait - 3:4', 'Editor', 'wpspeedo-team' ),
                'value' => '3/4',
            ],
            [
                'label' => _x( 'Portrait - 5:7', 'Editor', 'wpspeedo-team' ),
                'value' => '5/7',
            ],
            [
                'label' => _x( 'Portrait - 2:3', 'Editor', 'wpspeedo-team' ),
                'value' => '2/3',
            ],
            [
                'label' => _x( 'Portrait - 9:16', 'Editor', 'wpspeedo-team' ),
                'value' => '9/16',
            ],
            [
                'label' => _x( 'Landscape - 5:4', 'Editor', 'wpspeedo-team' ),
                'value' => '5/4',
            ],
            [
                'label' => _x( 'Landscape - 4:3', 'Editor', 'wpspeedo-team' ),
                'value' => '4/3',
            ],
            [
                'label' => _x( 'Landscape - 3:2', 'Editor', 'wpspeedo-team' ),
                'value' => '3/2',
            ],
            [
                'label' => _x( 'Landscape - 14:9', 'Editor', 'wpspeedo-team' ),
                'value' => '14/9',
            ],
            [
                'label' => _x( 'Landscape - 16:10', 'Editor', 'wpspeedo-team' ),
                'value' => '16/10',
            ],
            [
                'label' => _x( 'Landscape - 1.66:1', 'Editor', 'wpspeedo-team' ),
                'value' => '1.66/1',
            ],
            [
                'label' => _x( 'Landscape - 1.75:1', 'Editor', 'wpspeedo-team' ),
                'value' => '1.75/1',
            ],
            [
                'label' => _x( 'Landscape - 16:9', 'Editor', 'wpspeedo-team' ),
                'value' => '16/9',
            ],
            [
                'label' => _x( 'Landscape - 1.91:1', 'Editor', 'wpspeedo-team' ),
                'value' => '1.91/1',
            ],
            [
                'label' => _x( 'Landscape - 2:1', 'Editor', 'wpspeedo-team' ),
                'value' => '2/1',
            ],
            [
                'label' => _x( 'Landscape - 21:9', 'Editor', 'wpspeedo-team' ),
                'value' => '21/9',
            ]
        ];
        return $options;
    }

    public static function get_options_layout_mode() {
        $options = [[
            'label' => _x( 'Masonry', 'Editor', 'wpspeedo-team' ),
            'value' => 'masonry',
        ], [
            'label' => _x( 'Fit Rows', 'Editor', 'wpspeedo-team' ),
            'value' => 'fitRows',
        ]];
        return $options;
    }

    public static function get_shape_types() {
        $options = [
            'circle' => [
                'title' => _x( 'Circle', 'Editor', 'wpspeedo-team' ),
                'icon'  => 'fas fa-circle',
            ],
            'square' => [
                'title' => _x( 'Square', 'Editor', 'wpspeedo-team' ),
                'icon'  => 'fas fa-square-full',
            ],
            'radius' => [
                'title' => _x( 'Radius', 'Editor', 'wpspeedo-team' ),
                'icon'  => 'fas fa-square',
            ],
        ];
        return $options;
    }

    public static function get_options_theme() {
        $options = [
            [
                'label' => _x( 'Square One', 'Editor', 'wpspeedo-team' ),
                'value' => 'square-01',
            ],
            [
                'label' => _x( 'Square Two', 'Editor', 'wpspeedo-team' ),
                'value' => 'square-02',
            ],
            [
                'label' => _x( 'Square Three', 'Editor', 'wpspeedo-team' ),
                'value' => 'square-03',
            ],
            [
                'label' => _x( 'Square Four', 'Editor', 'wpspeedo-team' ),
                'value' => 'square-04',
            ],
            [
                'label' => _x( 'Square Five', 'Editor', 'wpspeedo-team' ),
                'value' => 'square-05',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Six', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-06',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Seven', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-07',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Eight', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-08',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Nine', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-09',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Ten', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-10',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Eleven', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-11',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Twelve', 'Editor', 'wpspeedo-team' ),
                'value'    => 'square-12',
            ],
            [
                'label' => _x( 'Circle One', 'Editor', 'wpspeedo-team' ),
                'value' => 'circle-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Two', 'Editor', 'wpspeedo-team' ),
                'value'    => 'circle-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Three', 'Editor', 'wpspeedo-team' ),
                'value'    => 'circle-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Four', 'Editor', 'wpspeedo-team' ),
                'value'    => 'circle-04',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Five', 'Editor', 'wpspeedo-team' ),
                'value'    => 'circle-05',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Six', 'Editor', 'wpspeedo-team' ),
                'value'    => 'circle-06',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz One', 'Editor', 'wpspeedo-team' ),
                'value'    => 'horiz-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Two', 'Editor', 'wpspeedo-team' ),
                'value'    => 'horiz-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Three', 'Editor', 'wpspeedo-team' ),
                'value'    => 'horiz-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Four', 'Editor', 'wpspeedo-team' ),
                'value'    => 'horiz-04',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table One', 'Editor', 'wpspeedo-team' ),
                'value'    => 'table-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Two', 'Editor', 'wpspeedo-team' ),
                'value'    => 'table-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Three', 'Editor', 'wpspeedo-team' ),
                'value'    => 'table-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Four', 'Editor', 'wpspeedo-team' ),
                'value'    => 'table-04',
            ]
        ];
        return $options;
    }

    public static function get_options_card_action() {
        $options = [
            [
                'label' => _x( 'None', 'Editor', 'wpspeedo-team' ),
                'value' => 'none',
            ],
            [
                'label' => _x( 'Single Page', 'Editor', 'wpspeedo-team' ),
                'value' => 'single-page',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Modal', 'Editor', 'wpspeedo-team' ),
                'value'    => 'modal',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Side Panel', 'Editor', 'wpspeedo-team' ),
                'value'    => 'side-panel',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Expand', 'Editor', 'wpspeedo-team' ),
                'value'    => 'expand',
            ],
            [
                'disabled' => true,
                'label'    => self::get_setting( 'link_1_label' ),
                'value'    => 'link_1',
            ],
            [
                'disabled' => true,
                'label'    => self::get_setting( 'link_2_label' ),
                'value'    => 'link_2',
            ]
        ];
        return $options;
    }

    public static function get_options_orderby() {
        $options = [
            [
                'label' => _x( 'ID', 'Editor', 'wpspeedo-team' ),
                'value' => 'ID',
            ],
            [
                'label' => _x( 'Title', 'Editor', 'wpspeedo-team' ),
                'value' => 'title',
            ],
            [
                'label' => _x( 'Date', 'Editor', 'wpspeedo-team' ),
                'value' => 'date',
            ],
            [
                'label' => _x( 'Random', 'Editor', 'wpspeedo-team' ),
                'value' => 'rand',
            ],
            [
                'label' => _x( 'Modified', 'Editor', 'wpspeedo-team' ),
                'value' => 'modified',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Custom Order', 'Editor', 'wpspeedo-team' ),
                'value'    => 'menu_order',
            ]
        ];
        return $options;
    }

    public static function get_post_term_slugs( $post_id, array $term_names, $separator = ' ' ) {
        $terms = [];
        foreach ( $term_names as $term_name ) {
            $_terms = get_the_terms( $post_id, $term_name );
            if ( !empty( $_terms ) && !is_wp_error( $_terms ) ) {
                $terms = array_merge( $terms, wp_list_pluck( $_terms, 'slug' ) );
            }
        }
        if ( !empty( $terms ) ) {
            $terms = array_map( 'urldecode', $terms );
            return implode( $separator, $terms );
        }
        return '';
    }

    public static function get_terms( $taxonomy, $args = [] ) {
        $args = array_merge( [
            'taxonomy'   => $taxonomy,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
            'include'    => [],
            'exclude'    => [],
        ], $args );
        // Generate Cache Key
        $cache_key = md5( 'wpspeedo_team_terms_' . serialize( $args ) );
        // Get Terms from Cache If Exists for Public Request
        if ( !is_admin() ) {
            $terms = get_transient( $cache_key );
            if ( $terms !== false ) {
                return $terms;
            }
        }
        // Get Terms from Database
        $terms = get_terms( $args );
        if ( empty( $terms ) || is_wp_error( $terms ) ) {
            return [];
        }
        // Set Cache
        set_transient( $cache_key, $terms, HOUR_IN_SECONDS );
        return $terms;
    }

    public static function get_group_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'group' ), $args );
    }

    public static function get_location_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'location' ), $args );
    }

    public static function get_language_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'language' ), $args );
    }

    public static function get_specialty_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'specialty' ), $args );
    }

    public static function get_gender_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'gender' ), $args );
    }

    public static function get_extra_one_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'extra-one' ), $args );
    }

    public static function get_extra_two_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'extra-two' ), $args );
    }

    public static function get_extra_three_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'extra-three' ), $args );
    }

    public static function get_extra_four_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'extra-four' ), $args );
    }

    public static function get_extra_five_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'extra-five' ), $args );
    }

    public static function get_term_options( $terms ) {
        $terms = wp_list_pluck( $terms, 'name', 'term_id' );
        return self::to_options( $terms );
    }

    public static function to_options( array $options ) {
        $_options = [];
        foreach ( $options as $key => $val ) {
            $_options[] = [
                'label' => $val,
                'value' => $key,
            ];
        }
        return $_options;
    }

    public static function get_control_options( $control_id, $args = null ) {
        $method = "get_options_{$control_id}";
        $options = self::$method( $args );
        foreach ( $options as &$option ) {
            if ( array_key_exists( 'disabled', $option ) ) {
                $option['label'] = self::get_pro_label() . $option['label'];
            }
        }
        return $options;
    }

    public static function get_active_themes() {
        $themes = [
            'square-01',
            'square-02',
            'square-03',
            'square-04',
            'square-05',
            'circle-01'
        ];
        return $themes;
    }

    public static function get_group_themes( $theme_category ) {
        $themes = self::get_active_themes();
        return array_filter( $themes, function ( $theme ) use($theme_category) {
            return strpos( $theme, $theme_category ) !== false;
        } );
    }

    public static function get_wps_team( $shortcode_id ) {
        return do_shortcode( sprintf( '[wpspeedo-team id=%d]', $shortcode_id ) );
    }

    public static function get_top_label_menu() {
        return 'edit.php?post_type=' . Utils::post_type_name();
    }

    public static function string_to_array( $terms = '' ) {
        if ( empty( $terms ) ) {
            return [];
        }
        return (array) array_filter( explode( ',', $terms ) );
    }

    public static function get_demo_data_status( $demo_type = '' ) {
        $status = [
            'post_data'      => wp_validate_boolean( get_option( 'wpspeedo_team_dummy_post_data_created' ) ),
            'shortcode_data' => wp_validate_boolean( get_option( 'wpspeedo_team_dummy_shortcode_data_created' ) ),
        ];
        if ( !empty( $demo_type ) && array_key_exists( $demo_type, $status ) ) {
            return $status[$demo_type];
        }
        return $status;
    }

    public static function get_social_classes( array $initials, array $settings ) {
        $initials = array_filter( $initials );
        $settings = array_filter( $settings );
        $config = array_merge( [
            'shape'               => 'circle',
            'bg_color_type'       => 'brand',
            'bg_color_type_hover' => 'brand',
            'color_type'          => 'custom',
            'color_type_hover'    => 'custom',
        ], $initials, $settings );
        $social_classes = ['wps--social-links'];
        if ( $config['shape'] ) {
            $social_classes[] = 'wps-si--shape-' . $config['shape'];
        }
        if ( $config['bg_color_type'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-bg-color';
        }
        if ( $config['bg_color_type_hover'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-bg-color--hover';
        }
        if ( $config['bg_color_type'] !== 'brand' && $config['color_type'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-color';
        }
        if ( $config['bg_color_type_hover'] !== 'brand' && $config['color_type_hover'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-color--hover';
        }
        return $social_classes;
    }

    public static function get_installed_time() {
        $installed_time = get_option( '_wps_team_installed_time' );
        if ( !empty( $installed_time ) ) {
            return $installed_time;
        }
        $installed_time = time();
        update_option( '_wps_team_installed_time', $installed_time );
        return $installed_time;
    }

    public static function get_timestamp_diff( $old_time, $new_time = null ) {
        if ( $new_time == null ) {
            $new_time = time();
        }
        return ceil( ($new_time - $old_time) / DAY_IN_SECONDS );
    }

    function minify_css( $css ) {
        // https://datayze.com/howto/minify-css-with-php
        $css = preg_replace( '/\\/\\*((?!\\*\\/).)*\\*\\//', '', $css );
        // negative look ahead
        $css = preg_replace( '/\\s{2,}/', ' ', $css );
        $css = preg_replace( '/\\s*([:;{}])\\s*/', '$1', $css );
        $css = preg_replace( '/;}/', '}', $css );
        return $css;
    }

    public static function get_post_link_attrs( $post_id, $shortcode_id = null, $action = 'single-page' ) {
        $attrs = [
            'href'   => '',
            'class'  => '',
            'target' => '',
            'rel'    => '',
        ];
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            return $attrs;
        }
        if ( $action === 'single-page' ) {
            $attrs['href'] = get_the_permalink( $post_id );
        }
        return $attrs;
    }

    public static function get_post_link_attrs_template( $shortcode_id = null, $action = 'single-page' ) {
        $attrs = [
            'href'   => '',
            'class'  => '',
            'target' => '',
            'rel'    => '',
        ];
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            return $attrs;
        }
        if ( $action === 'single-page' ) {
            $attrs['href'] = '{{=it.post_permalink}}';
        }
        return $attrs;
    }

    public static function get_the_title( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'card_action' => 'single-page',
            'tag'         => 'h3',
            'class'       => '',
        ], $args );
        $action = $args['card_action'];
        $tag = $args['tag'];
        $title_classes = ['wps-team--member-title wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $title_classes[] = $args['class'];
        }
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            $action = 'none';
        }
        if ( $action !== 'none' ) {
            $title_classes[] = 'team-member--link';
        }
        $html = sprintf( '<%s class="%s">', $tag, implode( ' ', $title_classes ) );
        if ( $action === 'none' ) {
            $html .= get_the_title();
        } else {
            $attrs = self::get_post_link_attrs( $post_id, self::shortcode_loader()->id, $action );
            $html .= sprintf(
                '<a href="%s" class="%s" %s %s>%s</a>',
                esc_attr( $attrs['href'] ),
                esc_attr( $attrs['class'] ),
                ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                get_the_title()
            );
        }
        $html .= sprintf( '</%s>', $tag );
        return $html;
    }

    public static function get_the_title_template( $args = [] ) {
        $args = shortcode_atts( [
            'card_action' => 'single-page',
            'tag'         => 'h3',
            'class'       => '',
        ], $args );
        $action = $args['card_action'];
        $tag = $args['tag'];
        $title_classes = ['wps-team--member-title wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $title_classes[] = $args['class'];
        }
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            $action = 'none';
        }
        if ( $action !== 'none' ) {
            $title_classes[] = 'team-member--link';
        }
        $html = sprintf( '<%s class="%s">', $tag, implode( ' ', $title_classes ) );
        if ( $action === 'none' ) {
            $html .= '{{=it.post_title}}';
        } else {
            $attrs = self::get_post_link_attrs_template( self::shortcode_loader()->id, $action );
            $html .= sprintf(
                '<a href="%s" class="%s" %s %s>%s</a>',
                esc_attr( $attrs['href'] ),
                esc_attr( $attrs['class'] ),
                ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                '{{=it.post_title}}'
            );
        }
        $html .= sprintf( '</%s>', $tag );
        return $html;
    }

    public static function get_render_info( $element, $context = 'general' ) {
        if ( $context == 'general' ) {
            return self::shortcode_loader()->get_setting( "show_{$element}" );
        }
        if ( $context == 'details' ) {
            return self::shortcode_loader()->get_setting( "show_details_{$element}" );
        }
    }

    public static function is_allowed_render( $element, $context = 'general', $force_show = false ) {
        if ( $force_show ) {
            return true;
        }
        $render_info = self::get_render_info( $element, $context );
        if ( $render_info == 'false' ) {
            return false;
        }
        return true;
    }

    public static function is_allowed_render_alt( $element, $context = 'general', $force_hide = false ) {
        if ( $force_hide ) {
            return false;
        }
        $render_info = self::get_render_info( $element, $context );
        if ( $render_info == 'true' ) {
            return true;
        }
        return false;
    }

    public static function get_the_thumbnail( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'               => 'general',
            'card_action'           => 'single-page',
            'thumbnail_type'        => 'image',
            'thumbnail_size'        => 'large',
            'thumbnail_size_custom' => [],
            'force_show'            => false,
            'tag'                   => 'div',
            'class'                 => '',
            'allow_ribbon'          => false,
        ], $args );
        if ( !self::is_allowed_render( 'thumbnail', $args['context'], $args['force_show'] ) ) {
            return '';
        }
        $tag = $args['tag'];
        $action = $args['card_action'];
        $thumb_wrapper_classes = ['team-member--thumbnail-wrapper wps-team--member-element'];
        $thumb_classes = ['team-member--thumbnail'];
        $args['thumbnail_type'] = 'image';
        $thumbnail_size = $args['thumbnail_size'];
        $gallery_html = '';
        if ( !empty( $args['class'] ) ) {
            $thumb_wrapper_classes[] = $args['class'];
        }
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            $action = 'none';
        }
        $html = sprintf( '<%s class="%s">', $tag, implode( ' ', $thumb_wrapper_classes ) );
        $html .= sprintf( '<div class="%s">', implode( ' ', $thumb_classes ) );
        $thumbnail_img_classes = '';
        if ( $action === 'none' ) {
            $html .= get_the_post_thumbnail( null, $thumbnail_size, [
                'class' => $thumbnail_img_classes,
            ] );
            $html .= $gallery_html;
        } else {
            $attrs = self::get_post_link_attrs( $post_id, self::shortcode_loader()->id, $action );
            $html .= sprintf(
                '<a href="%s" class="%s" %s %s %s>',
                esc_attr( $attrs['href'] ),
                esc_attr( $attrs['class'] ),
                ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                sprintf( 'aria-label="%s"', sprintf( esc_attr_x( 'Read More about %s.', 'Public', 'wpspeedo-team' ), get_the_title( $post_id ) ) )
            ) . get_the_post_thumbnail( null, $thumbnail_size ) . $gallery_html . '</a>';
        }
        if ( $args['allow_ribbon'] ) {
            $html .= Utils::get_the_ribbon( get_the_ID() );
        }
        $html .= sprintf( '</div></%s>', $tag );
        return $html;
    }

    public static function get_the_thumbnail_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'               => 'general',
            'card_action'           => 'single-page',
            'thumbnail_size'        => 'large',
            'thumbnail_size_custom' => [],
            'force_show'            => false,
            'tag'                   => 'div',
            'class'                 => '',
            'allow_ribbon'          => false,
        ], $args );
        if ( !self::is_allowed_render( 'thumbnail', $args['context'], $args['force_show'] ) ) {
            return '';
        }
        $thumbnail_size = $args['thumbnail_size'];
        $tag = $args['tag'];
        $thumb_classes = ['team-member--thumbnail-wrapper wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $thumb_classes[] = $args['class'];
        }
        $action = $args['card_action'];
        if ( !Utils::has_archive() && $action === 'single-page' ) {
            $action = 'none';
        }
        $html = sprintf( '<%s class="%s">', $tag, implode( ' ', $thumb_classes ) );
        $html .= '<div class="team-member--thumbnail">';
        if ( $action === 'none' ) {
            $html .= '{{=it.post_thumbnail}}';
        } else {
            $attrs = self::get_post_link_attrs_template( self::shortcode_loader()->id, $action );
            $html .= sprintf(
                '<a href="%s" class="%s" %s %s %s>',
                esc_attr( $attrs['href'] ),
                esc_attr( $attrs['class'] ),
                ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                sprintf( 'aria-label="%s"', sprintf( esc_attr_x( 'Read More about %s.', 'Public', 'wpspeedo-team' ), '{{=it.post_title}}' ) )
            ) . '{{=it.post_thumbnail}}' . '</a>';
        }
        if ( $args['allow_ribbon'] ) {
            $html .= Utils::get_the_ribbon( get_the_ID() );
        }
        $html .= sprintf( '</div></%s>', $tag );
        return $html;
    }

    public static function get_the_ribbon( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'class'   => '',
        ], $args );
        $ribbon_render = self::get_render_info( 'ribbon', $args['context'] );
        $show_ribbon = ( $ribbon_render == '' ? false : wp_validate_boolean( $ribbon_render ) );
        if ( !$show_ribbon ) {
            return '';
        }
        $ribbon_classes = ['wps-team--member-ribbon wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $ribbon_classes[] = $args['class'];
        }
        $ribbon = Utils::get_item_data( '_ribbon', $post_id );
        if ( empty( $ribbon ) ) {
            return '';
        }
        return sprintf( '<div class="%s">%s</div>', esc_attr( implode( ' ', $ribbon_classes ) ), esc_html( $ribbon ) );
    }

    public static function get_the_ribbon_template( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'class'   => '',
        ], $args );
        $ribbon_render = self::get_render_info( 'ribbon', $args['context'] );
        $show_ribbon = ( $ribbon_render == '' ? false : wp_validate_boolean( $ribbon_render ) );
        if ( !$show_ribbon ) {
            return '';
        }
        $ribbon_classes = ['wps-team--member-ribbon wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $ribbon_classes[] = $args['class'];
        }
        return sprintf( '<div class="%s">{{=it.ribbon}}</div>', esc_attr( implode( ' ', $ribbon_classes ) ) );
    }

    public static function shortcode_loader() {
        return $GLOBALS['shortcode_loader'];
    }

    public static function get_the_designation( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'tag'     => 'h4',
            'class'   => '',
        ], $args );
        if ( !self::is_allowed_render( 'designation', $args['context'] ) ) {
            return '';
        }
        $desig_classes = ['wps-team--member-desig wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $desig_classes[] = $args['class'];
        }
        $designation = Utils::get_item_data( '_designation', $post_id );
        if ( empty( $designation ) ) {
            return '';
        }
        return sprintf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            $args['tag'],
            esc_attr( implode( ' ', $desig_classes ) ),
            esc_html( $designation )
        );
    }

    public static function get_the_designation_template( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'tag'     => 'h4',
            'class'   => '',
        ], $args );
        if ( !self::is_allowed_render( 'designation', $args['context'] ) ) {
            return '';
        }
        $desig_classes = ['wps-team--member-desig wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $desig_classes[] = $args['class'];
        }
        return sprintf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            $args['tag'],
            esc_attr( implode( ' ', $desig_classes ) ),
            '{{=it.designation}}'
        );
    }

    public static function elements_display_order() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $elements = [
            'thumbnail'                                    => _x( 'Thumbnail', 'Editor', 'wpspeedo-team' ),
            'divider'                                      => _x( 'Divider', 'Editor', 'wpspeedo-team' ),
            'designation'                                  => _x( 'Designation', 'Editor', 'wpspeedo-team' ),
            'description'                                  => _x( 'Description', 'Editor', 'wpspeedo-team' ),
            'education'                                    => _x( 'Education', 'Editor', 'wpspeedo-team' ),
            'social'                                       => _x( 'Social', 'Editor', 'wpspeedo-team' ),
            'ribbon'                                       => _x( 'Ribbon/Tag', 'Editor', 'wpspeedo-team' ),
            'email'                                        => _x( 'Email', 'Editor', 'wpspeedo-team' ),
            'mobile'                                       => _x( 'Mobile', 'Editor', 'wpspeedo-team' ),
            'telephone'                                    => _x( 'Telephone', 'Editor', 'wpspeedo-team' ),
            'experience'                                   => _x( 'Experience', 'Editor', 'wpspeedo-team' ),
            'website'                                      => _x( 'Website', 'Editor', 'wpspeedo-team' ),
            'company'                                      => _x( 'Company', 'Editor', 'wpspeedo-team' ),
            'skills'                                       => _x( 'Skills', 'Editor', 'wpspeedo-team' ),
            'link_1'                                       => self::get_setting( 'link_1_label', 'Resume Link' ),
            'link_2'                                       => self::get_setting( 'link_2_label', 'Hire Link' ),
            self::get_taxonomy_name( 'group', true )       => $group_single_name,
            self::get_taxonomy_name( 'location', true )    => $location_single_name,
            self::get_taxonomy_name( 'language', true )    => $language_single_name,
            self::get_taxonomy_name( 'specialty', true )   => $specialty_single_name,
            self::get_taxonomy_name( 'gender', true )      => $gender_single_name,
            self::get_taxonomy_name( 'extra-one', true )   => $extra_one_single_name,
            self::get_taxonomy_name( 'extra-two', true )   => $extra_two_single_name,
            self::get_taxonomy_name( 'extra-three', true ) => $extra_three_single_name,
            self::get_taxonomy_name( 'extra-four', true )  => $extra_four_single_name,
            self::get_taxonomy_name( 'extra-five', true )  => $extra_five_single_name,
        ];
        return $elements;
    }

    public static function allowed_elements_display_order() {
        return [
            'thumbnail',
            'divider',
            'designation',
            'description',
            'social',
            'ribbon'
        ];
    }

    public static function get_sorted_elements() {
        $elements = array_keys( Utils::elements_display_order() );
        $_elements = [];
        foreach ( $elements as $element ) {
            $_elements[$element] = self::shortcode_loader()->get_setting( 'order_' . $element );
        }
        asort( $_elements );
        $element_keys = array_keys( $_elements );
        $element_keys = array_map( function ( $element_key ) {
            if ( in_array( $element_key, self::get_active_taxonomies( true ) ) ) {
                return $element_key;
            }
            return '_' . $element_key;
        }, $element_keys );
        return $element_keys;
    }

    public static function get_the_divider( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'class'   => '',
        ], $args );
        if ( !self::is_allowed_render( 'divider', $args['context'] ) ) {
            return '';
        }
        $divider_classes = ['wps-team--divider-wrapper wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $divider_classes[] = $args['class'];
        }
        $html = sprintf( '<div class="%s">', esc_attr( implode( ' ', $divider_classes ) ) );
        $html .= '<div class="wps-team--divider"></div>';
        $html .= '</div>';
        return $html;
    }

    public static function get_description_length( $length = null ) {
        if ( $length == null ) {
            $length = self::shortcode_loader()->get_setting( 'description_length' );
        }
        if ( $length == 0 ) {
            $length = PHP_INT_MAX - 500;
        }
        return $length;
    }

    public static function get_the_excerpt( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'            => 'general',
            'tag'                => 'div',
            'description_length' => 110,
            'add_read_more'      => false,
            'card_action'        => 'single-page',
            'read_more_text'     => '',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        $tag = $args['tag'];
        $des_length = $args['description_length'];
        $read_more_text = $args['read_more_text'];
        $read_more_link = '';
        if ( $des_length > 0 && $args['add_read_more'] && !empty( $read_more_text ) ) {
            $action = $args['card_action'];
            if ( !Utils::has_archive() && $action === 'single-page' ) {
                $action = 'none';
            }
            if ( $action !== 'none' ) {
                $attrs = self::get_post_link_attrs( $post_id, self::shortcode_loader()->id, $action );
                $read_more_link = sprintf(
                    '<a href="%s" class="%s" %s %s>%s</a>',
                    esc_attr( $attrs['href'] ),
                    esc_attr( $attrs['class'] ),
                    ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                    ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                    $read_more_text
                );
                $des_length = $des_length - mb_strlen( $read_more_text );
            }
        }
        $excerpt = Utils::max_letters( get_the_excerpt( $post_id ), $des_length );
        $excerpt = wpautop( sanitize_text_field( $excerpt ) . $read_more_link );
        return sprintf( '<%1$s class="wps-team--member-details wps-team--member-details-excerpt wps-team--member-element">%2$s</%1$s>', $tag, wp_kses_post( $excerpt ) );
    }

    public static function get_the_excerpt_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'        => 'general',
            'tag'            => 'div',
            'add_read_more'  => false,
            'card_action'    => 'single-page',
            'read_more_text' => '',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        $tag = $args['tag'];
        $read_more_link = '';
        $read_more_text = $args['read_more_text'];
        if ( $args['add_read_more'] && !empty( $args['read_more_text'] ) ) {
            $action = $args['card_action'];
            if ( !Utils::has_archive() && $action === 'single-page' ) {
                $action = 'none';
            }
            if ( $action !== 'none' ) {
                $attrs = self::get_post_link_attrs_template( self::shortcode_loader()->id, $action );
                $read_more_link = sprintf(
                    '<a href="%s" class="%s" %s %s>%s</a>',
                    esc_attr( $attrs['href'] ),
                    esc_attr( $attrs['class'] ),
                    ( empty( $attrs['target'] ) ? '' : sprintf( 'target="%s"', esc_attr( $attrs['target'] ) ) ),
                    ( empty( $attrs['rel'] ) ? '' : sprintf( 'rel="%s"', esc_attr( $attrs['rel'] ) ) ),
                    $read_more_text
                );
            }
        }
        return sprintf(
            '<%1$s class="wps-team--member-details wps-team--member-details-excerpt wps-team--member-element"><p>%2$s%3$s</p></%1$s>',
            $tag,
            '{{=it.excerpt}}',
            $read_more_link
        );
    }

    public static function get_the_description( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        return '<div class="wps-team--member-details wps-team--member-element">' . self::get_the_content( $post_id ) . '</div>';
    }

    public static function get_the_description_template( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        return '<div class="wps-team--member-details wps-team--member-element">{{=it.post_content}}</div>';
    }

    public static function get_the_education_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'education_title', _x( 'Education:', 'Public', 'wpspeedo-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--education-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_education( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render_alt( 'education', $args['context'] ) ) {
            return '';
        }
        $education = self::get_item_data( '_education', $post_id );
        if ( empty( $education ) ) {
            return;
        }
        ?>

		<div class="wps-team--member-education wps-team--member-element">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_education_title( $args );
        }
        ?>
			<div class="wps-team--member-details wps--education">
				<?php 
        echo wp_kses_post( $education );
        ?>
			</div>
		</div>
		
		<?php 
    }

    public static function get_the_education_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render_alt( 'education', $args['context'] ) ) {
            return '';
        }
        ?>

		{{? it._education }}

		<div class="wps-team--member-education wps-team--member-element">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_education_title( $args );
        }
        ?>
			<div class="wps-team--member-details wps--education">{{=it._education}}</div>
		</div>

		{{?}}
		
		<?php 
    }

    public static function wps_responsive_oembed( $html ) {
        return '<div class="wps-team--res-oembed">' . $html . '</div>';
    }

    public static function get_the_content( $post_id ) {
        add_filter( 'embed_oembed_html', get_called_class() . '::wps_responsive_oembed' );
        $content = get_the_content( null, false, $post_id );
        $content = apply_filters( 'the_content', $content );
        $content = wpautop( $content );
        $content = str_replace( ']]>', ']]&gt;', $content );
        remove_filter( 'embed_oembed_html', get_called_class() . '::wps_responsive_oembed' );
        return $content;
    }

    public static function parse_social_links( $social_links ) {
        $links = '';
        foreach ( $social_links as $slink ) {
            $links .= sprintf(
                '<li class="wps-si--%s">
				<a href="%s" aria-label="%s"%s>%s</a>
			</li>',
                Utils::get_brnad_name( $slink['social_icon']['icon'] ),
                esc_url_raw( $slink['social_link'] ),
                'Social Link',
                self::get_ext_url_params(),
                Icon_Manager::render_font_icon( $slink['social_icon'] )
            );
        }
        return $links;
    }

    public static function get_the_social_links_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'social_links_title', _x( 'Connect with me:', 'Public', 'wpspeedo-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--slinks-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_social_links( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'show_title' => false,
            'title_tag'  => 'h4',
            'tag'        => 'div',
        ], $args );
        if ( !self::is_allowed_render( 'social', $args['context'] ) ) {
            return '';
        }
        $social_links = array_filter( (array) Utils::get_item_data( '_social_links', $post_id ) );
        if ( empty( $social_links ) ) {
            return;
        }
        $tag = $args['tag'];
        printf( '<%s class="wps-team--member-s-links wps-team--member-element">', $tag );
        if ( $args['show_title'] ) {
            self::get_the_social_links_title( $args );
        }
        ?>

			<ul <?php 
        self::shortcode_loader()->print_attribute_string( 'social' );
        ?>>
				<?php 
        echo self::parse_social_links( $social_links );
        ?>
			</ul>

		<?php 
        printf( '</%s>', $tag );
    }

    public static function get_the_social_links_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'show_title' => false,
            'title_tag'  => 'h4',
            'tag'        => 'div',
        ], $args );
        if ( !self::is_allowed_render( 'social', $args['context'] ) ) {
            return '';
        }
        $tag = $args['tag'];
        ?>

		{{? it.social_links }}
			<?php 
        printf( '<%s class="wps-team--member-s-links wps-team--member-element">', $tag );
        if ( $args['show_title'] ) {
            if ( $args['show_title'] ) {
                self::get_the_social_links_title( $args );
            }
        }
        ?>
			<ul <?php 
        self::shortcode_loader()->print_attribute_string( 'social' );
        ?>>
				{{=it.social_links}}
			</ul>
			<?php 
        printf( '</%s>', $tag );
        ?>
		{{?}}

		<?php 
    }

    public static function get_the_link_1_title() {
        return plugin()->translations->get( 'link_1_btn_text', _x( 'My Resume', 'Public', 'wpspeedo-team' ) );
    }

    public static function get_the_link_2_title() {
        return plugin()->translations->get( 'link_2_btn_text', _x( 'Hire Me', 'Public', 'wpspeedo-team' ) );
    }

    public static function get_the_action_links( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'link_1'  => false,
            'link_2'  => false,
            'context' => 'general',
        ], $args );
        if ( $args['context'] == 'details' ) {
            $show_link_1 = self::shortcode_loader()->get_setting( 'show_details_link_1' );
            $show_link_2 = self::shortcode_loader()->get_setting( 'show_details_link_2' );
        } else {
            if ( $args['context'] == 'single' ) {
                $show_link_1 = self::get_setting( 'single_link_1' );
                $show_link_2 = self::get_setting( 'single_link_2' );
            } else {
                $show_link_1 = self::shortcode_loader()->get_setting( 'show_link_1' );
                $show_link_2 = self::shortcode_loader()->get_setting( 'show_link_2' );
            }
        }
        $show_link_1 = ( $show_link_1 == '' ? $args['link_1'] : wp_validate_boolean( $show_link_1 ) );
        $show_link_2 = ( $show_link_2 == '' ? $args['link_2'] : wp_validate_boolean( $show_link_2 ) );
        if ( !$show_link_1 && !$show_link_2 ) {
            return '';
        }
        $link_1 = self::get_item_data( '_link_1' );
        $link_2 = self::get_item_data( '_link_2' );
        if ( empty( $link_1 ) && empty( $link_2 ) ) {
            return '';
        }
        $html = sprintf( '<div class="wps-team--action-links wps-team--member-element">' );
        if ( $show_link_1 && !empty( $link_1 ) ) {
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-1"%s>%s</a>',
                esc_url_raw( $link_1 ),
                ( self::is_external_url( $link_1 ) ? self::get_ext_url_params() : '' ),
                esc_html( self::get_the_link_1_title() )
            );
        }
        if ( $show_link_2 && !empty( $link_2 ) ) {
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-2"%s>%s</a>',
                esc_url_raw( $link_2 ),
                ( self::is_external_url( $link_2 ) ? self::get_ext_url_params() : '' ),
                esc_html( self::get_the_link_2_title() )
            );
        }
        $html .= '</div>';
        return $html;
    }

    public static function get_the_action_links_template( $args = [] ) {
        $args = shortcode_atts( [
            'link_1'  => false,
            'link_2'  => false,
            'context' => 'general',
        ], $args );
        if ( $args['context'] == 'details' ) {
            $show_link_1 = self::shortcode_loader()->get_setting( 'show_details_link_1' );
            $show_link_2 = self::shortcode_loader()->get_setting( 'show_details_link_2' );
        } else {
            if ( $args['context'] == 'single' ) {
                $show_link_1 = self::get_setting( 'single_link_1' );
                $show_link_2 = self::get_setting( 'single_link_2' );
            } else {
                $show_link_1 = self::shortcode_loader()->get_setting( 'show_link_1' );
                $show_link_2 = self::shortcode_loader()->get_setting( 'show_link_2' );
            }
        }
        $show_link_1 = ( $show_link_1 == '' ? $args['link_1'] : wp_validate_boolean( $show_link_1 ) );
        $show_link_2 = ( $show_link_2 == '' ? $args['link_2'] : wp_validate_boolean( $show_link_2 ) );
        if ( !$show_link_1 && !$show_link_2 ) {
            return '';
        }
        ?>

		{{? it.link_1 || it.link_2 }}

		<div class="wps-team--action-links wps-team--member-element">

			<?php 
        if ( $show_link_1 ) {
            ?>
				{{? it.link_1 }}
					<?php 
            printf( '<a href="%s" class="wps-team--btn wps-team--link-1">%s</a>', '{{=it.link_1}}', esc_html( self::get_the_link_1_title() ) );
            ?>
				{{?}}
			<?php 
        }
        ?>

			<?php 
        if ( $show_link_2 ) {
            ?>
				{{? it.link_2 }}
					<?php 
            printf( '<a href="%s" class="wps-team--btn wps-team--link-2">%s</a>', '{{=it.link_2}}', esc_html( self::get_the_link_2_title() ) );
            ?>
				{{?}}
			<?php 
        }
        ?>

		</div>

		{{?}}

		<?php 
    }

    public static function parse_skills( $_skills ) {
        $skills = '';
        foreach ( $_skills as $skill ) {
            $skills .= sprintf(
                '<li>
				<span class="skill-name">%1$s</span>
				<span class="skill-value">%2$d%3$s</span>
				<span class="skill-bar" data-width="%2$d" style="width: %2$d%3$s"></span>
			</li>',
                sanitize_text_field( $skill['skill_name'] ),
                sanitize_text_field( $skill['skill_val'] ),
                '%'
            );
        }
        return $skills;
    }

    public static function get_the_skills_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'skills_title', _x( 'Skills:', 'Public', 'wpspeedo-team' ) );
        printf( '<%1$s class="wps-team--block-title team-member--skills-title">%2$s</%1$s>', sanitize_key( $args['title_tag'] ), esc_html( $title_text ) );
    }

    public static function get_the_skills( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render( 'skills', $args['context'] ) ) {
            return '';
        }
        $skills = array_filter( (array) Utils::get_item_data( '_skills', $post_id ) );
        if ( empty( $skills ) ) {
            return;
        }
        ?>

		<div class="wps-team--member-skills wps-team--member-element">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_skills_title( $args );
        }
        ?>
			<ul class="wps--skills">
				<?php 
        echo self::parse_skills( $skills );
        ?>
			</ul>
		</div>

		<?php 
    }

    public static function get_the_skills_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'    => 'general',
            'title_tag'  => 'h4',
            'show_title' => false,
        ], $args );
        if ( !self::is_allowed_render( 'skills', $args['context'] ) ) {
            return '';
        }
        ?>

		{{? it.skills }}

		<div class="wps-team--member-skills wps-team--member-element">
			<?php 
        if ( $args['show_title'] ) {
            self::get_the_skills_title( $args );
        }
        ?>
			<ul class="wps--skills">{{=it.skills}}</ul>
		</div>

		{{?}}
		
		<?php 
    }

    public static function get_the_field_label( $field_key, $label_type = '' ) {
        $field_label = '';
        if ( $label_type === 'icon' ) {
            switch ( $field_key ) {
                case '_mobile':
                    $field_label = '<i class="fas fa-mobile-alt"></i>';
                    break;
                case '_telephone':
                    $field_label = '<i class="fas fa-phone"></i>';
                    break;
                case '_email':
                    $field_label = '<i class="fas fa-envelope"></i>';
                    break;
                case '_website':
                    $field_label = '<i class="fas fa-globe"></i>';
                    break;
                case '_experience':
                    $field_label = '<i class="fas fa-briefcase"></i>';
                    break;
                case '_company':
                    $field_label = '<i class="fas fa-building"></i>';
                    break;
                case Utils::get_taxonomy_name( 'group', true ):
                    $field_label = '<i class="fas fa-tags"></i>';
                    break;
            }
            if ( !empty( $field_label ) ) {
                $field_label = '<span class="wps--info-label info-label--icon">' . $field_label . '</span>';
            }
        } else {
            switch ( $field_key ) {
                case '_mobile':
                    $field_label = plugin()->translations->get( 'mobile_meta_label', _x( 'Mobile:', 'Public', 'wpspeedo-team' ) );
                    break;
                case '_telephone':
                    $field_label = plugin()->translations->get( 'phone_meta_label', _x( 'Phone:', 'Public', 'wpspeedo-team' ) );
                    break;
                case '_email':
                    $field_label = plugin()->translations->get( 'email_meta_label', _x( 'Email:', 'Public', 'wpspeedo-team' ) );
                    break;
                case '_website':
                    $field_label = plugin()->translations->get( 'website_meta_label', _x( 'Website:', 'Public', 'wpspeedo-team' ) );
                    break;
                case '_experience':
                    $field_label = plugin()->translations->get( 'experience_meta_label', _x( 'Experience:', 'Public', 'wpspeedo-team' ) );
                    break;
                case '_company':
                    $field_label = plugin()->translations->get( 'company_meta_label', _x( 'Company:', 'Public', 'wpspeedo-team' ) );
                    break;
                case Utils::get_taxonomy_name( 'group', true ):
                    $field_label = plugin()->translations->get( 'group_meta_label', _x( 'Group:', 'Public', 'wpspeedo-team' ) );
                    break;
            }
            if ( !empty( $field_label ) ) {
                $field_label = '<strong class="wps--info-label info-label--text">' . $field_label . '</strong>';
            }
        }
        return $field_label;
    }

    public static function get_extra_info_fields( $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
            'fields'  => [],
        ], $args );
        $fields = (array) $args['fields'];
        $sorted_fields = self::get_sorted_elements();
        $display_fields = [];
        $supported_sorted_fields = array_intersect( $sorted_fields, array_merge( [
            '_telephone',
            '_email',
            '_website',
            '_experience',
            '_company',
            '_mobile'
        ], Utils::get_active_taxonomies( true ) ) );
        $supported_sorted_fields = array_values( $supported_sorted_fields );
        foreach ( $supported_sorted_fields as $s_field ) {
            $s_field_alt = ltrim( $s_field, '_' );
            $key = (( $args['context'] == 'details' ? 'show_details_' : 'show_' )) . $s_field_alt;
            $s_field_status = self::shortcode_loader()->get_setting( $key );
            if ( $s_field_status == 'true' || $s_field_status != 'false' && in_array( $s_field, $fields ) ) {
                $display_fields[] = $s_field;
            }
        }
        return array_intersect( $display_fields, $supported_sorted_fields );
    }

    public static function get_the_extra_info( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context'            => 'general',
            'fields'             => [],
            'info_style'         => '',
            'info_style_default' => 'center-aligned',
            'label_type'         => '',
            'label_type_default' => 'icon',
            'items_border'       => false,
            'info_top_border'    => false,
        ], $args );
        $fields = self::get_extra_info_fields( $args );
        if ( empty( $fields ) ) {
            return;
        }
        $info_classes = ['team-member--info-wrapper'];
        $info_style = ( empty( $args['info_style'] ) ? $args['info_style_default'] : $args['info_style'] );
        $label_type = ( empty( $args['label_type'] ) ? $args['label_type_default'] : $args['label_type'] );
        // $info_style = 'start-aligned';
        // $info_style = 'start-aligned-alt';
        // $info_style = 'center-aligned';
        // $info_style = 'center-aligned-alt';
        // $info_style = 'center-aligned-combined';
        // $info_style = 'justify-aligned';
        if ( in_array( $info_style, ['start-aligned-alt', 'center-aligned-alt', 'center-aligned-combined'] ) ) {
            $info_classes[] = 'wps-team--info-tabled';
        }
        if ( $args['items_border'] ) {
            $info_classes[] = 'wps-team--info-bordered';
        }
        $fields_html = '';
        foreach ( $fields as $field ) {
            $val = Utils::get_item_data( $field, $post_id );
            if ( empty( $val ) ) {
                continue;
            }
            $field_label = Utils::get_the_field_label( $field, $label_type );
            if ( $field === '_mobile' ) {
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="tel:%s">%s</a>', Utils::sanitize_phone_number( $val ), sanitize_text_field( $val ) ) . '</li>';
                continue;
            }
            if ( $field === '_telephone' ) {
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="tel:%s">%s</a>', Utils::sanitize_phone_number( $val ), sanitize_text_field( $val ) ) . '</li>';
                continue;
            }
            if ( $field === '_email' ) {
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="mailto:%1$s">%1$s</a>', sanitize_text_field( $val ) ) . '</li>';
                continue;
            }
            if ( $field === '_website' ) {
                $link_params = ( self::is_external_url( $val ) ? self::get_ext_url_params() : '' );
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="%1$s" %2$s>%1$s</a>', esc_url_raw( $val ), $link_params ) . '</li>';
                continue;
            }
            if ( $field === '_experience' ) {
                $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', sanitize_text_field( $val ) ) . '</li>';
                continue;
            }
            if ( $field === '_company' ) {
                $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', sanitize_text_field( $val ) ) . '</li>';
                continue;
            }
            $tax_roots = self::get_taxonomy_roots();
            foreach ( $tax_roots as $taxonomy ) {
                if ( $field === Utils::get_taxonomy_name( $taxonomy, true ) ) {
                    $val = wp_list_pluck( $val, 'name' );
                    $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', implode( ', ', $val ) ) . '</li>';
                    continue;
                }
            }
        }
        if ( empty( $fields_html ) ) {
            return '';
        }
        $info_classes[] = 'info--' . $info_style;
        if ( $args['info_top_border'] ) {
            $info_classes[] = 'wps-team--info-top-border';
        }
        return sprintf( '<div class="%s"><ul class="wps--member-info">', esc_attr( implode( ' ', $info_classes ) ) ) . $fields_html . '</ul></div>';
    }

    public static function get_the_extra_info_template( $args = [] ) {
        $args = shortcode_atts( [
            'context'            => 'general',
            'fields'             => [],
            'info_style'         => '',
            'info_style_default' => 'center-aligned',
            'label_type'         => '',
            'label_type_default' => 'icon',
            'items_border'       => false,
            'info_top_border'    => false,
        ], $args );
        $fields = self::get_extra_info_fields( $args );
        if ( empty( $fields ) ) {
            return;
        }
        $info_classes = ['team-member--info-wrapper'];
        $info_style = ( empty( $args['info_style'] ) ? $args['info_style_default'] : $args['info_style'] );
        $label_type = ( empty( $args['label_type'] ) ? $args['label_type_default'] : $args['label_type'] );
        // $info_style = 'start-aligned';
        // $info_style = 'start-aligned-alt';
        // $info_style = 'center-aligned';
        // $info_style = 'center-aligned-alt';
        // $info_style = 'center-aligned-combined';
        // $info_style = 'justify-aligned';
        if ( in_array( $info_style, ['start-aligned-alt', 'center-aligned-alt', 'center-aligned-combined'] ) ) {
            $info_classes[] = 'wps-team--info-tabled';
        }
        if ( $args['items_border'] ) {
            $info_classes[] = 'wps-team--info-bordered';
        }
        $fields_html = '';
        foreach ( $fields as $field ) {
            $val = "{{=it.{$field}}}";
            $field_label = Utils::get_the_field_label( $field, $label_type );
            if ( $field === '_mobile' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="tel:%1$s">%1$s</a>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            if ( $field === '_telephone' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="tel:%1$s">%1$s</a>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            if ( $field === '_email' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="mailto:%1$s">%1$s</a>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            if ( $field === '_website' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<a class="wps--info-text" href="%1$s">%1$s</a>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            if ( $field === '_experience' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            if ( $field === '_company' ) {
                $fields_html .= "{{? it.{$field}}}";
                $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', sanitize_text_field( $val ) ) . '</li>';
                $fields_html .= '{{?}}';
                continue;
            }
            $tax_roots = self::get_taxonomy_roots();
            foreach ( $tax_roots as $taxonomy ) {
                if ( $field === Utils::get_taxonomy_name( $taxonomy, true ) ) {
                    $fields_html .= "{{? it.{$field}}}";
                    $fields_html .= '<li>' . $field_label . sprintf( '<span class="wps--info-text">%s</span>', sanitize_text_field( $val ) ) . '</li>';
                    $fields_html .= "{{?}}";
                    continue;
                }
            }
        }
        if ( empty( $fields_html ) ) {
            return '';
        }
        $info_classes[] = 'info--' . $info_style;
        if ( $args['info_top_border'] ) {
            $info_classes[] = 'wps-team--info-top-border';
        }
        return sprintf( '<div class="%s"><ul class="wps--member-info">', esc_attr( implode( ' ', $info_classes ) ) ) . $fields_html . '</ul></div>';
    }

    public static function get_strings() {
        return include WPS_TEAM_INC_PATH . '/editor/strings.php';
    }

    public static function do_not_cache() {
        if ( !defined( 'DONOTCACHEPAGE' ) ) {
            define( 'DONOTCACHEPAGE', true );
        }
        if ( !defined( 'DONOTCACHEDB' ) ) {
            define( 'DONOTCACHEDB', true );
        }
        if ( !defined( 'DONOTMINIFY' ) ) {
            define( 'DONOTMINIFY', true );
        }
        if ( !defined( 'DONOTCDN' ) ) {
            define( 'DONOTCDN', true );
        }
        if ( !defined( 'DONOTCACHCEOBJECT' ) ) {
            define( 'DONOTCACHCEOBJECT', true );
        }
        // Set the headers to prevent caching for the different browsers.
        nocache_headers();
    }

}
