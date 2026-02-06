<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Data {
    use Taxonomy;
    public function __construct() {
        add_action( 'admin_menu', array($this, 'custom_taxonomies_menu'), 5 );
        add_action( 'in_admin_header', array($this, 'add_taxonomies_menu'), 0 );
        /*
         * Register Custom Post Types
         */
        add_action( 'init', array($this, 'register_cpts'), 0 );
        /*
         * Register Custom Taxonomies
         */
        add_action( 'init', array($this, 'register_taxonomies'), 0 );
        // Update First Name & Last Name from Quick Edit
        $post_type = Utils::post_type_name();
        add_action(
            "edit_post_{$post_type}",
            array($this, 'save_name_fields_quick_edit'),
            10,
            2
        );
        /*
         * Register Custom Metaboxes
         */
        add_action( 'add_meta_boxes', array($this, 'register_metaboxes') );
        /*
         * Handle Meta Fields Saving
         */
        add_action( 'save_post_' . Utils::post_type_name(), array($this, 'save_meta_fields') );
        /*
         * Display Columns in Members admin page
         */
        add_action( 'admin_head', [$this, 'add_columns_style'] );
        add_filter( 'manage_' . Utils::post_type_name() . '_posts_columns', [$this, 'post_type_columns'] );
        add_action(
            'manage_' . Utils::post_type_name() . '_posts_custom_column',
            [$this, 'post_type_columns_data'],
            10,
            2
        );
    }

    /*
     * Add order column to Taxonomies
     */
    public function save_name_fields_quick_edit( $post_id, $post ) {
        Utils::update_name_fields_from_title( $post_id, $post->post_title );
    }

    /*
     * Post type columns style
     */
    public function add_columns_style() {
        echo '<style>.post-type-wps-team-members .thumbnail.column-thumbnail img{border-radius:2px}.post-type-wps-team-members th.manage-column.column-thumbnail{width:100px}.wps-post--info{margin-bottom:4px}.wps-post--info:first-child{margin-top:6px}.wps-post--info:last-child{margin-bottom:6px}</style>';
    }

    /*
     * Add post type columns
     */
    public function post_type_columns( $columns ) {
        $_columns = [];
        $date = $columns['date'];
        $cb = $columns['cb'];
        $_columns['cb'] = $cb;
        $_columns['thumbnail'] = _x( 'Thumbnail', 'Dashboard', 'wps-team' );
        $_columns = array_merge( $_columns, $columns );
        $_columns['title'] = _x( 'Name', 'Dashboard', 'wps-team' );
        unset($_columns['date']);
        $_columns['contact_info'] = _x( 'Contact Info', 'Dashboard', 'wps-team' );
        $_columns['other_info'] = _x( 'Other Info', 'Dashboard', 'wps-team' );
        $_columns['date'] = $date;
        return $_columns;
    }

    /*
     * Handle post type columns data
     */
    public function post_type_columns_data( $column, $post_id ) {
        if ( $column == 'thumbnail' ) {
            echo get_the_post_thumbnail( $post_id, array(64, 64) );
        }
        if ( $column == 'contact_info' ) {
            $email = get_post_meta( $post_id, '_email', true );
            $mobile = get_post_meta( $post_id, '_mobile', true );
            $telephone = get_post_meta( $post_id, '_telephone', true );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Email:', 'Dashboard', 'wps-team' ), esc_html( $email ) );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Mobile:', 'Dashboard', 'wps-team' ), esc_html( $mobile ) );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Telephone:', 'Dashboard', 'wps-team' ), esc_html( $telephone ) );
        }
        if ( $column == 'other_info' ) {
            $company = get_post_meta( $post_id, '_company', true );
            $designation = get_post_meta( $post_id, '_designation', true );
            $website = get_post_meta( $post_id, '_website', true );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Company:', 'Dashboard', 'wps-team' ), esc_html( $company ) );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Designation:', 'Dashboard', 'wps-team' ), esc_html( $designation ) );
            printf( '<div class="wps-post--info"><strong class="wps-post--info-title">%s</strong>&nbsp;&nbsp;<span class="wps-post--info-data">%s</span></div>', esc_html_x( 'Website:', 'Dashboard', 'wps-team' ), esc_html( $website ) );
        }
    }

    /*
     * Register Custom Post Types
     */
    public function register_cpts() {
        $single_name = ucfirst( Utils::get_setting( 'member_single_name' ) );
        $plural_name = ucfirst( Utils::get_setting( 'member_plural_name' ) );
        $single_name_lc = lcfirst( $single_name );
        $plural_name_lc = lcfirst( $plural_name );
        $labels = array(
            'name'                  => $plural_name,
            'singular_name'         => $single_name,
            'menu_name'             => 'Team',
            'name_admin_bar'        => $single_name,
            'archives'              => sprintf( _x( '%s Archives', 'Team Post Type', 'wps-team' ), $single_name ),
            'attributes'            => sprintf( _x( '%s Attributes', 'Team Post Type', 'wps-team' ), $single_name ),
            'all_items'             => sprintf( _x( 'All %s', 'Team Post Type', 'wps-team' ), $plural_name ),
            'add_new_item'          => sprintf( _x( 'Add %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'add_new'               => sprintf( _x( 'Add %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'new_item'              => sprintf( _x( 'New %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'edit_item'             => sprintf( _x( 'Edit %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'update_item'           => sprintf( _x( 'Update %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'view_item'             => sprintf( _x( 'View %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'search_items'          => sprintf( _x( 'Search %s', 'Team Post Type', 'wps-team' ), $single_name ),
            'featured_image'        => sprintf( _x( '%s Image', 'Team Post Type', 'wps-team' ), $single_name ),
            'view_items'            => sprintf( _x( 'View %s', 'Team Post Type', 'wps-team' ), $plural_name ),
            'items_list'            => sprintf( _x( '%s list', 'Team Post Type', 'wps-team' ), $plural_name ),
            'items_list_navigation' => sprintf( _x( '%s list navigation', 'Team Post Type', 'wps-team' ), $plural_name ),
            'set_featured_image'    => sprintf( _x( 'Set %s image', 'Team Post Type', 'wps-team' ), $single_name_lc ),
            'remove_featured_image' => sprintf( _x( 'Remove %s image', 'Team Post Type', 'wps-team' ), $single_name_lc ),
            'use_featured_image'    => sprintf( _x( 'Use as %s image', 'Team Post Type', 'wps-team' ), $single_name_lc ),
            'insert_into_item'      => sprintf( _x( 'Insert into %s', 'Team Post Type', 'wps-team' ), $single_name_lc ),
            'uploaded_to_this_item' => sprintf( _x( 'Uploaded to this %s', 'Team Post Type', 'wps-team' ), $single_name_lc ),
            'filter_items_list'     => sprintf( _x( 'Filter %s list', 'Team Post Type', 'wps-team' ), $plural_name_lc ),
            'not_found'             => _x( 'Not found', 'Team Post Type', 'wps-team' ),
            'not_found_in_trash'    => _x( 'Not found in Trash', 'Team Post Type', 'wps-team' ),
        );
        $args = array(
            'label'                          => $single_name,
            'labels'                         => $labels,
            'supports'                       => array(
                'title',
                'editor',
                'thumbnail',
                'excerpt'
            ),
            'taxonomies'                     => array('group'),
            'hierarchical'                   => false,
            'public'                         => false,
            'show_in_menu'                   => true,
            'menu_position'                  => 5,
            'menu_icon'                      => Utils::get_plugin_icon(),
            'show_in_admin_bar'              => true,
            'can_export'                     => true,
            'has_archive'                    => false,
            'show_ui'                        => true,
            'rewrite'                        => false,
            'capability_type'                => 'post',
            'wpml_cf_fields'                 => true,
            'show_in_wpml_language_switcher' => true,
        );
        if ( Utils::has_archive() ) {
            $args['public'] = true;
            $args['has_archive'] = Utils::get_setting( 'enable_archive' );
            $args['rewrite'] = [
                'slug'       => Utils::get_archive_slug(),
                'with_front' => Utils::get_setting( 'with_front' ),
            ];
        }
        register_post_type( Utils::post_type_name(), $args );
    }

    /*
     * Register Custom Metaboxes
     */
    public function register_metaboxes() {
        add_meta_box(
            'member-details',
            _x( 'Member\'s Details', 'Admin Metabox', 'wps-team' ),
            array($this, 'metabox_content'),
            Utils::post_type_name()
        );
        add_meta_box(
            'member-gallery',
            _x( 'Member\'s Gallery', 'Admin Metabox', 'wps-team' ),
            array($this, 'metabox_gallery_content'),
            Utils::post_type_name(),
            'side',
            'low'
        );
    }

    /*
     * Custom Metabox Content
     */
    public function print_nonce() {
        wp_nonce_field( 'wps_save_meta_' . get_the_ID(), '_wps_meta_nonce' );
    }

    /*
     * Custom Metabox Content
     */
    public function metabox_content() {
        global $post;
        $meta_data = $this->get_validated_meta_data( $post->ID );
        // Sanitization & Validation Done
        printf( "<div id='wps-meta-boxes'><meta-box meta_data='%s'></meta-box></div>", esc_attr( json_encode( $meta_data ) ) );
        $this->print_education_meta_fields( $meta_data );
        $this->print_nonce();
    }

    /*
     * Print Education Meta Fields
     */
    public function print_education_meta_fields( $meta_data ) {
        ?>

        <!-- Education -->
        <div class="wps-meta-box--area d-flex">
            <div class="wps-meta-box--area-inner g-0 mt-0 pt-3 pb-3 flex-wrap">
                <section class="wps-section wps-section--education_section">
                    <h2 class="wps-section--title d-flex align-items-center justify-content-between"><?php 
        echo esc_html_x( 'Education', 'Admin Metabox', 'wps-team' );
        ?></h2>
                    <div class="wps-section--fields">
                        <div class="wps-field--wrapper">

                            <?php 
        ?>

                                <div class="wps-field wps-field-type--upgrade_notice wps-field--upgrade-notice wps-field-block wps-field-separator--none">
                                    <div class="wps-field-core d-flex flex-wrap align-items-center">
                                        <span class="wps-field-group d-flex">
                                            <div class="wps--upgrade-notice">
                                                <i class="fas fa-rocket"></i>
                                                <span>Upgrade to Pro</span>
                                            </div>
                                        </span>
                                    </div>
                                </div>

                            <?php 
        ?>

                        </div>
                    </div>
                </section>
            </div>
        </div>
        <!-- Education End -->

        <?php 
    }

    /*
     * Custom Metabox Gallery Content
     */
    public function metabox_gallery_content() {
        global $post;
        ?>

            <div style="margin-top: 14px;">Gallery Images are used for Flip & Carousel Layouts</div>
            <div class="wps--upgrade-notice" style="margin: 14px 0;"><i class="fas fa-rocket"></i> <span>Upgrade to Pro</span></div>

        <?php 
    }

    /*
     * Handle Meta Fields Saving
     */
    public function save_meta_fields( $post_id ) {
        if ( empty( $_POST['_wps_meta_nonce'] ) ) {
            return $post_id;
        }
        if ( !wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wps_meta_nonce'] ) ), 'wps_save_meta_' . $post_id ) ) {
            return $post_id;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return $post_id;
        }
        if ( get_post_status( $post_id ) === 'auto-draft' ) {
            return $post_id;
        }
        if ( !current_user_can( 'edit_page', $post_id ) || !current_user_can( 'edit_post', $post_id ) ) {
            return $post_id;
        }
        /*
         * Save Gallery Meta Fields
         */
        if ( !empty( $_POST['gallery'] ) ) {
            $gallery_data = ( !empty( $_POST['gallery'] ) && is_array( $_POST['gallery'] ) ? array_map( 'intval', wp_unslash( $_POST['gallery'] ) ) : [] );
            $gallery_data = array_filter( $gallery_data );
            if ( $gallery_data ) {
                update_post_meta( $post_id, '_gallery', $gallery_data );
            } else {
                delete_post_meta( $post_id, '_gallery' );
            }
        } else {
            delete_post_meta( $post_id, '_gallery' );
        }
        /*
         * Save Details Meta Fields
         */
        if ( !empty( $_POST['_wps_member_meta_data'] ) ) {
            $meta_data_raw = wp_unslash( $_POST['_wps_member_meta_data'] );
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
            $meta_data = json_decode( $meta_data_raw, true );
            $meta_data = $this->get_validated_meta_data( $post_id, $meta_data );
            // Sanitization & Validation Done
            // First Name & Last Name
            $first_name = ( isset( $_POST['_first_name'] ) ? sanitize_text_field( wp_unslash( $_POST['_first_name'] ) ) : '' );
            $last_name = ( isset( $_POST['_last_name'] ) ? sanitize_text_field( wp_unslash( $_POST['_last_name'] ) ) : '' );
            if ( $first_name !== '' ) {
                $meta_data['_first_name'] = $first_name;
            }
            if ( $last_name !== '' ) {
                $meta_data['_last_name'] = $last_name;
            }
            // Education
            if ( isset( $_POST['_education'] ) ) {
                $meta_data['_education'] = wp_kses_post( wp_unslash( $_POST['_education'] ) );
            }
            foreach ( $meta_data as $meta_key => $meta_value ) {
                update_post_meta( $post_id, $meta_key, $meta_value );
                Utils::update_all_posts_meta_vals();
            }
        }
    }

    /*
     * Get sanitized meta data
     */
    public function get_sanitize_meta_data( $data = [] ) {
        foreach ( $data as $meta_key => $meta_val ) {
            if ( in_array( $meta_key, [
                '_first_name',
                '_last_name',
                '_designation',
                '_company',
                '_ribbon',
                '_color',
                '_experience',
                '_mobile',
                '_telephone',
                '_fax',
                '_address'
            ] ) ) {
                $data[$meta_key] = sanitize_text_field( $meta_val );
                continue;
            }
            if ( $meta_key == '_email' ) {
                $data[$meta_key] = sanitize_email( $meta_val );
                continue;
            }
            if ( $meta_key == '_website' ) {
                $data[$meta_key] = sanitize_url( $meta_val );
                continue;
            }
            if ( $meta_key == '_social_links' ) {
                foreach ( $meta_val as &$s_link ) {
                    if ( !empty( $s_link['social_icon'] ) ) {
                        $s_link['social_icon'] = array_map( 'sanitize_text_field', $s_link['social_icon'] );
                    }
                    if ( !empty( $s_link['social_link'] ) ) {
                        $s_link['social_link'] = sanitize_url( $s_link['social_link'] );
                    }
                }
                $data[$meta_key] = $meta_val;
                continue;
            }
            if ( $meta_key == '_skills' ) {
                foreach ( $meta_val as &$skill ) {
                    if ( !empty( $skill['skill_name'] ) ) {
                        $skill['skill_name'] = sanitize_text_field( $skill['skill_name'] );
                    }
                    if ( !empty( $skill['skill_val'] ) ) {
                        $skill['skill_val'] = (int) $skill['skill_val'];
                    }
                }
                $data[$meta_key] = $meta_val;
                continue;
            }
        }
        return $data;
    }

    /*
     * Get validated meta data
     */
    public function get_validated_meta_data( $post_id, $data = [] ) {
        $controls = Utils::get_meta_box_controls();
        // Reading the Meta Fields
        if ( empty( $data ) ) {
            $meta_keys = Utils::get_meta_field_keys();
            if ( !empty( $meta_keys ) ) {
                foreach ( $meta_keys as $key ) {
                    $data[$key] = get_post_meta( $post_id, $key, true );
                    if ( empty( $data[$key] ) && !empty( $controls[$key] ) && array_key_exists( 'default', $controls[$key] ) ) {
                        $data[$key] = $controls[$key]['default'];
                    }
                }
            }
        }
        return $this->get_sanitize_meta_data( $data );
    }

}
