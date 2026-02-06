<?php

namespace WPSpeedo_Team;

class Elementor_Widget extends \Elementor\Widget_Base {
    
    public function get_name() {
        return 'wpspeedo_team';
    }

    public function get_title() {
        return __( 'WPS Team', 'wps-team' );
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
                'label' => __( 'Content', 'wps-team' ),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'shortcode_id',
            [
                'label' => __( 'Select Shortcode', 'wps-team' ),
                'label_block' => true,
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => self::get_shortcode_list(),
                'value' => ''
            ]
        );

        $this->end_controls_section();

    }

    public static function get_shortcode_list() {

        $shortcodes = Integration::get_shortcodes();
        
        if ( empty($shortcodes) ) return [];

        $shortcodes = [ Integration::shortcode_default_option() ] + wp_list_pluck( $shortcodes, 'name', 'id' );

        $_shortcodes = [];

        foreach ( $shortcodes as $key => $value ) {
            $_shortcodes['shortcode-' . $key] = esc_html( $value );
        }

        return $_shortcodes;

    }

    protected function render() {

        global $wps_team_is_builder;

        if ( is_admin() ) $wps_team_is_builder = true;

        $shortcode_id = $this->get_settings_for_display( 'shortcode_id' );

        if ( empty($shortcode_id) ) {
            echo wp_kses_post( Integration::get_empty_message() );
            return;
        }

        $shortcode_id = str_replace( 'shortcode-', '', $shortcode_id );
        if ( is_numeric($shortcode_id) ) {
            echo Integration::render_shortcode( (int) $shortcode_id ); // phpcs:ignore WordPress.Security.EscapeOutput
            return;
        }

    }

}