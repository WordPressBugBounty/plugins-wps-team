<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Assets_Singular extends Style_Manager {

    public function __construct() {

        $settings = Utils::get_settings();

        $_settings = [];

        foreach ( $settings as $key => $value ) {
            $_settings[ $key ] = [
                'value' => $value
            ];
        }

        $this->set_settings( $_settings );

    }

    public function generate_css( $shortcode_id ) {
        
        $selector = '.single-wps-team-members .wps-widget-container-single';

        $this->add_responsive_style( $selector, '--wps-title-color: {{value}}', 'title_color' );

        $this->add_responsive_style( $selector, '--wps-desig-color: {{value}}', 'designation_color' );
        $this->add_responsive_style( $selector, '--wps-text-color: {{value}}', 'desc_color' );
        $this->add_responsive_style( $selector . ' .wps-team--divider', '--wps-divider-bg-color: {{value}}', 'divider_color' );
        
        $this->add_style( $selector, '--wps-info-icon-color: {{value}}', 'info_icon_color' );
        $this->add_style( $selector, '--wps-info-text-color: {{value}}', 'info_text_color' );
        $this->add_style( $selector, '--wps-info-link-color: {{value}}', 'info_link_color' );
        $this->add_style( $selector, '--wps-info-link-hover-color: {{value}}', 'info_link_hover_color' );


        $this->add_dimension_style( $selector . ' ul.wps-si--shape-radius', '--wps-slink-br-radius', 'social_links_radius' );
        $this->add_dimension_style( $selector . ' ul.wps-si--shape-radius', '--wps-slink-br-radius-hover', 'social_links_radius_hover' );

        $this->add_style( $selector . ' .wps--social-links:not(.wps-si--b-color) li a', '--wps-slink-color: {{value}}', 'social_links_color' );
        $this->add_style( $selector . ' .wps--social-links:not(.wps-si--b-bg-color) li a', '--wps-slink-bg-color: {{value}}', 'social_links_bg_color' );
        
        $this->add_style( $selector . ' .wps--social-links:not(.wps-si--b-color--hover) li a', '--wps-slink-color-hover: {{value}}', 'social_links_color_hover' );
        $this->add_style( $selector . ' .wps--social-links:not(.wps-si--b-bg-color--hover) li a', '--wps-slink-bg-color-hover: {{value}}', 'social_links_bg_color_hover' );

    }

}