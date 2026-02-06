<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Meta_Box_Editor extends Editor_Controls {
    public function __construct( array $data = [], $args = null ) {
        parent::__construct( $data, $args );
        do_action( 'wpspeedo_team/metabox_editor/init', $this );
        add_action( 'edit_form_before_permalink', [$this, 'add_name_fields'] );
    }

    public function add_name_fields() {
        $_first_name = get_post_meta( get_the_ID(), '_first_name', true );
        $_last_name = get_post_meta( get_the_ID(), '_last_name', true );
        ?>

		<div class="wps-team--member-name-fields">

			<div class="wps-team--member-first-name">
				<label for="wps_member_first_name">
					<?php 
        echo plugin()->translations->get( 'first_name_label', _x( 'First Name', 'Admin Metabox', 'wps-team' ) );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
				</label>
				<input type="text" name="_first_name" size="30" value="<?php 
        echo esc_attr( $_first_name );
        ?>" id="wps_member_first_name" autocomplete="off">
			</div>

			<div class="wps-team--member-last-name">
				<label for="wps_member_last_name">
					<?php 
        echo plugin()->translations->get( 'last_name_label', _x( 'Last Name', 'Admin Metabox', 'wps-team' ) );
        // phpcs:ignore WordPress.Security.EscapeOutput
        ?>
				</label>
				<input type="text" name="_last_name" size="30" value="<?php 
        echo esc_attr( $_last_name );
        ?>" id="wps_member_last_name" autocomplete="off">
			</div>

		</div>

		<?php 
    }

    public function get_name() {
        return 'meta_box_editor';
    }

    protected function _register_controls() {
        $this->personal_info();
        $this->social_links();
        $this->skills();
        $this->pricing();
        $this->additional();
    }

    protected function personal_info() {
        $this->start_controls_section( 'personal_info_section', [
            'label' => _x( 'Personal Information', 'Admin Metabox', 'wps-team' ),
        ] );
        $this->add_control( '_designation', [
            'label'       => plugin()->translations->get( 'desig_label', _x( 'Designation', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_email', [
            'label'       => plugin()->translations->get( 'email_label', _x( 'Email Address', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_mobile', [
            'label'       => plugin()->translations->get( 'mobile_label', _x( 'Mobile (Personal)', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_telephone', [
            'label'       => plugin()->translations->get( 'telephone_label', _x( 'Telephone (Office)', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_fax', [
            'label'       => plugin()->translations->get( 'fax_label', _x( 'Fax', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_experience', [
            'label'       => plugin()->translations->get( 'experience_label', _x( 'Years of Experience', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_website', [
            'label'       => plugin()->translations->get( 'website_label', _x( 'Website', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_company', [
            'label'       => plugin()->translations->get( 'company_label', _x( 'Company', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_address', [
            'label'       => plugin()->translations->get( 'address_label', _x( 'Address', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $this->add_control( '_ribbon', [
            'label'       => plugin()->translations->get( 'ribbon_label', _x( 'Ribbon / Tag', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::TEXT,
        ] );
        $link_1_label = plugin()->translations->get( 'link_1_label', _x( 'Resume Link', 'Admin Metabox', 'wps-team' ) );
        $link_2_label = plugin()->translations->get( 'link_2_label', _x( 'Hire Link', 'Admin Metabox', 'wps-team' ) );
        $this->add_control( '_custom_url', [
            'label'       => Variables::get( 'custom_url_label' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
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
            'label'       => plugin()->translations->get( 'color_label', _x( 'Color', 'Admin Metabox', 'wps-team' ) ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::COLOR,
        ] );
        $this->end_controls_section();
    }

    protected function social_links() {
        $this->start_controls_section( 'social_links', [
            'label' => _x( 'Social Links', 'Admin Metabox', 'wps-team' ),
        ] );
        $repeater = new Repeater();
        $repeater->add_control( 'social_icon', [
            'type'        => Controls_Manager::ICON,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Icon', 'Admin Metabox', 'wps-team' ),
        ] );
        $repeater->add_control( 'social_link', [
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Link', 'Admin Metabox', 'wps-team' ),
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
            'label' => _x( 'Skills', 'Admin Metabox', 'wps-team' ),
        ] );
        $repeater = new Repeater();
        $repeater->add_control( 'skill_name', [
            'type'        => Controls_Manager::TEXT,
            'label_block' => true,
            'separator'   => 'none',
            'placeholder' => _x( 'Skill Name', 'Admin Metabox', 'wps-team' ),
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

    protected function pricing() {
        $this->start_controls_section( 'pricing', [
            'label' => _x( 'Pricing', 'Admin Metabox', 'wps-team' ),
        ] );
        $this->add_control( '_enable_pricing', [
            'label'       => Variables::get( 'enable_price_txt' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

    protected function additional() {
        $this->start_controls_section( 'additional', [
            'label' => _x( 'Additional Settings', 'Admin Metabox', 'wps-team' ),
        ] );
        $this->add_control( '_panel_position', [
            'label'       => Variables::get( 'panel_pos_label' ),
            'label_block' => false,
            'separator'   => 'none',
            'type'        => Controls_Manager::UPGRADE_NOTICE,
        ] );
        $this->end_controls_section();
    }

}
