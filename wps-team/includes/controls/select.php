<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Select extends Base_Data_Control {

	public function get_type() {
		return 'select';
	}

	public function get_value( $control, $settings ) {
		
		$value = parent::get_value( $control, $settings );
		
		$is_multiple = wp_validate_boolean( $value['multiple'] );
		
		$default = $is_multiple ? [] : $this->get_default_value();

		if ( !empty($value['default']) ) {
			$default = $value['default'];
			if ( $is_multiple && gettype($default) !== 'array' ) {
				$default = [ $value['default'] ];
			}
		}
		
		if ( !empty($value['value']) ) {

			if ( $is_multiple ) {
				if ( gettype($value['value']) !== 'array' ) $value['value'] = [ $value['value'] ];
				$value['value'] = array_map( 'sanitize_text_field', $value['value'] );
			} else {
				$value['value'] = sanitize_text_field( $value['value'] );
			}
			
			if ( !empty($value['options']) ) {
				$_values = $is_multiple ? $value['value'] : [ $value['value'] ];
				$_values = array_intersect( $_values, array_column( $value['options'], 'value') );
				$value['value'] = empty($_values) ? $default : ( $is_multiple ? $_values : array_shift( $_values ) );
			}
			
		} else {
			$value['value'] = $default;
		}

		if ( $is_multiple ) {
			$value['value'] = array_unique( $value['value'] );
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

}