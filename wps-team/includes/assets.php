<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Assets extends Assets_Manager {
    use Setting_Methods, AJAX_Template_Methods;
    public $settings;

    public function __construct() {
        $this->set_ajax_scope_hooks( '_assets_handler' );
        parent::__construct();
    }

    public function is_preview() {
        return Utils::is_shortcode_preview();
    }

    public function get_assets_key() {
        return 'wps-team';
    }

    public function asset_handler() {
        return 'wpspeedo-team';
    }

    public function is_frame_loading() {
        return !empty( $_GET['wps_team_sh_preview'] ) && $_GET['wps_team_sh_preview'] === 'wpspeedo_wps_team_frame_view';
    }

    public function build_assets_data( array $settings ) {
        $this->settings = $settings;
        $display_type = $this->get_setting( 'display_type' );
        $card_action = $this->get_setting( 'card_action' );
        $this->add_item_in_asset_list( 'styles', $this->asset_handler() );
        $this->add_item_in_asset_list( 'scripts', $this->asset_handler(), ['jquery'] );
        $this->add_item_in_asset_list( 'styles', $this->asset_handler(), ['wpspeedo-swiper'] );
        $this->add_item_in_asset_list( 'scripts', $this->asset_handler(), ['wpspeedo-swiper'] );
        if ( $display_type == 'carousel' ) {
        }
        if ( plugin()->integrations->is_divi_active() ) {
            $this->add_item_in_asset_list( 'styles', $this->asset_handler(), [$this->asset_handler() . '-divi'] );
        }
        $css = $this->get_custom_css( $settings['id'] );
        if ( !empty( $css ) ) {
            $this->add_item_in_asset_list( 'styles', 'inline', $css );
        }
    }

    public function build_assets_data_preview() {
        $this->add_item_in_asset_list( 'styles', $this->asset_handler(), ['wpspeedo-swiper'] );
        $this->add_item_in_asset_list( 'scripts', $this->asset_handler(), ['jquery', 'wpspeedo-swiper'] );
        if ( plugin()->integrations->is_divi_active() ) {
            $this->add_item_in_asset_list( 'styles', $this->asset_handler(), [$this->asset_handler() . '-divi'] );
        }
    }

    public function get_widget_fonts( $settings ) {
        $fonts = [];
        if ( !empty( $settings['typo_name_font_family'] ) ) {
            $fonts[] = $settings['typo_name_font_family']['value'];
        }
        if ( !empty( $settings['typo_desig_font_family'] ) ) {
            $fonts[] = $settings['typo_desig_font_family']['value'];
        }
        if ( !empty( $settings['typo_content_font_family'] ) ) {
            $fonts[] = $settings['typo_content_font_family']['value'];
        }
        if ( !empty( $settings['typo_meta_font_family'] ) ) {
            $fonts[] = $settings['typo_meta_font_family']['value'];
        }
        if ( empty( $fonts ) ) {
            $fonts = ['Cambo', 'Roboto', 'Fira Sans'];
        }
        return $fonts;
    }

    public function public_scripts() {
        if ( $this->is_preview() ) {
            $this->force_enqueue_assets();
            return;
        }
        $this->register_assets();
        $this->enqueue();
        $enabled_taxonomies = Utils::archive_enabled_taxonomies();
        if ( is_singular( 'wps-team-members' ) || is_post_type_archive( 'wps-team-members' ) || !empty( $enabled_taxonomies ) && is_tax( $enabled_taxonomies ) ) {
            if ( is_singular( 'wps-team-members' ) ) {
                wp_enqueue_style( 'wpspeedo-swiper' );
                wp_enqueue_script( 'wpspeedo-swiper' );
            }
            wp_enqueue_style( $this->asset_handler() );
            wp_enqueue_script( $this->asset_handler() );
            $css = $this->get_singular_styles();
            if ( !empty( $css ) ) {
                wp_add_inline_style( $this->asset_handler(), $this->get_singular_styles() );
            }
        }
    }

    public function get_singular_styles() {
        $Assets_Singular = new Assets_Singular();
        return $Assets_Singular->get_custom_css( null );
    }

    public function register_assets() {
        wp_register_style(
            'wpspeedo-fontawesome--all',
            WPS_TEAM_ASSET_URL . 'libs/fontawesome/css/all.min.css',
            '',
            WPS_TEAM_VERSION
        );
        wp_register_style(
            'wpspeedo-swiper',
            WPS_TEAM_ASSET_URL . 'libs/swiper/swiper-bundle.min.css',
            [],
            WPS_TEAM_VERSION
        );
        wp_register_script(
            'wpspeedo-swiper',
            WPS_TEAM_ASSET_URL . 'libs/swiper/swiper-bundle.min.js',
            [],
            WPS_TEAM_VERSION,
            true
        );
        wp_register_style(
            $this->asset_handler(),
            WPS_TEAM_ASSET_URL . 'css/style.min.css',
            ['wpspeedo-fontawesome--all'],
            WPS_TEAM_VERSION
        );
        wp_register_style(
            $this->asset_handler() . '-divi',
            WPS_TEAM_ASSET_URL . 'css/style-divi.min.css',
            [],
            WPS_TEAM_VERSION
        );
        wp_register_script(
            $this->asset_handler(),
            WPS_TEAM_ASSET_URL . 'js/script.min.js',
            ['jquery'],
            WPS_TEAM_VERSION,
            true
        );
        wp_register_style(
            $this->asset_handler() . '-preview',
            WPS_TEAM_ASSET_URL . 'admin/css/preview.min.css',
            [$this->asset_handler()],
            WPS_TEAM_VERSION
        );
        wp_register_script(
            $this->asset_handler() . '-preview',
            WPS_TEAM_ASSET_URL . 'admin/js/preview.min.js',
            [$this->asset_handler(), 'underscore'],
            WPS_TEAM_VERSION,
            true
        );
        $preview_data = [
            'is_pro' => wps_team_fs()->can_use_premium_code__premium_only(),
        ];
        wp_localize_script( $this->asset_handler() . '-preview', '_wps_team_preview_data', $preview_data );
    }

    public function generate_css( $shortcode_id ) {
        $selector = $this->shortcode_selector( $shortcode_id );
        $selector_popup = $this->shortcode_selector_popup( $shortcode_id );
        $selector_expand = $selector . ' .wps-widget-container-expand';
        $selector_side_panel = $this->shortcode_selector_side_panel( $shortcode_id );
        $this->add_responsive_style(
            $selector,
            '--wps-container-width: {{value}}{{unit}}',
            'container_width',
            ['value', 'unit']
        );
        $this->add_responsive_style( $selector, '--wps-item-col-gap-alt: calc(-{{value}}px)', 'gap' );
        $this->add_responsive_style( $selector, '--wps-item-col-gap: calc({{value}}px)', 'gap' );
        $this->add_responsive_style(
            $selector,
            '--wps-item-col-gap-vert: calc({{value}}px)',
            'gap_vertical',
            'value',
            'gap'
        );
        $this->add_responsive_style( $selector, '--wps-item-col-width: calc(100%/{{value}}*0.9999999)', 'columns' );
        $this->add_background_style( $selector, 'item_background_', '--wps-item-bg-color' );
        $this->add_style( $selector, '--wps-title-color: {{value}}', 'title_color' );
        $this->add_style( $selector, '--wps-title-color-hover: {{value}}', 'title_color_hover' );
        $selector_group_1 = implode( ',', [
            $selector,
            $selector_expand,
            $selector_popup,
            $selector_side_panel
        ] );
        $this->add_style( $selector_group_1, '--wps-ribbon-color: {{value}}', 'ribbon_text_color' );
        $this->add_style( $selector_group_1, '--wps-ribbon-bg-color: {{value}}', 'ribbon_bg_color' );
        $this->add_style( $selector, '--wps-desig-color: {{value}}', 'designation_color' );
        $this->add_style( $selector, '--wps-text-color: {{value}}', 'desc_color' );
        $this->add_style( $selector . ' .wps-team--divider', '--wps-divider-bg-color: {{value}}', 'divider_color' );
        $this->add_style( $selector, '--wps-info-icon-color: {{value}}', 'info_icon_color' );
        $this->add_style( $selector, '--wps-info-text-color: {{value}}', 'info_text_color' );
        $this->add_style( $selector, '--wps-info-link-color: {{value}}', 'info_link_color' );
        $this->add_style( $selector, '--wps-info-link-hover-color: {{value}}', 'info_link_hover_color' );
        $this->add_style( $selector, '--wps-thumb-object-pos: {{value}}', 'thumbnail_position' );
        if ( !empty( $aspect_ratio = $this->settings['aspect_ratio'] ) && $aspect_ratio['value'] !== 'default' ) {
            $this->add_style( $selector, '--wps-thumb-aspect-ratio: {{value}}', 'aspect_ratio' );
        }
    }

}
