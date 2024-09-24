<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Group_Control_Border extends Group_Base_Control {

	protected static $fields;

	public static function get_type() {
		return 'border';
	}

	protected function init_fields() {
		$fields = [];

		$none = _x( 'None', 'Editor: Border', 'wpspeedo-team' );

		$fields['border'] = [
			'label' => _x( 'Border Type', 'Editor: Border', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'separator' => '',
			'view_type' => 'arrange-1',
			'placeholder' => $none,
			'options' => [
				[ 'label' => $none, 'value' => '' ],
				[ 'label' => _x( 'Solid', 'Editor: Border', 'wpspeedo-team' ), 'value' => 'solid' ],
				[ 'label' => _x( 'Double', 'Editor: Border', 'wpspeedo-team' ), 'value' => 'double' ],
				[ 'label' => _x( 'Dotted', 'Editor: Border', 'wpspeedo-team' ), 'value' => 'dotted' ],
				[ 'label' => _x( 'Dashed', 'Editor: Border', 'wpspeedo-team' ), 'value' => 'dashed' ],
				[ 'label' => _x( 'Groove', 'Editor: Border', 'wpspeedo-team' ), 'value' => 'groove' ]
			]
		];

		$fields['width'] = [
			'label' => _x( 'Width', 'Editor: Border', 'wpspeedo-team' ),
			'type' => Controls_Manager::DIMENSIONS,
			'separator' => '',
			'condition' => [
				'border!' => '',
			],
			'responsive' => true,
		];

		$fields['color'] = [
			'label' => _x( 'Color', 'Editor: Border', 'wpspeedo-team' ),
			'type' => Controls_Manager::COLOR,
			'separator' => '',
			'default' => '',
			'condition' => [
				'border!' => '',
			],
		];

		return $fields;
	}

	protected function get_default_options() {
		return [
			'popover' => false,
		];
	}

}