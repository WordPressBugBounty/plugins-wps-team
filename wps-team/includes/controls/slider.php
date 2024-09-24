<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Slider extends Base_Data_Control {
	
	public function get_type() {
		return 'slider';
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( $value['value'] === '' ) return $value;
		// Validation
		if ( ! empty($value['value']) || $value['value'] == 0 ) $value['value'] = (float) $value['value'];
		return $value;
	}
	
	protected function get_default_settings() {

		return [
			'label_block' => true,
			'labels' => [],
			'scales' => 0,
			'handles' => 'default',
			'min' => 0,
			'max' => 100,
			'step' => 1
		];

	}

}