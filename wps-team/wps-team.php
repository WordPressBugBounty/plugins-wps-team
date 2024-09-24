<?php
/**
 * @package         WPSpeedo_Team_Members
 * 
 * Plugin Name: WPS Team
 * Description: Best Team Members plugin for your website
 * Plugin URI: https://wpspeedo.com/wps-team-pro?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Author: WPSpeedo
 * Author URI: https://wpspeedo.com?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * Version: 3.1.5
 * Domain Path: /languages
 * Text Domain: wpspeedo-team
  */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPS_TEAM_VERSION', '3.1.5' );

define( 'WPS_TEAM_FILE', __FILE__ );

define( 'WPS_TEAM_PATH', plugin_dir_path( WPS_TEAM_FILE ) );
define( 'WPS_TEAM_URL', plugin_dir_url( WPS_TEAM_FILE ) );

define( 'WPS_TEAM_INC_PATH', WPS_TEAM_PATH . 'includes/' );

define( 'WPS_TEAM_ADMIN_PATH', WPS_TEAM_PATH . 'admin/' );
define( 'WPS_TEAM_ADMIN_URL', WPS_TEAM_URL . 'admin/' );

define( 'WPS_TEAM_ASSET_URL', WPS_TEAM_URL . 'assets/' );
define( 'WPS_TEAM_ADMIN_ASSET_URL', WPS_TEAM_ASSET_URL . 'admin/' );

require_once WPS_TEAM_PATH . 'initialize.php';

if ( ! function_exists( 'get_wps_team' ) ) {
    function get_wps_team( $shortcode_id ) {
        return WPSpeedo_Team\Utils::get_wps_team( $shortcode_id );
    }
}