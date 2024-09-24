<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Wysiwyg extends Base_Data_Control {
	
	public function get_type() {
		return 'wysiwyg';
	}
	
	protected function get_default_settings() {
		return [
			'label_block' => true
		];
	}
}
