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

		if ( empty( $attributes['class'] ) ) {
			$attributes['class'] = $icon['icon'];
		} else {
			if ( is_array( $attributes['class'] ) ) {
				$attributes['class'][] = $icon['icon'];
			} else {
				$attributes['class'] .= ' ' . $icon['icon'];
			}
		}

		if ( str_contains( $icon['icon'], 'fa-bluesky' ) ) {
			return '<svg viewBox="0 0 512 512" fill="currentColor"><path d="M111.8 62.2C170.2 105.9 233 194.7 256 242.4c23-47.6 85.8-136.4 144.2-180.2c42.1-31.6 110.3-56 110.3 21.8c0 15.5-8.9 130.5-14.1 149.2C478.2 298 412 314.6 353.1 304.5c102.9 17.5 129.1 75.5 72.5 133.5c-107.4 110.2-154.3-27.6-166.3-62.9l0 0c-1.7-4.9-2.6-7.8-3.3-7.8s-1.6 3-3.3 7.8l0 0c-12 35.3-59 173.1-166.3 62.9c-56.5-58-30.4-116 72.5-133.5C100 314.6 33.8 298 15.7 233.1C10.4 214.4 1.5 99.4 1.5 83.9c0-77.8 68.2-53.4 110.3-21.8z"/></svg>';
		} else if ( str_contains( $icon['icon'], 'fa-square-bluesky' ) ) {
			return '<svg viewBox="0 0 448 512" fill="currentColor"><path d="M64 32C28.7 32 0 60.7 0 96L0 416c0 35.3 28.7 64 64 64l320 0c35.3 0 64-28.7 64-64l0-320c0-35.3-28.7-64-64-64L64 32zM224 247.4c14.5-30 54-85.8 90.7-113.3c26.5-19.9 69.3-35.2 69.3 13.7c0 9.8-5.6 82.1-8.9 93.8c-11.4 40.8-53 51.2-90 44.9c64.7 11 81.2 47.5 45.6 84c-67.5 69.3-97-17.4-104.6-39.6c0 0 0 0 0 0l-.3-.9c-.9-2.6-1.4-4.1-1.8-4.1s-.9 1.5-1.8 4.1c-.1 .3-.2 .6-.3 .9c0 0 0 0 0 0c-7.6 22.2-37.1 108.8-104.6 39.6c-35.5-36.5-19.1-73 45.6-84c-37 6.3-78.6-4.1-90-44.9c-3.3-11.7-8.9-84-8.9-93.8c0-48.9 42.9-33.5 69.3-13.7c36.7 27.5 76.2 83.4 90.7 113.3z"/></svg>';
		}

		return '<' . $tag . ' ' . Utils::render_html_attributes( $attributes ) . '></' . $tag . '>';

	}

	public static function render_icon( $icon, $attributes = [], $tag = 'i' ) {
		
		if ( empty( $icon['library'] ) ) return false;

		echo wp_kses_post( self::render_font_icon( $icon, $attributes, $tag ) );

		return true;

	}

}