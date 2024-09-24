<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Group_Base_Control {

	private $args = [];

	private $options;

	protected static $fields;

    abstract public static function get_type();

	final public function get_options( $option = null ) {
		if ( null === $this->options ) {
			$this->init_options();
		}

		if ( $option ) {
			if ( isset( $this->options[ $option ] ) ) {
				return $this->options[ $option ];
			}

			return null;
		}

		return $this->options;
	}

	final public function add_controls( Controls_Stack $element, array $user_args, array $options = [] ) {
		$this->init_args( $user_args );

		// Filter which controls to display
		$filtered_fields = $this->filter_fields();
		$filtered_fields = $this->prepare_fields( $filtered_fields );

		// For php < 7
		reset( $filtered_fields );

		if ( isset( $this->args['separator'] ) ) {
			$filtered_fields[ key( $filtered_fields ) ]['separator'] = $this->args['separator'];
		}

		$has_injection = false;

		if ( ! empty( $options['position'] ) ) {
			$has_injection = true;

			$element->start_injection( $options['position'] );

			unset( $options['position'] );
		}

		if ( $this->get_options( 'popover' ) ) {
			$this->start_popover( $element );
		}

		foreach ( $filtered_fields as $field_id => $field_args ) {
			// Add the global group args to the control
			$field_args = $this->add_group_args_to_field( $field_id, $field_args );

			// Register the control
			$id = $this->get_controls_prefix() . $field_id;

			if ( ! empty( $field_args['responsive'] ) ) {
				unset( $field_args['responsive'] );

				$element->add_responsive_control( $id, $field_args, $options );
			} else {
				$element->add_control( $id, $field_args, $options );
			}
		}

		if ( $this->get_options( 'popover' ) ) {
			$element->end_popover();
		}

		if ( $has_injection ) {
			$element->end_injection();
		}
	}

	final public function get_args() {
		return $this->args;
	}

	final public function get_fields() {
		if ( null === static::$fields ) {
			static::$fields = $this->init_fields();
		}

		return static::$fields;
	}

	public function get_controls_prefix() {
		return $this->args['name'] . '_';
	}

	abstract protected function init_fields();

	protected function get_default_options() {
		return [];
	}

	protected function get_child_default_args() {
		return [];
	}

	protected function filter_fields() {
		$args = $this->get_args();

		$fields = $this->get_fields();

		if ( ! empty( $args['include'] ) ) {
			$fields = array_intersect_key( $fields, array_flip( $args['include'] ) );
		}

		if ( ! empty( $args['exclude'] ) ) {
			$fields = array_diff_key( $fields, array_flip( $args['exclude'] ) );
		}

		return $fields;
	}

	protected function add_group_args_to_field( $control_id, $field_args ) {
		$args = $this->get_args();

		if ( ! empty( $args['tab'] ) ) {
			$field_args['tab'] = $args['tab'];
		}

		if ( ! empty( $args['section'] ) ) {
			$field_args['section'] = $args['section'];
		}

		foreach ( [ 'condition', 'conditions' ] as $condition_type ) {
			if ( ! empty( $args[ $condition_type ] ) ) {
				if ( empty( $field_args[ $condition_type ] ) ) {
					$field_args[ $condition_type ] = [];
				}

				$field_args[ $condition_type ] += $args[ $condition_type ];
			}
		}

		return $field_args;
	}

	protected function prepare_fields( $fields ) {
		$popover_options = $this->get_options( 'popover' );

		$popover_name = ! $popover_options ? null : $popover_options['starter_name'];

		foreach ( $fields as $field_key => &$field ) {
			if ( $popover_name ) {
				$field['condition'][ $popover_name . '!' ] = '';
			}

			if ( isset( $this->args['fields_options']['__all'] ) ) {
				$field = array_merge( $field, $this->args['fields_options']['__all'] );
			}

			if ( isset( $this->args['fields_options'][ $field_key ] ) ) {
				$field = array_merge( $field, $this->args['fields_options'][ $field_key ] );
			}

			if ( ! empty( $field['condition'] ) ) {
				$field = $this->add_condition_prefix( $field );
			}

			if ( ! empty( $field['conditions'] ) ) {
				$field['conditions'] = $this->add_conditions_prefix( $field['conditions'] );
			}

			if ( ! empty( $field['device_args'] ) ) {
				foreach ( $field['device_args'] as $device => $device_arg ) {
					if ( ! empty( $field['device_args'][ $device ]['condition'] ) ) {
						$field['device_args'][ $device ] = $this->add_condition_prefix( $field['device_args'][ $device ] );
					}

					if ( ! empty( $field['device_args'][ $device ]['conditions'] ) ) {
						$field['device_args'][ $device ]['conditions'] = $this->add_conditions_prefix( $field['device_args'][ $device ]['conditions'] );
					}
				}
			}
		}

		return $fields;
	}

	private function init_options() {
		$default_options = [
			'popover' => [
				'starter_name' => 'popover_toggle',
				'starter_value' => 'custom',
				'starter_title' => '',
			],
		];

		$this->options = array_replace_recursive( $default_options, $this->get_default_options() );
	}

	protected function init_args( $args ) {
		$this->args = array_merge( $this->get_default_args(), $this->get_child_default_args(), $args );
	}

	private function get_default_args() {
		return [
			'default' => '',
			'fields_options' => [],
		];
	}

	private function add_condition_prefix( $field ) {
		$controls_prefix = $this->get_controls_prefix();

		$prefixed_condition_keys = array_map(
			function( $key ) use ( $controls_prefix ) {
				return $controls_prefix . $key;
			},
			array_keys( $field['condition'] )
		);

		$field['condition'] = array_combine(
			$prefixed_condition_keys,
			$field['condition']
		);

		return $field;
	}

	private function add_conditions_prefix( $conditions ) {
		$controls_prefix = $this->get_controls_prefix();

		foreach ( $conditions['terms'] as & $condition ) {
			if ( isset( $condition['terms'] ) ) {
				$condition = $this->add_conditions_prefix( $condition );

				continue;
			}

			$condition['name'] = $controls_prefix . $condition['name'];
		}

		return $conditions;
	}

	private function start_popover( Controls_Stack $element ) {
		$popover_options = $this->get_options( 'popover' );

		$settings = $this->get_args();

		if ( isset( $settings['global'] ) ) {
			if ( ! isset( $popover_options['settings']['global'] ) ) {
				$popover_options['settings']['global'] = [];
			}

			$popover_options['settings']['global'] = array_replace_recursive( $popover_options['settings']['global'], $settings['global'] );
		}

		if ( isset( $settings['label'] ) ) {
			$label = $settings['label'];
		} else {
			$label = $popover_options['starter_title'];
		}

		$control_params = [
			'type' => Controls_Manager::POPOVER_TOGGLE,
			'label' => $label,
			'return_value' => $popover_options['starter_value'],
		];

		if ( ! empty( $popover_options['settings'] ) ) {
			$control_params = array_replace_recursive( $control_params, $popover_options['settings'] );
		}

		foreach ( [ 'condition', 'conditions' ] as $key ) {
			if ( ! empty( $settings[ $key ] ) ) {
				$control_params[ $key ] = $settings[ $key ];
			}
		}

		$starter_name = $popover_options['starter_name'];

		if ( isset( $this->args['fields_options'][ $starter_name ] ) ) {
			$control_params = array_merge( $control_params, $this->args['fields_options'][ $starter_name ] );
		}

		$control_params['groupPrefix'] = $this->get_controls_prefix();

		$element->add_control( $this->get_controls_prefix() . $starter_name, $control_params );

		$element->start_popover();
	}
}