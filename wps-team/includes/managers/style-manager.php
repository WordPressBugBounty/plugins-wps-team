<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Style_Manager extends Attribute_Manager {

    use Setting_Methods;

    public $settings = [];

    public $style = [];

    public $style_tablet = [];

    public $style_small_tablet = [];

    public $style_mobile = [];

    public function set_settings( $settings ) {
        $this->settings = $settings;
        return $this;
    }

    public function add_style_row( $selector, $out_css_props, $device = '' ) {

        $style = $device ? "style_$device" : 'style';

        if ( array_key_exists($selector, $this->$style) && empty($this->$style[$selector]) ) {
            $this->$style[$selector] = [];
        }

        $this->$style[$selector][] = $out_css_props;
    }

    public function add_style( $selector, $css_property, $setting_key, $field = 'value', $device = '' ) {

        $setting_key = $device ? $setting_key .'_'. $device : $setting_key;

        if ( ! is_array($field) ) $field = [$field];

        $setting = $this->get_setting( $setting_key, $field );

        $out_css_props = '';

        global $has_value;
        $has_value = false;

        try {

            $out_css_props = preg_replace_callback( '/{{([^}| ]*)}}/', function( $matches ) use ($setting, $has_value) {

                global $has_value;
                $parsed_value = '';
    
                if ( $matches[1] && isset( $setting[ $matches[1] ] ) ) {
                    $parsed_value = $setting[ $matches[1] ];
                }

                if ( $parsed_value !== '' ) $has_value = true;

                return $parsed_value;
    
            }, $css_property );

        } catch ( \Exception $e ) { return; }

        if ( $has_value ) {
            $this->add_style_row( $selector, $out_css_props, $device );
        }

    }

    public function add_responsive_style( $selector, $css_property, $setting_key, $field = 'value' ) {
        $this->add_style( $selector, $css_property, $setting_key, $field );
        $this->add_style( $selector, $css_property, $setting_key, $field, 'tablet' );
        $this->add_style( $selector, $css_property, $setting_key, $field, 'small_tablet' );
        $this->add_style( $selector, $css_property, $setting_key, $field, 'mobile' );
    }

    public function add_background_style( $selector, $setting_base, $prop, $device = '' ) {

        $has_bg =  $this->get_setting( $setting_base . 'background' );
        $type =    $this->get_setting( $setting_base . 'type' );
        $clr =     $this->get_setting( $setting_base . 'color' );

        if ( $has_bg === 'yes' && !empty($type) && !empty($clr) ) {

            if ( $type === 'classic' ) {

                $this->add_style_row( $selector, sprintf( '%s:%s', $prop, $clr ), $device );

            } else {

                $grad_type = $this->get_setting( $setting_base . 'gradient_type' );

                if ( !empty($grad_type) ) {
                    
                    $clr_stop =     $this->get_setting( $setting_base . 'color_stop' );
                    $clr_b =        $this->get_setting( $setting_base . 'color_b' );
                    $clr_b_stop =   $this->get_setting( $setting_base . 'color_b_stop' );
                    
                    $color_start = sprintf( '%s %s', $clr, $clr_stop . '%' );
                    $color_end = sprintf( '%s %s', $clr_b, $clr_b_stop . '%' );
                    
                    if ( $grad_type === 'linear' ) {
                        $grad_angle = $this->get_setting( $setting_base . 'gradient_angle' );
                        $gradient = sprintf( 'linear-gradient(%s, %s, %s)', $grad_angle . 'deg', $color_start, $color_end );
                    } else {
                        $grad_pos = $this->get_setting( $setting_base . 'gradient_position' );
                        $gradient = sprintf( 'radial-gradient(at %s, %s, %s)', $grad_pos, $color_start, $color_end );
                    }
    
                    $this->add_style_row( $selector, sprintf( '%s:%s', $prop, $gradient ), $device );
                }
            }
        }
    }

    public function add_typography_style( $selector, $setting_base, $device = '' ) {

        $font_family =      $this->get_setting( $setting_base . 'font_family' );
        $font_size =        $this->get_setting( $setting_base . 'font_size', 'all' );
        $font_weight =      $this->get_setting( $setting_base . 'font_weight' );
        $text_transform =   $this->get_setting( $setting_base . 'text_transform' );
        $font_style =       $this->get_setting( $setting_base . 'font_style' );
        $text_decoration =  $this->get_setting( $setting_base . 'text_decoration' );
        $line_height =      $this->get_setting( $setting_base . 'line_height', 'all' );
        $letter_spacing =   $this->get_setting( $setting_base . 'letter_spacing' );

        if ( !empty( $font_family ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s', 'font-family', $font_family ), $device );
        }

        if ( !empty( $font_size ) && !empty( $font_size['unit'] ) && ( !empty($font_size['value']) || $font_size['value'] == 0 ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s%s', 'font-size', $font_size['value'], $font_size['unit'] ), $device );
        }

        if ( !empty( $font_weight ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s', 'font-weight', $font_weight ), $device );
        }

        if ( !empty( $text_transform ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s', 'text-transform', $text_transform ), $device );
        }

        if ( !empty( $font_style ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s', 'font-style', $font_style ), $device );
        }

        if ( !empty( $text_decoration ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s', 'text-decoration', $text_decoration ), $device );
        }

        if ( !empty( $line_height ) && !empty( $line_height['unit'] ) && ( !empty($line_height['value']) || $line_height['value'] == 0 ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%s%s', 'line-height', $line_height['value'], $line_height['unit'] ), $device );
        }

        if ( !empty( $letter_spacing ) ) {
            $this->add_style_row( $selector, sprintf( '%s:%spx', 'letter-spacing', $letter_spacing ), $device );
        }

    }

    public function add_dimension_style( $selector, $prop, $setting_key, $device = '' ) {
        $unit = 'px';
        $value =  $this->get_setting( $setting_key );
        if ( empty($value) || ! is_array($value) ) return;
        if ( strlen($value['top']) && strlen($value['right']) && strlen($value['bottom']) && strlen($value['left']) ) {
            $value = sprintf( "%s$unit %s$unit %s$unit %s$unit", $value['top'], $value['right'], $value['bottom'], $value['left'] );
            $this->add_style_row( $selector, sprintf( '%s:%s', $prop, $value ), $device );
        }
    }

    public function add_dimension_style_alt( $selector, $prop, $setting_key, $device = '' ) {
        $unit = 'px';
        $value =  $this->get_setting( $setting_key );
        if ( empty($value) || ! is_array($value) ) return;
        $styles = [];
        if ( strlen($value['top']) ) $styles[] = sprintf( '%s:%s' . $unit, $prop . 'top', $value['top'] );
        if ( strlen($value['right']) ) $styles[] = sprintf( '%s:%s' . $unit, $prop . 'right', $value['right'] );
        if ( strlen($value['bottom']) ) $styles[] = sprintf( '%s:%s' . $unit, $prop . 'bottom', $value['bottom'] );
        if ( strlen($value['left']) ) $styles[] = sprintf( '%s:%s' . $unit, $prop . 'left', $value['left'] );
        $this->add_style_row( $selector, implode( ';', $styles ), $device );
    }

    public function validate_style_props( Array $style_props ) {
        $_style_props = [];
        foreach ( $style_props as $style_prop ) {
            $value = explode( ':', $style_prop, 2 );
            if ( empty($value[1]) || empty(trim($value[1])) ) continue;
            $_style_props[] = $style_prop;
        }
        return $_style_props;
    }

    public function build_css( $device = '' ) {

        $css = '';

        $style = $device ? "style_$device" : 'style';

        $style = $this->$style;

        if ( empty($style) ) return '';

        foreach ( $style as $selector => $style_props ) {
            $style_props = $this->validate_style_props( $style_props );
            $css.= sprintf( '%s{%s}', $selector, implode(';', $style_props) );
        }

        $css = str_replace( ';;', ';', $css );
        $css = str_replace( ';}', '}', $css );

        if ( $device == 'tablet' ) $css = sprintf( '@media screen and (max-width: 1024px){%s}', $css );
        if ( $device == 'small_tablet' ) $css = sprintf( '@media screen and (max-width: 767px){%s}', $css );
        if ( $device == 'mobile' ) $css = sprintf( '@media screen and (max-width: 480px){%s}', $css );

        return $css;
    }

    public function _generate_css( $shortcode_id ) {
        $this->generate_css( $shortcode_id );
        return $this->build_css() . $this->build_css( 'tablet' ) .  $this->build_css( 'small_tablet' ) . $this->build_css( 'mobile' );
    }

    public function get_custom_css( $shortcode_id ) {
        return $this->_generate_css( $shortcode_id );
    }

    abstract public function generate_css( $shortcode_id );
}