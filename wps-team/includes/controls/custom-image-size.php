<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Custom_Image_Size extends Base_Data_Control {
	
	public function get_type() {
		return 'custom_image_size';
	}
	
	public function get_default_value() {

		return [
			'width' => '',
			'height' => '',
			'crop' => false
		];
		
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( !empty($value['value']) ) {
			$value['value']['width'] = strlen( $value['value']['width'] ) ? intval( $value['value']['width'] ) : '';
			$value['value']['height'] = strlen( $value['value']['height'] ) ? intval( $value['value']['height'] ) : '';
			$value['value']['crop'] = strlen( $value['value']['crop'] ) ? wp_validate_boolean( $value['value']['crop'] ) : false;
		}
		return $value;
	}
	
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(), [
				'label_block' => true,
				'tooltips' => [
					'width' => _x('Width', 'Editor: Image Size', 'wpspeedo-team'),
					'height' => _x('Height', 'Editor: Image Size', 'wpspeedo-team'),
					'crop' => _x('Crop', 'Editor: Image Size', 'wpspeedo-team'),
					'apply' => _x('Apply', 'Editor: Image Size', 'wpspeedo-team'),
				],
			]
		);
	}
	
}