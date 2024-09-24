<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Heading extends Base_Data_Control {

	public function get_type() {
		return 'heading';
	}

	protected function get_default_settings() {
		return [
			'label_block' => true,
		];
	}
    
}