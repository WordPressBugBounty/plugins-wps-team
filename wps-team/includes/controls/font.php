<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Control_Font extends Base_Data_Control {

	public function get_type() {
		return 'font'; 
	}

	public function get_value( $control, $settings ) {
		
		$value = parent::get_value( $control, $settings );
		
		if ( !empty($value['value']) ) {

			$value['value'] = sanitize_text_field( $value['value'] );

			$fonts = [];

			if ( empty($value['options']) ) {
				$fonts = $this->get_default_settings()['options'];
			} else {
				$fonts = $value['options'];
			}

			if ( array_search( $value['value'], array_column( $fonts, 'value') ) === false ) {
				$value['value'] = $this->get_default_value();
			}

		}

		return $value;

	}

	protected function get_default_settings() {
		return [
			'placeholder' => '',
			'title' => '',
			'options' => Fonts::get_fonts(),
		];
	}

}