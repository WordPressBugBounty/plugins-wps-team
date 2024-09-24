<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

trait AJAX_Handler {

    public function set_ajax_scope_hooks() {

        add_action( 'wp_ajax_' . $this->ajax_key . $this->ajax_scope, array($this, 'handle_request_route') );
        
        if ( property_exists( $this, 'ajax_key_public' ) ) {
            add_action( 'wp_ajax_' . $this->ajax_key_public . $this->ajax_scope, array($this, 'handle_request_route_public') );
            add_action( 'wp_ajax_nopriv_' . $this->ajax_key_public . $this->ajax_scope, array($this, 'handle_request_route_public') );
        }
        
    }

    public function handle_request_route() {

        check_ajax_referer( '_' . $this->ajax_key . '_nonce' );

        if ( !empty($_REQUEST['route']) ) {
            $route = 'ajax_' . sanitize_key($_REQUEST['route']);
            if ( method_exists($this, $route) ) {
                $this->$route();
            }
        }

        wp_send_json_error( _x('Something is wrong, request not found', 'Dashboard', 'wpspeedo-team'), 404 );
    }

    public function handle_request_route_public() {

        check_ajax_referer( '_' . $this->ajax_key_public . '_nonce' );

        if ( !empty($_REQUEST['route']) ) {
            $route = 'ajax_public_' . sanitize_key($_REQUEST['route']);
            if ( method_exists($this, $route) ) {
                $this->$route();
            }
        }

        wp_send_json_error( _x('Something is wrong, request not found', 'Public', 'wpspeedo-team'), 404 );
    }

}