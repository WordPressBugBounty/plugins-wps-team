<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integration_Divi extends Integration {

    public $name;
    public $plugin_dir_url;

    public function __construct() {
        add_action( 'divi_extensions_init', [ $this, 'init' ] );

        // Divi 5 native module — self-guards when Divi 5 is not active.
        $d5_bootstrap = __DIR__ . '/d5/bootstrap.php';
        if ( file_exists( $d5_bootstrap ) ) {
            require_once $d5_bootstrap;
        }
    }

    public function init() {

        $this->name = 'wpspeedo-team-divi';
        $this->plugin_dir_url = WPS_TEAM_URL . 'includes/integrations/divi';

        add_action( 'et_builder_modules_loaded', [ $this, 'load_divi_module' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_head', [ $this, 'editor_style' ] );
    }

    /**
     * Whether Divi 5 builder is active.
     */
    public static function is_divi_5() {
        return function_exists( 'et_builder_d5_enabled' ) && et_builder_d5_enabled();
    }

    /**
     * Whether the Divi Visual Builder / Front-end Builder is enabled.
     */
    public static function is_fb_enabled() {
        return function_exists( 'et_core_is_fb_enabled' ) && et_core_is_fb_enabled();
    }

    public function editor_style() {

        // Divi 5 uses its own module list UI — skip Divi 4 module-list CSS.
        if ( self::is_divi_5() ) {
            return;
        }

        if ( ! self::is_fb_enabled() ) {
            return;
        }

        $icon = WPS_TEAM_URL . '/images/icon.svg';

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

    }

    public function enqueue_scripts() {

        // Divi 4 Visual Builder assets — skip when Divi 5 VB is active.
        if ( self::is_fb_enabled() && ! self::is_divi_5() ) {

            plugin()->assets->register_assets();
            plugin()->assets->build_assets_data_preview();
            plugin()->assets->enqueue_font_assets( plugin()->assets->assets['fonts'] );
            plugin()->assets->enqueue_style_assets( plugin()->assets->assets['styles'] );
            plugin()->assets->enqueue_script_assets( plugin()->assets->assets['scripts'] );

            $bundle_url = "{$this->plugin_dir_url}/builder.min.js";
            wp_enqueue_script( "{$this->name}-builder", $bundle_url, [ 'react-dom' ], WPS_TEAM_VERSION, true );

        }

        // Divi 4 frontend AJAX re-init helper — only needed for Divi 4 computed preview.
        if ( ! self::is_divi_5() ) {
            $bundle_url = "{$this->plugin_dir_url}/frontend.min.js";
            wp_enqueue_script( "{$this->name}-frontend", $bundle_url, [ 'jquery' ], WPS_TEAM_VERSION, true );
        }

    }

    function load_divi_module() {
        new Divi_Module();
    }

}
