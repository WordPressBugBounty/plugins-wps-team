<?php

namespace WPSpeedo_Team;

use WP_Query, WP_Error;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
if ( !trait_exists( __NAMESPACE__ . '\\Currency' ) ) {
    trait Currency
    {
    }
}
class Utils {
    use Currency;
    public static function join_classes( array $classes ) : string {
        $classes = array_filter( array_map( 'trim', $classes ) );
        $classes = array_unique( $classes );
        return implode( ' ', $classes );
    }

    public static function normalize_card_action( string $card_action, int $post_id ) : string {
        if ( Utils::has_archive() || $card_action !== 'single-page' ) {
            return $card_action;
        }
        return 'none';
    }

    public static function get_link_attrs_for_post( int $post_id, string $action, string $extra_class = '' ) : array {
        $shortcode_id = self::shortcode_loader()->id;
        $attrs = self::get_post_link_attrs( $post_id, $shortcode_id, $action );
        $attrs['class'] = self::join_classes( [$attrs['class'] ?? '', $extra_class] );
        return $attrs;
    }

    public static function attrs_to_html( array $attrs, array $allow = [] ) : string {
        $allowed = array_merge( [
            'href',
            'class',
            'target',
            'rel',
            'data-panel-position'
        ], $allow );
        $out = [];
        foreach ( $allowed as $key ) {
            if ( isset( $attrs[$key] ) && $attrs[$key] !== '' ) {
                $value = ( $key === 'href' ? esc_url( $attrs[$key] ) : esc_attr( $attrs[$key] ) );
                $out[] = sprintf( '%s="%s"', $key, $value );
            }
        }
        return implode( ' ', $out );
    }

    public static function render_link( array $attrs, string $inner_html, array $extra_attrs = [] ) : string {
        $merged = $attrs;
        foreach ( $extra_attrs as $k => $v ) {
            if ( $v !== '' && $v !== null ) {
                $merged[$k] = $v;
            }
        }
        return sprintf( '<a %s>%s</a>', self::attrs_to_html( $merged, ['aria-label'] ), $inner_html );
    }

    public static function elementor_get_post_meta( $post_id ) {
        $meta = get_post_meta( $post_id, '_elementor_data', true );
        if ( is_string( $meta ) && !empty( $meta ) ) {
            $meta = json_decode( $meta, true );
        }
        if ( empty( $meta ) ) {
            $meta = [];
        }
        return $meta;
    }

    public static function elementor_update_post_meta( $post_id, $value ) {
        update_metadata(
            'post',
            $post_id,
            '_elementor_data',
            wp_slash( wp_json_encode( $value ) )
        );
    }

    public static function get_posts_meta_cache_key( $meta_key, $post_type = null ) {
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        return sprintf( 'wps--meta-vals--%s_%s', $post_type, $meta_key );
    }

    public static function is_external_url( $url ) {
        $self_data = wp_parse_url( home_url() );
        $url_data = wp_parse_url( $url );
        if ( $self_data['host'] == $url_data['host'] ) {
            return false;
        }
        return true;
    }

    public static function get_ext_url_params() {
        return ' rel="nofollow noopener noreferrer" target="_blank"';
    }

    public static function update_posts_meta_vals( $meta_key, $post_type = null ) {
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        $cache_key = self::get_posts_meta_cache_key( $meta_key, $post_type );
        wp_cache_delete( $cache_key, 'wps_team' );
        return self::get_posts_meta_vals( $meta_key, $post_type );
    }

    public static function update_all_posts_meta_vals( $meta_fields = [], $post_type = null ) {
        $meta_fields = ( !empty( $meta_fields ) ? $meta_fields : ['_ribbon'] );
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        foreach ( $meta_fields as $meta_key ) {
            self::update_posts_meta_vals( $meta_key, $post_type );
        }
    }

    public static function get_posts_meta_vals( $meta_key, $post_type = null ) {
        global $wpdb;
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        $cache_key = self::get_posts_meta_cache_key( $meta_key, $post_type );
        $cache_data = wp_cache_get( $cache_key, 'wps_team' );
        if ( $cache_data === false ) {
            return $cache_data;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $results = $wpdb->get_results( $wpdb->prepare( "\n\t\t\t\tSELECT META.meta_value\n\t\t\t\tFROM {$wpdb->postmeta} AS META\n\t\t\t\tINNER JOIN {$wpdb->posts} AS POST ON META.post_id = POST.ID\n\t\t\t\tWHERE POST.post_type = %s\n\t\t\t\tAND POST.post_status = 'publish'\n\t\t\t\tAND META.meta_key = %s\n\t\t\t\t", $post_type, $meta_key ) );
        if ( !empty( $results ) ) {
            $results = wp_list_pluck( $results, 'meta_value' );
            $results = array_values( array_unique( $results ) );
            wp_cache_set( $cache_key, $results, 'wps_team' );
            return $results;
        }
        return [];
    }

    public static function get_posts( $query_args = [] ) {
        $args = [
            'posts_per_page' => -1,
            'paged'          => 1,
        ];
        $args = array_merge( $args, $query_args );
        $args = (array) apply_filters( 'wpspeedo_team/query_params', $args );
        $args['post_type'] = Utils::post_type_name();
        return new WP_Query($args);
    }

    public static function search_by_custom_criteria( $where, $wp_query ) {
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

    public static function paginate_links( $args ) {
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
        $is_ajax = wp_validate_boolean( $args['ajax'] );
        $shortcode_id = $args['shortcode_id'];
        $extra_links = (int) $args['edge_page_links'];
        $total = $query->max_num_pages;
        $current = $query->query['paged'];
        if ( $current < 1 ) {
            $current = 1;
        }
        if ( $current > $total ) {
            $current = $total;
        }
        if ( $total < 2 ) {
            return;
        }
        $paged_var = self::get_paged_var( $shortcode_id );
        $current_url = home_url( trailingslashit( $wp->request ) );
        $current_url = add_query_arg( self::sanitize_request( $_GET ), $current_url );
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( wp_doing_ajax() ) {
            $current_url = wp_get_referer();
        }
        $current_url = remove_query_arg( $paged_var, $current_url );
        $current_url = add_query_arg( $paged_var, '%#%', $current_url );
        return self::get_pagination( [
            'current'  => $current,
            'total'    => $total,
            'format'   => false,
            'base'     => $current_url,
            'is_ajax'  => $is_ajax,
            'mid_size' => $extra_links,
        ] );
    }

    public static function add_data_page_attr( $html ) {
        return preg_replace_callback( '/<a\\b([^>]*?)href="([^"]+)"([^>]*)>/i', function ( $m ) {
            $url = $m[2];
            $page = null;
            // Check query string first
            if ( preg_match( '/(?:paged|wps-team-\\d+-paged)=(\\d+)/', $url, $qmatch ) ) {
                $page = (int) $qmatch[1];
            } elseif ( preg_match( '#/page/(\\d+)/?#', $url, $pmatch ) ) {
                $page = (int) $pmatch[1];
            }
            return ( $page ? '<a' . $m[1] . 'href="' . $url . '" data-page="' . $page . '"' . $m[3] . '>' : $m[0] );
        }, $html );
    }

    public static function sanitize_request( $array ) {
        $sanitized = [];
        foreach ( $array as $key => $value ) {
            $sanitized[sanitize_key( $key )] = sanitize_text_field( wp_unslash( $value ) );
        }
        return $sanitized;
    }

    public static function get_pagination( $args ) {
        $args = shortcode_atts( [
            'current'  => 1,
            'total'    => 1,
            'format'   => false,
            'base'     => false,
            'is_ajax'  => false,
            'mid_size' => 2,
        ], $args );
        $pagination_args = [
            'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
            'format'    => '?paged=%#%',
            'total'     => $args['total'],
            'current'   => $args['current'],
            'type'      => 'array',
            'prev_text' => '<svg viewBox="0 0 96 96"><path d="M39.3756,48.0022l30.47-25.39a6.0035,6.0035,0,0,0-7.6878-9.223L26.1563,43.3906a6.0092,6.0092,0,0,0,0,9.2231L62.1578,82.615a6.0035,6.0035,0,0,0,7.6878-9.2231Z"/></svg>',
            'next_text' => '<svg viewBox="0 0 96 96"><path d="M69.8437,43.3876,33.8422,13.3863a6.0035,6.0035,0,0,0-7.6878,9.223l30.47,25.39-30.47,25.39a6.0035,6.0035,0,0,0,7.6878,9.2231L69.8437,52.6106a6.0091,6.0091,0,0,0,0-9.223Z"/></svg>',
            'mid_size'  => $args['mid_size'],
        ];
        $args['format'] && ($pagination_args['format'] = $args['format']);
        $args['base'] && ($pagination_args['base'] = $args['base']);
        $pages = paginate_links( $pagination_args );
        if ( !is_array( $pages ) ) {
            return;
        }
        $html = sprintf( '<div class="wps-pagination--wrap"><nav class="wps-team--navigation"><ul class="wps-team--pagination %s">', ( $args['is_ajax'] ? 'wps-team--pagination-ajax' : '' ) );
        foreach ( $pages as $page ) {
            $page = str_replace( ['page-numbers', 'current'], ['wps--page-numbers', 'wps--current'], $page );
            $html .= sprintf( '<li>%s</li>', $page );
        }
        $html .= '</ul></nav></div>';
        echo self::add_data_page_attr( $html );
        // phpcs:ignore WordPress.Security.EscapeOutput
    }

    public static function get_paged_var( $id ) {
        return 'wps-team-' . $id . '-paged';
    }

    public static function get_meta_field_keys() {
        $field_keys = [
            '_first_name',
            '_last_name',
            '_experience',
            '_company',
            '_skills',
            '_designation',
            '_telephone',
            '_fax',
            '_email',
            '_website',
            '_social_links',
            '_ribbon',
            '_mobile',
            '_address'
        ];
        return $field_keys;
    }

    public static function get_item_data( $data_key, $post_id = null, $shortcode_id = null ) {
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
                $value = wp_get_object_terms( $post_id, str_replace( '_', '-', $data_key ) );
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

    public static function load_template( $template_name ) {
        $template_folder = (string) apply_filters( 'wpspeedo_team/template/folder', 'wpspeedo-team' );
        $template_folder = '/' . trailingslashit( ltrim( $template_folder, '/\\' ) );
        // Load from mu-plugins if template exists
        $template_path = WPMU_PLUGIN_DIR . $template_folder . $template_name;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
        $template_path = WPMU_PLUGIN_DIR . $template_folder . 'pro/' . $template_name;
        if ( file_exists( $template_path ) ) {
            return $template_path;
        }
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
        return new WP_Error('wpspeedo_team/template/not_found', _x( 'Template file is not found', 'Dashboard', 'wps-team' ));
    }

    public static function get_temp_settings() {
        $temp_key = self::get_shortcode_preview_key();
        if ( $temp_key ) {
            $settings = get_transient( $temp_key );
            if ( !empty( $settings ) ) {
                return $settings;
            }
        }
        return [];
    }

    public static function is_shortcode_preview() {
        // phpcs:ignore WordPress.Security.NonceVerification
        return (bool) (!empty( $_REQUEST['wps_team_sh_preview'] ));
    }

    public static function get_shortcode_preview_key() {
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        return ( self::is_shortcode_preview() ? sanitize_text_field( wp_unslash( $_REQUEST['wps_team_sh_preview'] ) ) : null );
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

    public static function wp_trim_html_chars( $html, $maxLength = 110 ) {
        $allowed_tags = [
            'a'      => [
                'href'   => true,
                'title'  => true,
                'target' => true,
                'rel'    => true,
                'style'  => true,
            ],
            'strong' => [],
            'b'      => [],
            'em'     => [],
            'i'      => [],
            'u'      => [],
            'span'   => [
                'class' => true,
                'style' => true,
            ],
            'p'      => [
                'style' => true,
            ],
            'br'     => [],
            'ul'     => [],
            'ol'     => [],
            'li'     => [
                'style' => true,
            ],
        ];
        $html = wp_kses( $html, $allowed_tags );
        $html = strip_shortcodes( $html );
        $output = '';
        $len = 0;
        $open_tags = [];
        $was_trimmed = false;
        $regex = '/(<[^>]+>|[^<]+)/u';
        preg_match_all( $regex, $html, $matches );
        foreach ( $matches[0] as $token ) {
            if ( preg_match( '/^<[^>]+>$/', $token ) ) {
                // Tag
                if ( preg_match( '/^<(\\w+)[^>]*>$/', $token, $tag_match ) ) {
                    $open_tags[] = $tag_match[1];
                } elseif ( preg_match( '/^<\\/(\\w+)>$/', $token, $tag_match ) ) {
                    array_pop( $open_tags );
                }
                $output .= $token;
            } else {
                // Text
                $remaining = $maxLength - $len;
                $token_len = mb_strlen( $token );
                if ( $token_len <= $remaining ) {
                    $output .= $token;
                    $len += $token_len;
                } else {
                    $output .= mb_substr( $token, 0, $remaining );
                    $was_trimmed = true;
                    break;
                }
            }
        }
        // Close unclosed tags
        while ( $tag = array_pop( $open_tags ) ) {
            $output .= "</{$tag}>";
        }
        if ( $was_trimmed ) {
            $output .= '...';
        }
        return $output;
    }

    public static function get_brand_name( $icon ) {
        return str_replace( ['fab fa-', 'far fa-', 'fas fa-'], '', esc_attr( $icon ) );
    }

    public static function sanitize_phone_number( $phone ) {
        return preg_replace( '/[^0-9\\-\\_\\+]*/', '', $phone );
    }

    public static function default_settings() {
        return [
            'first_name_label'             => 'First Name',
            'last_name_label'              => 'Last Name',
            'desig_label'                  => 'Designation',
            'email_label'                  => 'Email Address',
            'mobile_label'                 => 'Mobile (Personal)',
            'telephone_label'              => 'Telephone (Office)',
            'fax_label'                    => 'Fax',
            'experience_label'             => 'Years of Experience',
            'website_label'                => 'Website',
            'company_label'                => 'Company',
            'address_label'                => 'Address',
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
            'read_more_link_text'          => 'Read More',
            'link_1_text'                  => 'My Resume',
            'link_2_text'                  => 'Hire Me',
            'social_links_title'           => 'Connect With Me:',
            'skills_title'                 => 'Skills:',
            'education_title'              => 'Education:',
            'mobile_meta_label'            => 'Mobile:',
            'phone_meta_label'             => 'Telephone:',
            'fax_meta_label'               => 'Fax:',
            'email_meta_label'             => 'Email:',
            'website_meta_label'           => 'Website:',
            'experience_meta_label'        => 'Experience:',
            'company_meta_label'           => 'Company:',
            'address_meta_label'           => 'Address:',
            'email_link_text'              => 'Send Email',
            'website_link_text'            => 'Visit Website',
            'mobile_link_text'             => 'Call on Mobile',
            'phone_link_text'              => 'Call on Telephone',
            'fax_link_text'                => 'Send Fax',
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
            'website_display_format'       => 'linked_raw',
            'email_display_format'         => 'linked_raw',
            'mobile_display_format'        => 'linked_raw',
            'telephone_display_format'     => 'linked_raw',
            'fax_display_format'           => 'linked_raw',
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
            'with_front'                   => true,
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

    public static function get_registered_image_sizes() {
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
            'label' => _x( 'Full', 'Editor', 'wps-team' ),
            'value' => 'full',
        ]] );
        $custom_size = [
            'label' => _x( 'Custom', 'Editor', 'wps-team' ),
            'value' => 'custom',
        ];
        $custom_size['label'] = self::get_pro_label() . $custom_size['label'];
        $custom_size['disabled'] = true;
        $_sizes[] = $custom_size;
        return $_sizes;
    }

    public static function get_thumbnail_position() {
        return [
            [
                'label' => _x( 'Top Left', 'Editor', 'wps-team' ),
                'value' => 'left top',
            ],
            [
                'label' => _x( 'Top Center', 'Editor', 'wps-team' ),
                'value' => 'center top',
            ],
            [
                'label' => _x( 'Top Right', 'Editor', 'wps-team' ),
                'value' => 'right top',
            ],
            [
                'label' => _x( 'Middle Left', 'Editor', 'wps-team' ),
                'value' => 'left center',
            ],
            [
                'label' => _x( 'Middle Center', 'Editor', 'wps-team' ),
                'value' => 'center center',
            ],
            [
                'label' => _x( 'Middle Right', 'Editor', 'wps-team' ),
                'value' => 'right center',
            ],
            [
                'label' => _x( 'Bottom Left', 'Editor', 'wps-team' ),
                'value' => 'left bottom',
            ],
            [
                'label' => _x( 'Bottom Center', 'Editor', 'wps-team' ),
                'value' => 'center bottom',
            ],
            [
                'label' => _x( 'Bottom Right', 'Editor', 'wps-team' ),
                'value' => 'right bottom',
            ]
        ];
    }

    public static function get_options_thumbnail_type( $excludes = [] ) {
        $options = [[
            'label' => _x( 'Image', 'Editor', 'wps-team' ),
            'value' => 'image',
        ], [
            'label'    => _x( 'Carousel', 'Editor', 'wps-team' ),
            'disabled' => true,
            'value'    => 'carousel',
        ], [
            'label'    => _x( 'Flip Image', 'Editor', 'wps-team' ),
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

    public static function get_options_display_format() {
        $options = [[
            'label' => _x( 'Clickable Value', 'Settings', 'wps-team' ),
            'value' => 'linked_raw',
        ], [
            'label' => _x( 'Plain Text (No Link)', 'Settings', 'wps-team' ),
            'value' => 'no_link',
        ], [
            'label'    => _x( 'Action Text', 'Settings', 'wps-team' ),
            'disabled' => true,
            'value'    => 'linked_text',
        ]];
        return $options;
    }

    public static function get_general_settings() {
        // Settings
        $defaults = self::default_settings();
        $settings = (array) get_option( self::get_option_name(), $defaults );
        $settings = array_merge( $defaults, $settings );
        // Set Essential Settings
        $fields = ['post_type_slug', 'member_plural_name', 'member_single_name'];
        foreach ( $fields as $field ) {
            if ( empty( $settings[$field] ) ) {
                $settings[$field] = $defaults[$field];
            }
        }
        return $settings;
    }

    public static function get_settings() {
        // General Settings
        $settings = self::get_general_settings();
        // Taxonomy Settings
        $taxonomy_settings = self::get_taxonomies_settings();
        // Merge Settings and Taxonomy Settings
        return array_merge( $settings, $taxonomy_settings );
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
        return _x( '(Pro) - ', 'Editor', 'wps-team' );
    }

    public static function get_options_display_type() {
        $options = [
            [
                'label' => _x( 'Grid', 'Editor', 'wps-team' ),
                'value' => 'grid',
            ],
            [
                'label' => _x( 'Carousel', 'Editor', 'wps-team' ),
                'value' => 'carousel',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Masonry', 'Editor', 'wps-team' ),
                'value'    => 'masonry',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Filter', 'Editor', 'wps-team' ),
                'value'    => 'filter',
            ]
        ];
        return $options;
    }

    public static function get_options_filters_theme() {
        $options = [[
            'disabled' => true,
            'label'    => _x( 'Style 01', 'Editor', 'wps-team' ),
            'value'    => 'style-01',
        ], [
            'disabled' => true,
            'label'    => _x( 'Style 02', 'Editor', 'wps-team' ),
            'value'    => 'style-02',
        ], [
            'disabled' => true,
            'label'    => _x( 'Style 03', 'Editor', 'wps-team' ),
            'value'    => 'style-03',
        ]];
        return $options;
    }

    public static function get_options_aspect_ratio() {
        $options = [
            [
                'label' => _x( 'Default', 'Editor', 'wps-team' ),
                'value' => 'default',
            ],
            [
                'label' => _x( 'Square - 1:1', 'Editor', 'wps-team' ),
                'value' => '1/1',
            ],
            [
                'label' => _x( 'Portrait - 6:7', 'Editor', 'wps-team' ),
                'value' => '6/7',
            ],
            [
                'label' => _x( 'Portrait - 5:6', 'Editor', 'wps-team' ),
                'value' => '5/6',
            ],
            [
                'label' => _x( 'Portrait - 4:5', 'Editor', 'wps-team' ),
                'value' => '4/5',
            ],
            [
                'label' => _x( 'Portrait - 8.5:11', 'Editor', 'wps-team' ),
                'value' => '8.5/11',
            ],
            [
                'label' => _x( 'Portrait - 3:4', 'Editor', 'wps-team' ),
                'value' => '3/4',
            ],
            [
                'label' => _x( 'Portrait - 5:7', 'Editor', 'wps-team' ),
                'value' => '5/7',
            ],
            [
                'label' => _x( 'Portrait - 2:3', 'Editor', 'wps-team' ),
                'value' => '2/3',
            ],
            [
                'label' => _x( 'Portrait - 9:16', 'Editor', 'wps-team' ),
                'value' => '9/16',
            ],
            [
                'label' => _x( 'Landscape - 5:4', 'Editor', 'wps-team' ),
                'value' => '5/4',
            ],
            [
                'label' => _x( 'Landscape - 4:3', 'Editor', 'wps-team' ),
                'value' => '4/3',
            ],
            [
                'label' => _x( 'Landscape - 3:2', 'Editor', 'wps-team' ),
                'value' => '3/2',
            ],
            [
                'label' => _x( 'Landscape - 14:9', 'Editor', 'wps-team' ),
                'value' => '14/9',
            ],
            [
                'label' => _x( 'Landscape - 16:10', 'Editor', 'wps-team' ),
                'value' => '16/10',
            ],
            [
                'label' => _x( 'Landscape - 1.66:1', 'Editor', 'wps-team' ),
                'value' => '1.66/1',
            ],
            [
                'label' => _x( 'Landscape - 1.75:1', 'Editor', 'wps-team' ),
                'value' => '1.75/1',
            ],
            [
                'label' => _x( 'Landscape - 16:9', 'Editor', 'wps-team' ),
                'value' => '16/9',
            ],
            [
                'label' => _x( 'Landscape - 1.91:1', 'Editor', 'wps-team' ),
                'value' => '1.91/1',
            ],
            [
                'label' => _x( 'Landscape - 2:1', 'Editor', 'wps-team' ),
                'value' => '2/1',
            ],
            [
                'label' => _x( 'Landscape - 21:9', 'Editor', 'wps-team' ),
                'value' => '21/9',
            ]
        ];
        return $options;
    }

    public static function get_options_layout_mode() {
        $options = [[
            'label' => _x( 'Masonry', 'Editor', 'wps-team' ),
            'value' => 'masonry',
        ], [
            'label' => _x( 'Fit Rows', 'Editor', 'wps-team' ),
            'value' => 'fitRows',
        ]];
        return $options;
    }

    public static function get_options_panel_position() {
        $options = [[
            'label' => _x( 'Left', 'Editor', 'wps-team' ),
            'value' => 'left',
        ], [
            'label' => _x( 'Right', 'Editor', 'wps-team' ),
            'value' => 'right',
        ], [
            'label' => _x( 'Dynamic', 'Editor', 'wps-team' ),
            'value' => 'dynamic',
        ]];
        return $options;
    }

    public static function get_options_meta_panel_position() {
        $options = [[
            'label' => _x( 'Left', 'Editor', 'wps-team' ),
            'value' => 'left',
        ], [
            'label' => _x( 'Right', 'Editor', 'wps-team' ),
            'value' => 'right',
        ]];
        return $options;
    }

    public static function get_shape_types() {
        $options = [
            'circle' => [
                'title' => _x( 'Circle', 'Editor', 'wps-team' ),
                'icon'  => 'fas fa-circle',
            ],
            'square' => [
                'title' => _x( 'Square', 'Editor', 'wps-team' ),
                'icon'  => 'fas fa-square-full',
            ],
            'radius' => [
                'title' => _x( 'Radius', 'Editor', 'wps-team' ),
                'icon'  => 'fas fa-square',
            ],
        ];
        return $options;
    }

    public static function get_options_theme() {
        $options = [
            [
                'label' => _x( 'Square One', 'Editor', 'wps-team' ),
                'value' => 'square-01',
            ],
            [
                'label' => _x( 'Square Two', 'Editor', 'wps-team' ),
                'value' => 'square-02',
            ],
            [
                'label' => _x( 'Square Three', 'Editor', 'wps-team' ),
                'value' => 'square-03',
            ],
            [
                'label' => _x( 'Square Four', 'Editor', 'wps-team' ),
                'value' => 'square-04',
            ],
            [
                'label' => _x( 'Square Five', 'Editor', 'wps-team' ),
                'value' => 'square-05',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Six', 'Editor', 'wps-team' ),
                'value'    => 'square-06',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Seven', 'Editor', 'wps-team' ),
                'value'    => 'square-07',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Eight', 'Editor', 'wps-team' ),
                'value'    => 'square-08',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Nine', 'Editor', 'wps-team' ),
                'value'    => 'square-09',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Ten', 'Editor', 'wps-team' ),
                'value'    => 'square-10',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Eleven', 'Editor', 'wps-team' ),
                'value'    => 'square-11',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Twelve', 'Editor', 'wps-team' ),
                'value'    => 'square-12',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Square Thirteen', 'Editor', 'wps-team' ),
                'value'    => 'square-13',
            ],
            [
                'label' => _x( 'Circle One', 'Editor', 'wps-team' ),
                'value' => 'circle-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Two', 'Editor', 'wps-team' ),
                'value'    => 'circle-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Three', 'Editor', 'wps-team' ),
                'value'    => 'circle-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Four', 'Editor', 'wps-team' ),
                'value'    => 'circle-04',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Five', 'Editor', 'wps-team' ),
                'value'    => 'circle-05',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Circle Six', 'Editor', 'wps-team' ),
                'value'    => 'circle-06',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz One', 'Editor', 'wps-team' ),
                'value'    => 'horiz-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Two', 'Editor', 'wps-team' ),
                'value'    => 'horiz-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Three', 'Editor', 'wps-team' ),
                'value'    => 'horiz-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Horiz Four', 'Editor', 'wps-team' ),
                'value'    => 'horiz-04',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table One', 'Editor', 'wps-team' ),
                'value'    => 'table-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Two', 'Editor', 'wps-team' ),
                'value'    => 'table-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Three', 'Editor', 'wps-team' ),
                'value'    => 'table-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Table Four', 'Editor', 'wps-team' ),
                'value'    => 'table-04',
            ]
        ];
        return $options;
    }

    public static function get_options_side_panel_theme() {
        $options = [[
            'disabled' => true,
            'label'    => _x( 'Style One', 'Editor', 'wps-team' ),
            'value'    => 'style-01',
        ], [
            'disabled' => true,
            'label'    => _x( 'Style Two', 'Editor', 'wps-team' ),
            'value'    => 'style-02',
        ]];
        return $options;
    }

    public static function get_options_card_action() {
        $options = [
            [
                'label' => _x( 'None', 'Editor', 'wps-team' ),
                'value' => 'none',
            ],
            [
                'label' => _x( 'Single Page', 'Editor', 'wps-team' ),
                'value' => 'single-page',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Modal', 'Editor', 'wps-team' ),
                'value'    => 'modal',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Side Panel', 'Editor', 'wps-team' ),
                'value'    => 'side-panel',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Expand', 'Editor', 'wps-team' ),
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
                'label' => _x( 'ID', 'Editor', 'wps-team' ),
                'value' => 'ID',
            ],
            [
                'label' => _x( 'First Name', 'Editor', 'wps-team' ),
                'value' => 'title',
            ],
            [
                'label' => _x( 'Last Name', 'Editor', 'wps-team' ),
                'value' => 'last_name',
            ],
            [
                'label' => _x( 'Date', 'Editor', 'wps-team' ),
                'value' => 'date',
            ],
            [
                'label' => _x( 'Random', 'Editor', 'wps-team' ),
                'value' => 'rand',
            ],
            [
                'label' => _x( 'Modified', 'Editor', 'wps-team' ),
                'value' => 'modified',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Custom Order', 'Editor', 'wps-team' ),
                'value'    => 'menu_order',
            ]
        ];
        return $options;
    }

    public static function get_options_terms_orderby() {
        $options = [
            [
                'label' => _x( 'Default', 'Editor', 'wps-team' ),
                'value' => 'none',
            ],
            [
                'label' => _x( 'ID', 'Editor', 'wps-team' ),
                'value' => 'id',
            ],
            [
                'label' => _x( 'Name', 'Editor', 'wps-team' ),
                'value' => 'name',
            ],
            [
                'label' => _x( 'Slug', 'Editor', 'wps-team' ),
                'value' => 'slug',
            ],
            [
                'label' => _x( 'Count', 'Editor', 'wps-team' ),
                'value' => 'count',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Custom Order', 'Editor', 'wps-team' ),
                'value'    => 'term_order',
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

    public static function get_post_term_classes( $post_id, array $term_names, $separator = ' ' ) {
        $terms = [];
        foreach ( $term_names as $term_name ) {
            $_terms = get_the_terms( $post_id, $term_name );
            if ( !empty( $_terms ) && !is_wp_error( $_terms ) ) {
                $terms = array_merge( $terms, wp_list_pluck( $_terms, 'hash_id' ) );
            }
        }
        if ( empty( $terms ) ) {
            return '';
        }
        return implode( $separator, $terms );
    }

    public static function get_terms( $taxonomy, $args = [] ) {
        $args = array_merge( [
            'taxonomy'   => $taxonomy,
            'orderby'    => 'name',
            'order'      => 'ASC',
            'hide_empty' => false,
        ], $args );
        // Generate Cache Key
        $cache_key = md5( 'wpspeedo_team_terms_' . serialize( $args ) );
        // Get Terms from Cache If Exists for Public Request
        if ( !is_admin() ) {
            $terms = wp_cache_get( $cache_key, 'wps_team' );
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
        wp_cache_set( $cache_key, $terms, 'wps_team' );
        return $terms;
    }

    public static function get_group_terms( $args = [] ) {
        return self::get_terms( self::get_taxonomy_name( 'group' ), $args );
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

    public static function get_social_settings( $class = null, $context = 'general' ) {
        if ( $context === 'single' ) {
            return [
                'shape'               => Utils::get_setting( 'social_links_shape' ),
                'bg_color_type'       => Utils::get_setting( 'social_links_bg_color_type' ),
                'bg_color_type_hover' => Utils::get_setting( 'social_links_bg_color_type_hover' ),
                'br_color_type'       => Utils::get_setting( 'social_links_br_color_type' ),
                'br_color_type_hover' => Utils::get_setting( 'social_links_br_color_type_hover' ),
                'color_type'          => Utils::get_setting( 'social_links_color_type' ),
                'color_type_hover'    => Utils::get_setting( 'social_links_color_type_hover' ),
            ];
        } else {
            if ( $context === 'general' ) {
                return [
                    'shape'               => $class->get_setting( 'social_links_shape' ),
                    'bg_color_type'       => $class->get_setting( 'social_links_bg_color_type' ),
                    'bg_color_type_hover' => $class->get_setting( 'social_links_bg_color_type_hover' ),
                    'br_color_type'       => $class->get_setting( 'social_links_br_color_type' ),
                    'br_color_type_hover' => $class->get_setting( 'social_links_br_color_type_hover' ),
                    'color_type'          => $class->get_setting( 'social_links_color_type' ),
                    'color_type_hover'    => $class->get_setting( 'social_links_color_type_hover' ),
                ];
            } else {
                if ( $context === 'details' ) {
                    return [
                        'shape'               => $class->get_setting( 'detail_social_links_shape' ),
                        'bg_color_type'       => $class->get_setting( 'detail_social_links_bg_color_type' ),
                        'bg_color_type_hover' => $class->get_setting( 'detail_social_links_bg_color_type_hover' ),
                        'br_color_type'       => $class->get_setting( 'detail_social_links_br_color_type' ),
                        'br_color_type_hover' => $class->get_setting( 'detail_social_links_br_color_type_hover' ),
                        'color_type'          => $class->get_setting( 'detail_social_links_color_type' ),
                        'color_type_hover'    => $class->get_setting( 'detail_social_links_color_type_hover' ),
                    ];
                }
            }
        }
    }

    public static function get_social_classes( $class = null, $initials = [], $context = 'general' ) {
        $defaults = [
            'shape'               => 'circle',
            'bg_color_type'       => 'brand',
            'bg_color_type_hover' => 'brand',
            'br_color_type'       => 'brand',
            'br_color_type_hover' => 'brand',
            'color_type'          => 'custom',
            'color_type_hover'    => 'custom',
        ];
        $initials = array_filter( $initials );
        $settings = self::get_social_settings( $class, $context );
        $settings = array_filter( $settings );
        $config = array_merge( $defaults, $initials, $settings );
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
        if ( $config['br_color_type'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-br-color';
        }
        if ( $config['br_color_type_hover'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-br-color--hover';
        }
        if ( $config['bg_color_type'] !== 'brand' && $config['color_type'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-color';
        }
        if ( $config['bg_color_type_hover'] !== 'brand' && $config['color_type_hover'] === 'brand' ) {
            $social_classes[] = 'wps-si--b-color--hover';
        }
        return $social_classes;
    }

    public static function set_social_attrs_for_detail_view( $shortcode_loader, $settings = [] ) {
        $settings = shortcode_atts( [
            'shape'               => 'circle',
            'bg_color_type'       => 'custom',
            'bg_color_type_hover' => 'brand',
            'br_color_type'       => 'custom',
            'br_color_type_hover' => 'brand',
            'color_type'          => 'brand',
            'color_type_hover'    => 'custom',
        ], $settings );
        $social_classes = Utils::get_social_classes( $shortcode_loader, $settings, 'details' );
        $shortcode_loader->add_attribute(
            'social_details',
            'class',
            $social_classes,
            true
        );
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

    public static function minify_css( $css ) {
        if ( empty( $css ) ) {
            return '';
        }
        $css = preg_replace( '!/\\*.*?\\*/!s', '', $css );
        $css = preg_replace( '/\\s*([:;{}])\\s*/', '$1', $css );
        $css = preg_replace( '/\\s*,\\s*/', ',', $css );
        $css = preg_replace( '/;}/', '}', $css );
        $css = preg_replace( '/\\s+/', ' ', $css );
        return trim( $css );
    }

    public static function validate_css( $css ) {
        $css = trim( (string) wp_unslash( $css ) );
        if ( empty( $css ) ) {
            return '';
        }
        $css = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $css );
        $css = preg_replace( '#<style(.*?)>(.*?)</style>#is', '', $css );
        return $css;
    }

    public static function minify_validated_css( $css ) {
        $css = self::validate_css( $css );
        $css = self::minify_css( $css );
        return $css;
    }

    public static function get_post_link_attrs( $post_id, $shortcode_id = null, $action = 'single-page' ) {
        $attrs = [
            'href'   => '',
            'class'  => '',
            'target' => '',
            'rel'    => '',
        ];
        if ( $action === 'single-page' && Utils::has_archive() ) {
            $attrs['href'] = get_the_permalink( $post_id );
            $attrs['class'] = 'wpspeedo-team--url';
        }
        $attrs = apply_filters(
            'wpspeedo_team/post_link_attrs',
            $attrs,
            $action,
            $post_id,
            $shortcode_id
        );
        return $attrs;
    }

    public static function get_the_title( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'card_action' => 'single-page',
            'tag'         => 'h3',
            'class'       => '',
        ], $args );
        $action = self::normalize_card_action( (string) $args['card_action'], $post_id );
        $tag_name = (string) $args['tag'];
        $title_classes = ['wps-team--member-title', 'wps-team--member-element'];
        if ( !empty( $args['class'] ) ) {
            $title_classes[] = $args['class'];
        }
        if ( $action !== 'none' ) {
            $title_classes[] = 'team-member--link';
        }
        $title_open = sprintf( '<%s class="%s">', esc_attr( $tag_name ), esc_attr( self::join_classes( $title_classes ) ) );
        $title_text = get_the_title( $post_id );
        if ( $action === 'none' ) {
            $content = esc_html( $title_text );
        } else {
            $link_attrs = self::get_link_attrs_for_post( (int) $post_id, $action );
            $content = self::render_link( $link_attrs, esc_html( $title_text ) );
        }
        printf(
            '%s%s</%s>',
            $title_open,
            $content,
            esc_attr( $tag_name )
        );
        // phpcs:ignore WordPress.Security.EscapeOutput
    }

    public static function get_render_info( $element, $context = 'general' ) {
        if ( $context == 'general' ) {
            return self::shortcode_loader()->get_setting( "show_{$element}" );
        }
        if ( $context == 'details' ) {
            return self::shortcode_loader()->get_setting( "show_details_{$element}" );
        }
        if ( $context == 'single' ) {
            return self::get_setting( "single_{$element}" );
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
        if ( !self::is_allowed_render( 'thumbnail', $args['context'], (bool) $args['force_show'] ) ) {
            return '';
        }
        $action = self::normalize_card_action( (string) $args['card_action'], $post_id );
        $wrapper_tag = (string) $args['tag'];
        $wrapper_classes = ['team-member--thumbnail-wrapper', 'wps-team--member-element'];
        $thumbnail_container = ['team-member--thumbnail'];
        $thumb_img_extra_class = '';
        $thumbnail_size = $args['thumbnail_size'];
        $gallery_html = '';
        $args['thumbnail_type'] = 'image';
        $html = sprintf( '<%s class="%s">', esc_attr( $wrapper_tag ), esc_attr( self::join_classes( $wrapper_classes ) ) );
        $html .= sprintf( '<div class="%s">', esc_attr( self::join_classes( $thumbnail_container ) ) );
        if ( wps_team_fs()->can_use_premium_code__premium_only() && $args['thumbnail_type'] === 'carousel' ) {
            $html .= '<div class="swiper-wrapper">';
        }
        if ( $action === 'none' ) {
            $html .= get_the_post_thumbnail( $post_id, $thumbnail_size, [
                'class' => $thumb_img_extra_class,
            ] );
            // phpcs:ignore WordPress.Security.EscapeOutput
            $html .= $gallery_html;
            // phpcs:ignore WordPress.Security.EscapeOutput
        } else {
            $link_attrs = self::get_link_attrs_for_post( (int) $post_id, $action );
            $aria = sprintf( 
                /* translators: %s: Post title. */
                esc_attr_x( 'Read More about %s.', 'Public', 'wps-team' ),
                get_the_title( $post_id )
             );
            $inner = get_the_post_thumbnail( $post_id, $thumbnail_size );
            // phpcs:ignore WordPress.Security.EscapeOutput
            $inner .= $gallery_html;
            // phpcs:ignore WordPress.Security.EscapeOutput
            $html .= self::render_link( $link_attrs, $inner, [
                'aria-label' => $aria,
            ] );
        }
        if ( wps_team_fs()->can_use_premium_code__premium_only() && $args['thumbnail_type'] === 'carousel' ) {
            $html .= '</div><div class="wps-team--carousel-navs"><button class="swiper-button-prev" tabindex="0" aria-label="Previous slide"><i aria-hidden="true" class="fas fa-chevron-left"></i></button><button class="swiper-button-next" tabindex="0" aria-label="Next slide"><i aria-hidden="true" class="fas fa-chevron-right"></i></button></div><div class="swiper-pagination"></div>';
        }
        echo $html;
        // phpcs:ignore WordPress.Security.EscapeOutput
        if ( !empty( $args['allow_ribbon'] ) ) {
            Utils::get_the_ribbon( get_the_ID() );
        }
        printf( '</div></%s>', esc_attr( $wrapper_tag ) );
        // phpcs:ignore WordPress.Security.EscapeOutput
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
        printf( '<div class="%s">%s</div>', esc_attr( self::join_classes( $ribbon_classes ) ), esc_html( $ribbon ) );
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
        printf(
            '<%1$s class="%2$s">%3$s</%1$s>',
            esc_attr( $args['tag'] ),
            esc_attr( self::join_classes( $desig_classes ) ),
            esc_html( $designation )
        );
    }

    public static function elements_display_order( $context = 'general' ) {
        $elements = [
            'thumbnail'   => _x( 'Thumbnail', 'Editor', 'wps-team' ),
            'divider'     => _x( 'Divider', 'Editor', 'wps-team' ),
            'designation' => _x( 'Designation', 'Editor', 'wps-team' ),
            'description' => _x( 'Description', 'Editor', 'wps-team' ),
            'education'   => _x( 'Education', 'Editor', 'wps-team' ),
            'social'      => _x( 'Social', 'Editor', 'wps-team' ),
            'ribbon'      => _x( 'Ribbon/Tag', 'Editor', 'wps-team' ),
            'email'       => _x( 'Email', 'Editor', 'wps-team' ),
            'mobile'      => _x( 'Mobile', 'Editor', 'wps-team' ),
            'telephone'   => _x( 'Telephone', 'Editor', 'wps-team' ),
            'fax'         => _x( 'Fax', 'Editor', 'wps-team' ),
            'experience'  => _x( 'Experience', 'Editor', 'wps-team' ),
            'website'     => _x( 'Website', 'Editor', 'wps-team' ),
            'company'     => _x( 'Company', 'Editor', 'wps-team' ),
            'address'     => _x( 'Address', 'Editor', 'wps-team' ),
            'skills'      => _x( 'Skills', 'Editor', 'wps-team' ),
            'link_1'      => self::get_setting( 'link_1_label', 'Resume Link' ),
            'link_2'      => self::get_setting( 'link_2_label', 'Hire Link' ),
            'pricing'     => _x( 'Pricing', 'Editor', 'wps-team' ),
        ];
        if ( $context == 'general' ) {
            $elements['read_more'] = _x( 'Read More Button', 'Editor', 'wps-team' );
        }
        if ( $context == 'single' ) {
            unset($elements['thumbnail']);
            unset($elements['social']);
        }
        foreach ( self::get_taxonomy_roots() as $tax_root ) {
            $elements[self::get_taxonomy_name( $tax_root, true )] = Utils::get_setting( Utils::to_field_key( $tax_root ) . '_single_name' );
        }
        return $elements;
    }

    public static function allowed_elements_display_order( $context = 'general' ) {
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
        printf( '<div class="%s"><div class="wps-team--divider"></div></div>', esc_attr( self::join_classes( $divider_classes ) ) );
    }

    public static function get_description_length( $length = null ) {
        if ( $length == null ) {
            $length = self::shortcode_loader()->get_setting( 'description_length' );
        }
        if ( !$length || $length < 1 ) {
            return PHP_INT_MAX - 500;
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
        $tag_name = (string) $args['tag'];
        $max_length = (int) $args['description_length'];
        $read_more_text = (string) $args['read_more_text'];
        $action = self::normalize_card_action( (string) $args['card_action'], $post_id );
        $read_more_link_html = '';
        if ( $max_length > 0 && !empty( $read_more_text ) && !empty( $args['add_read_more'] ) ) {
            if ( $action !== 'none' ) {
                $link_attrs = self::get_link_attrs_for_post( (int) $post_id, $action, 'wps-team--read-more-link' );
                $read_more_link_html = self::render_link( $link_attrs, esc_html( $read_more_text ) );
                $max_length = max( 0, $max_length - mb_strlen( $read_more_text ) );
            }
        }
        $trimmed = Utils::wp_trim_html_chars( get_the_excerpt( $post_id ), $max_length );
        $markup = wpautop( $trimmed . $read_more_link_html );
        printf( '<%1$s class="wps-team--member-details wps-team--member-details-excerpt wps-team--member-element">%2$s</%1$s>', esc_attr( $tag_name ), wp_kses_post( $markup ) );
    }

    public static function get_the_description( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'context' => 'general',
        ], $args );
        if ( !self::is_allowed_render( 'description', $args['context'] ) ) {
            return '';
        }
        ?>

		<div class="wps-team--member-details wps-team--member-element">
			<?php 
        self::get_the_content( $post_id );
        ?>
		</div>

		<?php 
    }

    public static function get_the_education_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'education_title', _x( 'Education:', 'Public', 'wps-team' ) );
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
        echo $content;
        // phpcs:ignore WordPress.Security.EscapeOutput --safe-html
    }

    public static function parse_social_links( $social_links ) {
        $links = '';
        foreach ( $social_links as $slink ) {
            $links .= sprintf(
                '<li class="wps-si--%s">
					<a href="%s" aria-label="%s"%s>%s</a>
				</li>',
                esc_attr( Utils::get_brand_name( $slink['social_icon']['icon'] ) ),
                esc_url_raw( $slink['social_link'] ),
                'Social Link',
                self::get_ext_url_params(),
                // phpcs:ignore WordPress.Security.EscapeOutput
                Icon_Manager::render_font_icon( $slink['social_icon'] )
            );
        }
        return $links;
    }

    public static function get_the_social_links_title( $args = [] ) {
        $args = shortcode_atts( [
            'title_tag' => 'h4',
        ], $args );
        $title_text = plugin()->translations->get( 'social_links_title', _x( 'Connect with me:', 'Public', 'wps-team' ) );
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
        if ( $args['context'] === 'general' ) {
            $render_string_key = 'social';
        } else {
            if ( $args['context'] === 'details' ) {
                $render_string_key = 'social_details';
            }
        }
        printf( '<%s class="wps-team--member-s-links wps-team--member-element">', esc_attr( $tag ) );
        if ( $args['show_title'] ) {
            self::get_the_social_links_title( $args );
        }
        ?>

			<ul <?php 
        self::shortcode_loader()->print_attribute_string( $render_string_key );
        ?>>
				<?php 
        echo self::parse_social_links( $social_links );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
			</ul>

		<?php 
        printf( '</%s>', esc_attr( $tag ) );
    }

    public static function get_the_read_more_title() {
        return plugin()->translations->get( 'read_more_link_text', _x( 'Read More', 'Public', 'wps-team' ) );
    }

    public static function get_the_link_1_title() {
        return plugin()->translations->get( 'link_1_btn_text', _x( 'My Resume', 'Public', 'wps-team' ) );
    }

    public static function get_the_link_2_title() {
        return plugin()->translations->get( 'link_2_btn_text', _x( 'Hire Me', 'Public', 'wps-team' ) );
    }

    public static function get_the_action_links( $post_id, $args = [] ) {
        $args = shortcode_atts( [
            'link_1'         => false,
            'link_2'         => false,
            'show_read_more' => false,
            'card_action'    => 'single-page',
            'context'        => 'general',
        ], $args );
        switch ( $args['context'] ) {
            case 'details':
                $show_link_1 = self::shortcode_loader()->get_setting( 'show_details_link_1' );
                $show_link_2 = self::shortcode_loader()->get_setting( 'show_details_link_2' );
                $show_read_more = false;
                break;
            case 'single':
                $show_link_1 = self::get_setting( 'single_link_1' );
                $show_link_2 = self::get_setting( 'single_link_2' );
                $show_read_more = false;
                break;
            default:
                $show_link_1 = self::shortcode_loader()->get_setting( 'show_link_1' );
                $show_link_2 = self::shortcode_loader()->get_setting( 'show_link_2' );
                $show_read_more = self::shortcode_loader()->get_setting( 'show_read_more' );
                break;
        }
        $show_link_1 = ( $show_link_1 === '' ? (bool) $args['link_1'] : wp_validate_boolean( $show_link_1 ) );
        $show_link_2 = ( $show_link_2 === '' ? (bool) $args['link_2'] : wp_validate_boolean( $show_link_2 ) );
        $show_read_more = ( $show_read_more === '' ? (bool) $args['show_read_more'] : wp_validate_boolean( $show_read_more ) );
        if ( !$show_link_1 && !$show_link_2 && !$show_read_more ) {
            return '';
        }
        $link_1_value = self::get_item_data( '_link_1' );
        $link_2_value = self::get_item_data( '_link_2' );
        if ( empty( $link_1_value ) && empty( $link_2_value ) && !$show_read_more ) {
            return '';
        }
        $html = '<div class="wps-team--action-links wps-team--member-element">';
        if ( $show_link_1 && !empty( $link_1_value ) ) {
            $ext_params = ( self::is_external_url( $link_1_value ) ? self::get_ext_url_params() : '' );
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-1"%s>%s</a>',
                esc_url( $link_1_value ),
                ( $ext_params ? ' ' . esc_attr( $ext_params ) : '' ),
                esc_html( self::get_the_link_1_title() )
            );
        }
        if ( $show_link_2 && !empty( $link_2_value ) ) {
            $ext_params = ( self::is_external_url( $link_2_value ) ? self::get_ext_url_params() : '' );
            $html .= sprintf(
                '<a href="%s" class="wps-team--btn wps-team--link-2"%s>%s</a>',
                esc_url( $link_2_value ),
                ( $ext_params ? ' ' . esc_attr( $ext_params ) : '' ),
                esc_html( self::get_the_link_2_title() )
            );
        }
        $normalized_action = self::normalize_card_action( (string) $args['card_action'], $post_id );
        if ( $show_read_more && $normalized_action !== 'none' ) {
            $link_attrs = self::get_link_attrs_for_post( (int) $post_id, $normalized_action, 'wps-team--btn wps-team--read-more-btn' );
            $html .= self::render_link( $link_attrs, esc_html( self::get_the_read_more_title() ) );
        }
        $html .= '</div>';
        echo $html;
        // phpcs:ignore WordPress.Security.EscapeOutput
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
        $title_text = plugin()->translations->get( 'skills_title', _x( 'Skills:', 'Public', 'wps-team' ) );
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
        echo wp_kses_post( self::parse_skills( $skills ) );
        ?>
			</ul>
		</div>

		<?php 
    }

    public static function get_all_shortcodes() {
        global $wpdb;
        $shortcodes = wp_cache_get( 'wps_team_all_shortcodes', 'wps_team' );
        if ( false === $shortcodes ) {
            $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            wp_cache_set( 'wps_team_all_shortcodes', $shortcodes, 'wps_team' );
        }
        return $shortcodes;
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
                case '_fax':
                    $field_label = '<i class="fas fa-fax"></i>';
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
                case '_address':
                    $field_label = '<i class="fas fa-map-marker-alt"></i>';
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
                    $field_label = plugin()->translations->get( 'mobile_meta_label', _x( 'Mobile:', 'Public', 'wps-team' ) );
                    break;
                case '_telephone':
                    $field_label = plugin()->translations->get( 'phone_meta_label', _x( 'Telephone:', 'Public', 'wps-team' ) );
                    break;
                case '_fax':
                    $field_label = plugin()->translations->get( 'fax_meta_label', _x( 'Fax:', 'Public', 'wps-team' ) );
                    break;
                case '_email':
                    $field_label = plugin()->translations->get( 'email_meta_label', _x( 'Email:', 'Public', 'wps-team' ) );
                    break;
                case '_website':
                    $field_label = plugin()->translations->get( 'website_meta_label', _x( 'Website:', 'Public', 'wps-team' ) );
                    break;
                case '_experience':
                    $field_label = plugin()->translations->get( 'experience_meta_label', _x( 'Experience:', 'Public', 'wps-team' ) );
                    break;
                case '_company':
                    $field_label = plugin()->translations->get( 'company_meta_label', _x( 'Company:', 'Public', 'wps-team' ) );
                    break;
                case '_address':
                    $field_label = plugin()->translations->get( 'address_meta_label', _x( 'Address:', 'Public', 'wps-team' ) );
                    break;
                case Utils::get_taxonomy_name( 'group', true ):
                    $field_label = plugin()->translations->get( 'group_meta_label', _x( 'Group:', 'Public', 'wps-team' ) );
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
            '_fax',
            '_email',
            '_website',
            '_experience',
            '_company',
            '_mobile',
            '_address'
        ], Utils::get_active_taxonomies( true ) ) );
        $supported_sorted_fields = array_values( $supported_sorted_fields );
        foreach ( $supported_sorted_fields as $s_field ) {
            $s_field_alt = ltrim( $s_field, '_' );
            $s_field_status = self::get_render_info( $s_field_alt, $args['context'] );
            if ( $s_field_status == 'true' || $s_field_status != 'false' && in_array( $s_field, $fields ) ) {
                $display_fields[] = $s_field;
            }
        }
        return array_intersect( $display_fields, $supported_sorted_fields );
    }

    public static function get_the_taxonomy_values( $tax_values, $separator = ', ' ) {
        return implode( '', array_map( function ( $i, $name ) use($tax_values, $separator) {
            $output = '<span class="wps--field-item">' . esc_html( $name ) . '</span>';
            if ( $i < count( $tax_values ) - 1 ) {
                $output .= '<span class="wps-field-sep">' . esc_html( $separator ) . '</span>';
            }
            return $output;
        }, array_keys( $tax_values ), $tax_values ) );
    }

    private static function render_contact_field(
        $field,
        $val,
        $label_html,
        $format,
        $protocol,
        $translation_key,
        $default_text,
        $link_attrs = ''
    ) {
        $class = esc_attr( 'wps--info-field' . $field );
        // Sanitize value by type
        if ( $protocol === 'mailto:' ) {
            $link_val = antispambot( sanitize_email( $val ) );
        } elseif ( in_array( $protocol, ['tel:', 'fax:'], true ) ) {
            $link_val = Utils::sanitize_phone_number( $val );
        } else {
            $link_val = esc_url( $val );
        }
        if ( $format === 'linked_raw' ) {
            return sprintf(
                '<li class="%s">%s<a class="wps--info-text" href="%s%s" %s>%s</a></li>',
                $class,
                $label_html,
                $protocol,
                $link_val,
                $link_attrs,
                esc_html( $val )
            );
        }
        if ( $format === 'linked_text' ) {
            $text = esc_html( plugin()->translations->get( $translation_key, $default_text ) );
            return sprintf(
                '<li class="%s">%s<a class="wps--info-text" href="%s%s" %s>%s</a></li>',
                $class,
                $label_html,
                $protocol,
                $link_val,
                $link_attrs,
                $text
            );
        }
        // No link
        return sprintf(
            '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
            $class,
            $label_html,
            esc_html( $val )
        );
    }

    public static function get_the_extra_info( $post_id, $args = [] ) {
        // Merge default arguments
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
        // Collect wrapper classes
        $info_classes = ['team-member--info-wrapper'];
        $info_style = ( $args['info_style'] ?: $args['info_style_default'] );
        $label_type = ( $args['label_type'] ?: $args['label_type_default'] );
        $complex_styles = ['start-aligned-alt', 'center-aligned-alt', 'center-aligned-combined'];
        // $info_style = 'start-aligned';
        // $info_style = 'start-aligned-alt';
        // $info_style = 'center-aligned';
        // $info_style = 'center-aligned-alt';
        // $info_style = 'center-aligned-combined';
        // $info_style = 'justify-aligned';
        if ( in_array( $info_style, $complex_styles, true ) ) {
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
            $field_label_html = wp_kses_post( Utils::get_the_field_label( $field, $label_type ) );
            // contains HTML
            switch ( $field ) {
                case '_mobile':
                    $format = self::get_setting( 'mobile_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'tel:',
                        'mobile_link_text',
                        _x( 'Call on Mobile', 'Public', 'wps-team' )
                    );
                    break;
                case '_telephone':
                    $format = self::get_setting( 'telephone_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'tel:',
                        'phone_link_text',
                        _x( 'Call on Telephone', 'Public', 'wps-team' )
                    );
                    break;
                case '_fax':
                    $format = self::get_setting( 'fax_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'fax:',
                        'fax_link_text',
                        _x( 'Send Fax', 'Public', 'wps-team' )
                    );
                    break;
                case '_email':
                    $format = self::get_setting( 'email_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        'mailto:',
                        'email_link_text',
                        _x( 'Send Email', 'Public', 'wps-team' )
                    );
                    break;
                case '_website':
                    $format = self::get_setting( 'website_display_format' );
                    $fields_html .= self::render_contact_field(
                        $field,
                        $val,
                        $field_label_html,
                        $format,
                        '',
                        // protocol not needed for website
                        'website_link_text',
                        _x( 'Visit Website', 'Public', 'wps-team' ),
                        ( self::is_external_url( $val ) ? self::get_ext_url_params() : '' )
                    );
                    break;
                case '_experience':
                case '_company':
                case '_address':
                    $fields_html .= sprintf(
                        '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
                        esc_attr( 'wps--info-field' . $field ),
                        $field_label_html,
                        esc_html( $val )
                    );
                    break;
                default:
                    // Handle taxonomy fields dynamically
                    foreach ( self::get_taxonomy_roots() as $taxonomy ) {
                        if ( $field === Utils::get_taxonomy_name( $taxonomy, true ) ) {
                            $fields_html .= sprintf(
                                '<li class="%s">%s<span class="wps--info-text">%s</span></li>',
                                esc_attr( 'wps--info-field_' . $taxonomy ),
                                $field_label_html,
                                wp_kses_post( Utils::get_the_taxonomy_values( wp_list_pluck( $val, 'name' ) ) )
                            );
                        }
                    }
                    break;
            }
        }
        if ( empty( $fields_html ) ) {
            return '';
        }
        $info_classes[] = 'info--' . $info_style;
        if ( $args['info_top_border'] ) {
            $info_classes[] = 'wps-team--info-top-border';
        }
        printf( '<div class="%s"><ul class="wps--member-info">%s</ul></div>', esc_attr( self::join_classes( $info_classes ) ), $fields_html );
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

    public static function delete_directory_recursive( $dir ) {
        if ( !file_exists( $dir ) ) {
            return false;
        }
        if ( !is_dir( $dir ) ) {
            return wp_delete_file( $dir );
        }
        foreach ( scandir( $dir ) as $item ) {
            if ( $item == '.' || $item == '..' ) {
                continue;
            }
            if ( !self::delete_directory_recursive( $dir . DIRECTORY_SEPARATOR . $item ) ) {
                return false;
            }
        }
        global $wp_filesystem;
        if ( empty( $wp_filesystem ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }
        return $wp_filesystem->rmdir( $dir, false );
    }

    public static function get_title_from_name_fields( $first_name = '', $last_name = '' ) {
        return trim( $first_name . ' ' . $last_name );
    }

    public static function update_name_fields_from_title( $post_id, $post_title ) {
        $name_parts = explode( ' ', $post_title );
        $first_name = '';
        $last_name = '';
        // Generate the name parts
        if ( count( $name_parts ) === 1 ) {
            $first_name = $name_parts[0];
        } else {
            $first_name = array_shift( $name_parts );
            $last_name = implode( ' ', $name_parts );
        }
        // Update the First Name
        if ( !empty( $first_name ) ) {
            update_post_meta( $post_id, '_first_name', $first_name );
        }
        // Update the Last Name
        if ( !empty( $last_name ) ) {
            update_post_meta( $post_id, '_last_name', $last_name );
        }
    }

    public static function sanitize_title_allow_slash( $title ) {
        // Temporarily replace slashes to preserve them
        $title = str_replace( '/', '___slash___', $title );
        // Use WordPress's sanitize_title
        $title = sanitize_title( $title );
        // Restore slashes
        $title = str_replace( '___slash___', '/', $title );
        return $title;
    }

    public static function maybe_json_encode( $data ) {
        if ( is_array( $data ) || is_object( $data ) ) {
            return wp_json_encode( $data );
        }
        return $data;
    }

    public static function maybe_json_decode( $data, $assoc = true ) {
        if ( !is_string( $data ) || trim( $data ) === '' ) {
            return $data;
        }
        $decoded = json_decode( $data, $assoc );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $decoded;
        }
        return $data;
    }

    public static function maybe_decoded_data( $data ) {
        if ( is_serialized( $data ) ) {
            return unserialize( $data );
        }
        $json = json_decode( $data, true );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $json;
        }
        return $data;
        // return as-is if not serialized or valid JSON
    }

    public static function db_last_error_message() {
        global $wpdb;
        $error_message = ( current_user_can( 'manage_options' ) ? $wpdb->last_error : esc_html__( 'An unexpected database error occurred.', 'wps-team' ) );
        /* translators: %s: Database error message */
        return sprintf( esc_html_x( 'Database Error: %s', 'Dashboard', 'wps-team' ), $error_message );
    }

    public static function sanitize_array_recursive( $array ) {
        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                $array[$key] = self::sanitize_array_recursive( $value );
            } else {
                $array[$key] = sanitize_text_field( $value );
            }
        }
        return $array;
    }

    public static function get_meta_box_controls() {
        static $controls = null;
        if ( $controls === null ) {
            $base_meta_box = new Meta_Box_Editor();
            $controls = $base_meta_box->get_controls();
        }
        return $controls;
    }

    public static function get_public_nonce() {
        $nonce = wp_create_nonce( '_wpspeedo_team_public_nonce' );
        do_action( 'litespeed_nonce', '_wpspeedo_team_public_nonce' );
        return $nonce;
    }

    public static function prepare_heavy_operation( $seconds = 300, $memory_type = 'admin' ) {
        // Raise memory limit safely
        @wp_raise_memory_limit( $memory_type );
        // Increase max execution time safely
        @set_time_limit( $seconds );
        @ini_set( 'max_execution_time', (int) $seconds );
    }

}
