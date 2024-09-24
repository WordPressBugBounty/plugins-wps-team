<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Shortcode {

	public function __construct() {
		add_shortcode( 'wpspeedo-team', [ $this, 'shortcode'] );
	}

    public function load_settings( $sc_id ) {
        $settings = plugin()->api->fetch_shortcode( $sc_id );
		if ( empty($settings) ) return [];
		return $settings['settings'];
    }
	
	public function shortcode( $args ) {

		global $wps_team_id;

		$wps_team_id = $args['id'];

		$settings = (array) $this->load_settings( $args['id'] );

		if ( empty($settings) ) return sprintf( '<h3>Team Shortcode <strong>%s</strong> not found</h3>', $args['id'] );
		
		ob_start();

		global $shortcode_loader;

		global $wps_team_is_builder;

		$mode = 'public';

		if ( $wps_team_is_builder ) {
			$mode = 'builder';
		}

        $shortcode_loader = new Shortcode_Loader([
			'id' => $args['id'],
			'settings' => $settings,
			'mode' => $mode
		]);

		$shortcode_loader->load_template();

		unset( $wps_team_id );

		return ob_get_clean();

	}

}