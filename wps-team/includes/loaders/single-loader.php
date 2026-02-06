<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Single_Loader extends Attribute_Manager {

    use Setting_Methods;

    function __construct() {
        $this->set_attributes();
        return $this;
    }

    public function set_attributes() {
        
        $this->add_attribute( 'wrapper', 'class', [
            'wps-container wps-widget wps-widget--team',
        ]);
        
        $this->add_attribute( 'wrapper_inner', 'class', [
            'wps-container--inner'
        ]);

        $this->add_attribute( 'single_item_row', 'class', 'wps-row' );
        $this->add_attribute( 'single_item_col', 'class', 'wps-col' );

        $this->add_attribute( 'wps-widget-single-page--wrapper', 'class', [
            'wps-container wps-widget--team wps-widget-container-single wps-team--social-hover-up'
        ]);

        $this->set_social_attributes();

    }

    public function set_social_attributes() {
        
        $social_classes = Utils::get_social_classes( false, [], 'single' );
        
        $this->add_attribute( 'social', 'class', $social_classes );

    }
    
}