<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Gutenberg extends Integration {

    public function __construct() {
        add_action( 'init', [ $this, 'initialize' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'editor_assets' ] );
        add_action( 'enqueue_block_assets', [ $this, 'editor_assets' ] );
    }

    public function initialize() {

        register_block_type( dirname(__FILE__) . '/block.json', array(
            'editor_script' => plugin()->assets->asset_handler() . '-block',
            'render_callback' => [$this, 'render_wpspeedo_block']
        ));

    }

    public function render_wpspeedo_block( $attributes ) {

        if ( !empty($_REQUEST['action']) && $_REQUEST['action'] == 'wps_team_block_data' ) {
            global $wps_team_is_builder;
            $wps_team_is_builder = true;
        }

        $shortcode_id = ( ! empty($attributes) && ! empty($attributes['shortcode']) ) ? (int) $attributes['shortcode'] : '';

        if ( empty($shortcode_id) ) {
            return Integration::display_empty_message();
        } else {
            return Integration::render_shortcode( $shortcode_id );
        }


    }

    public function editor_assets() {

        if ( ! is_admin() ) return;

        $this->load_assets();

        $asset_handler = plugin()->assets->asset_handler() . '-block';
        
        wp_enqueue_script( $asset_handler, plugin_dir_url( __FILE__ ) . 'block.min.js', ['wp-blocks', 'wp-server-side-render', 'jquery'], WPS_TEAM_VERSION, true );

        wp_add_inline_style( 'wp-block-editor', $this->get_block_css() );

        $wps_widget_block_data = array(
            'title' => __( 'WPS Team Members', 'wpspeedo-team' ),
            'description' => __( 'Display Team Members in Grid, Carousel & Filter Layouts', 'wpspeedo-team' ),
            'select_shortcode' => __( 'Select Shortcode', 'wpspeedo-team' ),
            'shortcodes' => (object) self::get_shortcode_list()
		);

		wp_localize_script( $asset_handler, 'wps_team_block_data', $wps_widget_block_data );

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

    public function get_block_css() {

        ob_start(); ?>
    
        .wps-team--toolbar {
            padding: 20px;
            border: 1px solid #3a3a42;
            border-radius: 2px;
        }

        .wps-team--toolbar label {
            display: block;
            margin-bottom: 6px;
            margin-top: -6px;
        }

        .wps-team--toolbar select {
            width: 260px;
            max-width: 100% !important;
            line-height: 38px !important;
            height: 38px;
        }

        .wps-team--block .wps-widget--team .wps-team--member-title a {
            color: inherit;
        }

        .wps-team--block .wps-widget--edit-link {
            position: relative;
            -webkit-transform: none;
            transform: none;
            width: 39px;
            margin-top: 14px;
        }
    
        <?php return ob_get_clean();
    
    }

}