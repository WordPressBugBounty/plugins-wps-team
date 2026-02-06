<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Shortcode_Editor extends Editor_Controls {
    public $taxonomies = [];

    public function __construct( array $data = [], $args = null ) {
        parent::__construct( $data, $args );
        do_action( 'wpspeedo_team/shortcode_editor/init', $this );
    }

    public function get_name() {
        return 'shortcode_editor';
    }

    protected function _register_controls() {
        $this->taxonomies = Utils::get_active_taxonomies();
        // General Section
        $this->general_section_group();
        // Elements Section
        $this->elements_section_group();
        // Query Section
        $this->query_section_group();
        // Style Section
        $this->style_section_group();
        // Typography Section
        $this->typo_section_group();
        // Advance Section
        $this->advance_section_group();
    }

    /**
     * General Section
     */
    protected function general_section_group() {
        // Layout Section
        $this->layout_section();
        // Carousel Section
        $this->carousel_section();
    }

    // Layout Section
    protected function layout_section() {
        $this->start_controls_section( 'layout_section', [
            'label' => _x( 'Layout', 'Editor', 'wps-team' ),
        ] );
        $this->add_control( 'display_type', [
            'label'       => _x( 'Display Type', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'display_type' ),
            'default'     => 'grid',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'theme', [
            'label'       => _x( 'Theme', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'theme' ),
            'default'     => 'square-01',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'card_action', [
            'label'       => _x( 'Card Action', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'card_action' ),
            'default'     => 'single-page',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'full_card_link', [
            'label'       => Variables::get( 'full_card_link_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_responsive_control( 'expand_top_space', [
            'label'                => _x( 'Expand Top Space', 'Editor', 'wps-team' ),
            'label_block'          => true,
            'type'                 => Controls_Manager::SLIDER,
            'min'                  => -500,
            'max'                  => 500,
            'default'              => 50,
            'tablet_default'       => 50,
            'small_tablet_default' => 50,
            'mobile_default'       => 50,
            'condition'            => [
                'card_action' => 'expand',
            ],
        ] );
        $this->add_responsive_control( 'container_width', [
            'label'                => _x( 'Container Width', 'Editor', 'wps-team' ),
            'label_block'          => true,
            'type'                 => Controls_Manager::SLIDER,
            'size_units'           => ['%', 'px', 'vw'],
            'range'                => [
                '%'  => [
                    'min'     => 1,
                    'max'     => 100,
                    'default' => 100,
                ],
                'px' => [
                    'min'     => 1,
                    'max'     => 2000,
                    'default' => 1200,
                ],
                'vw' => [
                    'min'     => 1,
                    'max'     => 100,
                    'default' => 80,
                ],
            ],
            'unit'                 => 'px',
            'tablet_unit'          => '%',
            'small_tablet_unit'    => '%',
            'mobile_unit'          => '%',
            'default'              => 1200,
            'tablet_default'       => 90,
            'small_tablet_default' => 90,
            'mobile_default'       => 85,
        ] );
        $this->add_responsive_control( 'columns', [
            'label'                => _x( 'Columns', 'Editor', 'wps-team' ),
            'label_block'          => false,
            'type'                 => Controls_Manager::NUMBER,
            'default'              => 3,
            'tablet_default'       => 3,
            'small_tablet_default' => 2,
            'mobile_default'       => 1,
            'class'                => 'wps-field--arrange-2',
        ] );
        $this->add_responsive_control( 'gap', [
            'label'       => _x( 'Gap', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::NUMBER,
            'class'       => 'wps-field--arrange-2',
        ] );
        $this->add_responsive_control( 'gap_vertical', [
            'label'       => _x( 'Gap Vertical', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::NUMBER,
            'class'       => 'wps-field--arrange-2',
            'condition'   => [
                'display_type' => ['grid', 'filter', 'masonry'],
            ],
        ] );
        $this->add_control( 'description_length', [
            'label'       => _x( 'Max Characters for Description', 'Editor', 'wps-team' ),
            'description' => _x( 'Set 0 to get full content.', 'Editor', 'wps-team' ),
            'label_block' => true,
            'render_type' => 'template',
            'type'        => Controls_Manager::SLIDER,
            'min'         => 0,
            'max'         => 1000,
            'step'        => 10,
            'default'     => 110,
        ] );
        $this->add_control( 'add_read_more', [
            'label'       => _x( 'Read More Link', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => false,
            'render_type' => 'template',
        ] );
        $this->add_control( 'read_more_text', [
            'label'       => _x( 'Read More Text', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::TEXT,
            'default'     => Utils::get_default( 'read_more_text' ),
            'render_type' => 'template',
            'condition'   => [
                'add_read_more' => true,
            ],
        ] );
        $this->end_controls_section();
    }

    // Carousel Section
    protected function carousel_section() {
        $this->start_controls_section( 'carousel_section', [
            'label'     => _x( 'Carousel Settings', 'Editor', 'wps-team' ),
            'condition' => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'speed', [
            'label'       => _x( 'Carousel Speed', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SLIDER,
            'min'         => 100,
            'max'         => 5000,
            'step'        => 100,
            'default'     => 800,
        ] );
        $this->add_control( 'dots', [
            'label'       => _x( 'Dots Pagination', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'navs', [
            'label'       => _x( 'Arrow Navigation', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'loop', [
            'label'       => _x( 'Carousel Loop', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'autoplay', [
            'label'       => Variables::get( 'autoplay' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'autoplay_delay', [
            'label'       => Variables::get( 'autoplay_delay' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'pause_on_hover', [
            'label'       => Variables::get( 'pause_on_hover' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'dynamic_dots', [
            'label'       => Variables::get( 'dynamic_dots' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'scroll_nagivation', [
            'label'       => Variables::get( 'scroll_nagivation' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'keyboard_navigation', [
            'label'       => Variables::get( 'keyboard_navigation' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    /**
     * Elements Section
     */
    protected function elements_section_group() {
        // Elements Section
        $this->elements_section();
        // Details
        $this->details_elements_section();
    }

    // Elements Section
    protected function elements_section() {
        $this->start_controls_section( 'elements_section', [
            'label' => _x( 'Elements Visibility', 'Editor', 'wps-team' ),
            'tab'   => 'elements',
        ] );
        $elements = Utils::allowed_elements_display_order();
        foreach ( Utils::elements_display_order() as $element_key => $element_title ) {
            if ( in_array( $element_key, $elements ) ) {
                $element_key = 'show_' . $element_key;
                $this->add_control( $element_key, [
                    'label'       => $element_title,
                    'label_block' => false,
                    'type'        => Controls_Manager::CHOOSE,
                    'options'     => [
                        'true'  => [
                            'title' => Variables::get( 'show_txt' ),
                            'icon'  => 'fas fa-eye',
                        ],
                        'false' => [
                            'title' => Variables::get( 'hide_txt' ),
                            'icon'  => 'fas fa-eye-slash',
                        ],
                    ],
                    'render_type' => 'template',
                ] );
            } else {
                $element_key = 'show_' . $element_key;
                $this->add_control( $element_key, [
                    'label'       => $element_title,
                    'label_block' => false,
                    'type'        => Controls_Manager::UPGRADE_NOTICE,
                ] );
            }
        }
        $this->end_controls_section();
    }

    // Details Elements Section
    protected function details_elements_section() {
        $this->start_controls_section( 'details_elements_section', [
            'label' => _x( 'Details Elements Visibility', 'Editor', 'wps-team' ),
            'tab'   => 'elements',
        ] );
        $elements = Utils::allowed_elements_display_order( 'details' );
        foreach ( Utils::elements_display_order( 'details' ) as $element_key => $element_title ) {
            if ( in_array( $element_key, $elements ) ) {
                $element_key = 'show_details_' . $element_key;
                $this->add_control( $element_key, [
                    'label'       => $element_title,
                    'label_block' => false,
                    'type'        => Controls_Manager::CHOOSE,
                    'options'     => [
                        'true'  => [
                            'title' => Variables::get( 'show_txt' ),
                            'icon'  => 'fas fa-eye',
                        ],
                        'false' => [
                            'title' => Variables::get( 'hide_txt' ),
                            'icon'  => 'fas fa-eye-slash',
                        ],
                    ],
                    'render_type' => 'template',
                ] );
            } else {
                $element_key = 'show_details_' . $element_key;
                $this->add_control( $element_key, [
                    'label'       => $element_title,
                    'label_block' => false,
                    'type'        => Controls_Manager::UPGRADE_NOTICE,
                ] );
            }
        }
        $this->end_controls_section();
    }

    /**
     * Style Section
     */
    protected function style_section_group() {
        // Text & Icons
        $this->style_text_icon_controls();
        // Single Item
        $this->style_item_styling_controls();
        // Custom Spacing
        $this->style_custom_spacing_controls();
        // Buttons
        $this->style_buttons_controls();
        // Carousel
        $this->style_carousel_color_controls();
        // Filters
        $this->style_filter_color_controls();
        // Social Links
        $this->style_social_links_controls();
        // Details Social Links
        $this->style_details_social_links_controls();
    }

    // Text & Icons
    protected function style_text_icon_controls() {
        $this->start_controls_section( 'style_section', [
            'label' => _x( 'Text & Icon Colors', 'Editor', 'wps-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'title_color', [
            'label'       => _x( 'Name Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'title_color_hover', [
            'label'       => _x( 'Name Color Hover', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'ribbon_text_color', [
            'label'       => _x( 'Ribbon Text Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'ribbon_bg_color', [
            'label'       => _x( 'Ribbon BG Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'designation_color', [
            'label'       => _x( 'Designation Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'desc_color', [
            'label'       => _x( 'Description Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'divider_color', [
            'label'       => _x( 'Divider Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_icon_color', [
            'label'       => _x( 'Info Icon Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_text_color', [
            'label'       => _x( 'Info Text Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_color', [
            'label'       => _x( 'Info Link Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_hover_color', [
            'label'       => _x( 'Info Link Hover Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'read_more_text_color', [
            'label'       => _x( 'Read More Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'read_more_text_hover_color', [
            'label'       => _x( 'Read More Hover Color', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_section();
    }

    // Single Item
    protected function style_item_styling_controls() {
        $this->start_controls_section( 'single_item_style', [
            'label' => _x( 'Single Item Style', 'Editor', 'wps-team' ),
            'tab'   => 'style',
        ] );
        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'  => 'item_background',
            'label' => Variables::get( 'background_txt' ),
            'types' => ['classic', 'gradient'],
        ] );
        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'  => 'item_background_hover',
            'label' => Variables::get( 'background_hover_txt' ),
            'types' => ['classic', 'gradient'],
        ] );
        $this->add_control( 'item_padding', [
            'label'       => Variables::get( 'padding_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'item_border_radius', [
            'label'       => Variables::get( 'border_radius_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Custom Spacing
    protected function style_custom_spacing_controls() {
        $this->start_controls_section( 'custom_spacing_styling', [
            'label' => _x( 'Space Customization', 'Editor', 'wps-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'thumbnail_spacing', [
            'label'       => Variables::get( 'thumbnail_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'title_spacing', [
            'label'       => Variables::get( 'title_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'desig_spacing', [
            'label'       => Variables::get( 'designation_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'desc_spacing', [
            'label'       => Variables::get( 'desc_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'devider_spacing', [
            'label'       => Variables::get( 'devider_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'social_spacing', [
            'label'       => Variables::get( 'social_icons_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'info_spacing', [
            'label'       => Variables::get( 'meta_info_spacing_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Buttons
    protected function style_buttons_controls() {
        $this->start_controls_section( 'buttons_styling', [
            'label' => _x( 'Resume & Hire Buttons', 'Editor', 'wps-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'heading_resume_button_style', [
            'label'       => Variables::get( 'resume_button_style_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'heading_hire_button_style', [
            'label'       => Variables::get( 'hire_button_style_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Carousel
    protected function style_carousel_color_controls() {
        $this->start_controls_section( 'carousel_styling', [
            'label'     => _x( 'Carousel Style', 'Editor', 'wps-team' ),
            'tab'       => 'style',
            'condition' => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'heading_carousel_navs', [
            'label'       => _x( 'Navs Styling', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->start_controls_tabs( 'carousel_nav_color_tabs' );
        $this->start_controls_tab( 'tab_carousel_nav_colors_normal', [
            'label' => Variables::get( 'normal_txt' ),
        ] );
        $this->add_control( 'carousel_nav_normal_icon_color', [
            'label'       => Variables::get( 'nav_icon_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_normal_bg_color', [
            'label'       => Variables::get( 'nav_bg_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_normal_br_color', [
            'label'       => Variables::get( 'nav_border_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_nav_colors_hover', [
            'label' => Variables::get( 'hover_txt' ),
        ] );
        $this->add_control( 'carousel_nav_hover_icon_color', [
            'label'       => Variables::get( 'nav_icon_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_hover_bg_color', [
            'label'       => Variables::get( 'nav_bg_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_hover_br_color', [
            'label'       => Variables::get( 'nav_border_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control( 'heading_carousel_dots', [
            'label'       => _x( 'Dots Styling', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->start_controls_tabs( 'carousel_dot_color_tabs' );
        $this->start_controls_tab( 'tab_carousel_dot_colors_normal', [
            'label' => Variables::get( 'normal_txt' ),
        ] );
        $this->add_control( 'carousel_dot_normal_bg_color', [
            'label'       => Variables::get( 'dot_bg_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_normal_br_color', [
            'label'       => Variables::get( 'dot_border_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_dot_colors_hover', [
            'label' => Variables::get( 'hover_txt' ),
        ] );
        $this->add_control( 'carousel_dot_hover_bg_color', [
            'label'       => Variables::get( 'dot_bg_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_hover_br_color', [
            'label'       => Variables::get( 'dot_border_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_dot_colors_active', [
            'label' => Variables::get( 'active_txt' ),
        ] );
        $this->add_control( 'carousel_dot_active_bg_color', [
            'label'       => Variables::get( 'dot_bg_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_active_br_color', [
            'label'       => Variables::get( 'dot_border_color_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->end_controls_section();
    }

    // Filters
    protected function style_filter_color_controls() {
        $this->start_controls_section( 'filters_styling', [
            'label'     => _x( 'Filters Style', 'Editor', 'wps-team' ),
            'tab'       => 'style',
            'condition' => [
                'display_type' => 'filter',
            ],
        ] );
        $this->add_control( 'heading_filter_colors', [
            'label'       => _x( 'Filters Styling', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Social Links
    protected function style_social_links_controls() {
        $this->start_controls_section( 'social_links_styling', [
            'label' => _x( 'Social Links', 'Editor', 'wps-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'heading_social_styling', [
            'label'       => _x( 'Social Links Styling', 'Editor', 'wps-team' ),
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Social Links
    protected function style_details_social_links_controls() {
        $this->start_controls_section( 'detail_social_links_styling', [
            'label'     => _x( 'Details Social Links', 'Editor', 'wps-team' ),
            'tab'       => 'style',
            'condition' => [
                'card_action' => ['modal', 'side-panel'],
            ],
        ] );
        $this->add_control( 'detail_heading_social_styling', [
            'label'       => _x( 'Social Links Styling', 'Editor', 'wps-team' ),
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    /**
     * Query Section
     */
    protected function query_section_group() {
        // Query
        $this->query_section();
        // Paging
        $this->query_paging_section();
        if ( !empty( $this->taxonomies ) ) {
            // Include
            $this->query_include_section();
            // Exclude
            $this->query_exclude_section();
        }
    }

    // Query
    protected function query_section() {
        $this->start_controls_section( 'query_section', [
            'label' => _x( 'Query', 'Editor', 'wps-team' ),
            'tab'   => 'query',
        ] );
        $this->add_control( 'show_all', [
            'label'       => _x( 'Display All Members', 'Editor', 'wps-team' ),
            'label_block' => false,
            'render_type' => 'template',
            'type'        => Controls_Manager::SWITCHER,
            'separator'   => 'none',
            'default'     => true,
        ] );
        $this->add_control( 'limit', [
            'label'       => _x( 'Display Limit', 'Editor', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::NUMBER,
            'default'     => 12,
            'min'         => 1,
            'max'         => 999,
            'render_type' => 'template',
            'separator'   => 'before',
            'class'       => 'wps-field--arrange-1',
            'condition'   => [
                'show_all' => false,
            ],
        ] );
        $this->add_control( 'orderby', [
            'label'       => Variables::get( 'order_by_txt' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'orderby' ),
            'default'     => 'date',
            'separator'   => 'before',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'order', [
            'label'       => Variables::get( 'order_txt' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => [[
                'label' => Variables::get( 'ascending_txt' ),
                'value' => 'ASC',
            ], [
                'label' => Variables::get( 'descending_txt' ),
                'value' => 'DESC',
            ]],
            'default'     => 'DESC',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->end_controls_section();
    }

    // Filters Order
    protected function filters_order_section() {
        $this->start_controls_section( 'filters_order_section', [
            'label'     => _x( 'Filters Order', 'Editor', 'wps-team' ),
            'tab'       => 'query',
            'condition' => [
                'display_type' => 'filter',
            ],
        ] );
        foreach ( Utils::get_taxonomy_roots() as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            if ( Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                $this->add_control( 'heading_' . $tax_root_key . '_order', [
                    'label' => Utils::get_setting( $tax_root_key . '_single_name' ),
                    'type'  => Controls_Manager::HEADING,
                ] );
                $this->add_control( $tax_root_key . '_orderby', [
                    'label'       => Variables::get( 'order_by_txt' ),
                    'label_block' => false,
                    'type'        => Controls_Manager::SELECT,
                    'render_type' => 'template',
                    'options'     => Utils::get_control_options( 'terms_orderby' ),
                    'default'     => 'none',
                    'separator'   => 'none',
                    'class'       => 'wps-field--arrange-1',
                ] );
                $this->add_control( $tax_root_key . '_order', [
                    'label'       => Variables::get( 'order_txt' ),
                    'label_block' => false,
                    'type'        => Controls_Manager::SELECT,
                    'render_type' => 'template',
                    'options'     => [[
                        'label' => Variables::get( 'ascending_txt' ),
                        'value' => 'ASC',
                    ], [
                        'label' => Variables::get( 'descending_txt' ),
                        'value' => 'DESC',
                    ]],
                    'default'     => 'DESC',
                    'separator'   => 'none',
                    'class'       => 'wps-field--arrange-1',
                ] );
            }
        }
        $this->end_controls_section();
    }

    // Paging
    protected function query_paging_section() {
        $this->start_controls_section( 'query_paging_section', [
            'label'     => _x( 'Paging / Loading', 'Editor', 'wps-team' ),
            'tab'       => 'query',
            'condition' => [
                'show_all' => false,
            ],
        ] );
        $this->add_control( 'enable_paging', [
            'label'       => Variables::get( 'enable_paging' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'display_type' => ['grid', 'filter'],
            ],
        ] );
        $this->add_control( 'enable_ajax_loading', [
            'label'       => Variables::get( 'enable_ajax_loading' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'paging_type', [
            'label'       => Variables::get( 'paging_type' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_paging' => true,
            ],
        ] );
        $this->add_control( 'ajax_paging_limit', [
            'label'       => Variables::get( 'ajax_paging_limit' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'edge_page_links', [
            'label'       => Variables::get( 'edge_page_links' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Include
    protected function query_include_section() {
        $this->start_controls_section( 'query_include_section', [
            'label' => _x( 'Include', 'Editor', 'wps-team' ),
            'tab'   => 'query',
        ] );
        foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            if ( $tax_root_key === 'group' || wps_team_fs()->can_use_premium_code() ) {
                if ( Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                    $terms = Utils::get_terms( Utils::get_taxonomy_name( $tax_root ) );
                    $this->add_control( 'include_by_' . $tax_root_key, [
                        'label'       => Variables::get( 'include_by_txt' ) . ' ' . $tax_single_name,
                        'label_block' => true,
                        'type'        => Controls_Manager::SELECT,
                        'render_type' => 'template',
                        'options'     => Utils::get_term_options( $terms ),
                        'placeholder' => Variables::get( 'select_txt' ) . ' ' . $tax_single_name,
                        'multiple'    => true,
                        'separator'   => 'none',
                    ] );
                }
            } else {
                $this->add_control( 'include_by_' . $tax_root_key, [
                    'label'       => Variables::get( 'include_by_txt' ) . ' ' . $tax_single_name,
                    'label_block' => true,
                    'type'        => Controls_Manager::UPGRADE_NOTICE,
                    'separator'   => 'none',
                ] );
            }
        }
        $this->end_controls_section();
    }

    // Exclude
    protected function query_exclude_section() {
        $this->start_controls_section( 'query_exclude_section', [
            'label' => _x( 'Exclude', 'Editor', 'wps-team' ),
            'tab'   => 'query',
        ] );
        foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            if ( $tax_root_key === 'group' || wps_team_fs()->can_use_premium_code() ) {
                if ( Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                    $terms = Utils::get_terms( Utils::get_taxonomy_name( $tax_root ) );
                    $this->add_control( 'exclude_by_' . $tax_root_key, [
                        'label'       => Variables::get( 'exclude_by_txt' ) . ' ' . $tax_single_name,
                        'label_block' => true,
                        'type'        => Controls_Manager::SELECT,
                        'render_type' => 'template',
                        'options'     => Utils::get_term_options( $terms ),
                        'placeholder' => Variables::get( 'select_txt' ) . ' ' . $tax_single_name,
                        'multiple'    => true,
                        'separator'   => 'none',
                    ] );
                }
            } else {
                $this->add_control( 'exclude_by_' . $tax_root_key, [
                    'label'       => Variables::get( 'exclude_by_txt' ) . ' ' . $tax_single_name,
                    'label_block' => true,
                    'type'        => Controls_Manager::UPGRADE_NOTICE,
                    'separator'   => 'none',
                ] );
            }
        }
        $this->end_controls_section();
    }

    /**
     * Typography Section
     */
    protected function typo_section_group() {
        // Card Typography
        $this->card_typo_controls();
        // Detail Typography
        $this->detail_typo_controls();
    }

    // Card Typography
    protected function card_typo_controls() {
        $this->start_controls_section( 'card_typo_section', [
            'label' => _x( 'Card Typography', 'Editor', 'wps-team' ),
            'tab'   => 'typo',
        ] );
        $this->add_control( 'typo_name', [
            'label'       => Variables::get( 'typo_name_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_desig', [
            'label'       => Variables::get( 'typo_designation_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_content', [
            'label'       => Variables::get( 'typo_content_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_meta', [
            'label'       => Variables::get( 'typo_meta_txt' ),
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_read_more', [
            'label'       => Variables::get( 'typo_read_more_txt' ),
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Detail Typography
    protected function detail_typo_controls() {
        $this->start_controls_section( 'detail_typo_section', [
            'label'     => _x( 'Detail Typography', 'Editor', 'wps-team' ),
            'tab'       => 'typo',
            'condition' => [
                'card_action' => ['modal', 'side-panel', 'expand'],
            ],
        ] );
        $this->add_control( 'detail_typo_name', [
            'label'       => Variables::get( 'typo_name_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'detail_typo_desig', [
            'label'       => Variables::get( 'typo_designation_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'detail_typo_content', [
            'label'       => Variables::get( 'typo_content_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'detail_typo_meta', [
            'label'       => Variables::get( 'typo_meta_txt' ),
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    /**
     * Advance Section
     */
    protected function advance_section_group() {
        // Thumbnail
        $this->thumbnail_section();
        // Container
        $this->container_section();
    }

    // Thumbnail
    protected function thumbnail_section() {
        $this->start_controls_section( 'advance_section', [
            'label' => _x( 'Thumbnail', 'Editor', 'wps-team' ),
            'tab'   => 'advance',
        ] );
        $this->add_control( 'thumbnail_type', [
            'label'       => _x( 'Thumbnail Type', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'thumbnail_type', ['carousel'] ),
            'default'     => 'image',
        ] );
        $this->add_control( 'detail_thumbnail_type', [
            'label'       => _x( 'Details Thumbnail Type', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'thumbnail_type' ),
            'default'     => 'image',
        ] );
        $this->add_control( 'aspect_ratio', [
            'label'       => _x( 'Thumbnail Aspect Ratio', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'options'     => Utils::get_control_options( 'aspect_ratio' ),
            'default'     => 'default',
        ] );
        $this->add_control( 'thumbnail_size', [
            'label'       => _x( 'Member Image Size', 'Editor', 'wps-team' ),
            'description' => _x( 'This image size is used for general layout.', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => Variables::get( 'select_size_txt' ),
        ] );
        $this->add_control( 'thumbnail_size_custom', [
            'label'       => Variables::get( 'set_custom_size_label' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'thumbnail_size' => 'custom',
            ],
        ] );
        $this->add_control( 'detail_thumbnail_size', [
            'label'       => _x( 'Member Detail\'s Image Size', 'Editor', 'wps-team' ),
            'description' => _x( 'This image size is used for modal, expand & panel layouts.', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => Variables::get( 'select_size_txt' ),
        ] );
        $this->add_control( 'detail_thumbnail_size_custom', [
            'label'       => Variables::get( 'set_custom_size_label' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'detail_thumbnail_size' => 'custom',
            ],
        ] );
        $this->add_control( 'thumbnail_position', [
            'label'       => _x( 'Thumbnail Position', 'Editor', 'wps-team' ),
            'description' => _x( 'This position is used for alignment of the thumbnail.', 'Editor', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'options'     => Utils::get_thumbnail_position(),
            'default'     => 'center center',
        ] );
        $this->end_controls_section();
    }

    // Container
    protected function container_section() {
        $this->start_controls_section( 'container_settings_section', [
            'label' => _x( 'Container Settings', 'Editor', 'wps-team' ),
            'tab'   => 'advance',
        ] );
        $this->add_control( 'container_background', [
            'label'       => Variables::get( 'background_color_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_custom_class', [
            'label'       => Variables::get( 'container_custom_class' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_padding', [
            'label'       => Variables::get( 'padding_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_z_index', [
            'label'       => Variables::get( 'container_z_index' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_border_radius', [
            'label'       => Variables::get( 'border_radius_txt' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        // $this->add_control( 'container_box_shadow', [
        // 	'label' => _x( 'Box Shadow', 'Editor', 'wps-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'container_border', [
        // 	'label' => _x( 'Border', 'Editor', 'wps-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'entrance_animation', [
        // 	'label' => _x( 'Entrance Animation', 'Editor', 'wps-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'hover_animation', [
        // 	'label' => _x( 'Hover Animation', 'Editor', 'wps-team' ),
        // 	'label_block' => true,
        // 	'separator' => 'none',
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        $this->end_controls_section();
    }

}
