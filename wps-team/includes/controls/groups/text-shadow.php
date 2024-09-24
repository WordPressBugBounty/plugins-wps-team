<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Group_Control_Text_Shadow extends Group_Base_Control {

	protected static $fields;

	public static function get_type() {
		return 'text-shadow';
	}

	protected function init_fields() {
		$controls = [];

		$controls['color'] = [
			'label' => _x( 'Text Shadow', 'Editor: Text Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::COLOR,
			'separator' => '',
		];

		$controls['horizontal'] = [
			'label' => _x( 'Horizontal', 'Editor: Text Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => -100,
			'max' => 100,
		];

		$controls['vertical'] = [
			'label' => _x( 'Vertical', 'Editor: Text Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => -100,
			'max' => 100,
		];

		$controls['blur'] = [
			'label' => _x( 'Blur', 'Editor: Text Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => 0,
			'max' => 100,
		];

		return $controls;
	}

	protected function get_default_options() {
		return [
			'popover' => [
				'starter_title' => _x( 'Text Shadow', 'Editor: Text Shadow Control', 'wpspeedo-team' ),
				'starter_name' => 'text_shadow_type',
				'starter_value' => 'yes',
				'settings' => [
					'separator' => 'after',
				],
			],
		];
	}
	
}