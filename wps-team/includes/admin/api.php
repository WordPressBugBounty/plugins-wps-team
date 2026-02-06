<?php

namespace WPSpeedo_Team;

use Error;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class API {
    use AJAX_Handler;
    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_ajax_handler';

    public function __construct() {
        $this->set_ajax_scope_hooks();
    }

    public function ajax_get_settings() {
        $settings = Utils::get_settings();
        wp_send_json_success( $settings );
    }

    public function sanitize_settings( $settings ) {
        if ( !empty( $settings['post_type_slug'] ) ) {
            if ( $settings['post_type_slug'] === '/' ) {
                $settings['post_type_slug'] = '';
            }
            $settings['post_type_slug'] = Utils::sanitize_title_allow_slash( $settings['post_type_slug'] );
        }
        $base_settings = new Settings_Editor();
        $settings = $base_settings->get_stack_formed_values( $settings );
        $settings = $base_settings->values_to_settings( $settings );
        $settings_editor = new Settings_Editor([
            'id'       => 'fake',
            'settings' => $settings,
        ]);
        // This class will handle the Sanitization & Validation.
        return $settings_editor->get_display_formated_values();
    }

    public function save_settings( $settings ) {
        $settings = $this->sanitize_settings( $settings );
        // Sanitization & Validation done manually.
        update_option( Utils::get_option_name(), $settings );
        do_action( 'wps_preference_update' );
        Utils::flush_rewrite_rules();
    }

    public function ajax_save_settings() {
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $settings = ( isset( $_REQUEST['settings'] ) ? wp_unslash( $_REQUEST['settings'] ) : [] );
        // Sanitize, Validate and Save Settings
        $this->save_settings( $settings );
        wp_send_json_success( [
            'message' => _x( 'Settings saved successfully', 'Settings', 'wps-team' ),
            'data'    => get_option( Utils::get_option_name() ),
        ] );
    }

    public function sanitize_taxonomy_settings( $settings ) {
        $taxonomy_keys = Utils::taxonomies_settings_keys();
        foreach ( $taxonomy_keys as $tax_key ) {
            if ( !isset( $settings[$tax_key] ) ) {
                continue;
            }
            if ( str_contains( $tax_key, 'enable_' ) ) {
                $settings[$tax_key] = wp_validate_boolean( $settings[$tax_key] );
            } else {
                if ( str_contains( $tax_key, '_slug' ) ) {
                    $settings[$tax_key] = sanitize_title( $settings[$tax_key] );
                } else {
                    $settings[$tax_key] = sanitize_text_field( $settings[$tax_key] );
                }
            }
        }
        return $settings;
    }

    public function ajax_save_taxonomy_settings() {
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $settings = ( isset( $_REQUEST['settings'] ) ? wp_unslash( $_REQUEST['settings'] ) : [] );
        // phpcs:ignore WordPress.Security.NonceVerification
        $taxonomy = ( isset( $_REQUEST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['taxonomy'] ) ) : '' );
        $tax_key = Utils::get_taxonomy_key( $taxonomy );
        $enable_taxonomy_key = "enable_{$tax_key}_taxonomy";
        $enable_archive_key = "enable_{$tax_key}_archive";
        $post_type_name = Utils::post_type_name();
        $settings = wp_list_pluck( $settings, 'value', 'name' );
        $settings[$enable_taxonomy_key] = isset( $settings[$enable_taxonomy_key] );
        $settings[$enable_archive_key] = isset( $settings[$enable_archive_key] );
        $saved_settings = Utils::get_taxonomies_settings();
        $settings = array_merge( $saved_settings, $settings );
        // Sanitization & Validation Done
        $settings = $this->sanitize_taxonomy_settings( $settings );
        update_option( Utils::get_taxonomies_option_name(), $settings );
        if ( $settings[$enable_taxonomy_key] ) {
            $tax_page_url = admin_url( sprintf( 'edit-tags.php?taxonomy=%s&post_type=%s', esc_attr( $taxonomy ), esc_attr( $post_type_name ) ) );
        } else {
            $tax_page_url = admin_url( sprintf( 'edit.php?post_type=%s&page=taxonomies&taxonomy=%s', esc_attr( $post_type_name ), esc_attr( $taxonomy ) ) );
        }
        wp_send_json_success( [
            'tax_page_url' => $tax_page_url,
        ] );
    }

    public function ajax_get_shortcodes() {
        $shortcodes = Utils::get_all_shortcodes();
        foreach ( $shortcodes as &$shortcode ) {
            $shortcode['settings'] = Utils::maybe_json_decode( $shortcode['settings'] );
            $shortcode['settings'] = $this->validate_shortcode( $shortcode )->get_settings_value();
            // Settings will be Sanitized & Validated by Shortcode_Editor class.
        }
        if ( !wp_doing_ajax() ) {
            return $shortcodes;
        }
        wp_send_json_success( $shortcodes );
    }

    public function fetch_shortcode( $shortcode_id ) {
        global $wpdb;
        $shortcode_id = abs( $shortcode_id );
        $shortcode = wp_cache_get( "wps_team_shortcode_{$shortcode_id}", 'wps_team' );
        if ( false === $shortcode ) {
            $shortcode = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wps_team WHERE id = %d LIMIT 1", $shortcode_id ), ARRAY_A );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            if ( !empty( $shortcode ) ) {
                wp_cache_set( "wps_team_shortcode_{$shortcode_id}", $shortcode, 'wps_team' );
            }
        }
        if ( empty( $shortcode ) ) {
            return;
        }
        if ( $wpdb->last_error !== '' ) {
            if ( wp_doing_ajax() ) {
                wp_send_json_error( Utils::db_last_error_message(), 500 );
            }
            return false;
        }
        $shortcode['settings'] = Utils::maybe_json_decode( $shortcode['settings'] );
        $shortcode['settings'] = $this->validate_shortcode( $shortcode )->get_settings_value();
        // Settings will be Sanitized & Validated by Shortcode_Editor class.
        return $shortcode;
    }

    public function ajax_update_shortcode() {
        global $wpdb;
        // phpcs:ignore WordPress.Security.NonceVerification
        $shortcode_id = ( isset( $_REQUEST['id'] ) ? absint( wp_unslash( $_REQUEST['id'] ) ) : 0 );
        if ( empty( $shortcode_id ) ) {
            wp_send_json_error( __( 'Invalid Shortcode ID', 'wps-team' ), 400 );
        }
        $data = [];
        $shortcode = $this->fetch_shortcode( $shortcode_id );
        // Update name if provided
        // phpcs:ignore WordPress.Security.NonceVerification
        $name = ( isset( $_REQUEST['name'] ) && $_REQUEST['name'] !== '' ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '' );
        if ( $name !== '' ) {
            $shortcode['name'] = $name;
            $data['name'] = $name;
        }
        $return_data = $shortcode;
        // Update settings if provided
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $settings = ( isset( $_REQUEST['settings'] ) ? wp_unslash( $_REQUEST['settings'] ) : '' );
        if ( !empty( $settings ) ) {
            $shortcode['settings'] = $settings;
            // Will be sanitized by Shortcode_Editor class
            $shortcode = $this->validate_shortcode( $shortcode );
            $data['settings'] = Utils::maybe_json_encode( $shortcode->get_settings_value() );
            $return_data = $shortcode->get_data();
        }
        $data['updated_at'] = current_time( 'mysql' );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->update(
            "{$wpdb->prefix}wps_team",
            $data,
            [
                'id' => $shortcode_id,
            ],
            $this->db_columns_format()
        );
        if ( $wpdb->last_error !== '' ) {
            wp_send_json_error( Utils::db_last_error_message(), 500 );
        }
        wp_cache_delete( "wps_team_shortcode_{$shortcode_id}", 'wps_team' );
        do_action( 'wps_shortcode_updated', $shortcode_id );
        wp_send_json_success( [
            'message' => sprintf( '<strong>%s</strong> %s', esc_html_x( 'Congrats!', 'Dashboard', 'wps-team' ), esc_html_x( 'Shortcode updated successfully', 'Dashboard', 'wps-team' ) ),
            'data'    => $return_data,
        ] );
    }

    public function validate_shortcode( $shortcode ) {
        $shortcode['settings'] = Utils::maybe_json_decode( $shortcode['settings'] );
        $setting_columns = array_column( $shortcode['settings'], 'name' );
        if ( empty( $setting_columns ) ) {
            $base_settings = new Shortcode_Editor();
            $shortcode['settings'] = $base_settings->values_to_settings( $shortcode['settings'] );
        }
        $shortcode = new Shortcode_Editor($shortcode);
        // This class will handle the Sanitization & Validation.
        return $shortcode;
    }

    public function ajax_create_shortcode() {
        global $wpdb;
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $raw_settings = ( isset( $_REQUEST['settings'] ) ? wp_unslash( $_REQUEST['settings'] ) : '' );
        if ( empty( $raw_settings ) ) {
            wp_send_json_error( esc_html__( 'Invalid settings data.', 'wps-team' ), 400 );
        }
        // phpcs:ignore WordPress.Security.NonceVerification
        $name = ( isset( $_REQUEST['name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['name'] ) ) : '' );
        $shortcode_name = ( $name !== '' ? $name : 'Undefined' );
        // Validate shortcode (sanitize inside the class)
        $shortcode = $this->validate_shortcode( [
            'id'       => uniqid(),
            'name'     => $shortcode_name,
            'settings' => $raw_settings,
        ] );
        $data = [
            'name'       => $shortcode->get_data( 'name' ),
            'settings'   => Utils::maybe_json_encode( $shortcode->get_settings_value() ),
            'created_at' => current_time( 'mysql' ),
            'updated_at' => current_time( 'mysql' ),
        ];
        $wpdb->insert( "{$wpdb->prefix}wps_team", $data, $this->db_columns_format() );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        if ( $wpdb->last_error !== '' ) {
            wp_send_json_error( Utils::db_last_error_message(), 500 );
        }
        $data['id'] = $wpdb->insert_id;
        wp_cache_set( "wps_team_shortcode_{$wpdb->insert_id}", $data, 'wps_team' );
        do_action( 'wps_shortcode_created', $wpdb->insert_id );
        wp_send_json_success( [
            'message' => sprintf( '<strong>%s</strong> %s', _x( 'Congrats!', 'Dashboard', 'wps-team' ), _x( 'Shortcode created successfully', 'Dashboard', 'wps-team' ) ),
            'data'    => $this->fetch_shortcode( $wpdb->insert_id ),
        ] );
    }

    public function ajax_delete_shortcode() {
        global $wpdb;
        // phpcs:ignore WordPress.Security.NonceVerification
        $id = ( isset( $_REQUEST['id'] ) ? absint( wp_unslash( $_REQUEST['id'] ) ) : 0 );
        if ( !$id ) {
            wp_send_json_error( esc_html__( 'Invalid shortcode ID.', 'wps-team' ), 400 );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->delete( "{$wpdb->prefix}wps_team", [
            'id' => $id,
        ], ['%d'] );
        if ( $wpdb->last_error !== '' ) {
            wp_send_json_error( Utils::db_last_error_message(), 500 );
        }
        wp_cache_delete( "wps_team_shortcode_{$id}", 'wps_team' );
        do_action( 'wps_shortcode_deleted', $id );
        wp_send_json_success( [
            'message' => sprintf( '<strong>%s</strong> %s', esc_html_x( 'Done!', 'Dashboard', 'wps-team' ), esc_html_x( 'Shortcode deleted successfully', 'Dashboard', 'wps-team' ) ),
            'data'    => [
                'id' => $id,
            ],
        ] );
    }

    public function ajax_clone_shortcode() {
        global $wpdb;
        // phpcs:ignore WordPress.Security.NonceVerification
        $clone_id = ( isset( $_REQUEST['clone_id'] ) ? absint( wp_unslash( $_REQUEST['clone_id'] ) ) : 0 );
        if ( !$clone_id ) {
            wp_send_json_error( _x( 'Clone Id not provided', 'Dashboard', 'wps-team' ), 400 );
        }
        $clone_shortcode = $this->fetch_shortcode( $clone_id );
        if ( empty( $clone_shortcode ) ) {
            wp_send_json_error( _x( 'Clone shortcode not found', 'Dashboard', 'wps-team' ), 404 );
        }
        $shortcode = $this->validate_shortcode( [
            'id'       => uniqid(),
            'name'     => $clone_shortcode['name'] . ' ' . _x( '- Cloned', 'Editor', 'wps-team' ),
            'settings' => $clone_shortcode['settings'],
        ] );
        $settings_data = $shortcode->get_settings_value();
        $data = array(
            "name"       => $shortcode->get_data( 'name' ),
            "settings"   => Utils::maybe_json_encode( $settings_data ),
            "created_at" => current_time( 'mysql' ),
            "updated_at" => current_time( 'mysql' ),
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $wpdb->insert( "{$wpdb->prefix}wps_team", $data, $this->db_columns_format() );
        if ( $wpdb->last_error !== '' ) {
            wp_send_json_error( Utils::db_last_error_message(), 500 );
        }
        $data['id'] = $wpdb->insert_id;
        wp_cache_set( "wps_team_shortcode_{$wpdb->insert_id}", $data, 'wps_team' );
        do_action( 'wps_shortcode_cloned', $wpdb->insert_id );
        wp_send_json_success( [
            'message' => sprintf( '<strong>%s</strong> %s', esc_html_x( 'Congrats!', 'Dashboard', 'wps-team' ), esc_html_x( 'Shortcode cloned successfully', 'Dashboard', 'wps-team' ) ),
            'data'    => $this->fetch_shortcode( $wpdb->insert_id ),
        ] );
    }

    public function ajax_temp_save_settings() {
        // phpcs:ignore WordPress.Security.NonceVerification
        $temp_key = ( isset( $_REQUEST['temp_key'] ) ? sanitize_key( wp_unslash( $_REQUEST['temp_key'] ) ) : 0 );
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $settings = ( isset( $_REQUEST['settings'] ) ? wp_unslash( $_REQUEST['settings'] ) : [] );
        if ( empty( $temp_key ) ) {
            wp_send_json_error( _x( 'No temp key provide', 'Editor', 'wps-team' ), 400 );
        }
        if ( empty( $settings ) ) {
            wp_send_json_error( _x( 'No temp settings provided', 'Editor', 'wps-team' ), 400 );
        }
        $shortcode = $this->validate_shortcode( [
            'id'       => $temp_key,
            'name'     => 'Fake Name',
            'settings' => $settings,
        ] );
        delete_transient( $temp_key );
        $settings_value = $shortcode->get_settings_value();
        set_transient( $temp_key, $settings_value, HOUR_IN_SECONDS );
        wp_send_json_success();
    }

    public function ajax_get_sort_data() {
        $data = [
            'posts'             => [],
            'group_terms'       => [],
            'location_terms'    => [],
            'language_terms'    => [],
            'specialty_terms'   => [],
            'gender_terms'      => [],
            'extra_one_terms'   => [],
            'extra_two_terms'   => [],
            'extra_three_terms' => [],
            'extra_four_terms'  => [],
            'extra_five_terms'  => [],
        ];
        foreach ( Utils::get_posts()->posts as $post ) {
            $data['posts'][] = [
                'ID'         => $post->ID,
                'post_title' => $post->post_title,
                'thumbnail'  => get_the_post_thumbnail_url( $post->ID, 'thumbnail' ),
            ];
        }
        $taxonomies = Utils::get_active_taxonomies();
        foreach ( $taxonomies as $taxonomy ) {
            if ( $taxonomy !== 'wps-team-group' ) {
                continue;
            }
            $tax_root_key = Utils::get_taxonomy_root( $taxonomy, true );
            $terms = Utils::get_terms( $taxonomy, [
                'orderby' => 'term_order',
                'order'   => 'asc',
            ] );
            if ( !empty( $terms ) ) {
                $data[$tax_root_key . '_terms'] = array_map( [$this, 'map_term_for_sort'], $terms );
            }
        }
        wp_reset_postdata();
        wp_send_json_success( $data );
    }

    public function map_term_for_sort( $term ) {
        return [
            'term_id' => $term->term_id,
            'name'    => $term->name,
        ];
    }

    public function db_columns_format() {
        return array(
            'name'       => '%s',
            'settings'   => '%s',
            'created_at' => '%s',
            'updated_at' => '%s',
        );
    }

}
