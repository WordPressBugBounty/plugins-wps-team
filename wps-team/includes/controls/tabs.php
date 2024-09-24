<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Tabs extends Base_Data_Control {

	public function get_type() {
		return 'tabs';
	}

	protected function get_default_settings() {
		return [
			'separator' => 'none',
		];
	}

}