<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Choose extends Base_Data_Control {

	public function get_type() {
		return 'choose';
	}

	protected function get_default_settings() {
		return [
			'options' => [],
			'toggle' => true,
			'label_block' => false,
		];
	}
	
}