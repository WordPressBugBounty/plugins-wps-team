<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Icon extends Base_Data_Control {

	public function get_type() {
		return 'icon';
	}

	public function get_default_value() {
		return [
			'icon'   => '',
			'library' => '',
		];
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( !empty($value['value']) ) {
			$value['value'] = array_map( 'sanitize_text_field', $value['value'] );
		}
		return $value;
	}

	protected function get_default_settings() {
		return [
			'label_block' => true,
		];
	}

}