<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Compatibility {

    // Constructor
    public function __construct() {

        if ( $this->is_theme('OceanWP') ) {
            if ( get_theme_mod( 'ocean_performance_scroll_effect', 'enabled' ) === 'enabled' ) {
                // Disable OceanWP scroll effect on team member links
                add_filter( 'wpspeedo_team/post_link_attrs', [ $this, 'modify_oceanwp_link_attrs' ], 10, 2 );
            }
        }

    }

    // Modify link attributes for OceanWP theme
    public function modify_oceanwp_link_attrs( $attrs, $action ) {
        if ( in_array( $action, ['modal', 'expand', 'side-panel'] ) ) {
            if ( ! isset( $attrs['class'] ) ) $attrs['class'] = 'opl-link';
            else $attrs['class'] .= ' opl-link';
        }
        return $attrs;
    }

    // Check if the $theme is in theme or parent theme
    public function is_theme( $theme ) {
        $current_theme = wp_get_theme();
        if ( $current_theme->get('Name') === $theme ) {
            return true;
        }
        if ( $current_theme->parent() && $current_theme->parent()->get('Name') === $theme ) {
            return true;
        }
        return false;
    }

}