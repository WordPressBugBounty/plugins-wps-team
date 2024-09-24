<?php

namespace WPSpeedo_Team;
use ET_Builder_Module;

if ( ! defined( 'ABSPATH' ) ) exit;

class Divi_Module extends ET_Builder_Module {

    public $slug       = 'wps_team_divi';
    public $vb_support = 'on';

    public function init() {
        $this->name = esc_html__( 'WPS Team', 'wpspeedo-team' );
    }

    public function get_fields() {

        return array(
            'shortcode'     => array(
                'label'           => esc_html__( 'Select Shortcode', 'wpspeedo-team' ),
                'type'            => 'select',
                'option_category' => 'basic_option',
                'description'     => esc_html__( 'Display Team Members', 'wpspeedo-team' ),
                'toggle_slug'     => 'main_content',
                'options'         => self::get_shortcode_list(),
                'computed_affects'   => array(
                    '__shortcode',
                ),
            ),
            '__shortcode' => array(
                'type'                => 'computed',
                'computed_callback'   => array( 'WPSpeedo_Team\Divi_Module', 'get_shortcode' ),
                'computed_depends_on' => array(
                    'shortcode',
                ),
                'computed_minimum' => array(
                    'shortcode',
                ),
            )
        );
    }

    static function get_shortcode( $args ) {

        $defaults = array(
            'shortcode' => ''
        );

        $args = wp_parse_args( $args, $defaults );

        if ( ! is_numeric( $args['shortcode'] ) ) {
            $shortcodes = self::get_shortcode_list( true );
            $args['shortcode'] = ! empty($shortcodes[ $args['shortcode'] ]) ?: 0;
        }

        return do_shortcode( sprintf( '[wpspeedo-team id="%s" /]', esc_attr($args['shortcode']) ) );

    }

    public function render( $unprocessed_props, $content, $render_slug ) {
        
        $shortcode_id = $this->props['shortcode'];

        $output = sprintf(
            '<div id="%2$s" class="%3$s">
                %1$s
            </div>',
            self::get_shortcode([
                'shortcode' => $shortcode_id
            ]),
            $this->module_id(),
            $this->module_classname( $render_slug )
        );

        return $output;

    }

    public static function get_shortcode_list( $reverse = false ) {

        $shortcodes = Integration::get_shortcodes();
        
        if ( !empty($shortcodes) ) {
            
            $shortcodes = [ Integration::shortcode_default_option() ] + wp_list_pluck( $shortcodes, 'name', 'id' );

            if ( ! $reverse ) return $shortcodes;

            return array_flip( $shortcodes );
        }

        return [];

    }

}