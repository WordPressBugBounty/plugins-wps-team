<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Control_Dimensions extends Base_Data_Control {
	
	public function get_type() {
		return 'dimensions';
	}
	
	public function get_default_value() {

		return [
			'top' => '',
			'right' => '',
			'bottom' => '',
			'left' => '',
			'linked' => true,
		];
		
	}

	public function get_value( $control, $settings ) {
		$value = parent::get_value( $control, $settings );
		if ( !empty($value['value']) ) {
			$value['value']['top'] = strlen( $value['value']['top'] ) ? intval( $value['value']['top'] ) : '';
			$value['value']['right'] = strlen( $value['value']['right'] ) ? intval( $value['value']['right'] ) : '';
			$value['value']['bottom'] = strlen( $value['value']['bottom'] ) ? intval( $value['value']['bottom'] ) : '';
			$value['value']['left'] = strlen( $value['value']['left'] ) ? intval( $value['value']['left'] ) : '';
			$value['value']['linked'] = wp_validate_boolean( $value['value']['linked'] );
		}
		return $value;
	}
	
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(), [
				'label_block' => true,
				'allowed_dimensions' => 'all',
				'placeholder' => '',
				'tooltips' => [
					'top' => _x('Top', 'Editor: Dimension', 'wpspeedo-team'),
					'right' => _x('Right', 'Editor: Dimension', 'wpspeedo-team'),
					'bottom' => _x('Bottom', 'Editor: Dimension', 'wpspeedo-team'),
					'left' => _x('Left', 'Editor: Dimension', 'wpspeedo-team'),
					'link' => _x('Link Together', 'Editor: Dimension', 'wpspeedo-team'),
				],
			]
		);
	}
	
}