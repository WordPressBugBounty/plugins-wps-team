<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Number extends Base_Data_Control {

	public function get_type() {
		return 'number';
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( !empty($value['value']) ) {
			$value['value'] = (float) $value['value'];
		}
		return $value;
	}

	protected function get_default_settings() {
		return [
			'input_type' => 'text',
			'placeholder' => '',
			'title' => '',
			'has_controls' => true
		];
	}
	
}