<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Integration {

    static function get_shortcodes() {
        global $wpdb;
        return $wpdb->get_results( "SELECT id, name FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A );
    }
    
    static function render_shortcode( $id ) {
        return do_shortcode( sprintf("[wpspeedo-team id=%s]", $id ) );
    }
    
    static function display_empty_message() {
        return sprintf( '<div class="wps--empty-message">%s</div>', 'Please Select a Shortcode from the Dropdown' );
    }
    
    function load_assets() {
        plugin()->assets->register_assets();
		wp_enqueue_style( 'wpspeedo-swiper' );
		wp_enqueue_style( 'wpspeedo-magnific-popup' );
		wp_enqueue_script( 'wpspeedo-swiper' );
		wp_enqueue_script( 'wpspeedo-magnific-popup' );
		wp_enqueue_script( 'wpspeedo-isotope' );
		wp_enqueue_script( 'wpspeedo-dot' );
		wp_enqueue_style( plugin()->assets->asset_handler() );
		wp_enqueue_script( plugin()->assets->asset_handler() );
    }

    static function shortcode_default_option() {
        return __( 'Select a Shortcode', 'wpspeedo-team' );
    }

    // abstract function get_shortcode_options();

}