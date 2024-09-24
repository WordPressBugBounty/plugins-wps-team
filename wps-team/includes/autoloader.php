<?php

namespace WPSpeedo_Team;

if ( ! defined( 'ABSPATH' ) ) exit;

class Autoloader {

	private static $classes_map;

	public static function run() {

		spl_autoload_register( [ __CLASS__, 'autoload' ] );

	}

	public static function get_classes_map() {

		if ( ! self::$classes_map ) {
			self::init_classes_map();
		}

		return self::$classes_map;
		
	}

	private static function init_classes_map() {

		self::$classes_map = [

			'Setting_Methods' => 'includes/traits/setting-methods.php',
			'AJAX_Template_Methods' => 'includes/traits/ajax-template-methods.php',
			'AJAX_Handler' => 'includes/traits/ajax-handler.php',
			'Date_Methods' => 'includes/traits/date-methods.php',
			
			'Utils' => 'includes/utils.php',
			'Assets' => 'includes/assets.php',
			'Assets_Singular' => 'includes/assets-singular.php',
			'Plugin' => 'includes/plugin.php',
			'Upgrader' => 'includes/upgrader.php',
			'Admin' => 'includes/admin/admin.php',
			'API' => 'includes/admin/api.php',
			'AJAX_Loading' => 'includes/ajax-loading.php',
			'Translations' => 'includes/translations.php',

			'Elementor_Widget' => 'includes/integrations/elementor/widget.php',
			'Integrations' => 'includes/integrations/integrations.php',
			'Integration' => 'includes/integrations/model/integration.php',
			'Integration_Elementor' => 'includes/integrations/elementor/integration.php',
			'Integration_Gutenberg' => 'includes/integrations/gutenberg/integration.php',
			'Integration_Divi' => 'includes/integrations/divi/integration.php',
			'Divi_Module' => 'includes/integrations/divi/module.php',

			'Taxonomy' => 'includes/traits/taxonomy.php',
			'Data' => 'includes/data.php',
			'Shortcode_Loader' => 'includes/loaders/shortcode-loader.php',
			'Single_Loader' => 'includes/loaders/single-loader.php',
			'Archive_Loader' => 'includes/loaders/archive-loader.php',
			'Plugin_Hooks' => 'includes/plugin-hooks.php',

			'Fonts' => 'includes/fonts.php',

			'Base_Control' => 'includes/base/base-control.php',
			'Group_Base_Control' => 'includes/base/group-base-control.php',
			'Base_Data_Control' => 'includes/base/base-data-control.php',
			'Base_Object' => 'includes/base/base-object.php',
			'Controls_Stack' => 'includes/base/controls-stack.php',
			'Repeater' => 'includes/base/repeater.php',
			'Editor_Controls' => 'includes/base/editor-controls.php',
			'Base_Notification' => 'includes/base/base-notification.php',

			'Conditions' => 'includes/conditions.php',
			'Controls_Manager' => 'includes/managers/controls-manager.php',
			'Icon_Manager' => 'includes/managers/icon-manager.php',
			'Attribute_Manager' => 'includes/managers/attribute-manager.php',
			'Style_Manager' => 'includes/managers/style-manager.php',
			'Assets_Manager' => 'includes/managers/assets-manager.php',
			'Notification_Manager' => 'includes/managers/notification-manager.php',
			'Import_Export_Manager' => 'includes/managers/import-export-manager.php',

			'Notifications' => 'includes/notifications/notifications.php',
			'Notification' => 'includes/notifications/model/notification.php',
			'Notice' => 'includes/notifications/model/notice.php',
			'Popup' => 'includes/notifications/model/popup.php',
			'Rating_Notice' => 'includes/notifications/rating-notice.php',
			'Demo_Import_Notice' => 'includes/notifications/demo-import-notice.php',

			'Control_Text' => 'includes/controls/text.php',
			'Control_Hidden' => 'includes/controls/hidden.php',
			'Control_Number' => 'includes/controls/number.php',
			'Control_Choose' => 'includes/controls/choose.php',
			'Control_Textarea' => 'includes/controls/textarea.php',
			'Control_Select' => 'includes/controls/select.php',
			'Control_Upgrade_Notice' => 'includes/controls/upgrade-notice.php',
			'Control_Heading' => 'includes/controls/heading.php',
			'Control_Font' => 'includes/controls/font.php',
			'Control_Switcher' => 'includes/controls/switcher.php',
			'Control_Slider' => 'includes/controls/slider.php',
			'Control_Dimensions' => 'includes/controls/dimensions.php',
			'Control_Repeater' => 'includes/controls/repeater.php',
			'Control_Icon' => 'includes/controls/icon.php',
			'Control_Color' => 'includes/controls/color.php',
			'Control_Wysiwyg' => 'includes/controls/wysiwyg.php',
			'Control_Tabs' => 'includes/controls/tabs.php',
			'Control_Tab' => 'includes/controls/tab.php',
			'Control_Custom_Image_Size' => 'includes/controls/custom-image-size.php',
			'Control_Popover_Toggle' => 'includes/controls/popover-toggle.php',
			'Group_Control_Background' => 'includes/controls/groups/background.php',
			'Group_Control_Typography' => 'includes/controls/groups/typography.php',
			'Group_Control_Border' => 'includes/controls/groups/border.php',
			'Group_Control_Box_Shadow' => 'includes/controls/groups/box-shadow.php',
			'Group_Control_Text_Shadow' => 'includes/controls/groups/text-shadow.php',

			'Control_Section' => 'includes/controls/section.php',
			
			'Shortcode_Editor' => 'includes/editor/shortcode-editor.php',
			'Meta_Box_Editor' => 'includes/editor/meta-box-editor.php',
			'Settings_Editor' => 'includes/editor/settings-editor.php',

			'Shortcode' => 'includes/shortcode.php',
			'Demo_Import' => 'includes/demo-import/demo-import.php'
			
		];

	}

	private static function load_class( $relative_class_name ) {

		$classes_map = self::get_classes_map();

		if ( isset( $classes_map[ $relative_class_name ] ) ) {

			$filename = WPS_TEAM_PATH . '/' . $classes_map[ $relative_class_name ];

		} else {

			$filename = strtolower(
				preg_replace(
					[ '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
					[ '$1-$2', '-', DIRECTORY_SEPARATOR ],
					$relative_class_name
				)
			);

			$filename = WPS_TEAM_PATH . $filename . '.php';

		}

		if ( is_readable( $filename ) ) require $filename;

	}

	private static function autoload( $class ) {

		if ( 0 !== strpos( $class, __NAMESPACE__ . '\\' ) ) return;

		$relative_class_name = preg_replace( '/^' . __NAMESPACE__ . '\\\/', '', $class );

		$final_class_name = __NAMESPACE__ . '\\' . $relative_class_name;

		if ( ! class_exists( $final_class_name ) ) self::load_class( $relative_class_name );

	}

}