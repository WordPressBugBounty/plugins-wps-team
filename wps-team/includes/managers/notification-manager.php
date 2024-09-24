<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Notification_Manager extends Base_Notification {

    public function __construct() {

        // Register Rating_Notice
        $this->register( new Rating_Notice );

        // Register Demo_Import_Notice
        $this->register( new Demo_Import_Notice );

    }

    public function load_script() {
        // if ( is_customize_preview() ) return;
        // wp_enqueue_script( 'wpspeedo-notices', plugins_url( 'notice-manager.js', __FILE__ ), ['jquery', 'common'], false, true );
        // wp_localize_script( 'wpspeedo-notices', 'wpspeedo_dismissible_notice', [ 'nonce' => wp_create_nonce('wpspeedo-dismissible-notice') ] );
    }

}