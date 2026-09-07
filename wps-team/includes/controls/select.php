<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Select extends Base_Data_Control {

	public function get_type() {
		return 'select';
	}

	public function get_value( $control, $settings ) {

		$value = parent::get_value( $control, $settings );

		if ( ! is_array( $value ) ) {
			$value = [ 'value' => $value ];
		}

		// Saved payloads are often `{ value: [...] }` without control flags.
		// Read `multiple` from the registered control, not the stored value.
		$is_multiple = wp_validate_boolean( $control['multiple'] ?? ( $value['multiple'] ?? false ) );
		$value['multiple'] = $is_multiple;

		$default = $is_multiple ? [] : $this->get_default_value();

		if ( ! empty( $value['default'] ) ) {
			$default = $value['default'];
			if ( $is_multiple && ! is_array( $default ) ) {
				$default = [ $value['default'] ];
			}
		}

		if ( $this->has_selected_value( $value['value'] ?? null ) ) {

			if ( $is_multiple ) {
				$value['value'] = $this->sanitize_multiple_values( $value['value'] );
			} else {
				$value['value'] = $this->sanitize_single_value( $value['value'] );
			}

			if ( ! empty( $value['options'] ) ) {
				$allowed = array_map( 'strval', array_column( $value['options'], 'value' ) );
				$_values = $is_multiple ? $value['value'] : [ $value['value'] ];
				$_values = array_values( array_intersect( array_map( 'strval', $_values ), $allowed ) );
				$value['value'] = empty( $_values ) ? $default : ( $is_multiple ? $_values : array_shift( $_values ) );
			}

		} else {
			$value['value'] = $default;
		}

		if ( $is_multiple ) {
			$value['value'] = array_values( array_unique( (array) $value['value'] ) );
		}

		return $value;
	}

	protected function get_default_settings() {
		return [
			'placeholder' => '',
			'title' => '',
			'options' => [],
			'multiple' => false
		];
	}

	/**
	 * True when the stored value represents a real selection (not empty).
	 *
	 * @param mixed $raw
	 */
	protected function has_selected_value( $raw ): bool {
		if ( $raw === null || $raw === '' || $raw === false ) {
			return false;
		}
		if ( is_array( $raw ) ) {
			return ! empty( $raw );
		}
		return true;
	}

	/**
	 * @param mixed $raw
	 * @return string
	 */
	protected function sanitize_single_value( $raw ) {
		if ( is_array( $raw ) && array_key_exists( 'value', $raw ) ) {
			$raw = $raw['value'];
		}
		if ( is_array( $raw ) ) {
			$raw = reset( $raw );
		}
		if ( ! is_scalar( $raw ) ) {
			return '';
		}
		return sanitize_text_field( (string) $raw );
	}

	/**
	 * @param mixed $raw
	 * @return string[]
	 */
	protected function sanitize_multiple_values( $raw ) {
		if ( ! is_array( $raw ) ) {
			$raw = [ $raw ];
		}

		$values = [];

		foreach ( $raw as $item ) {
			if ( is_array( $item ) && array_key_exists( 'value', $item ) ) {
				$item = $item['value'];
			}
			if ( ! is_scalar( $item ) ) {
				continue;
			}
			$item = sanitize_text_field( (string) $item );
			if ( $item !== '' ) {
				$values[] = $item;
			}
		}

		return $values;
	}

}
