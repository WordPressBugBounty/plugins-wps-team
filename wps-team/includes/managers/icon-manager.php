<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Icon_Manager {

	private static $tabs;

	private static function init_tabs() {
		$initial_tabs = [
			'fa-regular' => [
				'name' => 'fa-regular',
				'label' => esc_html_x( 'Font Awesome - Regular', 'Editor: Icon', 'wpspeedo-team' ),
				'url' => self::get_fa_asset_url( 'regular' ),
				'enqueue' => [ self::get_fa_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'far',
				'labelIcon' => 'fab fa-font-awesome-alt',
				'ver' => '5.15.4',
				'getIcons' => self::get_fa_asset_url( 'regular', 'js', false ),
				'native' => true,
			],
			'fa-solid' => [
				'name' => 'fa-solid',
				'label' => esc_html_x( 'Font Awesome - Solid', 'Editor: Icon', 'wpspeedo-team' ),
				'url' => self::get_fa_asset_url( 'solid' ),
				'enqueue' => [ self::get_fa_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'fas',
				'labelIcon' => 'fab fa-font-awesome',
				'ver' => '5.15.4',
				'getIcons' => self::get_fa_asset_url( 'solid', 'js', false ),
				'native' => true,
			],
			'fa-brands' => [
				'name' => 'fa-brands',
				'label' => esc_html_x( 'Font Awesome - Brands', 'Editor: Icon', 'wpspeedo-team' ),
				'url' => self::get_fa_asset_url( 'brands' ),
				'enqueue' => [ self::get_fa_asset_url( 'fontawesome' ) ],
				'prefix' => 'fa-',
				'displayPrefix' => 'fab',
				'labelIcon' => 'fab fa-font-awesome-flag',
				'ver' => '5.15.4',
				'getIcons' => self::get_fa_asset_url( 'brands', 'js', false ),
				'native' => true,
			],
		];

		$initial_tabs = apply_filters( 'wpspeedo_team/icons_manager/native', $initial_tabs );

		self::$tabs = $initial_tabs;
	}

	public static function get_icon_manager_tabs() {

		if ( ! self::$tabs ) self::init_tabs();

		$additional_tabs = (array) apply_filters( 'wpspeedo_team/icons_manager/additional_tabs', [] );

		return array_merge( self::$tabs, $additional_tabs );

	}

	private static function get_fa_asset_url( $filename, $ext_type = 'css', $add_suffix = true ) {
		$url = WPS_TEAM_ASSET_URL . 'libs/fontawesome/' . $ext_type . '/' . $filename;
		if ( $add_suffix ) $url .= '.min';
		return $url . '.' . $ext_type;
	}

	public static function get_icon_manager_tabs_config() {

		$tabs = [
			'all' => [
				'name' => 'all',
				'label' => esc_html_x( 'All Icons', 'Editor: Icon', 'wpspeedo-team' ),
				'labelIcon' => 'fas fa-bars',
				'native' => true,
			],
		];

		return array_values( array_merge( $tabs, self::get_icon_manager_tabs() ) );
	}

	public static function render_font_icon( $icon, $attributes = [], $tag = 'i' ) {

		$icon_types = self::get_icon_manager_tabs();

		if ( isset( $icon_types[ $icon['library'] ]['render_callback'] ) && is_callable( $icon_types[ $icon['library'] ]['render_callback'] ) ) {
			return call_user_func_array( $icon_types[ $icon['library'] ]['render_callback'], [ $icon, $attributes, $tag ] );
		}

		$content = '';

		if ( ! $content ) {
			if ( empty( $attributes['class'] ) ) {
				$attributes['class'] = $icon['icon'];
			} else {
				if ( is_array( $attributes['class'] ) ) {
					$attributes['class'][] = $icon['icon'];
				} else {
					$attributes['class'] .= ' ' . $icon['icon'];
				}
			}
		}

		return '<' . $tag . ' ' . Utils::render_html_attributes( $attributes ) . '></' . $tag . '>';

	}

	public static function render_icon( $icon, $attributes = [], $tag = 'i' ) {
		
		if ( empty( $icon['library'] ) ) return false;

		echo wp_kses_post( self::render_font_icon( $icon, $attributes, $tag ) );

		return true;

	}

}