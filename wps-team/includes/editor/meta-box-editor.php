<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Meta_Box_Editor extends Editor_Controls {
    public function __construct( array $data = [], array $args = null ) {
        parent::__construct( $data, $args );
        do_action( 'wpspeedo_team/metabox_editor/init', $this );
    }

    public function get_name() {
        return 'meta_box_editor';
    }

    protected function _register_controls() {
        $this->personal_info();
        $this->education();
        $this->social_links();
        $this->skills();
    }

    protected function personal_info() {
        $this->start_controls_section( 'personal_info_section', [
            'label' => _x( 'Personal Information', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $this->add_control( '_designation', [
            'label'       => plugin()->translations->get( 'desig_label', _x( 'Designation', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_email', [
            'label'       => plugin()->translations->get( 'email_label', _x( 'Email Address', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_mobile', [
            'label'       => plugin()->translations->get( 'mobile_label', _x( 'Mobile (Personal)', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_telephone', [
            'label'       => plugin()->translations->get( 'telephone_label', _x( 'Telephone (Office)', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_experience', [
            'label'       => plugin()->translations->get( 'experience_label', _x( 'Years of Experience', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_website', [
            'label'       => plugin()->translations->get( 'website_label', _x( 'Website', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_company', [
            'label'       => plugin()->translations->get( 'company_label', _x( 'Company', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_ribbon', [
            'label'       => plugin()->translations->get( 'ribbon_label', _x( 'Ribbon / Tag', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $link_1_label = plugin()->translations->get( 'link_1_label', _x( 'Resume Link', 'Admin Metabox', 'wpspeedo-team' ) );
        $link_2_label = plugin()->translations->get( 'link_2_label', _x( 'Hire Link', 'Admin Metabox', 'wpspeedo-team' ) );
        $this->add_control( '_link_1', [
            'label'       => $link_1_label,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( '_link_2', [
            'label'       => $link_2_label,
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->add_control( '_color', [
            'label'       => plugin()->translations->get( 'color_label', _x( 'Color', 'Admin Metabox', 'wpspeedo-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_section();
    }

    protected function education() {
        $this->start_controls_section( 'education_section', [
            'label' => _x( 'Education', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $this->add_control( '_education', [
            'label_block' => true,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    protected function social_links() {
        $this->start_controls_section( 'social_links', [
            'label' => _x( 'Social Links', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $repeater = new Repeater();
        $repeater->add_control( 'social_icon', [
            'type'        => Controls_Manager::ICON,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Icon', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $repeater->add_control( 'social_link', [
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Link', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $this->add_control( '_social_links', [
            'type'    => Controls_Manager::REPEATER,
            'fields'  => $repeater->get_fields(),
            'class'   => 'wps-field-group--repeater',
            'default' => [],
        ] );
        $this->end_controls_section();
    }

    protected function skills() {
        $this->start_controls_section( 'skills', [
            'label' => _x( 'Skills', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $repeater = new Repeater();
        $repeater->add_control( 'skill_name', [
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Skill Name', 'Admin Metabox', 'wpspeedo-team' ),
        ] );
        $repeater->add_control( 'skill_val', [
            'type'        => Controls_Manager::NUMBER,
            'label_block' => true,
            'separator'   => 'none',
            'min'         => 0,
            'max'         => 100,
            'step'        => 5,
        ] );
        $this->add_control( '_skills', [
            'type'    => Controls_Manager::REPEATER,
            'fields'  => $repeater->get_fields(),
            'class'   => 'wps-field-group--repeater',
            'default' => [],
        ] );
        $this->end_controls_section();
    }

}
