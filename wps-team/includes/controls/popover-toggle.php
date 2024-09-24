<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Popover_Toggle extends Base_Data_Control {

	public function get_type() {
		return 'popover_toggle';
	}

	protected function get_default_settings() {
		return [
			'return_value' => 'yes'
		];
	}

}