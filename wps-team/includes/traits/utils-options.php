<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
trait Utils_Options
{
    public static function get_pro_label() {
        return _x( '(Pro) - ', 'Editor', 'wps-team' );
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
        $defaults = Utils::default_settings();
        $settings = (array) get_option( self::get_option_name(), $defaults );
        $settings = array_merge( $defaults, $settings );
        // Fix boolean false / literal "false" saved when archive link was resolved before CPT registration.
        $apl = ( isset( $settings['archive_page_link'] ) ? $settings['archive_page_link'] : '' );
        if ( !is_string( $apl ) || 'false' === $apl ) {
            $link = get_post_type_archive_link( Utils::post_type_name() );
            $settings['archive_page_link'] = ( is_string( $link ) ? $link : '' );
        }
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

    /**
     * Normalize the three stored breakpoint max-width values (ordering + sane gaps).
     * Mobile max is never below 100px.
     *
     * @param int $mobile_max          Max width for the mobile tier (px).
     * @param int $small_tablet_max   Max width for the small tablet tier (px).
     * @param int $tablet_max         Max width for the tablet tier (px).
     * @return array{ mobile_max: int, small_tablet_max: int, tablet_max: int }
     */
    public static function normalize_breakpoint_widths( $mobile_max, $small_tablet_max, $tablet_max ) {
        $defaults = self::default_settings();
        $m = (int) $mobile_max;
        $st = (int) $small_tablet_max;
        $t = (int) $tablet_max;
        $m = max( 100, min( 998, $m ) );
        if ( $st < $m + 2 ) {
            $st = $m + 2;
        }
        if ( $st > 1998 ) {
            $st = 1998;
        }
        if ( $t < $st + 2 ) {
            $t = $st + 2;
        }
        if ( $t > 3998 ) {
            $t = 3998;
        }
        return [
            'mobile_max'       => $m,
            'small_tablet_max' => $st,
            'tablet_max'       => $t,
        ];
    }

    /**
     * Current normalized breakpoint max widths from saved settings.
     *
     * @return array{ mobile_max: int, small_tablet_max: int, tablet_max: int }
     */
    public static function get_breakpoint_widths() {
        $defaults = self::default_settings();
        $s = self::get_general_settings();
        $m = ( isset( $s['breakpoint_mobile_max'] ) ? (int) $s['breakpoint_mobile_max'] : (int) $defaults['breakpoint_mobile_max'] );
        $st = ( isset( $s['breakpoint_small_tablet_max'] ) ? (int) $s['breakpoint_small_tablet_max'] : (int) $defaults['breakpoint_small_tablet_max'] );
        $t = ( isset( $s['breakpoint_tablet_max'] ) ? (int) $s['breakpoint_tablet_max'] : (int) $defaults['breakpoint_tablet_max'] );
        return self::normalize_breakpoint_widths( $m, $st, $t );
    }

    /**
     * Breakpoint data for JavaScript (media queries, Swiper keys, ticker).
     *
     * @return array<string,int>
     */
    public static function get_breakpoints_for_client() {
        $w = self::get_breakpoint_widths();
        return [
            'mobile_max'       => $w['mobile_max'],
            'small_tablet_max' => $w['small_tablet_max'],
            'tablet_max'       => $w['tablet_max'],
            'small_tablet_min' => $w['mobile_max'] + 1,
            'tablet_min'       => $w['small_tablet_max'] + 1,
            'desktop_min'      => $w['tablet_max'] + 1,
            'swiper_sm'        => $w['mobile_max'] + 1,
            'swiper_md'        => $w['small_tablet_max'] + 1,
            'swiper_lg'        => $w['tablet_max'] + 1,
        ];
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

    public static function has_singular_page() {
        return wp_validate_boolean( self::get_setting( 'enable_singular_page' ) );
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

    public static function get_taxonomy_name( string $tax_root, bool $is_field = false ) {
        $name = 'wps-team-' . $tax_root;
        return ( $is_field ? self::to_field_key( $name ) : $name );
    }

    public static function get_taxonomy_root( string $taxonomy, bool $is_field = false ) {
        $tax_root = str_replace( 'wps-team-', '', $taxonomy );
        return ( $is_field ? self::to_field_key( $tax_root ) : $tax_root );
    }

    public static function get_taxonomy_key( string $taxonomy ) {
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

    public static function to_field_key( string $str ) {
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
            $_tax_roots[] = $tax_root . '_taxonomy_icon';
            $_tax_roots[] = $tax_root . '_slug';
        }
        return $_tax_roots;
    }

    public static function get_taxonomies_settings() {
        $taxonomy_keys = self::taxonomies_settings_keys();
        $default_settings = array_intersect_key( Utils::default_settings(), array_flip( $taxonomy_keys ) );
        $settings = get_option( self::get_taxonomies_option_name(), [] );
        $settings = array_merge( $default_settings, $settings );
        foreach ( $settings as $key => $val ) {
            if ( empty( $val ) && isset( $default_settings[$key] ) ) {
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
                'label'    => _x( 'Ticker', 'Editor', 'wps-team' ),
                'value'    => 'ticker',
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

    public static function get_options_carousel_layout() {
        $options = [
            [
                'label' => _x( 'Layout 01', 'Editor', 'wps-team' ),
                'value' => 'layout-01',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Layout 02', 'Editor', 'wps-team' ),
                'value'    => 'layout-02',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Layout 03', 'Editor', 'wps-team' ),
                'value'    => 'layout-03',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Layout 04', 'Editor', 'wps-team' ),
                'value'    => 'layout-04',
            ],
            [
                'disabled' => true,
                'label'    => _x( 'Layout 05', 'Editor', 'wps-team' ),
                'value'    => 'layout-05',
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

    public static function get_term_options( array $terms ) {
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

    public static function get_control_options( string $control_id, $args = null ) {
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

    /**
     * Carousel layout values allowed for the current license (front + saved data validation).
     *
     * @return string[]
     */
    public static function get_active_carousel_layouts() {
        $layouts = ['layout-01'];
        return $layouts;
    }

    public static function get_group_themes( string $theme_category ) {
        $themes = self::get_active_themes();
        return array_filter( $themes, function ( $theme ) use($theme_category) {
            return strpos( $theme, $theme_category ) !== false;
        } );
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
            $elements['read_more_link'] = _x( 'Read More Link', 'Editor', 'wps-team' );
            $elements['read_more_btn'] = _x( 'Read More Button', 'Editor', 'wps-team' );
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
            'ribbon',
            'read_more_link'
        ];
    }

    public static function get_sorted_elements() {
        $elements = array_keys( Utils::elements_display_order() );
        $_elements = [];
        foreach ( $elements as $element ) {
            $_elements[$element] = Utils::shortcode_loader()->get_setting( 'order_' . $element );
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

}