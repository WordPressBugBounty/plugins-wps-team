<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Repeater extends Base_Control {

	public function get_type() {
		return 'repeater';
	}

	public function get_default_value() {
		return [];
	}

	protected function get_default_settings() {
		return [
			'fields' => [],
			'title_field' => '',
			'prevent_empty' => true,
			'is_repeater' => true,
			'item_actions' => [
				'add' => true,
				'duplicate' => true,
				'remove' => true,
				'sort' => true,
			]
		];
	}

	/**
	 * Get repeater control value.
	 *
	 * Retrieve the value of the repeater control from a specific Controls_Stack.
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @param array $control  Control
	 * @param array $settings Controls_Stack settings
	 *
	 * @return mixed Control values.
	 */
	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );

		if ( ! empty( $value ) ) {
			foreach ( $value as &$item ) {
				foreach ( $control['fields'] as $field ) {
					$control_obj = Plugin::$instance->controls_manager->get_control( $field['type'] );

					// Prior to 1.5.0 the fields may contains non-data controls.
					if ( ! $control_obj instanceof Base_Control ) continue;

					$item[ $field['name'] ] = $control_obj->get_value( $field, $item );
				}
			}
		}

		return $value;
	}

	/**
	 * Import repeater.
	 *
	 * @since 1.8.0
	 * @access public
	 *
	 * @param array $settings     Control settings.
	 * @param array $control_data Optional. Control data. Default is an empty array.
	 *
	 * @return array Control settings.
	 */
	public function on_import( $settings, $control_data = [] ) {
		
		if ( empty( $settings ) || empty( $control_data['fields'] ) ) {
			return $settings;
		}

		$method = 'on_import';

		foreach ( $settings as &$item ) {
			foreach ( $control_data['fields'] as $field ) {
				if ( empty( $field['name'] ) || empty( $item[ $field['name'] ] ) ) {
					continue;
				}

				$control_obj = Plugin::$instance->controls_manager->get_control( $field['type'] );

				if ( ! $control_obj ) {
					continue;
				}

				if ( method_exists( $control_obj, $method ) ) {
					$item[ $field['name'] ] = $control_obj->{$method}( $item[ $field['name'] ], $field );
				}
			}
		}

		return $settings;
	}
	
}
