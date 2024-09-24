<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Tab extends Base_Data_Control {

	public function get_type() {
		return 'tab';
	}
	
	protected function get_default_settings() {
		return [
			'separator' => 'none',
		];
	}
	
}