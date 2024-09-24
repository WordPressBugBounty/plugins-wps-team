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

        $theme_defaults = [];

        $setting_atts = [
            'shape'                 => Utils::get_setting( 'social_links_shape' ),
            'bg_color_type'         => Utils::get_setting( 'social_links_bg_color_type' ),
            'bg_color_type_hover'   => Utils::get_setting( 'social_links_bg_color_type_hover' ),
            'color_type'            => Utils::get_setting( 'social_links_color_type' ),
            'color_type_hover'      => Utils::get_setting( 'social_links_color_type_hover' )
        ];
        
        $social_classes = Utils::get_social_classes( $theme_defaults, $setting_atts );
        
        $this->add_attribute( 'social', 'class', $social_classes );

    }
    
}