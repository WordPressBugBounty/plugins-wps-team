<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Demo_Import {
    use AJAX_Handler;
    public static $key = 'wpspeedo_team';

    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_demo_import_handler';

    public function __construct() {
        $this->set_ajax_scope_hooks();
        add_action( 'edit_post_' . Utils::post_type_name(), array($this, 'remove_dummy_indicator'), 10 );
        $this->setup_import_process();
        add_action( 'admin_init', [$this, 'maybe_disable_notice'] );
    }

    public function maybe_disable_notice() {
        $demo_import_notice = plugin()->notifications->manager->get( 'demo_import_notice' );
        if ( $demo_import_notice === false || !$demo_import_notice->is_active ) {
            return;
        }
        // Already Disabled the Notice
        $installed_age = Utils::get_timestamp_diff( Utils::get_installed_time() );
        if ( $installed_age > 7 ) {
            return $this->disable_notice();
        }
        if ( Utils::get_demo_data_status( 'post_data' ) || Utils::get_demo_data_status( 'shortcode_data' ) ) {
            return $this->disable_notice();
        }
        $post_count = Utils::get_posts( [
            'posts_per_page' => 1,
        ] )->post_count;
        wp_reset_query();
        if ( $post_count ) {
            return $this->disable_notice();
        }
    }

    public function disable_notice() {
        $demo_import_notice = plugin()->notifications->manager->get( 'demo_import_notice' );
        if ( $demo_import_notice !== false && $demo_import_notice->is_active ) {
            $demo_import_notice->is_active = false;
            $demo_import_notice->save();
        }
    }

    public function remove_dummy_indicator( $post_id ) {
        if ( empty( get_post_meta( $post_id, self::$key . '--dummy', true ) ) ) {
            return;
        }
        $taxonomies = $this->get_taxonomy_list();
        // Remove dummy indicator from texonomies
        $dummy_terms = wp_get_post_terms( $post_id, $taxonomies, [
            'fields'     => 'ids',
            'meta_key'   => self::$key . '--dummy',
            'meta_value' => 1,
        ] );
        if ( !empty( $dummy_terms ) ) {
            foreach ( $dummy_terms as $term_id ) {
                delete_term_meta( $term_id, self::$key . '--dummy', 1 );
            }
            delete_transient( self::$key . '_dummy_terms' );
        }
        // Remove dummy indicator from attachments
        $thumbnail_id = get_post_meta( $post_id, '_thumbnail_id', true );
        if ( !empty( $thumbnail_id ) ) {
            delete_post_meta( $thumbnail_id, self::$key . '--dummy', 1 );
        }
        delete_transient( self::$key . '_dummy_attachments' );
        // Remove dummy indicator from post
        delete_post_meta( $post_id, self::$key . '--dummy', 1 );
        delete_transient( self::$key . '_dummy_posts' );
    }

    public static function demo_import_key() {
        return self::$key . '_' . 'dummy-data-notice-forever';
    }

    public function get_taxonomy_list() {
        return ['wps-team-group'];
    }

    public function ajax_import_demo_data() {
        $this->disable_notice();
        if ( get_option( self::$key . '_dummy_post_data_created' ) !== false || get_transient( self::$key . '_dummy_post_data_creating' ) !== false ) {
            $message_202 = _x( 'Dummy team members already imported', 'Settings: Tools', 'wpspeedo-team' );
            if ( wp_doing_ajax() ) {
                wp_send_json_success( $message_202, 202 );
            }
            return [
                'status'  => 202,
                'message' => $message_202,
            ];
        }
        $this->create_dummy_attachments();
        $message = _x( 'Dummy team members imported', 'Settings: Tools', 'wpspeedo-team' );
        if ( wp_doing_ajax() ) {
            wp_send_json_success( $message, 200 );
        }
        return [
            'status'  => 200,
            'message' => $message,
        ];
    }

    public function ajax_remove_demo_data() {
        $this->delete_dummy_attachments();
        $this->delete_dummy_terms();
        $this->delete_dummy_posts();
        delete_option( self::$key . '_dummy_post_data_created' );
        delete_transient( self::$key . '_dummy_post_data_creating' );
        $message = _x( 'Dummy team members deleted', 'Settings: Tools', 'wpspeedo-team' );
        if ( wp_doing_ajax() ) {
            wp_send_json_success( $message, 200 );
        }
        return [
            'status'  => 200,
            'message' => $message,
        ];
    }

    public function get_taxonomy_ids_by_slugs( $taxonomy_group, $taxonomy_slugs = [] ) {
        $_terms = $this->get_dummy_terms();
        if ( empty( $_terms ) ) {
            return [];
        }
        $_terms = wp_filter_object_list( $_terms, [
            'taxonomy' => $taxonomy_group,
        ] );
        $_terms = array_values( $_terms );
        // reset the keys
        if ( empty( $_terms ) ) {
            return [];
        }
        $term_ids = [];
        foreach ( $taxonomy_slugs as $slug ) {
            $key = array_search( $slug, array_column( $_terms, 'slug' ) );
            if ( $key !== false ) {
                $term_ids[] = $_terms[$key]['term_id'];
            }
        }
        return $term_ids;
    }

    public function get_attachment_id_by_filename( $filename ) {
        if ( empty( $attachments = $this->get_dummy_attachments() ) ) {
            return '';
        }
        $filename = wp_basename( $filename, '.jpg' );
        $attachments = wp_filter_object_list( $attachments, [
            'post_name' => $filename,
        ] );
        if ( empty( $attachments ) ) {
            return '';
        }
        $attachments = array_values( $attachments );
        return $attachments[0]->ID;
    }

    public function get_tax_inputs( $tax_inputs = [] ) {
        if ( empty( $tax_inputs ) ) {
            return $tax_inputs;
        }
        foreach ( $tax_inputs as $tax_input => $tax_params ) {
            $tax_inputs[$tax_input] = $this->get_taxonomy_ids_by_slugs( $tax_input, $tax_params );
        }
        return $tax_inputs;
    }

    public function get_meta_inputs( $meta_inputs = [] ) {
        // if ( ! $this->is_pro ) {
        //     if ( isset($meta_inputs['_gs_com']) ) unset( $meta_inputs['_gs_com'] );
        //     if ( isset($meta_inputs['_gs_land']) ) unset( $meta_inputs['_gs_land'] );
        //     if ( isset($meta_inputs['_gs_cell']) ) unset( $meta_inputs['_gs_cell'] );
        //     if ( isset($meta_inputs['_gs_email']) ) unset( $meta_inputs['_gs_email'] );
        //     if ( isset($meta_inputs['_gs_address']) ) unset( $meta_inputs['_gs_address'] );
        //     if ( isset($meta_inputs['_gs_ribon']) ) unset( $meta_inputs['_gs_ribon'] );
        //     if ( isset($meta_inputs['gs_skill']) ) unset( $meta_inputs['gs_skill'] );
        //     if ( isset($meta_inputs['second_featured_img']) ) unset( $meta_inputs['second_featured_img'] );
        // }
        $meta_inputs['_thumbnail_id'] = $this->get_attachment_id_by_filename( $meta_inputs['_thumbnail_id'] );
        // Support for Wpspeedo metabox editor
        $meta_inputs['_wps_member_meta_keys'] = [
            '_designation',
            '_email',
            '_mobile',
            '_telephone',
            '_experience',
            '_website',
            '_company',
            '_ribbon',
            '_color',
            '_social_links',
            '_skills'
        ];
        $meta_inputs[self::$key . '--dummy'] = 1;
        return $meta_inputs;
    }

    // Posts
    public function create_dummy_posts() {
        do_action( self::$key . '_dummy_posts_process_start' );
        $file = plugin_dir_path( __FILE__ ) . 'demo-posts.json';
        $content = file_get_contents( $file );
        $posts = json_decode( $content, true );
        foreach ( $posts as $post ) {
            $post['tax_input'] = $this->get_tax_inputs( $post['tax_input'] );
            $post['meta_input'] = $this->get_meta_inputs( $post['meta_input'] );
            $post['post_type'] = Utils::post_type_name();
            $post['post_date'] = current_time( 'mysql' );
            $post['post_status'] = 'publish';
            wp_insert_post( $post );
        }
        do_action( self::$key . '_dummy_posts_process_finished' );
    }

    public function delete_dummy_posts() {
        $posts = $this->get_dummy_posts();
        if ( empty( $posts ) ) {
            return;
        }
        foreach ( $posts as $post ) {
            wp_delete_post( $post->ID, true );
        }
        delete_transient( self::$key . '_dummy_posts' );
    }

    public function get_dummy_posts() {
        $posts = get_transient( self::$key . '_dummy_posts' );
        if ( false !== $posts ) {
            return $posts;
        }
        $posts = get_posts( array(
            'numberposts' => -1,
            'post_type'   => Utils::post_type_name(),
            'meta_key'    => self::$key . '--dummy',
            'meta_value'  => 1,
        ) );
        if ( is_wp_error( $posts ) || empty( $posts ) ) {
            delete_transient( self::$key . '_dummy_posts' );
            return [];
        }
        set_transient( self::$key . '_dummy_posts', $posts, 3 * MINUTE_IN_SECONDS );
        return $posts;
    }

    // Attachments
    public function create_dummy_attachments() {
        do_action( self::$key . '_dummy_attachments_process_start' );
        $attachment_files = [
            'wpspeedo-team-01.jpg',
            'wpspeedo-team-02.jpg',
            'wpspeedo-team-03.jpg',
            'wpspeedo-team-04.jpg',
            'wpspeedo-team-05.jpg',
            'wpspeedo-team-06.jpg',
            'wpspeedo-team-07.jpg',
            'wpspeedo-team-08.jpg',
            'wpspeedo-team-09.jpg',
            'wpspeedo-team-10.jpg',
            'wpspeedo-team-11.jpg',
            'wpspeedo-team-12.jpg'
        ];
        wp_raise_memory_limit( 'image' );
        foreach ( $attachment_files as $file_name ) {
            $attach_id = media_sideload_image(
                plugin_dir_url( __FILE__ ) . 'img/' . $file_name,
                0,
                null,
                'id'
            );
            add_post_meta( $attach_id, self::$key . '--dummy', 1 );
        }
        do_action( self::$key . '_dummy_attachments_process_finished' );
    }

    public function delete_dummy_attachments() {
        $attachments = $this->get_dummy_attachments();
        if ( empty( $attachments ) ) {
            return;
        }
        foreach ( $attachments as $attachment ) {
            wp_delete_attachment( $attachment->ID, true );
        }
        delete_transient( self::$key . '_dummy_attachments' );
    }

    public function get_dummy_attachments() {
        $attachments = get_transient( self::$key . '_dummy_attachments' );
        if ( false !== $attachments ) {
            return $attachments;
        }
        $attachments = get_posts( array(
            'numberposts' => -1,
            'post_type'   => 'attachment',
            'post_status' => 'inherit',
            'meta_key'    => self::$key . '--dummy',
            'meta_value'  => 1,
        ) );
        if ( is_wp_error( $attachments ) || empty( $attachments ) ) {
            delete_transient( self::$key . '_dummy_attachments' );
            return [];
        }
        set_transient( self::$key . '_dummy_attachments', $attachments, 3 * MINUTE_IN_SECONDS );
        return $attachments;
    }

    // Terms
    public function create_dummy_terms() {
        do_action( self::$key . '_dummy_terms_process_start' );
        $terms = [
            [
                'name'  => 'Accountant',
                'slug'  => 'accountant',
                'group' => 'wps-team-group',
            ],
            [
                'name'  => 'Business',
                'slug'  => 'business',
                'group' => 'wps-team-group',
            ],
            [
                'name'  => 'Manager',
                'slug'  => 'manager',
                'group' => 'wps-team-group',
            ],
            [
                'name'  => 'Marketer',
                'slug'  => 'marketer',
                'group' => 'wps-team-group',
            ]
        ];
        foreach ( $terms as $term ) {
            $response = wp_insert_term( $term['name'], $term['group'], array(
                'slug' => $term['slug'],
            ) );
            if ( !is_wp_error( $response ) ) {
                add_term_meta( $response['term_id'], self::$key . '--dummy', 1 );
            }
        }
        do_action( self::$key . '_dummy_terms_process_finished' );
    }

    public function delete_dummy_terms() {
        $terms = $this->get_dummy_terms();
        if ( empty( $terms ) ) {
            return;
        }
        foreach ( $terms as $term ) {
            wp_delete_term( $term['term_id'], $term['taxonomy'] );
        }
        delete_transient( self::$key . '_dummy_terms' );
    }

    public function get_dummy_terms() {
        $terms = get_transient( self::$key . '_dummy_terms' );
        if ( false !== $terms ) {
            return $terms;
        }
        $taxonomies = $this->get_taxonomy_list();
        $terms = get_terms( array(
            'taxonomy'   => $taxonomies,
            'hide_empty' => false,
            'meta_key'   => self::$key . '--dummy',
            'meta_value' => 1,
        ) );
        $terms = json_decode( json_encode( $terms ), true );
        // Object to Array
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            delete_transient( self::$key . '_dummy_terms' );
            return [];
        }
        set_transient( self::$key . '_dummy_terms', $terms, 3 * MINUTE_IN_SECONDS );
        return $terms;
    }

    public function setup_import_process() {
        add_action( self::$key . '_dummy_attachments_process_start', function () {
            delete_option( self::$key . '_dummy_post_data_created' );
            set_transient( self::$key . '_dummy_post_data_creating', 1, 3 * MINUTE_IN_SECONDS );
        } );
        add_action( self::$key . '_dummy_attachments_process_finished', function () {
            $this->create_dummy_terms();
        } );
        add_action( self::$key . '_dummy_terms_process_finished', function () {
            $this->create_dummy_posts();
        } );
        add_action( self::$key . '_dummy_posts_process_finished', function () {
            delete_transient( self::$key . '_dummy_post_data_creating' );
            update_option( self::$key . '_dummy_post_data_created', 1 );
        } );
    }

}
