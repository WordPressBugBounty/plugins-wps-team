<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Repeater extends Editor_Controls {

    private static $counter = 0;

	public function __construct( array $data = [], array $args = null ) {
		self::$counter++;

		parent::__construct( $data, $args );

		$this->add_control(
			'_id',
			[
				'type' => Controls_Manager::TEXT,
				// 'type' => Controls_Manager::HIDDEN,
			]
		);
	}

    public function get_name() {
		return 'repeater-' . self::$counter;
	}

    public static function get_type() {
		return 'repeater';
	}

    public function add_control( $id, array $args, $options = [] ) {
		$current_tab = $this->get_current_tab();

		if ( null !== $current_tab ) {
			$args = array_merge( $args, $current_tab );
		}

		return parent::add_control( $id, $args, $options );
	}

    protected function _get_default_child_type( array $element_data ) {
		return false;
	}

    protected function handle_control_position( array $args, $control_id, $overwrite ) {
		return $args;
	}

    public function get_fields() {
		return array_values( $this->get_controls() );
	}

}