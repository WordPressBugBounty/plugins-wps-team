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
    use Utils_Options;
    use Utils_Template_Elements;
    public static function join_classes( array $classes ) : string {
        $classes = array_filter( array_map( 'trim', $classes ) );
        $classes = array_unique( $classes );
        return implode( ' ', $classes );
    }

    public static function normalize_card_action( string $card_action, int $post_id ) : string {
        if ( Utils::has_singular_page() || $card_action !== 'single-page' ) {
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

    public static function elementor_get_post_meta( int $post_id ) {
        $meta = get_post_meta( $post_id, '_elementor_data', true );
        if ( is_string( $meta ) && !empty( $meta ) ) {
            $meta = json_decode( $meta, true );
        }
        if ( empty( $meta ) ) {
            $meta = [];
        }
        return $meta;
    }

    public static function elementor_update_post_meta( int $post_id, mixed $value ) {
        update_metadata(
            'post',
            $post_id,
            '_elementor_data',
            wp_slash( wp_json_encode( $value ) )
        );
    }

    public static function get_posts_meta_cache_key( string $meta_key, $post_type = null ) {
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        return sprintf( 'wps--meta-vals--%s_%s', $post_type, $meta_key );
    }

    public static function is_external_url( string $url ) {
        $self_data = wp_parse_url( home_url() );
        $url_data = wp_parse_url( $url );
        // Treat invalid/relative URLs as internal to avoid notices.
        if ( !is_array( $url_data ) || empty( $url_data['host'] ) ) {
            return false;
        }
        if ( !is_array( $self_data ) || empty( $self_data['host'] ) ) {
            return true;
        }
        return $self_data['host'] !== $url_data['host'];
    }

    public static function get_ext_url_params() {
        return ' rel="nofollow noopener noreferrer" target="_blank"';
    }

    public static function update_posts_meta_vals( string $meta_key, $post_type = null ) {
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

    public static function get_posts_meta_vals( string $meta_key, $post_type = null ) {
        global $wpdb;
        if ( empty( $post_type ) ) {
            $post_type = self::post_type_name();
        }
        $cache_key = self::get_posts_meta_cache_key( $meta_key, $post_type );
        $cache_data = wp_cache_get( $cache_key, 'wps_team' );
        if ( $cache_data !== false ) {
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

    public static function search_by_custom_criteria( string $where, object $wp_query ) {
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

    public static function paginate_links( array $args ) {
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

    public static function add_data_page_attr( string $html ) {
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

    public static function sanitize_request( array $array ) {
        $sanitized = [];
        foreach ( $array as $key => $value ) {
            $sanitized_key = sanitize_key( $key );
            if ( is_array( $value ) ) {
                $value = wp_unslash( $value );
                $sanitized[$sanitized_key] = self::sanitize_array_recursive( $value );
                continue;
            }
            $sanitized[$sanitized_key] = sanitize_text_field( wp_unslash( $value ) );
        }
        return $sanitized;
    }

    public static function get_pagination( array $args ) {
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

    public static function get_paged_var( mixed $id ) {
        return 'wps-team-' . $id . '-paged';
    }

    public static function get_meta_field_keys() {
        $field_keys = [
            '_first_name',
            '_last_name',
            '_experience',
            '_company',
            '_skills_with_value',
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

    public static function get_item_data( string $data_key, $post_id = null, $shortcode_id = null ) {
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

    public static function load_template( string $template_name ) {
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

    public static function wp_trim_html_chars( string $html, int $maxLength = 110 ) {
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

    public static function get_brand_name( string $icon ) {
        return str_replace( ['fab fa-', 'far fa-', 'fas fa-'], '', esc_attr( $icon ) );
    }

    public static function sanitize_phone_number( string $phone ) {
        return preg_replace( '/[^0-9\\-\\_\\+]*/', '', $phone );
    }

    public static function default_settings() {
        $archive_page_link = get_post_type_archive_link( self::post_type_name() );
        $archive_page_link = ( is_string( $archive_page_link ) ? $archive_page_link : '' );
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
            'filter_search_text'           => 'Search',
            'filter_reset_button_text'     => 'Reset Filters',
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
            'read_more_btn_text'           => 'Read More',
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
            'archive_page_link'            => $archive_page_link,
            'thumbnail_size'               => 'full',
            'thumbnail_size_custom'        => [],
            'detail_thumbnail_size'        => 'full',
            'detail_thumbnail_size_custom' => [],
            'detail_thumbnail_type'        => 'image',
            'enable_archive'               => true,
            'enable_singular_page'         => true,
            'with_front'                   => true,
            'post_type_slug'               => 'wps-members',
            'member_plural_name'           => 'Members',
            'member_single_name'           => 'Member',
            'enable_group_taxonomy'        => true,
            'enable_group_archive'         => false,
            'group_slug'                   => 'wps-members-group',
            'group_plural_name'            => 'Groups',
            'group_single_name'            => 'Group',
            'group_taxonomy_icon'          => '',
            'enable_location_taxonomy'     => false,
            'enable_location_archive'      => false,
            'location_slug'                => 'wps-members-location',
            'location_plural_name'         => 'Locations',
            'location_single_name'         => 'Location',
            'location_taxonomy_icon'       => '',
            'enable_language_taxonomy'     => false,
            'enable_language_archive'      => false,
            'language_slug'                => 'wps-members-language',
            'language_plural_name'         => 'Languages',
            'language_single_name'         => 'Language',
            'language_taxonomy_icon'       => '',
            'enable_specialty_taxonomy'    => false,
            'enable_specialty_archive'     => false,
            'specialty_slug'               => 'wps-members-specialty',
            'specialty_plural_name'        => 'Specialties',
            'specialty_single_name'        => 'Specialty',
            'specialty_taxonomy_icon'      => '',
            'enable_gender_taxonomy'       => false,
            'enable_gender_archive'        => false,
            'gender_slug'                  => 'wps-members-gender',
            'gender_plural_name'           => 'Genders',
            'gender_single_name'           => 'Gender',
            'gender_taxonomy_icon'         => '',
            'enable_extra_one_taxonomy'    => false,
            'enable_extra_one_archive'     => false,
            'extra_one_slug'               => 'wps-members-extra-one',
            'extra_one_plural_name'        => 'Extra One',
            'extra_one_single_name'        => 'Extra One',
            'extra_one_taxonomy_icon'      => '',
            'enable_extra_two_taxonomy'    => false,
            'enable_extra_two_archive'     => false,
            'extra_two_slug'               => 'wps-members-extra-two',
            'extra_two_plural_name'        => 'Extra Two',
            'extra_two_single_name'        => 'Extra Two',
            'extra_two_taxonomy_icon'      => '',
            'enable_extra_three_taxonomy'  => false,
            'enable_extra_three_archive'   => false,
            'extra_three_slug'             => 'wps-members-extra-three',
            'extra_three_plural_name'      => 'Extra Three',
            'extra_three_single_name'      => 'Extra Three',
            'extra_three_taxonomy_icon'    => '',
            'enable_extra_four_taxonomy'   => false,
            'enable_extra_four_archive'    => false,
            'extra_four_slug'              => 'wps-members-extra-four',
            'extra_four_plural_name'       => 'Extra Four',
            'extra_four_single_name'       => 'Extra Four',
            'extra_four_taxonomy_icon'     => '',
            'enable_extra_five_taxonomy'   => false,
            'enable_extra_five_archive'    => false,
            'extra_five_slug'              => 'wps-members-extra-five',
            'extra_five_plural_name'       => 'Extra Five',
            'extra_five_single_name'       => 'Extra Five',
            'extra_five_taxonomy_icon'     => '',
            'breakpoint_mobile_max'        => 480,
            'breakpoint_small_tablet_max'  => 767,
            'breakpoint_tablet_max'        => 1024,
        ];
    }

    public static function get_default( $key = '' ) {
        $default_settings = self::default_settings();
        if ( array_key_exists( $key, $default_settings ) ) {
            return $default_settings[$key];
        }
        return null;
    }

    public static function get_post_term_slugs( int $post_id, array $term_names, string $separator = ' ' ) {
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

    public static function get_post_term_classes( int $post_id, array $term_names, string $separator = ' ' ) {
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

    public static function get_terms( string $taxonomy, $args = [] ) {
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

    /**
     * Group terms for filter UI / queries (same constraints as template-filter-header).
     *
     * @param object $shortcode_loader Object with get_setting().
     */
    public static function get_group_terms_for_shortcode_filter( object $shortcode_loader ) {
        $args = [
            'orderby'    => $shortcode_loader->get_setting( 'group_orderby' ),
            'order'      => $shortcode_loader->get_setting( 'group_order' ),
            'hide_empty' => true,
        ];
        $include = $shortcode_loader->get_setting( 'include_by_group' );
        if ( !empty( $include ) ) {
            $args['include'] = (array) $include;
        }
        $exclude = $shortcode_loader->get_setting( 'exclude_by_group' );
        if ( !empty( $exclude ) ) {
            $args['exclude'] = (array) $exclude;
            // phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
        }
        return self::get_group_terms( $args );
    }

    /**
     * Resolve group filter initial UI and AJAX tax restriction from shortcode settings.
     *
     * @param object $shortcode_loader Object with get_setting().
     * @param array  $groups           Group terms from get_group_terms_for_shortcode_filter().
     * @return array{all_active:bool,active_term_id:?int,ajax_tax_term_ids:int[]}
     */
    public static function resolve_group_filter_initial_state( object $shortcode_loader, array $groups ) {
        $show_filter_all = wp_validate_boolean( $shortcode_loader->get_setting( 'show_filter_all' ) );
        $allow_deselect = wp_validate_boolean( $shortcode_loader->get_setting( 'allow_group_deselect' ) );
        $raw = $shortcode_loader->get_setting( 'initial_filter' );
        if ( $raw === '' || $raw === null ) {
            $raw = '*';
        }
        if ( $raw !== '*' && is_numeric( $raw ) ) {
            $raw = (int) $raw;
        }
        $term_ids = array_map( 'intval', wp_list_pluck( $groups, 'term_id' ) );
        if ( empty( $term_ids ) ) {
            return [
                'all_active'        => false,
                'active_term_id'    => null,
                'ajax_tax_term_ids' => [],
            ];
        }
        if ( $raw !== '*' && !in_array( (int) $raw, $term_ids, true ) ) {
            $raw = (int) $term_ids[0];
        }
        if ( $raw === '*' ) {
            if ( $show_filter_all ) {
                return [
                    'all_active'        => true,
                    'active_term_id'    => null,
                    'ajax_tax_term_ids' => [],
                ];
            }
            if ( $allow_deselect ) {
                return [
                    'all_active'        => false,
                    'active_term_id'    => null,
                    'ajax_tax_term_ids' => [],
                ];
            }
            $first = (int) $term_ids[0];
            return [
                'all_active'        => false,
                'active_term_id'    => $first,
                'ajax_tax_term_ids' => [$first],
            ];
        }
        $tid = (int) $raw;
        return [
            'all_active'        => false,
            'active_term_id'    => $tid,
            'ajax_tax_term_ids' => [$tid],
        ];
    }

    public static function get_wps_team( int $shortcode_id ) {
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

    public static function set_social_attrs_for_detail_view( object $shortcode_loader, $settings = [] ) {
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

    public static function get_timestamp_diff( int $old_time, $new_time = null ) {
        if ( $new_time == null ) {
            $new_time = time();
        }
        return ceil( ($new_time - $old_time) / DAY_IN_SECONDS );
    }

    public static function minify_css( string $css ) {
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

    public static function validate_css( string $css ) {
        $css = trim( (string) wp_unslash( $css ) );
        if ( empty( $css ) ) {
            return '';
        }
        $css = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $css );
        $css = preg_replace( '#<style(.*?)>(.*?)</style>#is', '', $css );
        return $css;
    }

    public static function minify_validated_css( $css ) {
        $css = ( is_string( $css ) ? $css : '' );
        $css = self::validate_css( $css );
        $css = self::minify_css( $css );
        return $css;
    }

    public static function shortcode_loader() {
        return $GLOBALS['shortcode_loader'];
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

    public static function delete_directory_recursive( string $dir ) {
        $base_dir = wp_normalize_path( trailingslashit( get_temp_dir() ) . 'wpspeedo' );
        $base_dir = untrailingslashit( $base_dir );
        $resolved = realpath( $dir );
        $target = wp_normalize_path( ( $resolved !== false ? $resolved : $dir ) );
        $target = untrailingslashit( $target );
        // Safety guard: only allow deleting from plugin temp sandbox.
        if ( $target !== $base_dir && strpos( $target, $base_dir . '/' ) !== 0 ) {
            return false;
        }
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

    public static function update_name_fields_from_title( int $post_id, string $post_title ) {
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

    public static function sanitize_title_allow_slash( string $title ) {
        // Temporarily replace slashes to preserve them
        $title = str_replace( '/', '___slash___', $title );
        // Use WordPress's sanitize_title
        $title = sanitize_title( $title );
        // Restore slashes
        $title = str_replace( '___slash___', '/', $title );
        return $title;
    }

    public static function maybe_json_encode( mixed $data ) {
        if ( is_array( $data ) || is_object( $data ) ) {
            return wp_json_encode( $data );
        }
        return $data;
    }

    public static function maybe_json_decode( mixed $data, $assoc = true ) {
        if ( !is_string( $data ) || trim( $data ) === '' ) {
            return $data;
        }
        $decoded = json_decode( $data, $assoc );
        if ( json_last_error() === JSON_ERROR_NONE ) {
            return $decoded;
        }
        return $data;
    }

    public static function maybe_decoded_data( mixed $data ) {
        if ( !is_string( $data ) || trim( $data ) === '' ) {
            return $data;
        }
        if ( is_serialized( $data ) ) {
            // Prevent object injection; we only expect scalar/array data.
            return unserialize( $data, [
                'allowed_classes' => false,
            ] );
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

    public static function sanitize_array_recursive( array $array ) {
        foreach ( $array as $key => $value ) {
            if ( is_array( $value ) ) {
                $array[$key] = self::sanitize_array_recursive( $value );
            } else {
                $array[$key] = sanitize_text_field( $value );
            }
        }
        return $array;
    }

    /**
     * Sanitize taxonomy settings icon JSON (Font Awesome picker value).
     *
     * @param mixed $value Raw POST value.
     * @return string JSON or empty string.
     */
    public static function sanitize_taxonomy_icon_setting_value( mixed $value ) {
        if ( !is_string( $value ) || $value === '' ) {
            return '';
        }
        $data = json_decode( wp_unslash( $value ), true );
        if ( !is_array( $data ) ) {
            return '';
        }
        $icon = ( isset( $data['icon'] ) ? sanitize_text_field( $data['icon'] ) : '' );
        $library = ( isset( $data['library'] ) ? sanitize_key( $data['library'] ) : '' );
        if ( $icon === '' || $library === '' ) {
            return '';
        }
        $allowed = wp_list_pluck( Icon_Manager::get_icon_manager_tabs_config(), 'name' );
        if ( !in_array( $library, $allowed, true ) ) {
            return '';
        }
        $icon = preg_replace( '/[^a-zA-Z0-9_\\- ]/', '', $icon );
        return wp_json_encode( compact( 'icon', 'library' ) );
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
