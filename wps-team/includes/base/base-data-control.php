<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

abstract class Base_Data_Control extends Base_Control {

	public function get_value( $control, $settings ) {

		if ( ! isset( $control['default'] ) ) {
			$control['default'] = $this->get_default_value();
		}

		if ( isset( $settings[ $control['name'] ] ) ) {
			$value = $settings[ $control['name'] ] + [ 'value' => $control['default'] ];
		} else {
			$value = $control['default'];
		}

		return $value;

	}

}