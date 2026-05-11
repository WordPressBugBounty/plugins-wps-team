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

    public function get_wp_filesystem() {
        if ( ! function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem;
    }
    
    public function ajax_export_data() {
        
        // allow for manage_options capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __('You do not have permission to perform this action', 'wps-team'), 403 );
        }
        
        // Check for required data
        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $options = isset($_REQUEST['options']) ? (array) wp_unslash($_REQUEST['options']) : [];

        $options = wp_parse_args( $options, [
            'team_members' => false,
            'shortcodes'   => false,
            'settings'     => false,
        ] );

        $export_team_members = wp_validate_boolean( $options['team_members'] );
        $export_shortcodes   = wp_validate_boolean( $options['shortcodes'] );
        $export_settings     = wp_validate_boolean( $options['settings'] );

        // Check for valid export data
        if ( ! $export_team_members && ! $export_shortcodes && ! $export_settings ) {
            wp_send_json_error( __('No export data provided', 'wps-team'), 400 );
        }

        // Prepare for heavy operation
        Utils::prepare_heavy_operation();

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

        $manifest = [
            'schema_version' => '1.1.0',
            'plugin' => 'wps-team',
            'plugin_version' => defined( 'WPS_TEAM_VERSION' ) ? WPS_TEAM_VERSION : 'unknown',
            'site_url' => home_url(),
            'exported_at_utc' => gmdate( 'c' ),
            'options' => [
                'team_members' => $export_team_members,
                'shortcodes'   => $export_shortcodes,
                'settings'     => $export_settings,
            ],
            'sections' => array_keys( $json_data ),
        ];

        // Add JSON payloads to the zip file.
        $this->zip_instance->addFromString( 'data.json', json_encode( $json_data, JSON_PRETTY_PRINT ) );
        $this->zip_instance->addFromString( 'manifest.json', json_encode( $manifest, JSON_PRETTY_PRINT ) );

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

        // Taxonomy manifest helps cross-site portability/debugging.
        $json_data['taxonomy_manifest'] = [
            'all' => array_map( [ Utils::class, 'get_taxonomy_name' ], Utils::get_taxonomy_roots( true ) ),
            'active' => Utils::get_active_taxonomies(),
        ];

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

        $shortcodes = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}wps_team ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore

        foreach( $shortcodes as &$shortcode ) {
            $shortcode['settings'] = Utils::maybe_json_decode( $shortcode['settings'] );
            $shortcode['settings'] = plugin()->api->validate_shortcode( $shortcode )->get_settings_value(); // Settings will be Sanitized & Validated by Shortcode_Editor class.
            $shortcode['settings'] = Utils::maybe_json_encode( $shortcode['settings'] ); // Encode settings to JSON format
        }

        return $shortcodes;
    }

    // phpcs:ignore WordPress.Security.NonceVerification
    public function init_zip_file() {

        // Init the zip archive
        $this->zip_instance = new \ZipArchive();

        // Init the zip file
        $this->zip_file = get_temp_dir() . 'wpspeedo/wps-team--export.zip';

        // Delete the zip file if it exists
        if ( file_exists( $this->zip_file ) ) wp_delete_file( $this->zip_file );

        // Create the zip file
        wp_mkdir_p( dirname( $this->zip_file ) );

        // Open the zip file
        $this->zip_instance->open( $this->zip_file, \ZipArchive::CREATE | \ZipArchive::OVERWRITE );
    }

    public function send_zip_file_data() {

        // Close the zip file
        $this->zip_instance->close();

        // Initialize WP_Filesystem
        $wp_filesystem = $this->get_wp_filesystem();

        // Check file existence and readability
        if ( ! $wp_filesystem->exists( $this->zip_file ) || ! $wp_filesystem->is_readable( $this->zip_file ) ) {
            wp_send_json_error( __( 'Export file not found or inaccessible', 'wps-team' ), 500 );
        }

        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $site_host = is_string( $site_host ) && $site_host !== '' ? $site_host : 'site';
        $site_slug = strtolower( str_replace( '.', '-', sanitize_title( $site_host ) ) );
        $timestamp = gmdate( 'Ymd-His' );
        $filename  = sanitize_file_name( sprintf( '%s-%s-%s.zip', $site_slug, 'wps-team', $timestamp ) );

        // Send the zip file
        header( 'Content-Description: File Transfer' );
        header( 'Content-Type: application/zip' );
        header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
        header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
        header( 'Pragma: public' );
        header( 'Content-Length: ' . $wp_filesystem->size( $this->zip_file ) );

        // Output the file contents
        echo $wp_filesystem->get_contents( $this->zip_file ); // phpcs:ignore WordPress.Security.EscapeOutput

        // Delete the zip file after sending
        if ( $wp_filesystem->exists( $this->zip_file ) ) {
            $wp_filesystem->delete( $this->zip_file );
        }

        exit;
    }

    public function ajax_import_data() {

        // allow for manage_options capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __('You do not have permission to perform this action', 'wps-team'), 403 );
        }

        // Check for required data
        // phpcs:ignore WordPress.Security.NonceVerification
        if ( empty($_FILES['import_file']) ) wp_send_json_error( __('No import file provided', 'wps-team'), 400 );

        // Save the uploaded file
        $this->upload_dir = $this->save_imported_file();

        // Check if the data.json file exists
        $json_import_file = $this->upload_dir . '/data.json';
        if ( ! file_exists( $json_import_file ) ) wp_send_json_error( __('Invalid file', 'wps-team'), 400 );

        // Read and decode JSON data safely.
        $json_raw = file_get_contents( $json_import_file );
        if ( $json_raw === false ) {
            wp_send_json_error( __('Unable to read import file', 'wps-team'), 400 );
        }

        $json_data = json_decode( $json_raw, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            wp_send_json_error( __('Invalid JSON format in import file', 'wps-team'), 400 );
        }

        // Check for valid JSON payload.
        if ( empty( $json_data ) ) wp_send_json_error( __('Invalid file content', 'wps-team'), 400 );
        
        $manifest = $this->read_import_manifest( $this->upload_dir );
        $same_site_import = $this->is_same_site_import( $manifest );
        $inference_stats = [ 'checked' => 0, 'matched' => 0 ];
        if ( ! $same_site_import ) {
            $same_site_import = $this->infer_same_site_from_posts( (array) ( $json_data['posts'] ?? [] ), $inference_stats );
        }

        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $conflict_policy = isset( $_REQUEST['conflict_policy'] ) ? sanitize_key( wp_unslash( $_REQUEST['conflict_policy'] ) ) : 'upsert';
        $conflict_policy = $this->normalize_conflict_policy( $conflict_policy );

        // phpcs:ignore WordPress.Security.NonceVerification
        $dry_run = ! empty( $_REQUEST['dry_run'] ) && wp_validate_boolean( wp_unslash( $_REQUEST['dry_run'] ) );

        if ( $dry_run ) {
            $preview = $this->build_import_preview( $json_data, $conflict_policy, $same_site_import );

            Utils::delete_directory_recursive( $this->upload_dir );
            wp_send_json_success( [
                'message' => __( 'Import preview generated', 'wps-team' ),
                'preview' => $preview,
            ], 200 );
        }

        // Initiate the Import Process
        $this->import__team_data( $json_data, $conflict_policy, $same_site_import );

        // Delete the uploaded files
        Utils::delete_directory_recursive( $this->upload_dir );

        // Send the success message
        wp_send_json_success( __('Data imported successfully', 'wps-team'), 200 );

    }

    public function import__team_data( $json_data, $conflict_policy = 'upsert', $same_site_import = false ) {

        // Import the General Settings Data
        if (!empty($json_data['settings'])) {
            plugin()->api->save_settings( $json_data['settings'] );
        }

        // Import the Settings Data
        if ( ! empty( $json_data['taxonomy_settings'] ) ) {
            $settings = plugin()->api->sanitize_taxonomy_settings( $json_data['taxonomy_settings'] );
            update_option( Utils::get_taxonomies_option_name(), $settings );
            $this->register_taxonomies();
        } elseif ( ! empty( $json_data['taxonomy_manifest'] ) ) {
            // Ensure taxonomy registration is refreshed even if settings were not included.
            $this->register_taxonomies();
        }

        // Import the Attachments Data
        if (!empty($json_data['attachments'])) {
            $this->import__attachments($json_data['attachments'], $conflict_policy, $same_site_import);
        }

        // Import the Terms Data
        if (!empty($json_data['terms'])) {
            $this->import__terms($json_data['terms'], $conflict_policy);
        }

        // Import the Posts Data
        if (!empty($json_data['posts'])) {
            $this->import__posts($json_data['posts'], $conflict_policy, $same_site_import);
        }

        // Import the Shortcodes Data
        if (!empty($json_data['shortcodes'])) {
            $this->import__shortcodes($json_data['shortcodes'], $conflict_policy);
        }

    }

    public function import__attachments($attachments, $conflict_policy = 'upsert', $same_site_import = false) {

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        wp_raise_memory_limit('image');

        foreach ($attachments as $attachment) {

            $file = $this->upload_dir . '/attachments/' . $attachment['file_name'];

            if ( !file_exists($file) ) continue;

            $source_attachment_id = isset( $attachment['ID'] ) ? (int) $attachment['ID'] : 0;

            // Reuse existing mapped attachment on same-site re-import.
            $existing_attachment_id = $this->get_imported_post_id( $source_attachment_id, $same_site_import, 'attachment' );
            if ( $existing_attachment_id && get_post_type( $existing_attachment_id ) === 'attachment' ) {
                if ( $conflict_policy === 'skip_existing' || $conflict_policy === 'upsert' ) continue;
            }

            if ( ! $same_site_import && $conflict_policy !== 'duplicate' ) {
                // Fallback: reuse attachment by file name and backfill import-id mapping.
                $existing_attachment_id = $this->get_attachment_id_by_file_name( $attachment['file_name'] );
                if ( $existing_attachment_id ) {
                    if ( $source_attachment_id ) {
                        update_post_meta( $existing_attachment_id, '_wps_team_import_id', $source_attachment_id );
                    }
                    continue;
                }
            }

            if ( $existing_attachment_id && $conflict_policy === 'skip_existing' ) {
                continue;
            }

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

            if ( $source_attachment_id ) {
                update_post_meta( $attachment_id, '_wps_team_import_id', $source_attachment_id );
            }

        }

    }

    public function import__terms($terms, $conflict_policy = 'upsert') {

        foreach ($terms as $term) {

            $term_data = array(
                'slug' => $term['slug'],
                'description' => $term['description'],
                'parent' => $term['parent']
            );
                
            $inserted_term = wp_insert_term($term['name'], $term['taxonomy'], $term_data);

            if (is_wp_error($inserted_term)) {
                // Fallback to existing term by taxonomy + slug.
                $existing_term = get_term_by( 'slug', $term['slug'], $term['taxonomy'] );
                if ( ! $existing_term || is_wp_error( $existing_term ) ) continue;
                if ( $conflict_policy === 'skip_existing' || $conflict_policy === 'upsert' ) {
                    $inserted_term = [ 'term_id' => (int) $existing_term->term_id ];
                } else {
                    continue;
                }
            }

            $import_term_id = isset( $term['term_id'] ) ? (int) $term['term_id'] : 0;
            if ( $import_term_id ) {
                update_term_meta($inserted_term['term_id'], '_wps_team_import_id', $import_term_id);
            }

        }

    }

    public function import__posts($posts, $conflict_policy = 'upsert', $same_site_import = false) {

        foreach ($posts as $post) {

            $source_post_id = (int) ( $post['ID'] ?? 0 );

            $raw_meta_input = isset( $post['meta_input'] ) && is_array( $post['meta_input'] ) ? $post['meta_input'] : [];
            $meta_input = [];

            // Keep all meta rows instead of flattening to the first item only.
            foreach ( $raw_meta_input as $meta_key => $meta_values ) {
                if ( ! is_array( $meta_values ) ) {
                    $meta_values = [ $meta_values ];
                }
                $meta_input[ $meta_key ] = array_values( $meta_values );
            }

            if ( $conflict_policy !== 'duplicate' ) {
                $meta_input['_wps_team_import_id'] = [ $source_post_id ];
            }

            if ( isset( $meta_input['_thumbnail_id'] ) ) {
                $mapped_thumbnail_ids = [];
                foreach ( (array) $meta_input['_thumbnail_id'] as $thumb_value ) {
                    $thumbnail_id = $this->get_imported_post_id( (int) $thumb_value, $same_site_import, 'attachment' );
                    if ( $thumbnail_id ) {
                        $mapped_thumbnail_ids[] = $thumbnail_id;
                    }
                }

                if ( ! empty( $mapped_thumbnail_ids ) ) {
                    $meta_input['_thumbnail_id'] = $mapped_thumbnail_ids;
                } else {
                    unset( $meta_input['_thumbnail_id'] );
                }
            }

            if ( isset( $meta_input['_gallery'] ) && ! empty( $meta_input['_gallery'] ) ) {
                foreach ( $meta_input['_gallery'] as $meta_row_key => $gallery_value ) {
                    $mapped_gallery_ids = [];

                    if ( is_array( $gallery_value ) ) {
                        $gallery_ids = $gallery_value;
                    } elseif ( is_string( $gallery_value ) && str_contains( $gallery_value, ',' ) ) {
                        $gallery_ids = array_filter( array_map( 'trim', explode( ',', $gallery_value ) ) );
                    } else {
                        $gallery_ids = [ $gallery_value ];
                    }

                    foreach ( $gallery_ids as $gallery_id ) {
                        $mapped_id = $this->get_imported_post_id( (int) $gallery_id, $same_site_import, 'attachment' );
                        if ( $mapped_id ) {
                            $mapped_gallery_ids[] = $mapped_id;
                        }
                    }

                    if ( empty( $mapped_gallery_ids ) ) {
                        unset( $meta_input['_gallery'][ $meta_row_key ] );
                    } elseif ( is_array( $gallery_value ) ) {
                        $meta_input['_gallery'][ $meta_row_key ] = $mapped_gallery_ids;
                    } elseif ( is_string( $gallery_value ) && str_contains( $gallery_value, ',' ) ) {
                        $meta_input['_gallery'][ $meta_row_key ] = implode( ',', $mapped_gallery_ids );
                    } else {
                        $meta_input['_gallery'][ $meta_row_key ] = $mapped_gallery_ids[0];
                    }
                }

                if ( empty( $meta_input['_gallery'] ) ) {
                    unset( $meta_input['_gallery'] );
                }
            }

            unset($post['ID'], $post['meta_input']);

            foreach ( (array) ( $post['tax_input'] ?? [] ) as $taxonomy => $terms ) {
                foreach ($terms as $key => $term) {
                    $term_id = (int) $this->get_imported_term_id($term, $taxonomy);
                    if ($term_id) {
                        $terms[$key] = $term_id;
                    } else {
                        unset($terms[$key]);
                    }
                }
                $post['tax_input'][$taxonomy] = $terms;
            }

            $existing_post_id = $this->get_imported_post_id( $source_post_id, $same_site_import, Utils::post_type_name() );
            if ( $existing_post_id ) {
                if ( $conflict_policy === 'skip_existing' ) {
                    continue;
                } elseif ( $conflict_policy === 'duplicate' ) {
                    $inserted_post_id = wp_insert_post( $post, true );
                } else {
                    $post['ID'] = (int) $existing_post_id;
                    $inserted_post_id = wp_update_post( $post, true );
                }
            } else {
                $inserted_post_id = wp_insert_post( $post, true );
            }

            if ( is_wp_error( $inserted_post_id ) || empty( $inserted_post_id ) ) continue;

            // Rewrite all post meta to preserve multiple meta rows.
            foreach ( $meta_input as $meta_key => $meta_values ) {
                delete_post_meta( $inserted_post_id, $meta_key );
                foreach ( (array) $meta_values as $meta_value ) {
                    add_post_meta( $inserted_post_id, $meta_key, $meta_value, false );
                }
            }
        }
        
    }

    public function get_imported_term_id( $term_id, $taxonomy = '' ) {

        global $wpdb;

        $term_id = (int) $term_id;

        if ( ! $term_id ) return false;

        $mapped_term_id = $wpdb->get_var( $wpdb->prepare( "SELECT term_id FROM $wpdb->termmeta WHERE meta_key = '_wps_team_import_id' AND meta_value = %d LIMIT 1", $term_id ) ); // phpcs:ignore

        if ( $mapped_term_id ) return (int) $mapped_term_id;

        if ( ! empty( $taxonomy ) ) {
            $source_term = get_term( $term_id );
            if ( $source_term && ! is_wp_error( $source_term ) && ! empty( $source_term->slug ) ) {
                $existing_term = get_term_by( 'slug', $source_term->slug, $taxonomy );
                if ( $existing_term && ! is_wp_error( $existing_term ) ) {
                    return (int) $existing_term->term_id;
                }
            }
        }

        return false;
    }

    public function get_imported_post_id( $post_id, $allow_direct_match = false, $expected_post_type = '' ) {

        global $wpdb;

        $source_post_id = (int) $post_id;
        $post_id = $source_post_id;

        if ( ! $post_id ) return false;

        $post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wps_team_import_id' AND meta_value = %d LIMIT 1", $post_id ) ); // phpcs:ignore

        if ( $post_id ) return (int) $post_id;

        if ( $allow_direct_match ) {
            $direct_post = get_post( $source_post_id );
            if ( $direct_post && ! is_wp_error( $direct_post ) ) {
                if ( empty( $expected_post_type ) || $direct_post->post_type === $expected_post_type ) {
                    return (int) $direct_post->ID;
                }
            }
        }

        return false;
    }

    public function get_attachment_id_by_file_name( string $file_name ) {
        global $wpdb;

        $file_name = sanitize_file_name( $file_name );
        if ( $file_name === '' ) return 0;

        $like_pattern = '%' . $wpdb->esc_like( '/' . $file_name );

        $attachment_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
                $like_pattern
            )
        );

        return $attachment_id > 0 ? $attachment_id : 0;
    }

    private function normalize_conflict_policy( string $policy ) {
        $allowed = [ 'upsert', 'skip_existing', 'duplicate' ];
        return in_array( $policy, $allowed, true ) ? $policy : 'upsert';
    }

    public function import__shortcodes($shortcodes, $conflict_policy = 'upsert') {

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
                "updated_at"    => ! empty( $shortcode['updated_at'] ) ? $shortcode['updated_at'] : $shortcode['created_at'],
            );

            // Upsert shortcode to avoid duplicates on same-site re-import.
            $existing_shortcode_id = $this->get_existing_shortcode_id( $shortcode, $data['name'] );

            if ( $existing_shortcode_id ) {
                if ( $conflict_policy === 'skip_existing' ) {
                    continue;
                }

                if ( $conflict_policy === 'duplicate' ) {
                    $wpdb->insert( "{$wpdb->prefix}wps_team", $data, plugin()->api->db_columns_format() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                } else {
                    $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                        "{$wpdb->prefix}wps_team",
                        $data,
                        [ 'id' => (int) $existing_shortcode_id ],
                        plugin()->api->db_columns_format(),
                        [ '%d' ]
                    );
                }
            } else {
                $wpdb->insert( "{$wpdb->prefix}wps_team", $data, plugin()->api->db_columns_format() ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            }
        }

    }

    private function get_existing_shortcode_id( array $shortcode, string $normalized_name ) {
        global $wpdb;

        $table = "{$wpdb->prefix}wps_team";

        $source_id = isset( $shortcode['id'] ) ? (int) $shortcode['id'] : 0;
        if ( $source_id > 0 ) {
            $row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->prepare( "SELECT id, name FROM {$table} WHERE id = %d LIMIT 1", $source_id ),
                ARRAY_A
            );

            if ( ! empty( $row ) && isset( $row['name'] ) && trim( (string) $row['name'] ) === trim( $normalized_name ) ) {
                return (int) $row['id'];
            }
        }

        $created_at = isset( $shortcode['created_at'] ) ? sanitize_text_field( (string) $shortcode['created_at'] ) : '';
        if ( $created_at !== '' && $normalized_name !== '' ) {
            $matched_id = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE name = %s AND created_at = %s LIMIT 1",
                    $normalized_name,
                    $created_at
                )
            );

            if ( $matched_id > 0 ) return $matched_id;
        }

        return 0;
    }

    private function build_import_preview( array $json_data, string $conflict_policy, $same_site_import = false ) {
        $summary = [
            'conflict_policy' => $conflict_policy,
            'posts' => [ 'total' => 0, 'existing' => 0, 'new' => 0 ],
            'shortcodes' => [ 'total' => 0, 'existing' => 0, 'new' => 0 ],
            'terms' => [ 'total' => 0, 'existing' => 0, 'new' => 0 ],
            'attachments' => [ 'total' => 0, 'existing' => 0, 'new' => 0 ],
        ];

        foreach ( (array) ( $json_data['posts'] ?? [] ) as $post ) {
            $summary['posts']['total']++;
            $source_post_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
            if ( $source_post_id && $this->get_imported_post_id( $source_post_id, $same_site_import, Utils::post_type_name() ) ) {
                $summary['posts']['existing']++;
            } else {
                $summary['posts']['new']++;
            }
        }

        foreach ( (array) ( $json_data['shortcodes'] ?? [] ) as $shortcode ) {
            $summary['shortcodes']['total']++;
            $name = isset( $shortcode['name'] ) ? sanitize_text_field( (string) $shortcode['name'] ) : '';
            if ( $this->get_existing_shortcode_id( (array) $shortcode, $name ) ) {
                $summary['shortcodes']['existing']++;
            } else {
                $summary['shortcodes']['new']++;
            }
        }

        foreach ( (array) ( $json_data['terms'] ?? [] ) as $term ) {
            $summary['terms']['total']++;
            $term_id = isset( $term['term_id'] ) ? (int) $term['term_id'] : 0;
            $taxonomy = isset( $term['taxonomy'] ) ? sanitize_key( (string) $term['taxonomy'] ) : '';
            if ( $term_id && $this->get_imported_term_id( $term_id, $taxonomy ) ) {
                $summary['terms']['existing']++;
            } else {
                $summary['terms']['new']++;
            }
        }

        foreach ( (array) ( $json_data['attachments'] ?? [] ) as $attachment ) {
            $summary['attachments']['total']++;
            $source_attachment_id = isset( $attachment['ID'] ) ? (int) $attachment['ID'] : 0;
            $file_name = isset( $attachment['file_name'] ) ? (string) $attachment['file_name'] : '';

            $has_mapped_attachment = ( $source_attachment_id && $this->get_imported_post_id( $source_attachment_id, $same_site_import, 'attachment' ) );
            $has_name_matched_attachment = ( ! $same_site_import && $file_name !== '' && $this->get_attachment_id_by_file_name( $file_name ) );

            if ( $has_mapped_attachment || $has_name_matched_attachment ) {
                $summary['attachments']['existing']++;
            } else {
                $summary['attachments']['new']++;
            }
        }

        return $summary;
    }

    private function read_import_manifest( string $upload_dir ) {
        $manifest_file = trailingslashit( $upload_dir ) . 'manifest.json';
        if ( ! file_exists( $manifest_file ) ) return [];

        $manifest_raw = file_get_contents( $manifest_file );
        if ( $manifest_raw === false ) return [];

        $manifest = json_decode( $manifest_raw, true );
        return is_array( $manifest ) ? $manifest : [];
    }

    private function is_same_site_import( array $manifest ) {
        $source_site = isset( $manifest['site_url'] ) ? (string) $manifest['site_url'] : '';
        if ( $source_site === '' ) return false;

        $source_site = strtolower( untrailingslashit( $source_site ) );
        $current_site = strtolower( untrailingslashit( home_url() ) );

        // Fast path for exact normalized URL match.
        if ( $source_site === $current_site ) return true;

        // Fallback: tolerate scheme and trivial URL-format differences.
        $source_parts = wp_parse_url( $source_site );
        $current_parts = wp_parse_url( $current_site );

        if ( ! is_array( $source_parts ) || ! is_array( $current_parts ) ) return false;

        $source_host = strtolower( (string) ( $source_parts['host'] ?? '' ) );
        $current_host = strtolower( (string) ( $current_parts['host'] ?? '' ) );

        if ( $source_host === '' || $current_host === '' || $source_host !== $current_host ) {
            return false;
        }

        return true;
    }

    private function infer_same_site_from_posts( array $posts, &$stats = [] ) {
        if ( empty( $posts ) ) {
            $stats = [ 'checked' => 0, 'matched' => 0 ];
            return false;
        }

        $checked = 0;
        $matched = 0;

        foreach ( $posts as $post ) {
            $source_post_id = isset( $post['ID'] ) ? (int) $post['ID'] : 0;
            if ( $source_post_id < 1 ) continue;

            $checked++;
            $existing_post = get_post( $source_post_id );
            if ( $existing_post && ! is_wp_error( $existing_post ) && $existing_post->post_type === Utils::post_type_name() ) {
                $matched++;
            }

            // Sampling is enough for a reliable signal.
            if ( $checked >= 25 ) break;
        }

        if ( $checked === 0 ) {
            $stats = [ 'checked' => 0, 'matched' => 0 ];
            return false;
        }

        $stats = [ 'checked' => $checked, 'matched' => $matched ];

        return ( $matched / $checked ) >= 0.8;
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
        
        // phpcs:ignore WordPress.Security.NonceVerification
        $import_file = ! empty($_FILES['import_file']) && is_array($_FILES['import_file']) 
            // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
            ? array_map( fn($v) => is_array($v) ? $v : sanitize_text_field(wp_unslash($v)), $_FILES['import_file'] )
            : wp_send_json_error( __( 'No file uploaded', 'wps-team' ), 400 );

        $file_tmp_path  = $import_file['tmp_name'];
        $file_name      = sanitize_file_name( $import_file['name'] );
        $file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

        if ( $file_extension !== 'zip' ) {
            wp_send_json_error( __( 'Invalid file type', 'wps-team' ), 400 );
        }

        $upload_file_dir = get_temp_dir() . 'wpspeedo/wps-team';

        // Initialize WP_Filesystem
        $wp_filesystem = $this->get_wp_filesystem();

        // Delete existing directory
        if ( $wp_filesystem->is_dir( $upload_file_dir ) ) {
            Utils::delete_directory_recursive( $upload_file_dir );
        }

        // Create upload directory
        $wp_filesystem->mkdir( $upload_file_dir );

        $dest_file_path = $upload_file_dir . '/' . $file_name;

        // Copy uploaded file using WP_Filesystem
        if ( ! $wp_filesystem->copy( $file_tmp_path, $dest_file_path, true, FS_CHMOD_FILE ) ) {
            wp_send_json_error( __( 'File upload failed', 'wps-team' ), 400 );
        }

        // Extract zip
        $zip = new \ZipArchive();
        if ( $zip->open( $dest_file_path ) === true ) {
            $normalized_base = wp_normalize_path( trailingslashit( $upload_file_dir ) );

            // Prevent zip slip by validating every archive entry path.
            for ( $i = 0; $i < $zip->numFiles; $i++ ) {
                $entry = $zip->statIndex( $i );
                if ( ! is_array( $entry ) || empty( $entry['name'] ) ) {
                    $zip->close();
                    wp_send_json_error( __( 'Invalid archive entry', 'wps-team' ), 400 );
                }

                $entry_name = str_replace( '\\', '/', (string) $entry['name'] );

                // Disallow absolute paths and traversal segments.
                if (
                    strpos( $entry_name, '../' ) !== false ||
                    strpos( $entry_name, '..\\' ) !== false ||
                    preg_match( '#^(?:/|[a-zA-Z]:[/\\\\])#', $entry_name )
                ) {
                    $zip->close();
                    wp_send_json_error( __( 'Invalid archive path', 'wps-team' ), 400 );
                }

                $target_entry = wp_normalize_path( $normalized_base . ltrim( $entry_name, '/' ) );
                if ( strpos( $target_entry, $normalized_base ) !== 0 ) {
                    $zip->close();
                    wp_send_json_error( __( 'Invalid archive path', 'wps-team' ), 400 );
                }
            }

            $zip->extractTo( $upload_file_dir );
            $zip->close();

            // Delete the uploaded zip
            if ( $wp_filesystem->exists( $dest_file_path ) ) {
                $wp_filesystem->delete( $dest_file_path );
            }

        } else {
            wp_send_json_error( __( 'File upload failed', 'wps-team' ), 400 );
        }

        return $upload_file_dir;
    }

}