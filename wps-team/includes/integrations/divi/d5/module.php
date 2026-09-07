<?php
/**
 * Divi 5 native WPS Team module.
 *
 * @package WPSpeedo_Team
 */

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\Module\Style;
use ET\Builder\Packages\Module\Module;
use ET\Builder\Packages\Module\Options\Element\ElementClassnames;
use ET\Builder\Packages\ModuleLibrary\ModuleRegistration;

/**
 * Divi 5 Team module (frontend registration + render).
 */
class Divi_5_Module implements DependencyInterface {

	/**
	 * Module JSON folder used by ModuleRegistration (may be a cache path).
	 *
	 * @var string
	 */
	private static $module_json_folder = '';

	/**
	 * Load / register the module.
	 *
	 * Called by Divi's DependencyInterface loader after builder-5 is ready.
	 */
	public function load() {
		self::register_module();
	}

	/**
	 * Register module metadata + render callback.
	 */
	public static function register_module() {
		if ( ! wps_team_divi_5_ready() ) {
			return;
		}

		$folder = self::prepare_module_json_folder();
		if ( ! $folder ) {
			return;
		}

		ModuleRegistration::register_module(
			$folder,
			[
				'render_callback' => [ __CLASS__, 'render_callback' ],
			]
		);
	}

	/**
	 * Build module.json with dynamic shortcode select options and write to uploads cache.
	 *
	 * Important: decode/encode as objects (not associative arrays). PHP converts JSON `{}`
	 * to `[]` and sequential string keys ("0","1",…) to JSON arrays — both break Divi 5.
	 *
	 * @return string|false Absolute path to folder containing module.json (+ conversion-outline.json).
	 */
	public static function prepare_module_json_folder() {
		$base_json_path = __DIR__ . '/module.json';
		$outline_path   = __DIR__ . '/conversion-outline.json';

		if ( ! file_exists( $base_json_path ) ) {
			return false;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		$json = json_decode( file_get_contents( $base_json_path ) );
		if ( ! $json instanceof \stdClass ) {
			return false;
		}

		$options_obj = wps_team_divi_5_select_options_object( self::get_select_options() );

		if ( isset( $json->attributes->shortcodeId->settings->innerContent->item->component->props ) ) {
			$json->attributes->shortcodeId->settings->innerContent->item->component->props->options = $options_obj;
		}

		$upload = wp_upload_dir();
		if ( ! empty( $upload['error'] ) ) {
			// Fallback: register from plugin dir without dynamic options.
			self::$module_json_folder = __DIR__;
			return self::$module_json_folder;
		}

		$folder = trailingslashit( $upload['basedir'] ) . 'wps-team/divi-5-module';
		if ( ! wp_mkdir_p( $folder ) ) {
			self::$module_json_folder = __DIR__;
			return self::$module_json_folder;
		}

		$encoded = wp_json_encode( $json, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $encoded ) {
			self::$module_json_folder = __DIR__;
			return self::$module_json_folder;
		}

		$written = file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$folder . '/module.json',
			$encoded
		);

		if ( false === $written ) {
			self::$module_json_folder = __DIR__;
			return self::$module_json_folder;
		}

		if ( file_exists( $outline_path ) ) {
			copy( $outline_path, $folder . '/conversion-outline.json' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_copy
		}

		self::$module_json_folder = $folder;
		return self::$module_json_folder;
	}

	/**
	 * Convert select options to a stdClass so JSON keeps object keys (including "0").
	 *
	 * @param array $options id => [ 'label' => string ].
	 * @return \stdClass
	 */
	public static function select_options_as_object( array $options ) {
		return wps_team_divi_5_select_options_object( $options );
	}

	/**
	 * Select options for Divi 5 divi/select field.
	 *
	 * @return array
	 */
	public static function get_select_options() {
		return wps_team_divi_5_select_options();
	}

	/**
	 * Extract shortcode ID from Divi 5 attrs.
	 *
	 * @param array $attrs Module attrs.
	 * @return string
	 */
	public static function get_shortcode_id_from_attrs( $attrs ) {
		$value = $attrs['shortcodeId']['innerContent']['desktop']['value'] ?? '';
		if ( is_array( $value ) ) {
			$value = $value['value'] ?? '';
		}
		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * Module classnames.
	 *
	 * @param array $args Args.
	 */
	public static function module_classnames( $args ) {
		$classnames_instance = $args['classnamesInstance'];
		$attrs               = $args['attrs'];

		$classnames_instance->add(
			ElementClassnames::classnames(
				[
					'attrs' => $attrs['module']['decoration'] ?? [],
				]
			)
		);
	}

	/**
	 * Module styles.
	 *
	 * @param array $args Args.
	 */
	public static function module_styles( $args ) {
		$elements = $args['elements'];

		Style::add(
			[
				'id'            => $args['id'],
				'name'          => $args['name'],
				'orderIndex'    => $args['orderIndex'],
				'storeInstance' => $args['storeInstance'],
				'styles'        => [
					$elements->style(
						[
							'attrName'   => 'module',
							'styleProps' => [
								'disabledOn' => [
									'disabledModuleVisibility' => $args['settings']['disabledModuleVisibility'] ?? null,
								],
							],
						]
					),
				],
			]
		);
	}

	/**
	 * Module script data.
	 *
	 * @param array $args Args.
	 */
	public static function module_script_data( $args ) {
		$elements = $args['elements'];
		$elements->script_data(
			[
				'attrName' => 'module',
			]
		);
	}

	/**
	 * Frontend render callback.
	 *
	 * @param array    $attrs    Attributes.
	 * @param string   $content  Content.
	 * @param \WP_Block $block   Block.
	 * @param object   $elements Elements helper.
	 * @return string
	 */
	public static function render_callback( $attrs, $content, $block, $elements ) {
		$shortcode_id = self::get_shortcode_id_from_attrs( $attrs );

		if ( '' === $shortcode_id || '0' === $shortcode_id ) {
			$inner_html = Integration::get_empty_message();
		} else {
			$inner_html = Divi_Module::get_shortcode( [ 'shortcode' => $shortcode_id ] );
		}

		$module_inner = HTMLUtility::render(
			[
				'tag'               => 'div',
				'attributes'        => [
					'class' => 'et_pb_module_inner wps_team_divi',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $inner_html,
			]
		);

		$module_elements = $elements->style_components(
			[
				'attrName' => 'module',
			]
		);

		return Module::render(
			[
				'orderIndex'          => $block->parsed_block['orderIndex'] ?? null,
				'storeInstance'       => $block->parsed_block['storeInstance'] ?? null,
				'attrs'               => $attrs,
				'elements'            => $elements,
				'id'                  => $block->parsed_block['id'] ?? '',
				'moduleClassName'     => 'wps_team_divi',
				'name'                => $block->block_type->name ?? 'wpspeedo/team',
				'classnamesFunction'  => [ __CLASS__, 'module_classnames' ],
				'moduleCategory'      => $block->block_type->category ?? 'module',
				'stylesComponent'     => [ __CLASS__, 'module_styles' ],
				'scriptDataComponent' => [ __CLASS__, 'module_script_data' ],
				'children'            => $module_elements . $module_inner,
			]
		);
	}
}
