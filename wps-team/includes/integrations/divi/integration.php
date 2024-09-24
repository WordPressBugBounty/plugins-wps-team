<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Divi extends Integration {

    public $name;
    public $plugin_dir_url;

    public function __construct() {
        add_action( 'divi_extensions_init', [ $this, 'init' ] );
    }

    public function init() {

        $this->name = 'wpspeedo-team-divi';
        $this->plugin_dir_url = WPS_TEAM_URL . 'includes/integrations/divi';

        add_action( 'et_builder_modules_loaded', [ $this, 'load_divi_module' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_head', [ $this, 'editor_style' ] );
    }

    public function editor_style() {

        if ( ! \et_core_is_fb_enabled() ) return;

        $icon = WPS_TEAM_URL . '/images/icon.svg';

        ob_start();

        ?>
        <style>

            .et-db #et-boc .et-l .et-fb-modules-list ul > li.wps_team_divi:before {
                background: url('<?php echo esc_attr( $icon ); ?>') no-repeat center center;
                background-size: contain;
                content: "";
                height: 28px;
            }
            
            .et-db #et-boc .et-l .et-fb-modules-list ul > li.wps_team_divi {
                height: 67px;
            }

        </style>
        <?php

        echo ob_get_clean();

    }

    public function enqueue_scripts() {

        if ( \et_core_is_fb_enabled() ) {

            plugin()->assets->register_assets();
            plugin()->assets->build_assets_data_preview();
            plugin()->assets->enqueue_font_assets( plugin()->assets->assets['fonts'] );
            plugin()->assets->enqueue_style_assets( plugin()->assets->assets['styles'] );
            plugin()->assets->enqueue_script_assets( plugin()->assets->assets['scripts'] );

            $bundle_url   = "{$this->plugin_dir_url}/builder.min.js";
            wp_enqueue_script( "{$this->name}-builder", $bundle_url, ['react-dom'], WPS_TEAM_VERSION, true );

        }

        $bundle_url   = "{$this->plugin_dir_url}/frontend.min.js";
        wp_enqueue_script( "{$this->name}-frontend", $bundle_url, ['jquery'], WPS_TEAM_VERSION, true );

    }

    function load_divi_module() {
        new Divi_Module();
    }

}