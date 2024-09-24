<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Plugin {
    public static $instance = null;

    public $tabs = [];

    public $controls_manager;

    public $admin;

    public $translations;

    public $translations_adv;

    public $api;

    public $assets;

    public $notifications;

    public $integrations;

    private function __construct() {
        $this->load();
    }

    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function load() {
        $this->set_tabs();
        $this->translations = new Translations();
        $this->controls_manager = new Controls_Manager();
        $this->admin = new Admin();
        new Plugin_Hooks();
        $this->api = new API();
        $this->notifications = new Notifications();
        $this->assets = new Assets();
        $this->integrations = new Integrations();
        new Data();
        new Shortcode();
        new Demo_Import();
        do_action( 'wpspeedo_team/loaded', $this );
    }

    public function set_tabs() {
        $this->tabs = [
            'general'  => [
                'key'   => 'general',
                'title' => 'General',
                'icon'  => '<i class="fas fa-globe"></i>',
            ],
            'elements' => [
                'key'   => 'elements',
                'title' => 'Elements',
                'icon'  => '<i class="fas fa-th-list"></i>',
            ],
            'query'    => [
                'key'   => 'query',
                'title' => 'Query',
                'icon'  => '<i class="fas fa-database"></i>',
            ],
            'style'    => [
                'key'   => 'style',
                'title' => 'Style',
                'icon'  => '<i class="fas fa-palette"></i>',
            ],
            'typo'     => [
                'key'   => 'typo',
                'title' => 'Typo',
                'icon'  => '<i class="fas fa-text-height"></i>',
            ],
            'advance'  => [
                'key'   => 'advance',
                'title' => 'Advance',
                'icon'  => '<i class="fas fa-tools"></i>',
            ],
        ];
        add_filter( 'wpspeedo_team/controls/tabs', function () {
            return wp_list_pluck( $this->tabs, 'title' );
        } );
        add_filter( 'wpspeedo_team/controls/default_tab', function () {
            return array_key_first( $this->tabs );
        } );
    }

    public function get_install_time() {
        return get_option( '_wpspeedo_team_installed_time' );
    }

}
