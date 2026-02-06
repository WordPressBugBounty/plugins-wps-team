<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Settings_Editor extends Editor_Controls {
    public function __construct( array $data = [], array $args = [] ) {
        parent::__construct( $data, $args );
        do_action( 'wpspeedo_team/settings_editor/init', $this );
    }

    public function get_name() {
        return 'meta_box_editor';
    }

    protected function _register_controls() {
        // General Settings
        $this->general_settings();
        // Admin Text Settings
        $this->translation_settings();
        // Advance Settings
        $this->advance_settings();
        // Single Page Settings
        $this->single_page_settings();
        // Custom Scripts
        $this->custom_scripts_settings();
    }

    protected function general_settings() {
        $this->start_controls_section( 'general_settings_section', [
            'label'      => _x( 'General Settings', 'Settings: General', 'wps-team' ),
            'menu_label' => _x( 'General', 'Settings: General', 'wps-team' ),
            'icon'       => 'fas fa-tools',
            'path'       => 'general',
        ] );
        $this->general_post_type_settings();
        $this->general_contact_display_format_settings();
        $this->end_controls_section();
    }

    protected function general_post_type_settings() {
        $this->add_control( 'post_type_settings', [
            'label'       => _x( 'Post Type', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->add_control( 'member_single_name', [
            'label'       => _x( 'Member Single Name', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'member_single_name' ),
            'default'     => Utils::get_default( 'member_single_name' ),
        ] );
        $this->add_control( 'member_plural_name', [
            'label'       => _x( 'Member Plural Name', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'member_plural_name' ),
            'default'     => Utils::get_default( 'member_plural_name' ),
        ] );
        $this->add_control( 'enable_archive', [
            'label'       => _x( 'Enable Single/Archive Page', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::SWITCHER,
            'default'     => Utils::get_default( 'enable_archive' ),
        ] );
        $this->add_control( 'post_type_slug', [
            'label'       => _x( 'Archive Slug', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'default'     => Utils::get_archive_slug(),
            'condition'   => [
                'enable_archive' => true,
            ],
        ] );
        $this->add_control( 'with_front', [
            'label'       => _x( 'Include Base Slug in URLs', 'Settings: General', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::SWITCHER,
            'default'     => Utils::get_default( 'with_front' ),
            'condition'   => [
                'enable_archive' => true,
            ],
        ] );
    }

    protected function general_contact_display_format_settings() {
        $this->add_control( 'contact_display_format_settings', [
            'label'       => _x( 'Contact Display Format', 'Settings: Display Format', 'wps-team' ),
            'description' => _x( 'Choose how contact information should be displayed: plain text, clickable value, or action text (e.g., "Email Me", "Call Now").', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->add_control( 'website_display_format', [
            'label'       => _x( 'Website', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'none',
            'default'     => Utils::get_default( 'website_display_format' ),
            'options'     => Utils::get_control_options( 'display_format' ),
        ] );
        $this->add_control( 'email_display_format', [
            'label'       => _x( 'Email', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'none',
            'default'     => Utils::get_default( 'email_display_format' ),
            'options'     => Utils::get_control_options( 'display_format' ),
        ] );
        $this->add_control( 'mobile_display_format', [
            'label'       => _x( 'Mobile', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'none',
            'default'     => Utils::get_default( 'mobile_display_format' ),
            'options'     => Utils::get_control_options( 'display_format' ),
        ] );
        $this->add_control( 'telephone_display_format', [
            'label'       => _x( 'Telephone', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'none',
            'default'     => Utils::get_default( 'telephone_display_format' ),
            'options'     => Utils::get_control_options( 'display_format' ),
        ] );
        $this->add_control( 'fax_display_format', [
            'label'       => _x( 'Fax', 'Settings: Display Format', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'none',
            'default'     => Utils::get_default( 'fax_display_format' ),
            'options'     => Utils::get_control_options( 'display_format' ),
        ] );
    }

    protected function translation_settings() {
        $this->start_controls_section( 'admin_texts_settings_section', [
            'label'      => _x( 'Translation Settings', 'Settings: Translation', 'wps-team' ),
            'menu_label' => _x( 'Translation', 'Settings: Translation', 'wps-team' ),
            'icon'       => 'fas fa-file-word',
            'path'       => 'translations',
        ] );
        $this->add_control( 'enable_multilingual', [
            'label'       => _x( 'Enable Multilingual', 'Settings: Translation', 'wps-team' ),
            'description' => _x( 'For simple uses, text changes are ok, but if you want to translate with multiple languages, enable this option and use a multilingual plugin to create translations for multiple languages.', 'Settings: Translation', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::SWITCHER,
            'default'     => Utils::get_default( 'enable_multilingual' ),
        ] );
        $this->add_control( 'admin_fields_labels_title', [
            'label'       => 'Admin: Meta Field Titles',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'desig_label', [
            'label'       => 'Designation',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'email_label', [
            'label'       => 'Email Address',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'mobile_label', [
            'label'       => 'Mobile (Personal',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'telephone_label', [
            'label'       => 'Telephone (Office)',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'fax_label', [
            'label'       => 'Fax',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'experience_label', [
            'label'       => 'Years of Experience',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'website_label', [
            'label'       => 'Website',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'company_label', [
            'label'       => 'Company',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'address_label', [
            'label'       => 'Address',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'ribbon_label', [
            'label'       => 'Ribbon / Tag',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'link_1_label', [
            'label'       => 'Resume Link Label',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'link_2_label', [
            'label'       => 'Hire Link Label',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'color_label', [
            'label'       => 'Color',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'public_filters_labels_title', [
            'label'       => 'Public: Filters Texts',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'filter_search_text', [
            'label'       => Variables::get( 'search_filter_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        foreach ( Utils::get_taxonomy_roots() as $tax_root ) {
            $tax_root_key = Utils::to_field_key( $tax_root );
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            if ( !Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                continue;
            }
            $this->add_control( 'filter_all_' . $tax_root_key . '_text', [
                'label'       => $tax_single_name . ' ' . Variables::get( 'all_filter_txt' ),
                'label_block' => false,
                'separator'   => 'none',
                'type'        => Controls_Manager::UPGRADE_NOTICE,
                'condition'   => [
                    'enable_multilingual' => false,
                ],
            ] );
        }
        $this->add_control( 'custom_fields_labels_title', [
            'label'       => 'Public: Custom Field Labels',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'read_more_link_text', [
            'label'       => 'Read More Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'link_1_btn_text', [
            'label'       => 'Resume Button Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'link_2_btn_text', [
            'label'       => 'Hire Button Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'social_links_title', [
            'label'       => 'Social links title:',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'social_links_title' ),
            'default'     => Utils::get_default( 'social_links_title' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'skills_title', [
            'label'       => 'Skills title:',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'skills_title' ),
            'default'     => Utils::get_default( 'skills_title' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'education_title', [
            'label'       => 'Education title:',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'education_title' ),
            'default'     => Utils::get_default( 'education_title' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'mobile_meta_label', [
            'label'       => 'Mobile Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'mobile_meta_label' ),
            'default'     => Utils::get_default( 'mobile_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'phone_meta_label', [
            'label'       => 'Telephone Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'phone_meta_label' ),
            'default'     => Utils::get_default( 'phone_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'email_meta_label', [
            'label'       => 'Email Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'email_meta_label' ),
            'default'     => Utils::get_default( 'email_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'website_meta_label', [
            'label'       => 'Website Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'website_meta_label' ),
            'default'     => Utils::get_default( 'website_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'experience_meta_label', [
            'label'       => 'Experience Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'experience_meta_label' ),
            'default'     => Utils::get_default( 'experience_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'company_meta_label', [
            'label'       => 'Company Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'company_meta_label' ),
            'default'     => Utils::get_default( 'company_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'address_meta_label', [
            'label'       => 'Address Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'address_meta_label' ),
            'default'     => Utils::get_default( 'address_meta_label' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'contact_action_link_texts', [
            'label'       => 'Public: Contact Action Link Texts',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'email_link_text', [
            'label'       => 'Email Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'email_link_text' ),
            'default'     => Utils::get_default( 'email_link_text' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'website_link_text', [
            'label'       => 'Website Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'website_link_text' ),
            'default'     => Utils::get_default( 'website_link_text' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'mobile_link_text', [
            'label'       => 'Mobile Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'mobile_link_text' ),
            'default'     => Utils::get_default( 'mobile_link_text' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'phone_link_text', [
            'label'       => 'Telephone Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'phone_link_text' ),
            'default'     => Utils::get_default( 'phone_link_text' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'fax_link_text', [
            'label'       => 'Fax Link Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'fax_link_text' ),
            'default'     => Utils::get_default( 'fax_link_text' ),
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'taxonomy_fields_labels_title', [
            'label'       => 'Public: Taxonomy Field Labels',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        if ( Utils::get_setting( 'enable_group_taxonomy' ) ) {
            $this->add_control( 'group_meta_label', [
                'label'       => Variables::get( 'group_single_name' ) . ' ' . Variables::get( 'label_txt' ),
                'label_block' => false,
                'separator'   => 'none',
                'type'        => Controls_Manager::TEXT,
                'placeholder' => Utils::get_default( 'group_meta_label' ),
                'default'     => Utils::get_default( 'group_meta_label' ),
                'condition'   => [
                    'enable_multilingual' => false,
                ],
            ] );
        }
        foreach ( Utils::get_taxonomy_roots() as $tax_root ) {
            if ( $tax_root === 'group' ) {
                continue;
            }
            $tax_root_key = Utils::to_field_key( $tax_root );
            if ( !Utils::get_setting( 'enable_' . $tax_root_key . '_taxonomy' ) ) {
                continue;
            }
            $tax_single_name = Utils::get_setting( $tax_root_key . '_single_name' );
            $this->add_control( $tax_root_key . '_meta_label', [
                'label'       => $tax_single_name . ' ' . Variables::get( 'label_txt' ),
                'label_block' => false,
                'separator'   => 'none',
                'type'        => Controls_Manager::UPGRADE_NOTICE,
                'condition'   => [
                    'enable_multilingual' => false,
                ],
            ] );
        }
        $this->add_control( 'other_translations', [
            'label'       => 'Public: Others',
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'load_more_text', [
            'label'       => 'Load More Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'return_to_archive_text', [
            'label'       => 'Back to Team Page Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->add_control( 'no_results_found_text', [
            'label'       => 'No Results Found Text',
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'enable_multilingual' => false,
            ],
        ] );
        $this->end_controls_section();
    }

    protected function advance_settings() {
        $set_custom_size_label = _x( 'Set Custom Size', 'Settings: Advance', 'wps-team' );
        $set_custom_size_desc = _x( 'Set custom size for image, enable the Crop Option to crop the image to exact dimensions (normally proportional will be applied)', 'Settings: Advance', 'wps-team' );
        $this->start_controls_section( 'advance_settings_section', [
            'label'      => _x( 'Advance Settings', 'Settings: Advance', 'wps-team' ),
            'menu_label' => _x( 'Advance', 'Settings: Advance', 'wps-team' ),
            'icon'       => 'fas fa-user-ninja',
            'path'       => 'advance',
        ] );
        $this->add_control( 'archive_page_link', [
            'label'       => _x( 'Return/Archive Page Link', 'Settings: Advance', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::TEXT,
            'placeholder' => Utils::get_default( 'archive_page_link' ),
            'default'     => Utils::get_default( 'archive_page_link' ),
        ] );
        $this->add_control( 'disable_google_fonts_loading', [
            'label'       => _x( 'Disable Google Fonts Loading', 'Settings: Advance', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::SWITCHER,
            'default'     => Utils::get_default( 'disable_google_fonts_loading' ),
        ] );
        $this->add_control( 'thumbnail_size', [
            'label'       => _x( 'Member Image Size', 'Settings: Advance', 'wps-team' ),
            'description' => _x( 'This image size is used for general layout globally for all shortcodes, unless it is overridden from the specific shortcode.', 'Settings: Advance', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'before',
            'default'     => Utils::get_default( 'thumbnail_size' ),
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => _x( 'Select Size', 'Settings: Advance', 'wps-team' ),
        ] );
        $this->add_control( 'thumbnail_size_custom', [
            'label'       => Variables::get( 'set_custom_size_label' ),
            'label_block' => false,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'thumbnail_size' => 'custom',
            ],
        ] );
        $this->add_control( 'detail_thumbnail_size', [
            'label'       => _x( 'Member Detail\'s Image Size', 'Settings: Advance', 'wps-team' ),
            'description' => _x( 'This image size is used for modal, expand, panel & single layouts globally for all shortcodes, unless it is overridden from the specific shortcode', 'Settings: Advance', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SELECT,
            'separator'   => 'before',
            'default'     => Utils::get_default( 'detail_thumbnail_size' ),
            'options'     => Utils::get_registered_image_sizes(),
            'placeholder' => _x( 'Select Size', 'Settings: Advance', 'wps-team' ),
        ] );
        $this->add_control( 'detail_thumbnail_size_custom', [
            'label'       => Variables::get( 'set_custom_size_label' ),
            'label_block' => false,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
            'condition'   => [
                'detail_thumbnail_size' => 'custom',
            ],
        ] );
        $this->end_controls_section();
    }

    protected function single_page_settings() {
        $this->start_controls_section( 'single_page_settings_section', [
            'label'      => _x( 'Single Page Settings', 'Settings: Single Page', 'wps-team' ),
            'menu_label' => _x( 'Single Page', 'Settings: Single Page', 'wps-team' ),
            'icon'       => 'fas fa-file-image',
            'path'       => 'single-page',
        ] );
        // Thumbs & Carousel
        $this->elements_visibility_controls();
        // Thumbs & Carousel
        $this->thumbs_carousel_controls();
        // Text & Icons
        $this->style_text_icon_controls();
        // Social Icons
        $this->social_icons_controls();
        $this->end_controls_section();
    }

    protected function custom_scripts_settings() {
        $this->start_controls_section( 'custom_scripts_settings_section', [
            'label'      => _x( 'Custom Scripts', 'Settings: Custom Scripts', 'wps-team' ),
            'menu_label' => _x( 'Custom Scripts', 'Settings: Custom Scripts', 'wps-team' ),
            'icon'       => 'fas fa-code',
            'path'       => 'custom-scripts',
        ] );
        $this->add_control( 'custom_css', [
            'label'       => _x( 'Custom CSS', 'Settings: Custom Scripts', 'wps-team' ),
            'description' => _x( 'This CSS code will be applied globally to the team members layout where the shortcode is used or in the single member view.', 'Settings: Custom Scripts', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::CODE,
            'eventType'   => 'change',
        ] );
        $this->end_controls_section();
    }

    // Action Links
    protected function elements_visibility_controls() {
        $this->add_control( 'elements_visibility', [
            'label'       => _x( 'Elements', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $elements = Utils::allowed_elements_display_order();
        foreach ( Utils::elements_display_order( 'single' ) as $element_key => $element_title ) {
            if ( in_array( $element_key, $elements ) ) {
                $element_key = 'single_' . $element_key;
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
                $element_key = 'single_' . $element_key;
                $this->add_control( $element_key, [
                    'label'       => $element_title,
                    'label_block' => false,
                    'type'        => Controls_Manager::UPGRADE_NOTICE,
                ] );
            }
        }
        $this->add_control( 'archive_page', [
            'label'       => _x( 'Return/Archive Page Link', 'Settings: Return Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::SWITCHER,
            'render_type' => 'template',
            'default'     => Utils::get_default( 'archive_page' ),
        ] );
    }

    // Thumbs & Carousel
    protected function thumbs_carousel_controls() {
        $this->add_control( 'thumbs_and_carousel_title', [
            'label'       => _x( 'Thumbs & Carousel', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->add_control( 'detail_thumbnail_type', [
            'label'       => _x( 'Thumbnail Type', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::SELECT,
            'default'     => Utils::get_default( 'detail_thumbnail_type' ),
            'options'     => Utils::get_control_options( 'thumbnail_type' ),
            'placeholder' => _x( 'Thumbnail Type', 'Settings: Single Page', 'wps-team' ),
        ] );
    }

    // Text & Icons
    protected function style_text_icon_controls() {
        $this->add_control( 'text_and_icons_title', [
            'label'       => _x( 'Text & Icon Colors', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->add_control( 'title_color', [
            'label'       => _x( 'Name Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
            'separator'   => 'after',
        ] );
        $this->add_control( 'designation_color', [
            'label'       => _x( 'Designation Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'desc_color', [
            'label'       => _x( 'Description Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'divider_color', [
            'label'       => _x( 'Divider Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_icon_color', [
            'label'       => _x( 'Info Icon Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_text_color', [
            'label'       => _x( 'Info Text Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_color', [
            'label'       => _x( 'Info Link Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->add_control( 'info_link_hover_color', [
            'label'       => _x( 'Info Link Hover Color', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'type'        => Controls_Manager::COLOR,
        ] );
    }

    // Social Icons
    protected function social_icons_controls() {
        $this->add_control( 'social_icons_title', [
            'label'       => _x( 'Social Links', 'Settings: Single Page', 'wps-team' ),
            'label_block' => false,
            'separator'   => 'before',
            'type'        => Controls_Manager::HEADING,
        ] );
        $this->add_control( 'heading_social_styling', [
            'label'       => _x( 'Social Links Styling', 'Settings: Single Page', 'wps-team' ),
            'label_block' => true,
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
    }

}
