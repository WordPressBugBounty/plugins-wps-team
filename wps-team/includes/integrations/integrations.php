<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integrations {
    
    public function __construct() {
        new Integration_Elementor();
        new Integration_Gutenberg();
        new Integration_Divi();
        new Integration_WPBakery();
    }

    function is_divi_active() {
        if ( ! defined('ET_BUILDER_PLUGIN_ACTIVE') || ! ET_BUILDER_PLUGIN_ACTIVE ) return false;
        return et_core_is_builder_used_on_current_request();
    }

}