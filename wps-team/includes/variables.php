<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Variables {

    protected static $vars = [];

    public function __construct() {
        add_action( 'after_setup_theme', [ $this, 'init' ] );
    }

    public static function init() {
        self::$vars = [
            'group_single_name'             => Utils::get_setting('group_single_name'),
            'location_single_name'          => Utils::get_setting('location_single_name'),
            'language_single_name'          => Utils::get_setting('language_single_name'),
            'specialty_single_name'         => Utils::get_setting('specialty_single_name'),
            'gender_single_name'            => Utils::get_setting('gender_single_name'),
            'extra_one_single_name'         => Utils::get_setting('extra_one_single_name'),
            'extra_two_single_name'         => Utils::get_setting('extra_two_single_name'),
            'extra_three_single_name'       => Utils::get_setting('extra_three_single_name'),
            'extra_four_single_name'        => Utils::get_setting('extra_four_single_name'),
            'extra_five_single_name'        => Utils::get_setting('extra_five_single_name'),

            'panel_pos_label'               => _x('Panel Position', 'Editor', 'wps-team'),
            'custom_url_label'              => _x('Custom URL', 'Admin Metabox', 'wps-team'),
            'filter_txt'                    => _x('Filter', 'Admin', 'wps-team'),
            'label_txt'                     => _x('Label', 'Admin', 'wps-team'),
            'select_txt'                    => _x('Select', 'Admin', 'wps-team'),
            'filter_all_txt'                => _x('Filter All Text', 'Admin', 'wps-team'),
            'search_filter_txt'             => _x('Search Filter Text', 'Admin', 'wps-team'),
            'include_by_txt'                => _x('Include by', 'Admin', 'wps-team'),
            'exclude_by_txt'                => _x('Exclude by', 'Admin', 'wps-team'),
            'border_radius_txt'             => _x('Border Radius', 'Editor', 'wps-team'),
            'show_txt'                      => _x('Show', 'Editor', 'wps-team'),
            'hide_txt'                      => _x('Hide', 'Editor', 'wps-team'),
            'normal_txt'                    => _x('Normal', 'Editor', 'wps-team'),
            'hover_txt'                     => _x('Hover', 'Editor', 'wps-team'),
            'active_txt'                    => _x('Active', 'Editor', 'wps-team'),
            'text_color_txt'                => _x('Text Color', 'Editor', 'wps-team'),
            'select_size_txt'               => _x('Select Size', 'Editor', 'wps-team'),
            'padding_txt'                   => _x('Padding', 'Editor', 'wps-team'),
            'dot_bg_color_txt'              => _x('Dot BG Color', 'Editor', 'wps-team'),
            'dot_border_color_txt'          => _x('Dot Border Color', 'Editor', 'wps-team'),
            'background_color_type_txt'     => _x('Background Color Type', 'Editor', 'wps-team'),
            'background_color_txt'          => _x('Background Color', 'Editor', 'wps-team'),
            'border_color_type_txt'         => _x('Border Color Type', 'Editor', 'wps-team'),
            'border_color_txt'              => _x('Border Color', 'Editor', 'wps-team'),
            'icon_color_type_txt'           => _x('Icon Color Type', 'Editor', 'wps-team'),
            'icon_color_txt'                => _x('Icon Color', 'Editor', 'wps-team'),
            'order_by_txt'                  => _x('Order By', 'Editor', 'wps-team'),
            'order_txt'                     => _x('Order', 'Editor', 'wps-team'),
            'ascending_txt'                 => _x('Ascending', 'Editor', 'wps-team'),
            'descending_txt'                => _x('Descending', 'Editor', 'wps-team'),
            'typo_name_txt'                 => _x('Typo: Name', 'Editor', 'wps-team'),
            'typo_designation_txt'          => _x('Typo: Designation', 'Editor', 'wps-team'),
            'typo_content_txt'              => _x('Typo: Content', 'Editor', 'wps-team'),
            'typo_meta_txt'                 => _x('Typo: Meta', 'Editor', 'wps-team'),
            'typo_read_more_txt'            => _x('Typo: Read More', 'Editor', 'wps-team'),
            'links_radius_txt'              => _x('Links Radius', 'Editor', 'wps-team'),
            'resume_button_style_txt'       => _x('Resume Button Style', 'Editor', 'wps-team'),
            'hire_button_style_txt'         => _x('Hire Button Style', 'Editor', 'wps-team'),
            'thumbnail_spacing_txt'         => _x('Thumbnail Spacing', 'Editor', 'wps-team'),
            'title_spacing_txt'             => _x('Title Spacing', 'Editor', 'wps-team'),
            'designation_spacing_txt'       => _x('Designation Spacing', 'Editor', 'wps-team'),
            'desc_spacing_txt'              => _x('Description Spacing', 'Editor', 'wps-team'),
            'devider_spacing_txt'           => _x('Devider Spacing', 'Editor', 'wps-team'),
            'social_icons_spacing_txt'      => _x('Social Icons Spacing', 'Editor', 'wps-team'),
            'meta_info_spacing_txt'         => _x('Meta Info Spacing', 'Editor', 'wps-team'),
            'background_txt'                => _x('Background', 'Editor', 'wps-team'),
            'background_hover_txt'          => _x('Background Hover', 'Editor', 'wps-team'),
            'full_card_link_txt'            => _x('Full Card Link', 'Editor', 'wps-team'),
    
            'autoplay'                      => _x('Autoplay', 'Editor', 'wps-team'),
            'autoplay_delay'                => _x('Autoplay Delay', 'Editor', 'wps-team'),
            'pause_on_hover'                => _x('Pause On Hover', 'Editor', 'wps-team'),
            'dynamic_dots'                  => _x('Dynamic Dots', 'Editor', 'wps-team'),
            'scroll_nagivation'             => _x('Scroll Navigation', 'Editor', 'wps-team'),
            'keyboard_navigation'           => _x('Keyboard Navigation', 'Editor', 'wps-team'),
            'nav_icon_color_txt'            => _x('Nav Icon Color', 'Editor', 'wps-team'),
            'nav_bg_color_txt'              => _x('Nav BG Color', 'Editor', 'wps-team'),
            'nav_border_color_txt'          => _x('Nav Border Color', 'Editor', 'wps-team'),
		    'filter_text_color_txt'         => _x('Filter Text Color', 'Editor', 'wps-team'),
		    'filter_bg_color_txt'           => _x('Filter BG Color', 'Editor', 'wps-team'),
		    'filter_border_color_txt'       => _x('Filter Border Color', 'Editor', 'wps-team'),
            'enable_paging' 			    => _x('Enable Paging', 'Editor', 'wps-team'),
            'paging_type' 			        => _x('Paging Type', 'Editor', 'wps-team'),
            'ajax_paging_limit' 		    => _x('Load More Limit', 'Editor', 'wps-team'),
            'edge_page_links' 		        => _x('Page Spread Range', 'Editor', 'wps-team'),
            'enable_ajax_loading' 	        => _x('Enable AJAX Loading', 'Editor', 'wps-team'),
		    'set_custom_size_label'         => _x('Set Custom Size', 'Editor', 'wps-team'),
		    'set_custom_size_desc'          => _x('Enable the Crop Option to crop the image to exact dimensions', 'Editor', 'wps-team'),
            'container_custom_class'        => _x('Custom Class', 'Editor', 'wps-team'),
            'container_z_index'             => _x('Z Index', 'Editor', 'wps-team'),

            'enable_price_txt'              => _x('Enable Pricing', 'Admin Metabox', 'wps-team'),
            'currency_txt'                  => _x('Currency', 'Admin Metabox', 'wps-team'),
            'currency_pos_txt'              => _x('Currency position', 'Admin Metabox', 'wps-team'),
            'price_txt'                     => _x('Price', 'Admin Metabox', 'wps-team'),
            'sale_price_txt'                => _x('Sale Price', 'Admin Metabox', 'wps-team'),
            'min_price_txt'                 => _x('Min Price', 'Admin Metabox', 'wps-team'),
            'max_price_txt'                 => _x('Max Price', 'Admin Metabox', 'wps-team'),
        ];
    }

    public static function get( $key ) {
        return isset( self::$vars[ $key ] ) ? self::$vars[ $key ] : null;
    }

}