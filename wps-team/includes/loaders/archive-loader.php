<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Archive_Loader extends Attribute_Manager {

    use Setting_Methods;

    public $id = null;

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

        $this->set_social_attributes();

    }

    public function set_social_attributes() {

        $theme_defaults = [];
        $setting_atts = [];

        $setting_atts['shape'] = $this->get_setting( 'social_links_shape' );
        $setting_atts['bg_color_type'] = $this->get_setting( 'social_links_bg_color_type' );
        $setting_atts['bg_color_type_hover'] = $this->get_setting( 'social_links_bg_color_type_hover' );
        $setting_atts['color_type'] = $this->get_setting( 'social_links_color_type' );
        $setting_atts['color_type_hover'] = $this->get_setting( 'social_links_color_type_hover' );

        $social_classes = Utils::get_social_classes( $theme_defaults, $setting_atts );
        
        $this->add_attribute( 'social', 'class', $social_classes );

    }
    
}