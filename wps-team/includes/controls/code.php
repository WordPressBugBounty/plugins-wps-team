<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Code extends Base_Data_Control {
	
	public function get_type() {
		return 'code';
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( !empty($value['value']) ) {
			$value['value'] = Utils::minify_validated_css( $value['value'] );
		}
		return $value;
	}
	
	protected function get_default_settings() {
		return [
			'label_block' => true,
			'minHeight' => '',
			'lineNumbers' => true,
			'mode' => 'css'
		];
	}
}
