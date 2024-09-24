<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Switcher extends Base_Data_Control {
	
	public function get_type() {
		return 'switcher';
	}

	public function get_value( $control, $settings ) {

		$data = parent::get_value( $control, $settings );
		
		if ( is_array($data) ) {
			
			if ( array_key_exists('value', $data) ) {
				$data['value'] = wp_validate_boolean( $data['value'] );
			} else {
				$data['default'] = wp_validate_boolean( $data['default'] );
			}
			
		}

		return $data;

	}
	
	protected function get_default_settings() {
		return [
			'return_value' => true,
		];
	}
	
}