<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

trait AJAX_Handler {

    /**
     * Admin AJAX routes that change site-wide settings or sensitive data.
     *
     * @return string[]
     */
    protected function get_manage_options_ajax_routes() {
        return [
            'get_settings',
            'save_settings',
            'save_taxonomy_settings',
            'export_data',
            'import_data',
            'parse_csv',
            'enable_taxonomies_for_bulk_import',
            'import_csv',
            'erase_reset_data',
            'import_demo_data',
            'remove_demo_data',
            'purge_cache',
            'get_template_status',
        ];
    }

    protected function current_user_can_ajax_route( $route ) {
        $route = sanitize_key( (string) $route );

        if ( in_array( $route, $this->get_manage_options_ajax_routes(), true ) ) {
            return current_user_can( 'manage_options' );
        }

        return current_user_can( 'edit_posts' );
    }

    public function set_ajax_scope_hooks() {

        add_action( 'wp_ajax_' . $this->ajax_key . $this->ajax_scope, array($this, 'handle_request_route') );
        
        if ( property_exists( $this, 'ajax_key_public' ) ) {
            add_action( 'wp_ajax_' . $this->ajax_key_public . $this->ajax_scope, array($this, 'handle_request_route_public') );
            add_action( 'wp_ajax_nopriv_' . $this->ajax_key_public . $this->ajax_scope, array($this, 'handle_request_route_public') );
        }
        
    }

    public function handle_request_route() {

        check_ajax_referer( '_' . $this->ajax_key . '_nonce' );

        // phpcs:ignore WordPress.Security.NonceVerification
        $route_key = ! empty( $_REQUEST['route'] ) ? sanitize_key( wp_unslash( $_REQUEST['route'] ) ) : '';

        if ( $route_key && ! $this->current_user_can_ajax_route( $route_key ) ) {
            wp_send_json_error( esc_html__( 'You do not have permission to perform this action.', 'wps-team' ), 403 );
        }

        if ( $route_key ) {
            $route = 'ajax_' . $route_key;
            if ( method_exists( $this, $route ) ) {
                $this->$route();
            }
        }

        wp_send_json_error( _x('Something is wrong, request not found', 'Dashboard', 'wps-team'), 404 );
    }

    public function handle_request_route_public() {

        if ( !empty($_GET['route']) && $_GET['route'] === 'get_fresh_nonce' ) {
            $this->ajax_public_get_fresh_nonce();
        }

        check_ajax_referer( '_' . $this->ajax_key_public . '_nonce' );

        if ( !empty($_REQUEST['route']) ) {
            $route = 'ajax_public_' . sanitize_key($_REQUEST['route']);
            if ( method_exists($this, $route) ) {
                $this->$route();
            }
        }

        wp_send_json_error( _x('Something is wrong, request not found', 'Public', 'wps-team'), 404 );
    }

}