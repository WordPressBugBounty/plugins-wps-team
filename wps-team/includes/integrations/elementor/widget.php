<?php

namespace WPSpeedo_Team;

class Elementor_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'wpspeedo_team';
    }

    public function get_title() {
        return __( 'WPS Team', 'wpspeedo-team' );
    }

    public function get_icon() {
        return 'wpspeedo_team';
    }

    public function get_categories() {
        return [ 'wpspeedo', 'basic', 'general' ];
    }

    protected function _register_controls() {

        $this->start_controls_section(
            'content_section',
            [
                'label' => __( 'Content', 'wpspeedo-team' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'shortcode_id',
            [
                'label' => __( 'Select Shortcode', 'wpspeedo-team' ),
                'label_block' => true,
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => self::get_shortcode_list(),
                'value' => ''
            ]
        );

        $this->end_controls_section();

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

    protected function render() {

        global $wps_team_is_builder;

        if ( is_admin() ) $wps_team_is_builder = true;

        $shortcode_id = $this->get_settings_for_display( 'shortcode_id' );

        if ( empty($shortcode_id) || ! is_numeric($shortcode_id) ) {
            echo Integration::display_empty_message();
        } else {
            echo Integration::render_shortcode( $shortcode_id );
        }

    }

}