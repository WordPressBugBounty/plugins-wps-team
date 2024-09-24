<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Section extends Base_Control {
	
	public function get_type() {
		return 'section';
	}
	
	protected function get_default_settings() {
		return [
			'separator' => '',
		];
	}

}