<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visual-only control: draggable breakpoint bar (values live in linked number fields).
 */
class Control_Breakpoint_Bar extends Base_Control {

	public function get_type() {
		return 'breakpoint_bar';
	}

	protected function get_default_settings() {
		return [
			'label_block'   => true,
			'linked_fields' => [],
		];
	}
}
