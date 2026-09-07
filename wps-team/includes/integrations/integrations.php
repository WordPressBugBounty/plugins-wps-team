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
        // Divi Builder plugin.
        if ( defined( 'ET_BUILDER_PLUGIN_ACTIVE' ) && ET_BUILDER_PLUGIN_ACTIVE ) {
            return function_exists( 'et_core_is_builder_used_on_current_request' )
                && et_core_is_builder_used_on_current_request();
        }

        // Divi / Extra theme (builder bundled with theme).
        if ( defined( 'ET_BUILDER_THEME' ) && ET_BUILDER_THEME ) {
            return function_exists( 'et_core_is_builder_used_on_current_request' )
                && et_core_is_builder_used_on_current_request();
        }

        return false;
    }

}