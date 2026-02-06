<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Integration {

    static function get_shortcodes() {
        return array_map( function( $item ) {
            return [
                'name' => $item['name'],
                'id'   => $item['id']
            ];
        }, Utils::get_all_shortcodes() );
    }
    
    static function render_shortcode( $id ) {
        return do_shortcode( sprintf("[wpspeedo-team id=%s]", $id ) );
    }
    
    static function get_empty_message() {
        return sprintf( '<div class="wps--empty-message">%s</div>', esc_html__( 'Please Select a Shortcode from the Dropdown', 'wps-team' ) );
    }
    
    function load_assets() {

        plugin()->assets->register_assets();

		wp_enqueue_style( 'wpspeedo-swiper' );
		wp_enqueue_style( 'wpspeedo-magnific-popup' );
        
		wp_enqueue_script( 'wpspeedo-swiper' );
		wp_enqueue_script( 'wpspeedo-magnific-popup' );
		wp_enqueue_script( 'wpspeedo-isotope' );

		wp_enqueue_style( plugin()->assets->asset_handler() );
		wp_enqueue_script( plugin()->assets->asset_handler() );
    }

    static function shortcode_default_option() {
        return __( 'Select a Shortcode', 'wps-team' );
    }

    // abstract function get_shortcode_options();

}