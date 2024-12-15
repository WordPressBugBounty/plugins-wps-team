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
        
        set_transient( 'wps_team_csv_rows', $rows, DAY_IN_SECONDS );

        $allowed = array( 'name', 'designation', 'email', 'company' );

        $rows = array_map(function( $row ) use ($allowed) {
            return array_intersect_key( $row, array_flip($allowed) );
        }, $rows );

        wp_send_json_success( $rows );

    }

    public function ajax_import_csv() {

        $index = isset( $_REQUEST['index'] ) ? (int) $_REQUEST['index'] : null;

        if ( ! is_numeric($index) ) wp_send_json_error( _x( 'Row not found', 'Bulk Import', 'wpspeedo-team' ), 400 );

        $rows = get_transient( 'wps_team_csv_rows', [] );

        $row = $this->map_row_data( $rows[$index] );

        if ( empty($row['name']) ) wp_send_json_error( _x( 'Row name not found', 'Bulk Import', 'wpspeedo-team' ), 400 );

        $item = [
            'post_title'    => $row['name'],
            'post_content'  => empty($row['description']) ? '' : $row['description'],
            'post_status'   => 'publish',
            'post_type'     => Utils::post_type_name(),
            'tax_input'     => $this->get_row_tax_input( $row ),
            'meta_input'    => $this->get_row_meta_input( $row )
        ];

        $post_id = wp_insert_post( $item );

        if ( is_wp_error( $post_id ) ) wp_send_json_error( _x( "Couldn't insert post", 'Bulk Import', 'wpspeedo-team' ), 400 );

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

        if ( !empty($row['groups']) )       $tax_input[ $tax_group ]        = $this->get_row_term_ids( $row['groups'], $tax_group );
        if ( !empty($row['locations']) )    $tax_input[ $tax_location ]     = $this->get_row_term_ids( $row['locations'], $tax_location );
        if ( !empty($row['languages']) )    $tax_input[ $tax_language ]     = $this->get_row_term_ids( $row['languages'], $tax_language );
        if ( !empty($row['specialties']) )  $tax_input[ $tax_specialty ]    = $this->get_row_term_ids( $row['specialties'], $tax_specialty );
        if ( !empty($row['genders']) )      $tax_input[ $tax_gender ]       = $this->get_row_term_ids( $row['genders'], $tax_gender );
        if ( !empty($row['extra_one']) )    $tax_input[ $tax_extra_one ]    = $this->get_row_term_ids( $row['extra_one'], $tax_extra_one );
        if ( !empty($row['extra_two']) )    $tax_input[ $tax_extra_two ]    = $this->get_row_term_ids( $row['extra_two'], $tax_extra_two );
        if ( !empty($row['extra_three']) )  $tax_input[ $tax_extra_three ]  = $this->get_row_term_ids( $row['extra_three'], $tax_extra_three );
        if ( !empty($row['extra_four']) )   $tax_input[ $tax_extra_four ]   = $this->get_row_term_ids( $row['extra_four'], $tax_extra_four );
        if ( !empty($row['extra_five']) )   $tax_input[ $tax_extra_five ]   = $this->get_row_term_ids( $row['extra_five'], $tax_extra_five );

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

    public function get_row_meta_input( $row ) {

        $meta_input = [];

        if ( !empty($row['experience']) )   $meta_input['_experience']      = sanitize_text_field( $row['experience'] );
        if ( !empty($row['company']) )      $meta_input['_company']         = sanitize_text_field( $row['company'] );
        if ( !empty($row['designation']) )  $meta_input['_designation']     = sanitize_text_field( $row['designation'] );
        if ( !empty($row['telephone']) )    $meta_input['_telephone']       = sanitize_text_field( $row['telephone'] );
        if ( !empty($row['email']) )        $meta_input['_email']           = sanitize_email( $row['email'] );
        if ( !empty($row['website']) )      $meta_input['_website']         = esc_url_raw( $row['website'] );
        if ( !empty($row['ribbon']) )       $meta_input['_ribbon']          = sanitize_text_field( $row['ribbon'] );
        if ( !empty($row['mobile']) )       $meta_input['_mobile']          = sanitize_text_field( $row['mobile'] );
        if ( !empty($row['color']) )        $meta_input['_color']           = sanitize_text_field( $row['color'] );
        if ( !empty($row['education']) )    $meta_input['_education']       = wp_kses_post( $row['education'] );
        if ( !empty($row['link_one']) )     $meta_input['_link_1']          = esc_url_raw( $row['link_one'] );
        if ( !empty($row['link_two']) )     $meta_input['_link_2']          = esc_url_raw( $row['link_two'] );
        if ( !empty($row['skills']) )       $meta_input['_skills']          = (array) $row['skills'];
        if ( !empty($row['social_links']) ) $meta_input['_social_links']    = (array) $row['social_links'];
        if ( !empty($row['thumbnail']) )    $meta_input['_thumbnail_id']    = (int) $this->get_thumbnail_id( $row['thumbnail'] );
        if ( !empty($row['gallery']) )      $meta_input['_gallery']         = (array) $this->get_gallery_ids( $row['gallery'] );

        $meta_input['_wps_member_meta_keys'] = Utils::get_meta_field_keys();

        return $meta_input;

    }

    public function get_file_rows() {

        $items = [];

        // File extension
        $extension = pathinfo( $_FILES['file']['name'], PATHINFO_EXTENSION );

        // If file extension is 'csv'
        if ( !empty($_FILES['file']['name']) && $extension == 'csv' ) {

            // Open file in read mode
            $csv_file = fopen($_FILES['file']['tmp_name'], 'r');

            // Header Row
            $header_row = fgetcsv( $csv_file );

            $header_row = array_map( [ $this, 'sanitize_header_row' ], $header_row );

            if ( count($header_row) !== 28 ) return new WP_Error('invalid_file', _x('Invalid File Content', 'Bulk Import', 'wpspeedo-team') );
    
            // Read file
            while ( ( $csv_data = fgetcsv($csv_file) ) !== false ) {

                if ( count($csv_data) !== 28 ) return new WP_Error('invalid_file', _x('Invalid File Content', 'Bulk Import', 'wpspeedo-team') );

                $csv_data = array_map( function( $column ) {
                    return _wp_json_convert_string( trim($column) );
                }, $csv_data );
    
                $items[] = array_combine( $header_row, $csv_data );
    
            }

        }

        return $items;
    }

    public function sanitize_header_row($string) {
        $string = str_replace( [' ', '-'], '_', $string); // Replaces all spaces with hyphens.
        $string = strtolower( _wp_json_convert_string( trim( $string ) ) );
        return preg_replace('/[^A-Za-z0-9\_]/', '', $string); // Removes special chars.
    }

    public function parse_to_array( $array ) {
        return array_map( 'trim', explode( ',', str_replace(', ', ',', $array) ) );
    }

    public function parse_to_array_deep( $array, $columns ) {

        if ( empty($array) ) return [];

        $array = $this->parse_to_array( $array );

        return array_map( function( $data ) use ( $columns ) {
            $data = array_map( 'trim', explode( '=>', $data ) );
            return array_combine( $columns, $data );
        }, $array );

    }

    public function get_thumbnail_id( $thumbnail ) {

        if ( intval( $thumbnail ) ) return $thumbnail;

        wp_raise_memory_limit( 'image' );

        $thumbnail = media_sideload_image( $thumbnail, 0, null, 'id' );

        if ( is_wp_error( $thumbnail ) ) return '';

        return $thumbnail;

    }

    public function get_gallery_ids( $thumbnail_ids ) {

        $thumbnail_ids = $this->parse_to_array( $thumbnail_ids );
        $thumbnail_ids = array_filter( $thumbnail_ids );

        if ( empty($thumbnail_ids) ) return [];

        if ( intval( $thumbnail_ids[0] ) ) return array_map( 'intval', $thumbnail_ids );

        wp_raise_memory_limit( 'image' );

        $_thumbnail_ids = [];

        foreach ( $thumbnail_ids as $thumbnail ) {
            $_thumbnail = media_sideload_image( $thumbnail, 0, null, 'id' );
            if ( ! is_wp_error( $_thumbnail ) ) {
                $_thumbnail_ids[] = $_thumbnail;
            }
        }

        return $_thumbnail_ids;

    }

}