<?php

namespace WPSpeedo_Team;

use WP_Error;

if ( ! defined('ABSPATH') ) exit;

class Bulk_Import_Manager {

    use AJAX_Handler;

    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_bulk_import_handler';

    public function __construct() {

        $this->set_ajax_scope_hooks();

    }

    public function ajax_parse_csv() {

        $rows = $this->get_file_rows();

        if ( is_wp_error($rows) ) wp_send_json_error( $rows->get_error_message(), 400 );

        // Prepare for heavy operation
        Utils::prepare_heavy_operation();
        
        set_transient( 'wps_team_csv_rows', $rows, DAY_IN_SECONDS );

        $allowed = array( 'first_name', 'last_name', 'designation', 'email', 'company' );

        $rows = array_map(function( $row ) use ($allowed) {
            return array_intersect_key( $row, array_flip($allowed) );
        }, $rows );
        
        wp_send_json_success( $rows );

    }

    public function ajax_import_csv() {

        // phpcs:ignore WordPress.Security.NonceVerification
        $index = isset( $_REQUEST['index'] ) ? (int) $_REQUEST['index'] : null;

        if ( ! is_numeric($index) ) wp_send_json_error( _x( 'Row not found', 'Bulk Import', 'wps-team' ), 400 );

        // Prepare for heavy operation
        Utils::prepare_heavy_operation();

        $rows = get_transient( 'wps_team_csv_rows', [] );

        $row = $this->map_row_data( $rows[$index] );

        if ( empty($row['first_name']) || empty($row['last_name']) ) wp_send_json_error( _x( 'Row name not found', 'Bulk Import', 'wps-team' ), 400 );

        $item = [
            'post_title'    => Utils::get_title_from_name_fields( $row['first_name'], $row['last_name'] ),
            'post_content'  => empty($row['description']) ? '' : $row['description'],
            'post_status'   => 'publish',
            'post_type'     => Utils::post_type_name(),
            'tax_input'     => $this->get_row_tax_input( $row ),
            'meta_input'    => $this->get_row_meta_input( $row )
        ];

        $post_id = wp_insert_post( $item );

        if ( is_wp_error( $post_id ) ) wp_send_json_error( _x( "Couldn't insert post", 'Bulk Import', 'wps-team' ), 400 );

        wp_send_json_success();

    }

    public function map_row_data( $data ) {
        
        // Taxonomies
        $data['groups']         = $this->parse_to_array( $data['groups'] );
        $data['locations']      = $this->parse_to_array( $data['locations'] );
        $data['languages']      = $this->parse_to_array( $data['languages'] );
        $data['specialties']    = $this->parse_to_array( $data['specialties'] );
        $data['genders']        = $this->parse_to_array( $data['genders'] );
        $data['extra_one']      = $this->parse_to_array( $data['extra_one'] );
        $data['extra_two']      = $this->parse_to_array( $data['extra_two'] );
        $data['extra_three']    = $this->parse_to_array( $data['extra_three'] );
        $data['extra_four']     = $this->parse_to_array( $data['extra_four'] );
        $data['extra_five']     = $this->parse_to_array( $data['extra_five'] );

        // Skills
        $data['skills']         = $this->parse_to_array_deep( $data['skills'], ['skill_name', 'skill_val'] );

        // Social Links
        $data['social_links']   = $this->parse_to_array_deep( $data['social_links'], ['social_icon', 'social_link'] );
        $data['social_links']   = array_map( array( $this, 'build_icon_data' ), $data['social_links'] );

        return $data;

    }

    public function build_icon_data( $icon ) {

        $_icon = $icon['social_icon'];
        
        if ( strpos( $_icon, 'far' ) !== false ) {
            $library = 'fa-regular';
        } else if ( strpos( $_icon, 'fas' ) !== false ) {
            $library = 'fa-solid';
        } else {
            $library = 'fa-brands';
        }

        $icon['social_icon'] = [
            'icon' => $_icon,
            'library' => $library
        ];

        return $icon;

    }

    public function get_row_tax_input( $row ) {

        $tax_input = [];

        $tax_group       = Utils::get_taxonomy_name( 'group' );
        $tax_location    = Utils::get_taxonomy_name( 'location' );
        $tax_language    = Utils::get_taxonomy_name( 'language' );
        $tax_specialty   = Utils::get_taxonomy_name( 'specialty' );
        $tax_gender      = Utils::get_taxonomy_name( 'gender' );
        $tax_extra_one   = Utils::get_taxonomy_name( 'extra-one' );
        $tax_extra_two   = Utils::get_taxonomy_name( 'extra-two' );
        $tax_extra_three = Utils::get_taxonomy_name( 'extra-three' );
        $tax_extra_four  = Utils::get_taxonomy_name( 'extra-four' );
        $tax_extra_five  = Utils::get_taxonomy_name( 'extra-five' );

        if ( ! empty($row['groups']) )       $tax_input[ $tax_group ]        = $this->get_row_term_ids( $row['groups'], $tax_group );
        if ( ! empty($row['locations']) )    $tax_input[ $tax_location ]     = $this->get_row_term_ids( $row['locations'], $tax_location );
        if ( ! empty($row['languages']) )    $tax_input[ $tax_language ]     = $this->get_row_term_ids( $row['languages'], $tax_language );
        if ( ! empty($row['specialties']) )  $tax_input[ $tax_specialty ]    = $this->get_row_term_ids( $row['specialties'], $tax_specialty );
        if ( ! empty($row['genders']) )      $tax_input[ $tax_gender ]       = $this->get_row_term_ids( $row['genders'], $tax_gender );
        if ( ! empty($row['extra_one']) )    $tax_input[ $tax_extra_one ]    = $this->get_row_term_ids( $row['extra_one'], $tax_extra_one );
        if ( ! empty($row['extra_two']) )    $tax_input[ $tax_extra_two ]    = $this->get_row_term_ids( $row['extra_two'], $tax_extra_two );
        if ( ! empty($row['extra_three']) )  $tax_input[ $tax_extra_three ]  = $this->get_row_term_ids( $row['extra_three'], $tax_extra_three );
        if ( ! empty($row['extra_four']) )   $tax_input[ $tax_extra_four ]   = $this->get_row_term_ids( $row['extra_four'], $tax_extra_four );
        if ( ! empty($row['extra_five']) )   $tax_input[ $tax_extra_five ]   = $this->get_row_term_ids( $row['extra_five'], $tax_extra_five );

        return $tax_input;

    }

    public function get_row_term_ids( $terms, $taxonomy ) {

        $term_ids = [];

        foreach ( $terms as $term ) {

            $_term = get_term_by( 'name', $term, $taxonomy );

            if ( $_term ) {
                $term_ids[] = $_term->term_id;
            } else {
                $response = wp_insert_term( $term, $taxonomy );
                if ( ! is_wp_error($response) ) {
                    $term_ids[] = $response['term_id'];
                }
            }

        }

        return array_values( array_unique($term_ids) );

    }

    public function empty( $data ) {

        if ( empty( $data ) ) return true;
        if ( is_array( $data ) ) return false;

        $str = trim( (string) $data );
        if ( $str === '' ) return true;

        if ( strtolower( $str ) === 'na' ) return true;

        return false;
    }

    public function get_row_meta_input( $row ) {

        $meta_input = [];

        if ( ! $this->empty($row['first_name']) )   $meta_input['_first_name']      = sanitize_text_field( $row['first_name'] );
        if ( ! $this->empty($row['last_name']) )    $meta_input['_last_name']       = sanitize_text_field( $row['last_name'] );
        if ( ! $this->empty($row['designation']) )  $meta_input['_designation']     = sanitize_text_field( $row['designation'] );
        if ( ! $this->empty($row['email']) )        $meta_input['_email']           = sanitize_email( $row['email'] );
        if ( ! $this->empty($row['mobile']) )       $meta_input['_mobile']          = sanitize_text_field( $row['mobile'] );
        if ( ! $this->empty($row['telephone']) )    $meta_input['_telephone']       = sanitize_text_field( $row['telephone'] );
        // if ( ! $this->empty($row['fax']) )          $meta_input['_fax']             = sanitize_text_field( $row['fax'] );
        if ( ! $this->empty($row['experience']) )   $meta_input['_experience']      = sanitize_text_field( $row['experience'] );
        if ( ! $this->empty($row['website']) )      $meta_input['_website']         = esc_url_raw( $row['website'] );
        if ( ! $this->empty($row['company']) )      $meta_input['_company']         = sanitize_text_field( $row['company'] );
        if ( ! $this->empty($row['address']) )      $meta_input['_address']         = sanitize_text_field( $row['address'] );
        if ( ! $this->empty($row['ribbon']) )       $meta_input['_ribbon']          = sanitize_text_field( $row['ribbon'] );
        if ( ! $this->empty($row['link_one']) )     $meta_input['_link_1']          = esc_url_raw( $row['link_one'] );
        if ( ! $this->empty($row['link_two']) )     $meta_input['_link_2']          = esc_url_raw( $row['link_two'] );
        if ( ! $this->empty($row['color']) )        $meta_input['_color']           = sanitize_text_field( $row['color'] );
        if ( ! $this->empty($row['education']) )    $meta_input['_education']       = wp_kses_post( $row['education'] );
        if ( ! $this->empty($row['thumbnail']) )    $meta_input['_thumbnail_id']    = (int) $this->get_thumbnail_id( $row['thumbnail'] );
        if ( ! $this->empty($row['gallery']) )      $meta_input['_gallery']         = (array) $this->get_gallery_ids( $row['gallery'] );
        if ( ! $this->empty($row['social_links']) ) $meta_input['_social_links']    = (array) $row['social_links'];
        if ( ! $this->empty($row['skills']) )       $meta_input['_skills']          = (array) $row['skills'];

        return $meta_input;

    }

    public function get_file_rows() {
        $items = [];

        // phpcs:ignore WordPress.Security.NonceVerification
        if ( empty( $file = $_FILES['file'] ) || ! is_array( $file ) ) {
            return new WP_Error( 'no_file', __( 'No file uploaded', 'wps-team' ) );
        }

        $import_file = array_map( function( $value ) {
            return is_array( $value ) ? $value : sanitize_text_field( wp_unslash( $value ) );
        }, $file );

        if ( empty( $import_file['name'] ) || empty( $import_file['tmp_name'] ) ) {
            return new WP_Error( 'invalid_file', __( 'Invalid file upload', 'wps-team' ) );
        }

        $extension = strtolower( pathinfo( $import_file['name'], PATHINFO_EXTENSION ) );
        if ( $extension !== 'csv' ) {
            return new WP_Error( 'invalid_file', __( 'Only CSV files are allowed', 'wps-team' ) );
        }

        // Use SplFileObject for proper CSV parsing (handles quotes & newlines)
        $file = new \SplFileObject( $import_file['tmp_name'] );
        $file->setFlags( \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY );
        $file->setCsvControl( ',', '"', '\\' );

        // Header row
        $header_row = $file->fgetcsv();
        $header_row = array_map( [ $this, 'sanitize_header_row' ], $header_row );

        if ( count( $header_row ) !== 30 ) {
            return new WP_Error( 'invalid_file', _x( 'Invalid File Content (header)', 'Bulk Import', 'wps-team' ) );
        }

        foreach ( $file as $index => $csv_data ) {

            // Skip header row
            if ( $index === 0 ) continue;

            // skip empty rows
            if ( empty( $csv_data ) || ( count( $csv_data ) === 1 && $csv_data[0] === null ) ) continue;

            // Pad/trim to 30 columns
            $csv_data = array_pad( $csv_data, 30, '' );
            $csv_data = array_slice( $csv_data, 0, 30 );

            $csv_data = array_map( function( $column ) {
                return _wp_json_convert_string( trim( (string) $column ) );
            }, $csv_data );

            $items[] = array_combine( $header_row, $csv_data );
        }

        return $items;
    }

    public function sanitize_header_row($string) {
        $string = str_replace( [' ', '-'], '_', $string); // Replaces all spaces with hyphens.
        $string = strtolower( _wp_json_convert_string( trim( $string ) ) );
        return preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
    }

    public function parse_to_array( $data ) {
        if ( $this->empty($data) ) return [];
        return array_map( 'trim', explode( ',', str_replace(', ', ',', $data) ) );
    }

    public function parse_to_array_deep( $data, $columns ) {

        if ( $this->empty($data) ) return [];

        $data = $this->parse_to_array( $data );

        return array_map( function( $single_data ) use ( $columns ) {
            $single_data = array_map( 'trim', explode( '=>', $single_data ) );
            return array_combine( $columns, $single_data );
        }, $data );

    }

    public function get_thumbnail_id( $thumbnail ) {

        // 1. If it's already an ID (numeric string or int)
        if ( is_numeric( $thumbnail ) && intval( $thumbnail ) > 0 ) {
            return intval( $thumbnail );
        }

        // 2. Normalize: trim + remove spaces
        $thumbnail = trim( $thumbnail );
        if ( $thumbnail === '' ) return '';

        // 3. CASE: Local wp-content path (e.g. /wp-content/uploads/file.jpg)
        if ( strpos( $thumbnail, '/wp-content/' ) === 0 ) {

            // Build absolute path
            $absolute_path = ABSPATH . ltrim( $thumbnail, '/' );

            if ( file_exists( $absolute_path ) ) {
                // Convert path to URL
                $upload_dir = wp_upload_dir();
                $url = str_replace( realpath( $upload_dir['basedir'] ), $upload_dir['baseurl'], realpath( $absolute_path ) );

                // Get attachment ID
                $id = attachment_url_to_postid( $url );

                if ( $id ) return $id;
            }

            // If file exists but no attachment found
            return '';
        }

        // 4. CASE: Valid URL (starts with http or https)
        if ( filter_var( $thumbnail, FILTER_VALIDATE_URL ) ) {

            $id = media_sideload_image( $thumbnail, 0, null, 'id' );

            if ( is_wp_error( $id ) ) {
                return '';
            }

            return $id;
        }

        // 5. Anything else is invalid
        return '';
    }

    public function get_gallery_ids( $thumbnail_ids ) {

        // Convert to array (your existing helper)
        $thumbnail_ids = $this->parse_to_array( $thumbnail_ids );
        $thumbnail_ids = array_filter( $thumbnail_ids );

        if ( empty( $thumbnail_ids ) ) {
            return [];
        }

        $ids = [];

        foreach ( $thumbnail_ids as $thumb ) {

            $id = $this->get_thumbnail_id( $thumb ); // Reuse your improved function

            if ( ! empty( $id ) && is_numeric( $id ) ) {
                $ids[] = intval( $id );
            }
        }

        return $ids;
    }

}