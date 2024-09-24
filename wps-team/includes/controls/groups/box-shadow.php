<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Group_Control_Box_Shadow extends Group_Base_Control {

	protected static $fields;

	public static function get_type() {
		return 'box-shadow';
	}

	protected function init_fields() {
		$controls = [];

		$controls['color'] = [
			'label' => _x( 'Color', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::COLOR,
			'separator' => '',
		];

		$controls['horizontal'] = [
			'label' => _x( 'Horizontal', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => -100,
			'max' => 100,
		];

		$controls['vertical'] = [
			'label' => _x( 'Vertical', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => -100,
			'max' => 100,
		];

		$controls['blur'] = [
			'label' => _x( 'Blur', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => 0,
			'max' => 100,
		];

		$controls['spread'] = [
			'label' => _x( 'Spread', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'min' => -100,
			'max' => 100,
		];

		$controls['position'] = [
			'label' => _x( 'Position', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'separator' => '',
			'options' => [
				[ 'label' => _x( 'Outline', 'Editor: Box Shadow Control', 'wpspeedo-team' ), 'value' => '' ],
				[ 'label' => _x( 'Inset', 'Editor: Box Shadow Control', 'wpspeedo-team' ), 'value' => 'inset' ],
			],
			'placeholder' => _x( 'Outline', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
			'render_type' => 'ui',
			'view_type' => 'arrange-1'
		];

		return $controls;
	}

	protected function get_default_options() {
		return [
			'popover' => [
				'starter_title' => _x( 'Box Shadow', 'Editor: Box Shadow Control', 'wpspeedo-team' ),
				'starter_name' => 'wrapper',
				'starter_value' => 'yes',
				'settings' => [
					'separator' => 'after',
				],
			],
		];
	}
}