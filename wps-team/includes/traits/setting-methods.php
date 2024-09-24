<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

trait Setting_Methods {

    public function get_setting( String $setting, $field = 'value' ) {

        if ( ! isset( $this->settings[$setting] ) ) return null;

        $setting = $this->settings[$setting];

        if ( is_array($field) ) {
            return array_intersect_key( $setting, array_flip($field) );
        }

        if ( $field == 'all' ) return $setting;
        
        return $setting[$field];

    }

    public function set_setting( String $setting, $value ) {

        if ( ! isset( $this->settings[$setting] ) ) return;

        $this->settings[$setting]['value'] = $value;

    }

    public function get_shortcode_id( $id ) {
        return 'wps-widget--team-' . $id;
    }
    
    public function shortcode_selector( $id ) {
        return sprintf( '#wps-widget--team-%s.wps-widget--team', $id );
    }
    
    public function shortcode_selector_popup( $id ) {
        return sprintf( '.wps-widget--team.wps-widget-container-modal.wps-team-%s--popup', $id );
    }
    
    public function shortcode_selector_side_panel( $id ) {
        return sprintf( '.wps-widget--team.wps-widget-container-side-panel.wps-team-%s--side-panel', $id );
    }
    
    public function element_visibility( $element_setting_key, $default ) {
		$visibility = $this->get_setting( $element_setting_key );
        if ( $visibility == 'true' || $visibility == 'false' ) return wp_validate_boolean( $visibility );
        return (bool) $default;
	}

    public function should_display( $element_setting_key, $default ) {
		$visibility = $this->get_setting( $element_setting_key );
        if ( $visibility == 'true' || $visibility == 'false' ) return wp_validate_boolean( $visibility );
        return (bool) $default;
	}
    
}
