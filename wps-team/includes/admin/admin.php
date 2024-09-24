<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
final class Admin {
    public function __construct() {
        add_action( 'admin_menu', [$this, 'register_admin_menu'] );
        add_action( 'admin_enqueue_scripts', [$this, 'admin_scripts'] );
        add_action( 'admin_enqueue_scripts', [$this, 'meta_box_scripts'], 999 );
        $this->maybe_create_db_table();
        $this->load_editor_template();
        return $this;
    }

    public function load_editor_template() {
        if ( !Utils::is_shortcode_preview() ) {
            return;
        }
        Utils::do_not_cache();
        add_action( 'template_redirect', function () {
            global $shortcode_loader;
            $settings = (array) Utils::get_temp_settings();
            $settings = apply_filters( 'wpspeedo_team/shortcode_settings/', $settings, $shortcode_loader );
            $shortcode_loader = new Shortcode_Loader([
                'id'       => 'preview',
                'settings' => $settings,
                'mode'     => 'preview',
            ]);
            $shortcode_loader->load_template();
            die;
        } );
        add_action( 'show_admin_bar', '__return_false' );
    }

    public function add_shortcode_body_class( $classes ) {
        if ( Utils::is_shortcode_preview() ) {
            return array_merge( $classes, array('gs-sm-sec-shortcode-preview--page') );
        }
        return $classes;
    }

    public function register_admin_menu() {
        $capability = 'manage_options';
        $callback = [$this, 'plugin_admin_page'];
        $shortcode_menu_title = _x( 'Shortcodes', 'Menu Label', 'wpspeedo-team' );
        $settings_menu_title = _x( 'Settings', 'Menu Label', 'wpspeedo-team' );
        $order_menu_title = _x( 'Sort Order', 'Menu Label', 'wpspeedo-team' );
        $get_help_menu = _x( 'Get Help', 'Menu Label', 'wpspeedo-team' );
        $tools_menu = _x( 'Tools', 'Menu Label', 'wpspeedo-team' );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $shortcode_menu_title,
            $shortcode_menu_title,
            $capability,
            'wps-team',
            $callback,
            50
        );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $settings_menu_title,
            $settings_menu_title,
            $capability,
            'wps-team#/settings',
            $callback,
            60
        );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $order_menu_title,
            $order_menu_title,
            $capability,
            'wps-team#/custom-order',
            $callback,
            70
        );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $tools_menu,
            $tools_menu,
            $capability,
            'wps-team#/tools',
            $callback,
            80
        );
        add_submenu_page(
            Utils::get_top_label_menu(),
            $get_help_menu,
            $get_help_menu,
            $capability,
            'wps-team#/get-help',
            $callback,
            100
        );
        if ( wps_team_fs()->is_not_paying() && !wps_team_fs()->is_trial() ) {
            add_submenu_page(
                Utils::get_top_label_menu(),
                _x( 'WPS Team - Trial', 'Menu Label', 'wpspeedo-team' ),
                _x( 'Free Trial', 'Menu Label', 'wpspeedo-team' ),
                'manage_options',
                wps_team_fs()->get_trial_url()
            );
        }
    }

    public function plugin_admin_page() {
        ?>
        <div class="wpspeedo--plugin-wrap wpspeedo--team-members-wrap">
            <div class="wpspeedo--app-container">
                <div id="wpspeedo--app">
                    <router-view :key="$route.fullPath"></router-view>
                </div>
            </div>
        </div>
        <?php 
    }

    public function maybe_create_db_table() {
        global $wpdb;
        $wps_team_db_ver = '1.0';
        if ( get_option( "{$wpdb->prefix}wps_team_db_ver" ) == $wps_team_db_ver ) {
            return;
        }
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wps_team (\n            id BIGINT(20) unsigned NOT NULL AUTO_INCREMENT,\n            name TEXT NOT NULL,\n            settings LONGTEXT NOT NULL,\n            created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',\n            updated_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',\n            PRIMARY KEY (id)\n        )" . $wpdb->get_charset_collate() . ";";
        if ( get_option( "{$wpdb->prefix}wps_team_db_ver" ) < $wps_team_db_ver ) {
            dbDelta( $sql );
        }
        update_option( "{$wpdb->prefix}wps_team_db_ver", $wps_team_db_ver );
    }

    public function admin_scripts( $hook ) {
        if ( Utils::post_type_name() . '_page_wps-team' != $hook ) {
            return;
        }
        wp_register_style(
            'wpspeedo-fontawesome--all',
            WPS_TEAM_ASSET_URL . 'libs/fontawesome/css/all.min.css',
            '',
            WPS_TEAM_VERSION
        );
        wp_enqueue_style(
            'wpspeedo-team-admin',
            WPS_TEAM_ADMIN_ASSET_URL . 'css/style.min.css',
            ['wpspeedo-fontawesome--all'],
            WPS_TEAM_VERSION
        );
        $shortcode_editor = new Shortcode_Editor();
        $settings_editor = new Settings_Editor();
        $data = array(
            'nonce'     => wp_create_nonce( '_wpspeedo_team_nonce' ),
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'adminurl'  => admin_url(),
            'siteurl'   => home_url(),
            'pluginurl' => WPS_TEAM_URL,
            'version'   => WPS_TEAM_VERSION,
            'action'    => 'wpspeedo_team_ajax_handler',
            'fields'    => $shortcode_editor->get_controls(),
            'tabs'      => plugin()->tabs,
            'settings'  => $settings_editor->get_controls(),
            'icon_data' => Icon_Manager::get_icon_manager_tabs_config(),
            'is_pro'    => false,
        );
        $is_whitelabeled = wps_team_fs()->is_whitelabeled();
        $is_paying_or_trial = wps_team_fs()->is_paying_or_trial();
        $data['show_upgrade'] = !$is_whitelabeled && !$is_paying_or_trial;
        $data['translations'] = Utils::get_strings();
        $data['demo_data_status'] = Utils::get_demo_data_status();
        wp_register_script(
            'wpspeedo-team-admin',
            WPS_TEAM_ADMIN_ASSET_URL . 'js/script.min.js',
            ['jquery', 'underscore', 'jquery-ui-sortable'],
            WPS_TEAM_VERSION,
            true
        );
        wp_localize_script( 'wpspeedo-team-admin', '_wps_team_data', $data );
        wp_enqueue_script( 'wpspeedo-team-admin' );
    }

    public function meta_box_scripts( $hook ) {
        if ( !in_array( $hook, ['post.php', 'post-new.php'] ) ) {
            return;
        }
        if ( Utils::post_type_name() !== get_post_type() ) {
            return;
        }
        wp_register_style(
            'wpspeedo-fontawesome--all',
            WPS_TEAM_ASSET_URL . 'libs/fontawesome/css/all.min.css',
            '',
            WPS_TEAM_VERSION
        );
        wp_enqueue_style(
            'wpspeedo-team--meta-box',
            WPS_TEAM_ADMIN_ASSET_URL . 'css/meta-box.min.css',
            ['wpspeedo-fontawesome--all'],
            WPS_TEAM_VERSION
        );
        $meta_box_editor = new Meta_Box_Editor();
        $data = [
            'nonce'     => wp_create_nonce( '_wpspeedo_team_nonce' ),
            'ajaxurl'   => admin_url( 'admin-ajax.php' ),
            'adminurl'  => admin_url(),
            'siteurl'   => home_url(),
            'action'    => 'wpspeedo_team_ajax_handler',
            'fields'    => $meta_box_editor->get_controls(),
            'icon_data' => Icon_Manager::get_icon_manager_tabs_config(),
        ];
        wp_register_script(
            'wpspeedo-team--meta-box',
            WPS_TEAM_ADMIN_ASSET_URL . 'js/meta-box.min.js',
            ['jquery', 'underscore'],
            WPS_TEAM_VERSION,
            true
        );
        wp_localize_script( 'wpspeedo-team--meta-box', '_wps_team_data', $data );
        wp_enqueue_script( 'wpspeedo-team--meta-box' );
    }

}
