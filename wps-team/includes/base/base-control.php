<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Base_Control extends Base_Object {
	
	private $_base_settings = [
		'label' => '',
		'description' => '',
		'show_label' => true,
		'label_block' => false,
		'separator' => 'after',
	];
	
	abstract public function get_type();

	public function __construct() {
		$this->set_settings( array_merge( $this->_base_settings, $this->get_default_settings() ) );
	}
	
	protected function get_default_settings() {
		return [];
	}

	public function get_default_value() {
		return '';
	}

}