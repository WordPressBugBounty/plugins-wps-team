<?php

namespace WPSpeedo_Team;

if ( !function_exists( 'wps_team_fs' ) ) {
    // Create a helper function for easy SDK access.
    function wps_team_fs() {
        global $wps_team_fs;
        if ( !isset( $wps_team_fs ) ) {
            // Activate multisite network integration.
            if ( !defined( 'WP_FS__PRODUCT_10548_MULTISITE' ) ) {
                define( 'WP_FS__PRODUCT_10548_MULTISITE', true );
            }
            // Include Freemius SDK.
            require_once WPS_TEAM_INC_PATH . 'freemius/start.php';
            $wps_team_fs = fs_dynamic_init( array(
                'id'             => '10548',
                'slug'           => 'wps-team',
                'premium_slug'   => 'wps-team-pro',
                'type'           => 'plugin',
                'public_key'     => 'pk_18753df98c36bc34e975fda5f111c',
                'is_premium'     => false,
                'premium_suffix' => 'Pro',
                'has_addons'     => false,
                'has_paid_plans' => true,
                'trial'          => array(
                    'days'               => 10,
                    'is_require_payment' => true,
                ),
                'menu'           => array(
                    'slug'       => 'edit.php?post_type=wps-team-members',
                    'first-path' => 'edit.php?post_type=wps-team-members&page=wps-team#/get-help',
                ),
                'is_live'        => true,
            ) );
        }
        return $wps_team_fs;
    }

    // Init Freemius.
    wps_team_fs();
    // Signal that SDK was initiated.
    do_action( 'wps_team_fs_loaded' );
}
// Disable Contact Page for Free Users
add_filter(
    "fs_is_submenu_visible_wps-team",
    function ( $is_visible, $menu ) {
        if ( $menu == 'contact' && !wps_team_fs()->can_use_premium_code() ) {
            return false;
        }
        return $is_visible;
    },
    10,
    2
);