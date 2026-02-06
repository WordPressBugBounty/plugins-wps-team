<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_WPBakery extends Integration {

    public function __construct() {
        add_action( 'vc_before_init', [ $this, 'register_widget' ] );
        add_action( 'admin_footer', [$this, 'print_preview_scripts'] );
        add_action( 'vc_load_iframe_jscss', [$this, 'frontend_enqueue_scripts'] );
    }

    public function register_widget() {

        $params = [

            'name' => 'WPS Team',
            'base' => 'wpspeedo-team',
            'description' => esc_html_x( 'Display Team Members Created by WPS Team plugin', 'WPBakery Page Builder', 'wps-team' ),
            'category' => 'WPSpeedo',
            'icon' => WPS_TEAM_URL . '/images/icon-colored.svg',
            'params' => [
                [
                    'type' => 'dropdown',
                    'heading' => esc_html_x( 'Select Shortcode', 'WPBakery Page Builder', 'wps-team' ),
                    'param_name' => 'id',
                    'value' => $this->get_shortcode_list( true )
                ]
            ]
        
        ];
        
        vc_map( $params );
    }

    public static function get_shortcode_list( $reverse = false ) {

        $shortcodes = Integration::get_shortcodes();
        
        if ( !empty($shortcodes) ) {
            
            $shortcodes = [ Integration::shortcode_default_option() ] + wp_list_pluck( $shortcodes, 'name', 'id' );

            if ( ! $reverse ) return $shortcodes;

            return array_flip( $shortcodes );
        }

        return [];

    }

    public function frontend_enqueue_scripts() {
        $this->load_assets();
    }

    public function print_preview_scripts() {
        wp_enqueue_script( plugin()->assets->asset_handler() . '-wpbakery-preview', plugin_dir_url( __FILE__ ) . 'editor.min.js', ['jquery'], WPS_TEAM_VERSION, true );
    }

}