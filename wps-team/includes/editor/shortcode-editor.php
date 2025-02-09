<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Shortcode_Editor extends Editor_Controls {
    public $taxonomies = [];

    public function __construct( array $data = [], array $args = null ) {
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
            'label' => _x( 'Layout', 'Editor', 'wpspeedo-team' ),
        ] );
        $this->add_control( 'display_type', [
            'label'       => _x( 'Display Type', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'display_type' ),
            'default'     => 'grid',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'theme', [
            'label'       => _x( 'Theme', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'theme' ),
            'default'     => 'square-01',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'card_action', [
            'label'       => _x( 'Card Action', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'card_action' ),
            'default'     => 'single-page',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_responsive_control( 'container_width', [
            'label'                => _x( 'Container Width', 'Editor', 'wpspeedo-team' ),
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
            'label'                => _x( 'Columns', 'Editor', 'wpspeedo-team' ),
            'label_block'          => false,
            'type'                 => Controls_Manager::NUMBER,
            'default'              => 3,
            'tablet_default'       => 3,
            'small_tablet_default' => 2,
            'mobile_default'       => 1,
            'class'                => 'wps-field--arrange-2',
        ] );
        $this->add_responsive_control( 'gap', [
            'label'       => _x( 'Gap', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::NUMBER,
            'class'       => 'wps-field--arrange-2',
        ] );
        $this->add_responsive_control( 'gap_vertical', [
            'label'       => _x( 'Gap Vertical', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::NUMBER,
            'class'       => 'wps-field--arrange-2',
            'condition'   => [
                'display_type' => ['grid', 'filter'],
            ],
        ] );
        $this->add_control( 'description_length', [
            'label'       => _x( 'Max Characters for Description', 'Editor', 'wpspeedo-team' ),
            'description' => _x( 'Set 0 to get full content.', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'render_type' => 'template',
            'type'        => Controls_Manager::SLIDER,
            'min'         => 0,
            'max'         => 1000,
            'step'        => 10,
            'default'     => 110,
        ] );
        $this->add_control( 'add_read_more', [
            'label'       => _x( 'Read More Link', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => false,
            'render_type' => 'template',
        ] );
        $this->add_control( 'read_more_text', [
            'label'       => _x( 'Read More Text', 'Editor', 'wpspeedo-team' ),
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
        $autoplay = _x( 'Autoplay', 'Editor', 'wpspeedo-team' );
        $autoplay_delay = _x( 'Autoplay Delay', 'Editor', 'wpspeedo-team' );
        $pause_on_hover = _x( 'Pause On Hover', 'Editor', 'wpspeedo-team' );
        $dynamic_dots = _x( 'Dynamic Dots', 'Editor', 'wpspeedo-team' );
        $scroll_nagivation = _x( 'Scroll Navigation', 'Editor', 'wpspeedo-team' );
        $keyboard_navigation = _x( 'Keyboard Navigation', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'carousel_section', [
            'label'     => _x( 'Carousel Settings', 'Editor', 'wpspeedo-team' ),
            'condition' => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'speed', [
            'label'       => _x( 'Carousel Speed', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SLIDER,
            'min'         => 100,
            'max'         => 5000,
            'step'        => 100,
            'default'     => 800,
        ] );
        $this->add_control( 'dots', [
            'label'       => _x( 'Dots Pagination', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'navs', [
            'label'       => _x( 'Arrow Navigation', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'loop', [
            'label'       => _x( 'Carousel Loop', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'default'     => true,
            'render_type' => 'template',
        ] );
        $this->add_control( 'autoplay', [
            'label'       => $autoplay,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'autoplay_delay', [
            'label'       => $autoplay_delay,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'pause_on_hover', [
            'label'       => $pause_on_hover,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'dynamic_dots', [
            'label'       => $dynamic_dots,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'scroll_nagivation', [
            'label'       => $scroll_nagivation,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'keyboard_navigation', [
            'label'       => $keyboard_navigation,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'elements_section', [
            'label' => _x( 'Elements Visibility', 'Editor', 'wpspeedo-team' ),
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
                            'title' => $show_txt,
                            'icon'  => 'fas fa-eye',
                        ],
                        'false' => [
                            'title' => $hide_txt,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'details_elements_section', [
            'label' => _x( 'Details Elements Visibility', 'Editor', 'wpspeedo-team' ),
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
                            'title' => $show_txt,
                            'icon'  => 'fas fa-eye',
                        ],
                        'false' => [
                            'title' => $hide_txt,
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
    }

    // Text & Icons
    protected function style_text_icon_controls() {
        $this->start_controls_section( 'style_section', [
            'label' => _x( 'Text & Icon Colors', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'title_color', [
            'label'       => _x( 'Name Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
            'separator'   => 'after',
        ] );
        $this->add_control( 'title_color_hover', [
            'label'       => _x( 'Name Color Hover', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'ribbon_text_color', [
            'label'       => _x( 'Ribbon Text Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'ribbon_bg_color', [
            'label'       => _x( 'Ribbon BG Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'designation_color', [
            'label'       => _x( 'Designation Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'desc_color', [
            'label'       => _x( 'Description Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'divider_color', [
            'label'       => _x( 'Divider Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_icon_color', [
            'label'       => _x( 'Info Icon Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_text_color', [
            'label'       => _x( 'Info Text Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_color', [
            'label'       => _x( 'Info Link Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_hover_color', [
            'label'       => _x( 'Info Link Hover Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'read_more_text_color', [
            'label'       => _x( 'Read More Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'read_more_text_hover_color', [
            'label'       => _x( 'Read More Hover Color', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_section();
    }

    // Single Item
    protected function style_item_styling_controls() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'single_item_style', [
            'label' => _x( 'Single Item Style', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'style',
        ] );
        $this->add_group_control( Group_Control_Background::get_type(), [
            'name'  => 'item_background',
            'label' => $background_txt,
            'types' => ['classic', 'gradient'],
        ] );
        $this->add_control( 'item_padding', [
            'label'       => $padding_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'item_border_radius', [
            'label'       => $border_radius_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Custom Spacing
    protected function style_custom_spacing_controls() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'custom_spacing_styling', [
            'label' => _x( 'Space Customization', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'title_spacing', [
            'label'       => $title_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'desig_spacing', [
            'label'       => $designation_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'desc_spacing', [
            'label'       => $desc_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'devider_spacing', [
            'label'       => $devider_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'social_spacing', [
            'label'       => $social_icons_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'info_spacing', [
            'label'       => $meta_info_spacing_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Buttons
    protected function style_buttons_controls() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'buttons_styling', [
            'label' => _x( 'Resume & Hire Buttons', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'heading_resume_button_style', [
            'label'       => $resume_button_style_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'heading_hire_button_style', [
            'label'       => $hire_button_style_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Carousel
    protected function style_carousel_color_controls() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $nav_icon_color_txt = _x( 'Nav Icon Color', 'Editor', 'wpspeedo-team' );
        $nav_bg_color_txt = _x( 'Nav BG Color', 'Editor', 'wpspeedo-team' );
        $nav_border_color_txt = _x( 'Nav Border Color', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'carousel_styling', [
            'label'     => _x( 'Carousel Style', 'Editor', 'wpspeedo-team' ),
            'tab'       => 'style',
            'condition' => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'heading_carousel_navs', [
            'label'       => _x( 'Navs Styling', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->start_controls_tabs( 'carousel_nav_color_tabs' );
        $this->start_controls_tab( 'tab_carousel_nav_colors_normal', [
            'label' => $normal_txt,
        ] );
        $this->add_control( 'carousel_nav_normal_icon_color', [
            'label'       => $nav_icon_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_normal_bg_color', [
            'label'       => $nav_bg_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_normal_br_color', [
            'label'       => $nav_border_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_nav_colors_hover', [
            'label' => $hover_txt,
        ] );
        $this->add_control( 'carousel_nav_hover_icon_color', [
            'label'       => $nav_icon_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_hover_bg_color', [
            'label'       => $nav_bg_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_nav_hover_br_color', [
            'label'       => $nav_border_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->end_controls_tabs();
        $this->add_control( 'heading_carousel_dots', [
            'label'       => _x( 'Dots Styling', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->start_controls_tabs( 'carousel_dot_color_tabs' );
        $this->start_controls_tab( 'tab_carousel_dot_colors_normal', [
            'label' => $normal_txt,
        ] );
        $this->add_control( 'carousel_dot_normal_bg_color', [
            'label'       => $dot_bg_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_normal_br_color', [
            'label'       => $dot_border_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_dot_colors_hover', [
            'label' => $hover_txt,
        ] );
        $this->add_control( 'carousel_dot_hover_bg_color', [
            'label'       => $dot_bg_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_hover_br_color', [
            'label'       => $dot_border_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_tab();
        $this->start_controls_tab( 'tab_carousel_dot_colors_active', [
            'label' => $active_txt,
        ] );
        $this->add_control( 'carousel_dot_active_bg_color', [
            'label'       => $dot_bg_color_txt,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'carousel_dot_active_br_color', [
            'label'       => $dot_border_color_txt,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $filter_text_color_txt = _x( 'Filter Text Color', 'Editor', 'wpspeedo-team' );
        $filter_bg_color_txt = _x( 'Filter BG Color', 'Editor', 'wpspeedo-team' );
        $filter_border_color_txt = _x( 'Filter Border Color', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'filters_styling', [
            'label'     => _x( 'Filters Style', 'Editor', 'wpspeedo-team' ),
            'tab'       => 'style',
            'condition' => [
                'display_type' => 'filter',
            ],
        ] );
        $this->add_control( 'heading_filter_colors', [
            'label'       => _x( 'Filters Styling', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Social Links
    protected function style_social_links_controls() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'social_links_styling', [
            'label' => _x( 'Social Links', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'style',
        ] );
        $this->add_control( 'heading_social_styling', [
            'label'       => _x( 'Social Links Styling', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'query_section', [
            'label' => _x( 'Query', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'query',
        ] );
        $this->add_control( 'show_all', [
            'label'       => _x( 'Display All Members', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'render_type' => 'template',
            'type'        => Controls_Manager::SWITCHER,
            'separator'   => 'none',
            'default'     => true,
        ] );
        $this->add_control( 'limit', [
            'label'       => _x( 'Display Limit', 'Editor', 'wpspeedo-team' ),
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
        $this->add_control( 'is_filter_ajax', [
            'label'       => _x( 'Enable AJAX Filter', 'Editor', 'wpspeedo-team' ),
            'label_block' => false,
            'render_type' => 'template',
            'type'        => Controls_Manager::SWITCHER,
            'separator'   => 'before',
            'default'     => false,
            'condition'   => [
                'display_type' => 'filter',
            ],
        ] );
        $this->add_control( 'orderby', [
            'label'       => $order_by_txt,
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'orderby' ),
            'default'     => 'date',
            'separator'   => 'before',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->add_control( 'order', [
            'label'       => $order_txt,
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => [[
                'label' => $ascending_txt,
                'value' => 'ASC',
            ], [
                'label' => $descending_txt,
                'value' => 'DESC',
            ]],
            'default'     => 'DESC',
            'class'       => 'wps-field--arrange-1',
        ] );
        $this->end_controls_section();
    }

    // Filters Order
    protected function filters_order_section() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'filters_order_section', [
            'label'     => _x( 'Filters Order', 'Editor', 'wpspeedo-team' ),
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
                    'label'       => $order_by_txt,
                    'label_block' => false,
                    'type'        => Controls_Manager::SELECT,
                    'render_type' => 'template',
                    'options'     => Utils::get_control_options( 'terms_orderby' ),
                    'default'     => 'none',
                    'separator'   => 'none',
                    'class'       => 'wps-field--arrange-1',
                ] );
                $this->add_control( $tax_root_key . '_order', [
                    'label'       => $order_txt,
                    'label_block' => false,
                    'type'        => Controls_Manager::SELECT,
                    'render_type' => 'template',
                    'options'     => [[
                        'label' => $ascending_txt,
                        'value' => 'ASC',
                    ], [
                        'label' => $descending_txt,
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
        $enable_paging = _x( 'Enable Paging', 'Editor', 'wpspeedo-team' );
        $paging_type = _x( 'Paging Type', 'Editor', 'wpspeedo-team' );
        $ajax_paging_limit = _x( 'Lore More Limit', 'Editor', 'wpspeedo-team' );
        $edge_page_links = _x( 'Page Spread Range', 'Editor', 'wpspeedo-team' );
        $enable_ajax_loading = _x( 'Enable AJAX Loading', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'query_paging_section', [
            'label'     => _x( 'Paging / Loading', 'Editor', 'wpspeedo-team' ),
            'tab'       => 'query',
            'condition' => [
                'show_all' => false,
            ],
        ] );
        $this->add_control( 'enable_paging', [
            'label'       => $enable_paging,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'display_type' => ['grid', 'filter'],
            ],
        ] );
        $this->add_control( 'enable_ajax_loading', [
            'label'       => $enable_ajax_loading,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'display_type' => 'carousel',
            ],
        ] );
        $this->add_control( 'paging_type', [
            'label'       => $paging_type,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_paging' => true,
            ],
        ] );
        $this->add_control( 'ajax_paging_limit', [
            'label'       => $ajax_paging_limit,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'edge_page_links', [
            'label'       => $edge_page_links,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    // Include
    protected function query_include_section() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'query_include_section', [
            'label' => _x( 'Include', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'query',
        ] );
        foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            if ( $tax_root_key === 'group' || wps_team_fs()->can_use_premium_code() ) {
                if ( Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                    $terms = Utils::get_terms( Utils::get_taxonomy_name( $tax_root ) );
                    $this->add_control( 'include_by_' . $tax_root_key, [
                        'label'       => $include_by_txt . ' ' . $tax_single_name,
                        'label_block' => true,
                        'type'        => Controls_Manager::SELECT,
                        'render_type' => 'template',
                        'options'     => Utils::get_term_options( $terms ),
                        'placeholder' => $select_txt . ' ' . $tax_single_name,
                        'multiple'    => true,
                        'separator'   => 'none',
                    ] );
                }
            } else {
                $this->add_control( 'include_by_' . $tax_root_key, [
                    'label'       => $include_by_txt . ' ' . $tax_single_name,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'query_exclude_section', [
            'label' => _x( 'Exclude', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'query',
        ] );
        foreach ( Utils::get_taxonomy_roots( true ) as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            if ( $tax_root_key === 'group' || wps_team_fs()->can_use_premium_code() ) {
                if ( Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                    $terms = Utils::get_terms( Utils::get_taxonomy_name( $tax_root ) );
                    $this->add_control( 'exclude_by_' . $tax_root_key, [
                        'label'       => $exclude_by_txt . ' ' . $tax_single_name,
                        'label_block' => true,
                        'type'        => Controls_Manager::SELECT,
                        'render_type' => 'template',
                        'options'     => Utils::get_term_options( $terms ),
                        'placeholder' => $select_txt . ' ' . $tax_single_name,
                        'multiple'    => true,
                        'separator'   => 'none',
                    ] );
                }
            } else {
                $this->add_control( 'exclude_by_' . $tax_root_key, [
                    'label'       => $exclude_by_txt . ' ' . $tax_single_name,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $this->start_controls_section( 'typo_section', [
            'label' => _x( 'Typography', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'typo',
        ] );
        $this->add_control( 'typo_name', [
            'label'       => $typo_name_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_desig', [
            'label'       => $typo_designation_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_content', [
            'label'       => $typo_content_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'typo_meta', [
            'label'       => $typo_meta_txt,
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
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $set_custom_size_label = _x( 'Set Custom Size', 'Editor', 'wpspeedo-team' );
        $set_custom_size_desc = _x( 'Enable the Crop Option to crop the image to exact dimensions', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'advance_section', [
            'label' => _x( 'Thumbnail', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'advance',
        ] );
        $this->add_control( 'thumbnail_type', [
            'label'       => _x( 'Thumbnail Type', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'thumbnail_type', ['carousel'] ),
            'default'     => 'image',
        ] );
        $this->add_control( 'detail_thumbnail_type', [
            'label'       => _x( 'Details Thumbnail Type', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_control_options( 'thumbnail_type' ),
            'default'     => 'image',
        ] );
        $this->add_control( 'aspect_ratio', [
            'label'       => _x( 'Thumbnail Aspect Ratio', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'options'     => Utils::get_control_options( 'aspect_ratio' ),
            'default'     => 'default',
        ] );
        $this->add_control( 'thumbnail_size', [
            'label'       => _x( 'Member Image Size', 'Editor', 'wpspeedo-team' ),
            'description' => _x( 'This image size is used for general layout.', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => $select_size_txt,
        ] );
        $this->add_control( 'thumbnail_size_custom', [
            'label'       => $set_custom_size_label,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'thumbnail_size' => 'custom',
            ],
        ] );
        $this->add_control( 'detail_thumbnail_size', [
            'label'       => _x( 'Member Detail\'s Image Size', 'Editor', 'wpspeedo-team' ),
            'description' => _x( 'This image size is used for modal, expand & panel layouts.', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'render_type' => 'template',
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => $select_size_txt,
        ] );
        $this->add_control( 'detail_thumbnail_size_custom', [
            'label'       => $set_custom_size_label,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'detail_thumbnail_size' => 'custom',
            ],
        ] );
        $this->add_control( 'thumbnail_position', [
            'label'       => _x( 'Thumbnail Position', 'Editor', 'wpspeedo-team' ),
            'description' => _x( 'This position is used for alignment of the thumbnail.', 'Editor', 'wpspeedo-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::SELECT,
            'options'     => Utils::get_thumbnail_position(),
            'default'     => 'center center',
        ] );
        $this->end_controls_section();
    }

    // Container
    protected function container_section() {
        include WPS_TEAM_PATH . 'includes/editor/variables.php';
        $container_custom_class = _x( 'Custom Class', 'Editor', 'wpspeedo-team' );
        $container_z_index = _x( 'Z Index', 'Editor', 'wpspeedo-team' );
        $this->start_controls_section( 'container_settings_section', [
            'label' => _x( 'Container Settings', 'Editor', 'wpspeedo-team' ),
            'tab'   => 'advance',
        ] );
        $this->add_control( 'container_background', [
            'label'       => $background_color_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_custom_class', [
            'label'       => $container_custom_class,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_padding', [
            'label'       => $padding_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_z_index', [
            'label'       => $container_z_index,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( 'container_border_radius', [
            'label'       => $border_radius_txt,
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        // $this->add_control( 'container_box_shadow', [
        // 	'label' => _x( 'Box Shadow', 'Editor', 'wpspeedo-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'container_border', [
        // 	'label' => _x( 'Border', 'Editor', 'wpspeedo-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'entrance_animation', [
        // 	'label' => _x( 'Entrance Animation', 'Editor', 'wpspeedo-team' ),
        // 	'label_block' => true,
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        // $this->add_control( 'hover_animation', [
        // 	'label' => _x( 'Hover Animation', 'Editor', 'wpspeedo-team' ),
        // 	'label_block' => true,
        // 	'separator' => 'none',
        // 	'type' => Controls_Manager::UPGRADE_NOTICE,
        // ]);
        $this->end_controls_section();
    }

}
