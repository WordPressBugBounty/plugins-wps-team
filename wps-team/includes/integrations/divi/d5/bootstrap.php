<?php
/**
 * Divi 5 native module bootstrap.
 *
 * Loaded from Integration_Divi. Self-guards when Divi 5 is not available.
 * Never require Divi core files via hardcoded ABSPATH paths.
 *
 * @package WPSpeedo_Team
 */

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether Divi 5 APIs needed for our module are available.
 */
function wps_team_divi_5_ready() {
	if ( ! function_exists( 'et_builder_d5_enabled' ) || ! et_builder_d5_enabled() ) {
		return false;
	}

	if ( ! interface_exists( \ET\Builder\Framework\DependencyManagement\Interfaces\DependencyInterface::class ) ) {
		return false;
	}

	if ( ! class_exists( \ET\Builder\Packages\ModuleLibrary\ModuleRegistration::class ) ) {
		return false;
	}

	return true;
}

/**
 * Register Divi 5 module on Divi's dependency tree (Divi loads interfaces first).
 *
 * @param object $dependency_tree Divi dependency tree.
 */
function wps_team_divi_5_register_on_tree( $dependency_tree ) {
	if ( ! wps_team_divi_5_ready() ) {
		return;
	}

	if ( ! class_exists( __NAMESPACE__ . '\\Divi_5_Module', false ) ) {
		require_once __DIR__ . '/module.php';
	}

	$dependency_tree->add_dependency( new Divi_5_Module() );
}

/**
 * Enqueue Divi 5 Visual Builder bundle.
 */
function wps_team_divi_5_enqueue_vb_assets() {
	if ( ! wps_team_divi_5_ready() ) {
		return;
	}

	if ( ! function_exists( 'et_core_is_fb_enabled' ) || ! et_core_is_fb_enabled() ) {
		return;
	}

	if ( ! class_exists( \ET\Builder\VisualBuilder\Assets\PackageBuildManager::class ) ) {
		return;
	}

	$script_url = WPS_TEAM_URL . 'includes/integrations/divi/d5/visual-builder/build/wps-team-divi-5.js';

	\ET\Builder\VisualBuilder\Assets\PackageBuildManager::register_package_build(
		[
			'name'    => 'wps-team-divi-5-visual-builder',
			'version' => WPS_TEAM_VERSION,
			'script'  => [
				'src'                => $script_url,
				'deps'               => [
					'react',
					'jquery',
					'divi-module-library',
					'divi-vendor-wp-hooks',
					'wp-hooks',
					'wp-api-fetch',
					'divi-rest',
				],
				'enqueue_top_window' => false,
				'enqueue_app_window' => true,
			],
		]
	);

	// Ensure team frontend assets are available for VB preview HTML.
	plugin()->assets->register_assets();
	plugin()->assets->build_assets_data_preview();
	plugin()->assets->enqueue_font_assets( plugin()->assets->assets['fonts'] );
	plugin()->assets->enqueue_style_assets( plugin()->assets->assets['styles'] );
	plugin()->assets->enqueue_script_assets( plugin()->assets->assets['scripts'] );
}

/**
 * Select options for Divi 5 divi/select field (id => { label }).
 *
 * @return array
 */
function wps_team_divi_5_select_options() {
	$options = [
		'0' => [
			'label' => Integration::shortcode_default_option(),
		],
	];

	$shortcodes = Integration::get_shortcodes();
	if ( empty( $shortcodes ) ) {
		return $options;
	}

	foreach ( $shortcodes as $item ) {
		// String keys required; numeric ints would still coerce badly in some encoders.
		$options[ (string) $item['id'] ] = [
			'label' => $item['name'],
		];
	}

	return $options;
}

/**
 * REST route for Divi 5 VB shortcode preview.
 */
function wps_team_divi_5_register_rest_routes() {
	register_rest_route(
		'wps-team/v1',
		'/divi-preview',
		[
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\wps_team_divi_5_rest_preview',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
			'args'                => [
				'id' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		]
	);

	register_rest_route(
		'wps-team/v1',
		'/divi-shortcodes',
		[
			'methods'             => 'GET',
			'callback'            => __NAMESPACE__ . '\\wps_team_divi_5_rest_shortcodes',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
		]
	);
}

/**
 * Convert select options to a stdClass so JSON keeps object keys (including "0").
 *
 * @param array $options id => [ 'label' => string ].
 * @return \stdClass
 */
function wps_team_divi_5_select_options_object( array $options ) {
	$object = new \stdClass();

	foreach ( $options as $id => $option ) {
		$item        = new \stdClass();
		$item->label = isset( $option['label'] ) ? (string) $option['label'] : (string) $id;
		$object->{(string) $id} = $item;
	}

	return $object;
}

/**
 * Return shortcode select options for Divi 5 VB.
 *
 * Must be a JSON object (not array). Sequential 0..n PHP array keys become a
 * JSON array and Divi's select then cannot resolve the selected option label.
 *
 * @return \WP_REST_Response
 */
function wps_team_divi_5_rest_shortcodes() {
	return rest_ensure_response(
		[
			'options' => wps_team_divi_5_select_options_object( wps_team_divi_5_select_options() ),
		]
	);
}

/**
 * Return rendered shortcode HTML for VB preview.
 *
 * @param \WP_REST_Request $request Request.
 * @return \WP_REST_Response|\WP_Error
 */
function wps_team_divi_5_rest_preview( $request ) {
	$id = $request->get_param( 'id' );

	if ( '' === $id || '0' === $id ) {
		return rest_ensure_response(
			[
				'html' => Integration::get_empty_message(),
			]
		);
	}

	$html = Divi_Module::get_shortcode( [ 'shortcode' => $id ] );

	return rest_ensure_response(
		[
			'html' => $html,
		]
	);
}

// Register early — callbacks self-guard when Divi 5 is unavailable.
add_action( 'divi_module_library_modules_dependency_tree', __NAMESPACE__ . '\\wps_team_divi_5_register_on_tree' );
add_action( 'divi_visual_builder_assets_before_enqueue_scripts', __NAMESPACE__ . '\\wps_team_divi_5_enqueue_vb_assets' );
add_action( 'rest_api_init', __NAMESPACE__ . '\\wps_team_divi_5_register_rest_routes' );
