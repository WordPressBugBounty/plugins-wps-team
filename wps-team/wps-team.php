<?php
/**
 * @package         WPSpeedo_Team_Members
 * 
 * Plugin Name: WPS Team
 * Plugin URI: https://wpspeedo.com/wps-team-pro?utm_source=wp-plugins&utm_campaign=plugin-uri&utm_medium=wp-dash
 * Description: The Ultimate Team Plugin to Elevate Your Website
 * Version: 3.5.6
 * Author: WPSpeedo
 * Author URI: https://wpspeedo.com?utm_source=wp-plugins&utm_campaign=author-uri&utm_medium=wp-dash
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wps-team
 * Domain Path: /languages
 * Requires PHP: 7.0
 * Requires at least: 5.9
  */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WPS_TEAM_VERSION', '3.5.6' );

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