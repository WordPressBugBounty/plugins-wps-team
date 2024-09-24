<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Elementor extends Integration {

    public function __construct() {
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widget' ] );
        add_action( 'elementor/elements/categories_registered', [$this, 'add_widget_category'] );
        add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'print_editor_styles' ] );
        add_action( 'elementor/preview/enqueue_styles', [ $this, 'print_preview_styles' ] );
        add_action( 'elementor/preview/enqueue_scripts', [ $this, 'print_preview_scripts' ] );
    }

    public function register_widget() {
        \Elementor\Plugin::instance()->widgets_manager->register( new Elementor_Widget() );
    }

    public function add_widget_category( $elements_manager ) {
    
        $elements_manager->add_category( 'wpspeedo', [
            'title' => _x( 'WPSpeedo Widgets', 'Elementor Widget', 'wpspeedo-team' ),
            'icon' => 'fa fa-plug',
        ]);
    
    }
    
    public function print_editor_styles() {
        printf( '<style>body #elementor-panel-elements-wrapper .icon .wpspeedo_team{background:url("%s") no-repeat center center;background-size:contain;height:28px;display:block;}</style>', esc_url_raw( Utils::get_plugin_icon() ) );
    }

    public function print_preview_styles() {
        
        plugin()->assets->register_assets();
        plugin()->assets->build_assets_data_preview();

        plugin()->assets->enqueue_font_assets( plugin()->assets->assets['fonts'] );
        plugin()->assets->enqueue_style_assets( plugin()->assets->assets['styles'] );

    }

    public function print_preview_scripts() {
        
        plugin()->assets->register_assets();
        plugin()->assets->build_assets_data_preview();

        plugin()->assets->enqueue_script_assets( plugin()->assets->assets['scripts'] );

        wp_enqueue_script( plugin()->assets->asset_handler() . '-elementor-preview', plugin_dir_url( __FILE__ ) . 'preview.js', ['jquery'], WPS_TEAM_VERSION, true );

    }

}