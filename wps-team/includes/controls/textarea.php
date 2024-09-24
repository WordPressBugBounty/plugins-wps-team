<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Textarea extends Base_Data_Control {

	public function get_type() {
		return 'textarea';
	}

	protected function get_default_settings() {
		return [
			'label_block' => true,
			'rows' => 5,
			'placeholder' => ''
		];
	}

}