<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Export_Import_Manager {

    use AJAX_Handler, Taxonomy;

    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_export_import_handler';

    private $zip_instance;

    private $zip_file;

    private $is_pro;
    
    private $upload_dir;

    public function __construct() {

        $this->is_pro = wps_team_fs()->can_use_premium_code__premium_only();

        $this->set_ajax_scope_hooks();

    }

    public function ajax_export_data() {

        // allow for manage_options capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __('You do not have permission to perform this action', 'wpspeedo-team'), 403 );
        }

        // Check for required data
        if ( empty( $options = $_REQUEST['options'] ) ) {
            wp_send_json_error( __('No export data provided', 'wpspeedo-team'), 400 );
        }

        // Validate the export data
        $export_team_members = wp_validate_boolean( $options['team_members'] );
        $export_shortcodes   = wp_validate_boolean( $options['shortcodes'] );
        $export_settings     = wp_validate_boolean( $options['settings'] );

        // Check for valid export data
        if ( ! $export_team_members && ! $export_shortcodes && ! $export_settings ) {
            wp_send_json_error( __('No export data provided', 'wpspeedo-team'), 400 );
        }

        // Init the zip archive
        $this->init_zip_file();

        // Init the JSON data
        $json_data = [];

        // Add Posts Data to the zip file
        if ( $export_team_members ) $json_data = $this->export__team_members( $json_data );

        // Add Shortcodes Data to the zip file
        if ( $export_shortcodes ) $json_data = $this->export__shortcodes( $json_data );

        // Add Settings Data to the zip file
        if ( $export_settings ) $json_data = $this->export__settings( $json_data );

        // Add the JSON data to the zip file
        $this->zip_instance->addFromString( 'data.json', json_encode( $json_data, JSON_PRETTY_PRINT ) );

        // Send the zip file
        $this->send_zip_file_data();

    }

    public function export__team_members($json_data = []) {

        $json_data['posts']       = [];
        $json_data['attachments'] = [];
        $json_data['terms']       = [];

        $posts = get_posts([
            'posts_per_page'    => -1,
            'post_type'         => 'wps-team-members',
        ]);

        // Add Posts Data to the zip file
        foreach ($posts as $post) {

            extract((array) $post);

            $post_data = compact("ID", "post_date", "post_date_gmt", "post_content", "post_title", "post_excerpt", "post_status", "comment_status", "ping_status", "post_password", "post_name", "post_modified", "post_modified_gmt", "post_parent", "menu_order", "post_type");

            $post_data['meta_input'] = get_post_meta($ID, '', true);

            foreach ($post_data['meta_input'] as $meta_key => $meta_value) {
                foreach ($meta_value as $key => $value) {
                    if (is_serialized($value)) {
                        $meta_value[$key] = maybe_unserialize($value);
                    }
                }
                $post_data['meta_input'][$meta_key] = $meta_value;
            }

            $post_data['tax_input'] = [];
            foreach ( Utils::get_active_taxonomies() as $taxonomy ) {
                $post_data['tax_input'][$taxonomy] = wp_get_post_terms($ID, $taxonomy, ['fields' => 'ids']);
            }

            unset($post_data['meta_input']['_edit_last']);
            unset($post_data['meta_input']['_edit_lock']);
            unset($post_data['meta_input']['wpspeedo-team-demo_data']);

            $json_data['posts'][] = $post_data;
        }

        // Generate Attachments Data
        foreach ($posts as $post) {

            $thumbnail_id = get_post_thumbnail_id($post->ID);

            if ( ! empty( $thumbnail_id ) ) {
                $thumbnail_data = $this->get_attachment_export_data($thumbnail_id);
                $json_data['attachments'][] = $thumbnail_data;
            }

            $gallery_ids = get_post_meta($post->ID, '_gallery', true);

            if ( ! empty( $gallery_ids ) ) {
                foreach ($gallery_ids as $gallery_id) {
                    $json_data['attachments'][] = $this->get_attachment_export_data($gallery_id);
                }
            }
        }

        // Add Attachments Data to the zip file
        foreach ($json_data['attachments'] as $key => $attachment) {
            $file_name = basename($attachment['file_path']);
            $this->zip_instance->addFile($attachment['file_path'], 'attachments/' . basename($attachment['file_path']));
            $json_data['attachments'][$key]['file_name'] = $file_name;
            unset($json_data['attachments'][$key]['file_path']);
        }

        // Add Terms Data to the zip file
        $json_data['terms'] = get_terms([
            'taxonomy' => Utils::get_active_taxonomies(),
            'hide_empty' => false
        ]);

        return $json_data;
    }

    public function export__shortcodes($json_data = []) {

        // Add Shortcodes Data to the zip file
        $json_data['shortcodes'] = $this->get_shortcode_list();

        // Return the generated data
        return $json_data;
    }

    public function export__settings($json_data = []) {

        // Add Generat Settings to zip
        $json_data['settings'] = Utils::get_general_settings();

        // Add Taxonomy Settings to zip
        $json_data['taxonomy_settings'] = Utils::get_taxonomies_settings();

        // Return the generated data
        return $json_data;
    }

    public function get_attachment_export_data($attachment_id) {
        $attachment = get_post($attachment_id);
        $attachment_data = array(
            'ID' => $attachment->ID,
            'title' => $attachment->post_title,
            'description' => $attachment->post_content,
            'caption' => $attachment->post_excerpt,
            'alt_text' => get_post_meta($attachment_id, '_wp_attachment_image_alt', true),
            'file_path' => get_attached_file($attachment_id)
        );
        return $attachment_data;
    }

    public function get_shortcode_list() {

        global $wpdb;

        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A );

        foreach( $shortcodes as &$shortcode ) {
            $shortcode['settings'] = Utils::maybe_json_decode( $shortcode['settings'] );
            $shortcode['settings'] = plugin()->api->validate_shortcode( $shortcode )->get_settings_value(); // Settings will be Sanitized & Validated by Shortcode_Editor class.
            $shortcode['settings'] = Utils::maybe_json_encode( $shortcode['settings'] ); // Encode settings to JSON format
        }

        return $shortcodes;
    }

    public function init_zip_file() {

        // Init the zip archive
        $this->zip_instance = new \ZipArchive();

        // Init the zip file
        $this->zip_file = get_temp_dir() . 'wpspeedo/wps-team--export.zip';

        // Delete the zip file if it exists
        if ( file_exists( $this->zip_file ) ) unlink( $this->zip_file );

        // Create the zip file
        wp_mkdir_p( dirname( $this->zip_file ) );

        // Open the zip file
        $this->zip_instance->open( $this->zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
    }

    public function send_zip_file_data() {

        // Close the zip file
        $this->zip_instance->close();

        // Check file existence and readability
        if ( ! file_exists($this->zip_file) || ! is_readable($this->zip_file) ) {
            wp_send_json_error( __('Export file not found or inaccessible', 'wpspeedo-team'), 500 );
        }

        // Send the zip file
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="wps-team--export.zip"' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Content-Length: ' . filesize($this->zip_file) );
        header( 'Pragma: public' );

        readfile( $this->zip_file );

        // Delete the zip file
        if ( file_exists( $this->zip_file ) ) unlink( $this->zip_file );
        exit;
    }

    public function ajax_import_data() {

        // allow for manage_options capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __('You do not have permission to perform this action', 'wpspeedo-team'), 403 );
        }

        // Check for required data
        if ( empty($_FILES['import_file']) ) wp_send_json_error( __('No import file provided', 'wpspeedo-team'), 400 );

        // Save the uploaded file
        $this->upload_dir = $this->save_imported_file();

        // Check if the data.json file exists
        $json_import_file = $this->upload_dir . '/data.json';
        if ( ! file_exists( $json_import_file ) ) wp_send_json_error( __('Invalid file', 'wpspeedo-team'), 400 );

        // Read the JSON data
        $json_data = @file_get_contents($this->upload_dir . '/data.json');
        $json_data = json_decode($json_data, true);

        // Check for valid JSON data
        if (empty($json_data)) wp_send_json_error( __('Invalid file content', 'wpspeedo-team'), 400 );
        
        // Initiate the Import Process
        $this->import__team_data( $json_data );

        // Delete the uploaded files
        Utils::delete_directory_recursive( $this->upload_dir );

        // Send the success message
        wp_send_json_success( __('Data imported successfully', 'wpspeedo-team'), 200 );

    }

    public function import__team_data( $json_data ) {

        // Import the General Settings Data
        if (!empty($json_data['settings'])) {
            plugin()->api->save_settings( $json_data['settings'] );
        }

        // Import the Settings Data
        if ( ! empty( $json_data['taxonomy_settings'] ) ) {
            $settings = plugin()->api->sanitize_taxonomy_settings( $json_data['taxonomy_settings'] );
            update_option( Utils::get_taxonomies_option_name(), $settings );
            $this->register_taxonomies();
        }

        // Import the Attachments Data
        if (!empty($json_data['attachments'])) {
            $this->import__attachments($json_data['attachments']);
        }

        // Import the Terms Data
        if (!empty($json_data['terms'])) {
            $this->import__terms($json_data['terms']);
        }

        // Import the Posts Data
        if (!empty($json_data['posts'])) {
            $this->import__posts($json_data['posts']);
        }

        // Import the Shortcodes Data
        if (!empty($json_data['shortcodes'])) {
            $this->import__shortcodes($json_data['shortcodes']);
        }

    }

    public function import__attachments($attachments) {

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        wp_raise_memory_limit('image');

        foreach ($attachments as $attachment) {

            $file = $this->upload_dir . '/attachments/' . $attachment['file_name'];

            if ( !file_exists($file) ) continue;

            $mirror = wp_upload_bits(basename($file), null, file_get_contents($file));

            if (!empty($mirror['error'])) continue;

            $attachment_data = array(
                'guid'           => $mirror['url'],
                'post_mime_type' => $mirror['type'],
                'post_title'     => $attachment['title'],
                'post_content'   => $attachment['description'],
                'post_status'    => 'inherit',
                'post_excerpt'   => $attachment['caption'],
                'meta_input'     => array(
                    '_wp_attachment_image_alt' => $attachment['alt_text'],
                    '_wps_team_import_id' => $attachment['ID']
                )
            );

            $attachment_id = wp_insert_attachment($attachment_data, $mirror['file']);

            if (is_wp_error($attachment_id)) continue;

            $attach_data = wp_generate_attachment_metadata($attachment_id, $mirror['file']);

            wp_update_attachment_metadata($attachment_id, $attach_data);

        }

    }

    public function import__terms($terms) {

        foreach ($terms as $term) {

            $term_data = array(
                'slug' => $term['slug'],
                'description' => $term['description'],
                'parent' => $term['parent']
            );
                
            $inserted_term = wp_insert_term($term['name'], $term['taxonomy'], $term_data);
            
            if (is_wp_error($inserted_term)) continue;

            add_term_meta($inserted_term['term_id'], '_wps_team_import_id', $term['term_id']);

        }

    }

    public function import__posts($posts) {

        foreach ($posts as $post) {

            $meta_input = $post['meta_input'];

            $meta_input = array_map(function($value) {
                if ( !empty($value) ) {
                    if ( is_array($value) ) {
                        return $value[0];
                    } else {
                        return $value;
                    }
                }
                return '';
            }, $meta_input);

            $meta_input['_wps_team_import_id'] = $post['ID'];

            unset($post['ID']);

            if ( isset( $meta_input['_thumbnail_id'] ) ) {
                $thumbnail_id = $this->get_imported_post_id( $meta_input['_thumbnail_id'] );
                if ($thumbnail_id) {
                    $meta_input['_thumbnail_id'] = $thumbnail_id;
                } else {
                    unset($meta_input['_thumbnail_id']);
                }
            }

            if ( isset( $meta_input['_gallery'] ) && !empty( $meta_input['_gallery'] ) ) {
                foreach ($meta_input['_gallery'] as $key => $gallery_id) {
                    $thumbnail_id = $this->get_imported_post_id( $gallery_id );
                    if ($thumbnail_id) {
                        $meta_input['_gallery'][$key] = $thumbnail_id;
                    } else {
                        unset($meta_input['_gallery'][$key]);
                    }
                }
            }

            $post['meta_input'] = $meta_input;

            foreach ($post['tax_input'] as $taxonomy => $terms) {
                foreach ($terms as $key => $term) {
                    $term_id = (int) $this->get_imported_term_id($term);
                    if ($term_id) {
                        $terms[$key] = $term_id;
                    } else {
                        unset($terms[$key]);
                    }
                }
                $post['tax_input'][$taxonomy] = $terms;
            }

            wp_insert_post($post);
        }
        
    }

    public function get_imported_term_id( $term_id ) {

        global $wpdb;

        $term_id = (int) $term_id;

        if ( ! $term_id ) return false;

        $term_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '_wps_team_import_id' AND meta_value = %d LIMIT 1", $term_id ) );

        if ( ! $term_id ) return false;

        return $term_id;
    }

    public function get_imported_post_id( $post_id ) {

        global $wpdb;

        $post_id = (int) $post_id;

        if ( ! $post_id ) return false;

        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wps_team_import_id' AND meta_value = %d LIMIT 1", $post_id ) );

        if ( ! $post_id ) return false;

        return $post_id;
    }

    public function import__shortcodes($shortcodes) {

        global $wpdb;

        foreach ($shortcodes as $shortcode) {

            // Decode JSON settings
            $shortcode['settings'] = json_decode($shortcode['settings'], true);

            if ( $shortcode['settings'] === null ) continue; // Skip if settings are not valid JSON

            // Validate the shortcode settings
            $_shortcode = plugin()->api->validate_shortcode([
                'id' => uniqid(), // Fake ID
                'name' => empty($shortcode['name']) ? 'Undefined' : sanitize_text_field( $shortcode['name'] ),
                'settings' => $shortcode['settings'] // Settings will be Sanitized & Validated by Shortcode_Editor class.
            ]);

            // Build the data array
            $data = array(
                "name"          => $_shortcode->get_data('name'),
                "settings"      => Utils::maybe_json_encode( $_shortcode->get_settings_value() ),
                "created_at"    => $shortcode['created_at'],
                "updated_at"    => $shortcode['created_at'],
            );

            // Insert the shortcode
            $wpdb->insert( "{$wpdb->prefix}wps_team", $data, plugin()->api->db_columns_format() );
        }

    }

    public function replace_with_imported_terms($terms) {

        if (empty($terms)) return '';

        $terms = explode(',', $terms);

        $terms = array_map(function($term) {
            $term_id = $this->get_imported_term_id( (int) $term);
            return $term_id ? $term_id : '';
        }, $terms);

        $terms = array_filter($terms);

        return implode(',', $terms);
    }

    public function save_imported_file() {

        $import_file     = $_FILES['import_file'];
        $file_tmp_path   = $import_file['tmp_name'];
        $file_name       = $import_file['name'];
        $file_name_cmps  = explode(".", $file_name);
        $file_extension  = strtolower(end($file_name_cmps));

        if ( $file_extension != 'zip' ) wp_send_json_error( __('Invalid file type', 'wpspeedo-team'), 400 );

        $upload_file_dir = get_temp_dir() . 'wpspeedo/wps-team';

        if ( is_dir($upload_file_dir) ) Utils::delete_directory_recursive( $upload_file_dir );

        wp_mkdir_p($upload_file_dir);

        $dest_file_path  = $upload_file_dir . '/' . $file_name;

        if (move_uploaded_file($file_tmp_path, $dest_file_path)) {
            $zip = new \ZipArchive;
            if ($zip->open($dest_file_path) === true) {
                $zip->extractTo($upload_file_dir);
                $zip->close();
                unlink($dest_file_path);
            } else {
                wp_send_json_error(__('File upload failed', 'wpspeedo-team'), 400);
            }
        } else {
            wp_send_json_error(__('File upload failed', 'wpspeedo-team'), 400);
        }

        return $upload_file_dir;
    }

}