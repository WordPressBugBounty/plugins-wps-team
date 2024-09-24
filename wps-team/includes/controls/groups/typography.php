<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Group_Control_Typography extends Group_Base_Control {

	protected static $fields;

	private static $_scheme_fields_keys = [ 'font_family', 'font_weight' ];

	public static function get_scheme_fields_keys() {
		return self::$_scheme_fields_keys;
	}

	public static function get_type() {
		return 'typography';
	}

	protected function init_fields() {
		$fields = [];

		$fields['font_family'] = [
			'label' => _x( 'Family', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::FONT,
			'view_type' => 'arrange-1',
			'placeholder' => 'Default',
			'render_type' => 'template',
			'default' => '',
			'separator' => '',
		];

		$fields['font_size'] = [
			'label' => _x( 'Size', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'size_units' => [ 'px', 'em', 'rem', 'vw' ],
			'unit' => 'px',
			'tablet_unit' => 'px',
			'small_tablet_unit' => 'px',
			'mobile_unit' => 'px',
			'responsive' => true,
		];

		$typo_weight_options = [
			[
				'label' => _x( 'Default', 'Editor: Typography', 'wpspeedo-team' ),
				'value' => ''
			]
		];

		foreach ( array_merge( [ 'normal', 'bold' ], range( 100, 900, 100 ) ) as $weight ) {
			$typo_weight_options[] = [
				'label' => ucfirst( $weight ),
				'value' =>  $weight
			];
		}

		$fields['font_weight'] = [
			'label' => _x( 'Weight', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'separator' => '',
			'view_type' => 'arrange-1',
			'placeholder' => 'Default',
			'default' => '',
			'options' => $typo_weight_options,
		];

		$fields['text_transform'] = [
			'label' => _x( 'Transform', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'separator' => '',
			'view_type' => 'arrange-1',
			'placeholder' => 'Default',
			'default' => '',
			'options' => [
				[
					'label' => _x( 'Default', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => ''
				],
				[
					'label' => _x( 'Uppercase', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'uppercase'
				],
				[
					'label' => _x( 'Lowercase', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'lowercase'
				],
				[
					'label' => _x( 'Capitalize', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'capitalize'
				],
				[
					'label' => _x( 'Normal', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'none'
				]
			],
		];

		$fields['font_style'] = [
			'label' => _x( 'Style', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'view_type' => 'arrange-1',
			'separator' => '',
			'placeholder' => 'Default',
			'default' => '',
			'options' => [
				[	'label' => _x( 'Default', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => ''
				],
				[	'label' => _x( 'Normal', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'normal'
				],
				[	'label' => _x( 'Italic', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'italic'
				],
				[	'label' => _x( 'Oblique', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'oblique'
				]
			],
		];

		$fields['text_decoration'] = [
			'label' => _x( 'Decoration', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SELECT,
			'view_type' => 'arrange-1',
			'separator' => '',
			'placeholder' => 'Default',
			'default' => '',
			'options' => [
				[	'label' => _x( 'Default', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => ''
				],
				[	'label' => _x( 'Underline', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'underline'
				],
				[	'label' => _x( 'Overline', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'overline'
				],
				[	'label' => _x( 'Line Through', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'line-through'
				],
				[	'label' => _x( 'None', 'Editor: Typography', 'wpspeedo-team' ),
					'value' => 'none'
				]
			],
		];

		$fields['line_height'] = [
			'label' => _x( 'Line-Height', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'unit' => 'em',
			'tablet_unit' => 'em',
			'small_tablet_unit' => 'em',
			'mobile_unit' => 'em',
			'responsive' => true,
			'size_units' => [ 'px', 'em' ],
		];

		$fields['letter_spacing'] = [
			'label' => _x( 'Letter Spacing', 'Editor: Typography', 'wpspeedo-team' ),
			'type' => Controls_Manager::SLIDER,
			'separator' => '',
			'unit' => 'px',
			'tablet_unit' => 'px',
			'small_tablet_unit' => 'px',
			'mobile_unit' => 'px',
			'range' => [
				'px' => [
					'min' => -10,
					'max' => 10,
					'step' => 0.1,
				],
			],
			'responsive' => true,
		];

		return $fields;
	}

	protected function prepare_fields( $fields ) {
		array_walk(
			$fields, function( &$field, $field_name ) {

				if ( in_array( $field_name, [ 'typography', 'popover_toggle' ] ) ) {
					return;
				}

				$selector_value = ! empty( $field['selector_value'] ) ? $field['selector_value'] : str_replace( '_', '-', $field_name ) . ': {{VALUE}};';

				$field['selectors'] = [
					'{{SELECTOR}}' => $selector_value,
				];
			}
		);

		return parent::prepare_fields( $fields );
	}

	protected function add_group_args_to_field( $control_id, $field_args ) {
		$field_args = parent::add_group_args_to_field( $control_id, $field_args );

		$field_args['groupPrefix'] = $this->get_controls_prefix();
		$field_args['groupType'] = 'typography';

		$args = $this->get_args();

		if ( in_array( $control_id, self::get_scheme_fields_keys() ) && ! empty( $args['scheme'] ) ) {
			$field_args['scheme'] = [
				'type' => self::get_type(),
				'value' => $args['scheme'],
				'key' => $control_id,
			];
		}

		return $field_args;
	}

	protected function get_default_options() {
		return [
			'popover' => [
				'starter_name' => 'typography',
				'starter_title' => _x( 'Typography', 'Editor: Typography', 'wpspeedo-team' ),
				'settings' => [
					'groupType' => 'typography',
				],
			],
		];
	}
}
