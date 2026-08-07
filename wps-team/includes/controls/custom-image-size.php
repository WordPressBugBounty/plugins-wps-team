<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Custom_Image_Size extends Base_Data_Control {
	
	public function get_type() {
		return 'custom_image_size';
	}
	
	public function get_default_value() {

		return [
			'width'  => '',
			'height' => '',
			'crop'   => false,
		];
		
	}

	/**
	 * Normalize legacy/invalid saved values (empty string, list array, etc.)
	 * into the object shape the editor expects.
	 *
	 * @param mixed $raw Raw value.
	 * @return array{width:int|string,height:int|string,crop:bool}
	 */
	public function normalize_value( $raw ) {
		$default = $this->get_default_value();

		if ( ! is_array( $raw ) || array_values( $raw ) === $raw ) {
			return $default;
		}

		$value           = array_merge( $default, $raw );
		$value['width']  = ( isset( $value['width'] ) && strlen( (string) $value['width'] ) ) ? intval( $value['width'] ) : '';
		$value['height'] = ( isset( $value['height'] ) && strlen( (string) $value['height'] ) ) ? intval( $value['height'] ) : '';
		$value['crop']   = ! empty( $value['crop'] ) ? wp_validate_boolean( $value['crop'] ) : false;

		return $value;
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );

		// Parent sometimes returns the default object unwrapped.
		if ( is_array( $value ) && ! array_key_exists( 'value', $value ) && ( isset( $value['width'] ) || isset( $value['height'] ) || isset( $value['crop'] ) ) ) {
			$value = [ 'value' => $value ];
		}

		if ( ! is_array( $value ) ) {
			$value = [ 'value' => $this->get_default_value() ];
		}

		$value['value'] = $this->normalize_value( $value['value'] ?? null );

		return $value;
	}
	
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(), [
				'label_block' => true,
				'tooltips' => [
					'width' => _x('Width', 'Editor: Image Size', 'wps-team'),
					'height' => _x('Height', 'Editor: Image Size', 'wps-team'),
					'crop' => _x('Crop', 'Editor: Image Size', 'wps-team'),
					'apply' => _x('Apply', 'Editor: Image Size', 'wps-team'),
				],
			]
		);
	}
	
}
