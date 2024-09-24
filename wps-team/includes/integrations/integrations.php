<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Integrations {

    public $elementor;
    public $gutenberg;
    public $divi;
    
    public function __construct() {

        $this->elementor = new Integration_Elementor();
        $this->gutenberg = new Integration_Gutenberg();
        $this->divi = new Integration_Divi();

    }

    function is_divi_active() {
        if ( ! defined('ET_BUILDER_PLUGIN_ACTIVE') || ! ET_BUILDER_PLUGIN_ACTIVE ) return false;
        return et_core_is_builder_used_on_current_request();
    }

}