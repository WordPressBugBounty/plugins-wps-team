<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Initialize {
    public function __construct() {
        if ( !version_compare( PHP_VERSION, '7.0', '>=' ) ) {
            return add_action( 'admin_notices', array($this, 'fail_php_version') );
        }
        if ( !version_compare( get_bloginfo( 'version' ), '5.9', '>=' ) ) {
            return add_action( 'admin_notices', array($this, 'fail_wp_version') );
        }
        add_action( 'plugins_loaded', array($this, 'load_freemius'), 5 );
        add_action( 'plugins_loaded', array($this, 'load_textdomain') );
        add_action( 'plugins_loaded', array($this, 'save_install_time') );
        add_action( 'plugins_loaded', array($this, 'initialize') );
        add_action( 'admin_head', [$this, 'load_admin_icon_css'] );
    }

    function load_freemius() {
        require_once WPS_TEAM_PATH . 'freemius.php';
    }

    function load_admin_icon_css() {
        echo "<style>#adminmenu .menu-icon-wps-team-members .wp-menu-image img{padding-top:8px;width:22px;opacity:.8;height:auto;}</style>";
    }

    public function load_textdomain() {
    }

    public function save_install_time() {
        $installed_time = get_option( '_wps_team_installed_time' );
        if ( !$installed_time ) {
            update_option( '_wps_team_installed_time', time() );
        }
    }

    public function fail_php_version() {
        $message = '<strong>WPS Team</strong> ' . sprintf( 
            /* translators: %s: Required PHP version */
            esc_html_x( 'plugin requires PHP version %s+, plugin is currently NOT RUNNING.', 'Dashboard', 'wps-team' ),
            '5.6'
         );
        echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
    }

    public function fail_wp_version() {
        $message = '<strong>WPS Team</strong> ' . sprintf( 
            /* translators: %s: Required WordPress version */
            esc_html_x( 'plugin requires WordPress version %s+. Because you are using an earlier version, the plugin is currently NOT RUNNING.', 'Dashboard', 'wps-team' ),
            '5.0'
         );
        echo wp_kses_post( sprintf( '<div class="error">%s</div>', wpautop( $message ) ) );
    }

    function maybe_update_version() {
        if ( WPS_TEAM_VERSION === get_option( 'wps_team_version' ) ) {
            return false;
        }
        Upgrader::instance( get_option( 'wps_team_version' ), WPS_TEAM_VERSION );
        update_option( 'wps_team_version', WPS_TEAM_VERSION );
        plugin()->assets->assets_purge_all();
        return true;
    }

    function maybe_update_pro_status() {
        $is_pro_active = get_option( 'wps_team_is_pro_active' );
        $current_pro_status = ( wps_team_fs()->can_use_premium_code() ? 'yes' : 'no' );
        if ( $is_pro_active === $current_pro_status ) {
            return;
        }
        update_option( 'wps_team_is_pro_active', $current_pro_status );
        plugin()->assets->assets_purge_all();
    }

    public function initialize() {
        require_once WPS_TEAM_INC_PATH . 'autoloader.php';
        require_once WPS_TEAM_INC_PATH . 'thumbly.php';
        Autoloader::run();
        Plugin::instance();
        $this->maybe_update_pro_status();
        $this->maybe_update_version();
    }

}

new Initialize();
function plugin() {
    return Plugin::instance();
}
