<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Editor_Controls extends Controls_Stack {

	private $default_args = [];

	protected function _register_controls() {}

	public function get_default_args( $item = null ) {
		return self::get_items( $this->default_args, $item );
	}

	protected function get_initial_config() {
		
		$config = [
			'name' => $this->get_name(),
		];

		return $config;
	}

	public function __construct( array $data = [], array $args = null ) {
		
		if ( $data ) {
		} elseif ( $args ) {
			$this->default_args = $args;
		}

		parent::__construct( $data );

	}
	
}