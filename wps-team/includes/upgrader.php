<?php

namespace WPSpeedo_Team;

use WP_Query;

if ( ! defined('ABSPATH') ) exit;

class Upgrader {

    public static $instance = null;
    public $old_version;
    public $new_version;

    public function __construct( $old_version, $new_version ) {
        $this->old_version = $old_version;
        $this->new_version = $new_version;
        $this->run();
    }
    
	public static function instance( $old_version, $new_version ) {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self( $old_version, $new_version );
		}
        return self::$instance;
    }

    public function upgrade_paths() {
        return [ '2.4.0', '2.5.7', '2.5.8', '2.7.0', '3.1.0', '3.2.1', '3.3.1', '3.4.5', '3.4.6' ];
    }

    public function run() {
        if ( $this->old_version === $this->new_version ) return;
        foreach ( $this->upgrade_paths() as $version ) {
            if ( version_compare( $version, $this->old_version, '>' ) ) {
                $ungrade_fn = '_v_' . str_replace( '.', '_', $version );
                if ( method_exists( $this, $ungrade_fn ) ) {
                    $this->$ungrade_fn();
                }
            }
        }
    }

    public function _v_2_4_0() {

        $themes = [
            'theme-one'   => 'square-01',
            'theme-two'   => 'square-02',
            'theme-three' => 'square-03',
            'theme-four'  => 'square-04',
            'theme-five'  => 'square-05',
            'theme-six'   => 'circle-01',
            'horiz-one'   => 'horiz-01',
            'horiz-two'   => 'horiz-02',
            'horiz-three' => 'horiz-03',
            'horiz-four'  => 'horiz-04',
        ];

        global $wpdb;

        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        
        foreach ( $shortcodes as &$shortcode ) {

            $shortcode['settings'] = maybe_unserialize( $shortcode['settings'] );

            if ( !empty( $theme = $shortcode['settings']['theme']['value'] ) && array_key_exists( $theme, $themes ) ) {
                $shortcode['settings']['theme']['value'] = $themes[ $theme ];

                $shortcode['settings'] = maybe_serialize( $shortcode['settings'] );
                $shortcode["updated_at"] = current_time('mysql');
                $wpdb->update( "{$wpdb->prefix}wps_team" , $shortcode, array( 'id' => $shortcode['id'] ),  plugin()->api->db_columns_format() ); // phpcs:ignore
            }

        }

    }

    public function _v_2_5_7() {

        if ( ! wps_team_fs()->can_use_premium_code() ) return;
        
        global $wpdb;
        
        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        
        foreach ( $shortcodes as &$shortcode ) {

            $shortcode['settings'] = maybe_unserialize( $shortcode['settings'] );

            if ( $shortcode['settings']['typo_name_font_size']['value'] == 0 ) $shortcode['settings']['typo_name_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_desig_font_size']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_content_font_size']['value'] == 0 ) $shortcode['settings']['typo_content_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_meta_font_size']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_tablet']['value'] = '';

            $shortcode['settings'] = maybe_serialize( $shortcode['settings'] );
            $shortcode["updated_at"] = current_time('mysql');
            $wpdb->update( "{$wpdb->prefix}wps_team" , $shortcode, array( 'id' => $shortcode['id'] ),  plugin()->api->db_columns_format() ); // phpcs:ignore

        }

    }

    public function _v_2_5_8() {

        if ( ! wps_team_fs()->can_use_premium_code() ) return;
        
        global $wpdb;
        
        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        
        foreach ( $shortcodes as &$shortcode ) {

            $shortcode['settings'] = maybe_unserialize( $shortcode['settings'] );

            // Type Name

            if ( $shortcode['settings']['typo_name_font_size']['value'] == 0 ) $shortcode['settings']['typo_name_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_name_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_name_line_height']['value'] == 0 ) $shortcode['settings']['typo_name_line_height']['value'] = '';
            if ( $shortcode['settings']['typo_name_line_height_mobile']['value'] == 0 ) $shortcode['settings']['typo_name_line_height_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_name_line_height_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_line_height_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_name_line_height_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_line_height_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_name_letter_spacing']['value'] == 0 ) $shortcode['settings']['typo_name_letter_spacing']['value'] = '';
            if ( $shortcode['settings']['typo_name_letter_spacing_mobile']['value'] == 0 ) $shortcode['settings']['typo_name_letter_spacing_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_name_letter_spacing_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_letter_spacing_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_name_letter_spacing_tablet']['value'] == 0 ) $shortcode['settings']['typo_name_letter_spacing_tablet']['value'] = '';

            // Type Desig

            if ( $shortcode['settings']['typo_desig_font_size']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_desig_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_desig_line_height']['value'] == 0 ) $shortcode['settings']['typo_desig_line_height']['value'] = '';
            if ( $shortcode['settings']['typo_desig_line_height_mobile']['value'] == 0 ) $shortcode['settings']['typo_desig_line_height_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_desig_line_height_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_line_height_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_desig_line_height_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_line_height_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_desig_letter_spacing']['value'] == 0 ) $shortcode['settings']['typo_desig_letter_spacing']['value'] = '';
            if ( $shortcode['settings']['typo_desig_letter_spacing_mobile']['value'] == 0 ) $shortcode['settings']['typo_desig_letter_spacing_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_desig_letter_spacing_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_letter_spacing_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_desig_letter_spacing_tablet']['value'] == 0 ) $shortcode['settings']['typo_desig_letter_spacing_tablet']['value'] = '';

            // Type Content

            if ( $shortcode['settings']['typo_content_font_size']['value'] == 0 ) $shortcode['settings']['typo_content_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_content_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_content_line_height']['value'] == 0 ) $shortcode['settings']['typo_content_line_height']['value'] = '';
            if ( $shortcode['settings']['typo_content_line_height_mobile']['value'] == 0 ) $shortcode['settings']['typo_content_line_height_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_content_line_height_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_line_height_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_content_line_height_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_line_height_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_content_letter_spacing']['value'] == 0 ) $shortcode['settings']['typo_content_letter_spacing']['value'] = '';
            if ( $shortcode['settings']['typo_content_letter_spacing_mobile']['value'] == 0 ) $shortcode['settings']['typo_content_letter_spacing_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_content_letter_spacing_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_letter_spacing_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_content_letter_spacing_tablet']['value'] == 0 ) $shortcode['settings']['typo_content_letter_spacing_tablet']['value'] = '';

            // Typo Meta

            if ( $shortcode['settings']['typo_meta_font_size']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_mobile']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_meta_font_size_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_font_size_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_meta_line_height']['value'] == 0 ) $shortcode['settings']['typo_meta_line_height']['value'] = '';
            if ( $shortcode['settings']['typo_meta_line_height_mobile']['value'] == 0 ) $shortcode['settings']['typo_meta_line_height_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_meta_line_height_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_line_height_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_meta_line_height_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_line_height_tablet']['value'] = '';

            if ( $shortcode['settings']['typo_meta_letter_spacing']['value'] == 0 ) $shortcode['settings']['typo_meta_letter_spacing']['value'] = '';
            if ( $shortcode['settings']['typo_meta_letter_spacing_mobile']['value'] == 0 ) $shortcode['settings']['typo_meta_letter_spacing_mobile']['value'] = '';
            if ( $shortcode['settings']['typo_meta_letter_spacing_small_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_letter_spacing_small_tablet']['value'] = '';
            if ( $shortcode['settings']['typo_meta_letter_spacing_tablet']['value'] == 0 ) $shortcode['settings']['typo_meta_letter_spacing_tablet']['value'] = '';

            $shortcode['settings'] = maybe_serialize( $shortcode['settings'] );
            $shortcode["updated_at"] = current_time('mysql');
            $wpdb->update( "{$wpdb->prefix}wps_team" , $shortcode, array( 'id' => $shortcode['id'] ),  plugin()->api->db_columns_format() ); // phpcs:ignore

        }

    }

    public function _v_2_7_0() {

        if ( ! wps_team_fs()->can_use_premium_code() ) return;
        
        global $wpdb;
        
        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        
        foreach ( $shortcodes as &$shortcode ) {

            $shortcode['settings'] = maybe_unserialize( $shortcode['settings'] );

            $filter_inner_space                 = (int) $shortcode['settings']['filter_inner_space']['value'];
            $filter_inner_space_mobile          = (int) $shortcode['settings']['filter_inner_space_mobile']['value'];
            $filter_inner_space_small_tablet    = (int) $shortcode['settings']['filter_inner_space_small_tablet']['value'];
            $filter_inner_space_tablet          = (int) $shortcode['settings']['filter_inner_space_tablet']['value'];

            if ( $filter_inner_space != 0 ) {
                $shortcode['settings']['filter_inner_space']['value'] = $filter_inner_space * 2;
            }

            if ( $filter_inner_space_mobile != 0 ) {
                $shortcode['settings']['filter_inner_space_mobile']['value'] = $filter_inner_space_mobile * 2;
            }

            if ( $filter_inner_space_small_tablet != 0 ) {
                $shortcode['settings']['filter_inner_space_small_tablet']['value'] = $filter_inner_space_small_tablet * 2;
            }

            if ( $filter_inner_space_tablet != 0 ) {
                $shortcode['settings']['filter_inner_space_tablet']['value'] = $filter_inner_space_tablet * 2;
            }

            $shortcode['settings'] = maybe_serialize( $shortcode['settings'] );
            $shortcode["updated_at"] = current_time('mysql');
            $wpdb->update( "{$wpdb->prefix}wps_team" , $shortcode, array( 'id' => $shortcode['id'] ),  plugin()->api->db_columns_format() ); // phpcs:ignore

        }

    }

    public function _v_3_1_0() {

        // Copy Taxonomy Settings from old to new key
		$defaults = Utils::default_settings();
		$settings = (array) get_option( Utils::get_option_name(), $defaults );
		$settings = array_merge( $defaults, $settings );
        $taxonomy_setting_keys = array_flip( Utils::taxonomies_settings_keys() );
        $settings = array_intersect_key( $settings, $taxonomy_setting_keys );
        update_option( Utils::get_taxonomies_option_name(), $settings );
        
        // Twitter Icon change
        $team_members = get_posts([
            'fields'         => 'ids',
            'post_type'      => Utils::post_type_name(),
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ]);

        foreach ( $team_members as $team_member_id ) {
            $changed = false;
            $social_links = Utils::get_item_data( '_social_links', $team_member_id );

            foreach ( $social_links as &$social_link ) {
                if ( $social_link['social_icon']['icon'] == 'fab fa-twitter' ) {
                    $changed = true;
                    $social_link['social_icon']['icon'] = 'fab fa-x-twitter';
                }
            }

            if ( $changed ) {
                update_post_meta( $team_member_id, '_social_links', $social_links );
            }

        }

    }

    public function _v_3_2_1() {

        if ( ! class_exists( '\Elementor\Plugin' ) ) return;

        $args = [
            'post_type' => 'any',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [ // phpcs:ignore
                [
                    'key' => '_elementor_data',
                    'compare' => 'EXISTS'
                ]
            ]
        ];
    
        $query = new WP_Query($args);
    
        foreach ($query->posts as $post_id) {
            $elementor_data = Utils::elementor_get_post_meta( $post_id );

            if ( ! str_contains( json_encode($elementor_data), 'wpspeedo_team' ) ) continue;

            // Flag to check if the post was updated
            $updated = false;
    
            // Loop through the Elementor sections and elements
            $this->_v_3_2_1_update_elementor_shortcode_id( $elementor_data, $updated );
    
            // Only update the post meta if changes were made
            if ( $updated ) {

                // Save as array if it was originally an array
                Utils::elementor_update_post_meta( $post_id, $elementor_data );
            }

        }
    
        wp_reset_postdata();
    }

    public function _v_3_2_1_update_elementor_shortcode_id( &$widgets, &$updated ) {

        foreach ($widgets as &$widget) {
    
            if ( isset($widget['widgetType']) && $widget['widgetType'] === 'wpspeedo_team' ) {
                if ( isset($widget['settings']['shortcode_id']) && is_numeric($widget['settings']['shortcode_id']) ) {
                    if ( ! str_contains( $widget['settings']['shortcode_id'], 'shortcode-' ) ) {
                        $widget['settings']['shortcode_id'] = 'shortcode-' . $widget['settings']['shortcode_id'];
                        $updated = true;
                    }
                }
                continue;
            }
    
            // Recursively check for nested sections
            if (!empty($widget['elements'])) {
                $this->_v_3_2_1_update_elementor_shortcode_id( $widget['elements'], $updated );
            }

        }

    }

    public function _v_3_3_1() {
        
        $team_members = get_posts([
            'fields'         => 'ids',
            'post_type'      => Utils::post_type_name(),
            'posts_per_page' => -1,
            'post_status'    => 'any',
        ]);

        foreach ( $team_members as $team_member_id ) {
            Utils::update_name_fields_from_title( $team_member_id, get_the_title( $team_member_id ) );
        }

    }

    public function _v_3_4_5() {

        global $wpdb;

        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore
        
        foreach ( $shortcodes as &$shortcode ) {

            $shortcode['settings'] = Utils::maybe_decoded_data( $shortcode['settings'] );

            if ( ! empty( $shortcode['settings']['ribbon_text_color']['value'] ) ) {
                $shortcode['settings']['detail_ribbon_text_color'] = [
                    'value' => $shortcode['settings']['ribbon_text_color']['value']
                ];
            }

            if ( ! empty( $shortcode['settings']['ribbon_bg_color']['value'] ) ) {
                $shortcode['settings']['detail_ribbon_bg_color'] = [
                    'value' => $shortcode['settings']['ribbon_bg_color']['value']
                ];
            }

            $shortcode['settings'] = json_encode( $shortcode['settings'] );
            $shortcode["updated_at"] = current_time('mysql');
            $wpdb->update( "{$wpdb->prefix}wps_team" , $shortcode, array( 'id' => $shortcode['id'] ),  plugin()->api->db_columns_format() ); // phpcs:ignore

        }

    }

    public function _v_3_4_6() {

        $this->_v_3_4_5();

    }

}