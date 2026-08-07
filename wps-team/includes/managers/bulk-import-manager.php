<?php

namespace WPSpeedo_Team;

use WP_Error;

if ( ! defined('ABSPATH') ) exit;

class Bulk_Import_Manager {

    use AJAX_Handler, Taxonomy;

    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_bulk_import_handler';

    public function __construct() {

        $this->set_ajax_scope_hooks();

    }

    /**
     * Transient key scoped per user so parallel imports do not overwrite each other.
     */
    protected function get_csv_transient_key() {
        return 'wps_team_bulk_csv_' . get_current_user_id();
    }

    /**
     * Supported CSV header slugs (after sanitization). Whitelist; unknown headers are ignored.
     */
    public static function get_allowed_import_column_slugs() {
        static $allowed = null;
        if ( null !== $allowed ) {
            return $allowed;
        }

        $slugs = [
            'post_id',
            'id',
            'first_name',
            'last_name',
            'description',
            'designation',
            'email',
            'mobile',
            'telephone',
            'fax',
            'experience',
            'website',
            'company',
            'address',
            'ribbon',
            'link_one',
            'link_two',
            'color',
            'education',
            'thumbnail',
            'gallery',
            'social_links',
            'skills',
            'custom_url',
            'groups',
            'locations',
            'languages',
            'specialties',
            'genders',
            'extra_one',
            'extra_two',
            'extra_three',
            'extra_four',
            'extra_five',
        ];

        $allowed = array_values( array_unique( $slugs ) );
        return $allowed;
    }

    /**
     * CSV column slug => taxonomy root (e.g. groups → group).
     *
     * @return array<string, string>
     */
    public static function get_taxonomy_csv_slug_to_root_map() {
        return [
            'groups'      => 'group',
            'locations'   => 'location',
            'languages'   => 'language',
            'specialties' => 'specialty',
            'genders'     => 'gender',
            'extra_one'   => 'extra-one',
            'extra_two'   => 'extra-two',
            'extra_three' => 'extra-three',
            'extra_four'  => 'extra-four',
            'extra_five'  => 'extra-five',
        ];
    }

    /**
     * Taxonomies that have non-empty CSV data but are disabled (not registered).
     *
     * @param array<int, array<string, mixed>> $rows Parsed CSV rows.
     * @return array{ inactive_with_data: array<int, array{ csv_slug: string, root: string, label: string }> }
     */
    protected function get_taxonomy_import_notice_from_rows( array $rows ) {
        $map           = self::get_taxonomy_csv_slug_to_root_map();
        $allowed_roots = array_flip( Utils::get_taxonomy_roots( true ) );
        $slugs_hit     = [];

        foreach ( $rows as $row ) {
            if ( ! is_array( $row ) ) {
                continue;
            }
            foreach ( $map as $slug => $root ) {
                if ( ! isset( $allowed_roots[ $root ] ) ) {
                    continue;
                }
                if ( ! isset( $row[ $slug ] ) ) {
                    continue;
                }
                if ( ! $this->empty( $row[ $slug ] ) ) {
                    $slugs_hit[ $slug ] = $root;
                }
            }
        }

        if ( empty( $slugs_hit ) ) {
            return [ 'inactive_with_data' => [] ];
        }

        $active = array_flip( Utils::get_active_taxonomies() );
        $out    = [];

        foreach ( $slugs_hit as $slug => $root ) {
            $tax_name = Utils::get_taxonomy_name( $root );
            if ( isset( $active[ $tax_name ] ) ) {
                continue;
            }
            $field_key = Utils::to_field_key( $root );
            $label     = (string) Utils::get_setting( $field_key . '_plural_name' );
            if ( $label === '' ) {
                $label = ucwords( str_replace( [ '-', '_' ], ' ', $root ) );
            }
            $out[ $root ] = [
                'csv_slug' => $slug,
                'root'     => $root,
                'label'    => $label,
            ];
        }

        $list = array_values( $out );
        usort(
            $list,
            static function ( $a, $b ) {
                return strcasecmp( $a['label'], $b['label'] );
            }
        );

        return [ 'inactive_with_data' => $list ];
    }

    /**
     * @param array $row Whitelisted row from CSV.
     * @return array{0: array, 1: string[]} [ normalized row, present column slugs ]
     */
    protected function normalize_import_row( array $row ) {
        $allowed = array_flip( self::get_allowed_import_column_slugs() );
        $ignored = [];
        foreach ( array_keys( $row ) as $key ) {
            if ( ! isset( $allowed[ $key ] ) ) {
                $ignored[] = $key;
                unset( $row[ $key ] );
            }
        }

        if ( isset( $row['id'] ) && $row['id'] !== '' && ( ! isset( $row['post_id'] ) || $row['post_id'] === '' ) ) {
            $row['post_id'] = $row['id'];
        }
        unset( $row['id'] );

        return [ $row, $ignored ];
    }

    protected function assert_bulk_import_allowed() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'You do not have permission to perform this action', 'wps-team' ), 403 );
        }
        if ( function_exists( 'wps_team_fs' ) && ! wps_team_fs()->can_use_premium_code__premium_only() ) {
            wp_send_json_error( __( 'Bulk import requires WP Team Pro.', 'wps-team' ), 403 );
        }
    }

    public function ajax_parse_csv() {

        $this->assert_bulk_import_allowed();

        $parsed = $this->get_file_rows();

        if ( is_wp_error( $parsed ) ) {
            wp_send_json_error( $parsed->get_error_message(), 400 );
        }

        Utils::prepare_heavy_operation();

        set_transient( $this->get_csv_transient_key(), $parsed['rows'], DAY_IN_SECONDS );

        wp_send_json_success(
            [
                'rows'                   => $parsed['rows'],
                'ignored_headers'        => $parsed['ignored_headers'],
                'taxonomy_import_notice' => $this->get_taxonomy_import_notice_from_rows( $parsed['rows'] ),
            ]
        );

    }

    /**
     * Enable every taxonomy that has data in the current bulk-import CSV session but is still disabled.
     */
    public function ajax_enable_taxonomies_for_bulk_import() {

        $this->assert_bulk_import_allowed();

        $rows = get_transient( $this->get_csv_transient_key() );

        if ( empty( $rows ) || ! is_array( $rows ) ) {
            wp_send_json_error(
                _x( 'No import session found. Upload the CSV again.', 'Bulk Import', 'wps-team' ),
                400
            );
        }

        $notice = $this->get_taxonomy_import_notice_from_rows( $rows );
        $roots = array_column( $notice['inactive_with_data'], 'root' );

        if ( empty( $roots ) ) {
            wp_send_json_success(
                [
                    'message' => _x( 'All relevant taxonomies are already enabled.', 'Bulk Import', 'wps-team' ),
                ]
            );
        }

        $saved = Utils::get_taxonomies_settings();
        foreach ( $roots as $root ) {
            $key            = 'enable_' . Utils::to_field_key( $root ) . '_taxonomy';
            $saved[ $key ] = true;
        }

        $saved = plugin()->api->sanitize_taxonomy_settings( $saved );
        update_option( Utils::get_taxonomies_option_name(), $saved );

        $this->register_taxonomies();
        Utils::flush_rewrite_rules();

        wp_send_json_success(
            [
                'message'       => _x( 'Required taxonomies are now enabled. You can run Import Now.', 'Bulk Import', 'wps-team' ),
                'enabled_roots' => array_values( $roots ),
            ]
        );

    }

    public function ajax_import_csv() {

        $this->assert_bulk_import_allowed();

        // phpcs:ignore WordPress.Security.NonceVerification
        $index = isset( $_REQUEST['index'] ) ? (int) $_REQUEST['index'] : null;

        if ( ! is_numeric( $index ) ) {
            wp_send_json_error( _x( 'Row not found', 'Bulk Import', 'wps-team' ), 400 );
        }

        Utils::prepare_heavy_operation();

        $rows = get_transient( $this->get_csv_transient_key(), [] );

        if ( ! isset( $rows[ $index ] ) || ! is_array( $rows[ $index ] ) ) {
            wp_send_json_error( _x( 'Row expired or not found. Please upload the CSV again.', 'Bulk Import', 'wps-team' ), 400 );
        }

        $raw_row    = $rows[ $index ];
        $present    = array_keys( $raw_row );
        $mapped     = $this->map_row_data( $raw_row );
        $target_id  = $this->get_update_post_id( $raw_row );

        if ( $this->empty( $mapped['first_name'] ?? '' ) || $this->empty( $mapped['last_name'] ?? '' ) ) {
            wp_send_json_error( _x( 'first_name and last_name are required for every row', 'Bulk Import', 'wps-team' ), 400 );
        }

        if ( $target_id && get_post_type( $target_id ) !== Utils::post_type_name() ) {
            // If provided post_id is missing/invalid/not team-member, fallback to create.
            $target_id = 0;
        }

        if ( $target_id && ! current_user_can( 'edit_post', $target_id ) ) {
            wp_send_json_error( _x( 'You cannot edit this team member', 'Bulk Import', 'wps-team' ), 403 );
        }

        $first_name = sanitize_text_field( (string) ( $mapped['first_name'] ?? '' ) );
        $last_name  = sanitize_text_field( (string) ( $mapped['last_name'] ?? '' ) );

        $item = [
            'post_title'   => Utils::get_title_from_name_fields( $first_name, $last_name ),
            'post_status'  => 'publish',
            'post_type'    => Utils::post_type_name(),
            'meta_input'   => $this->get_row_meta_input( $mapped, $present ),
        ];

        $tax_input = $this->get_row_tax_input( $mapped, $present );
        if ( ! empty( $tax_input ) ) {
            $item['tax_input'] = $tax_input;
        }

        if ( $target_id ) {
            if ( in_array( 'description', $present, true ) ) {
                $item['post_content'] = isset( $mapped['description'] ) ? wp_kses_post( (string) $mapped['description'] ) : '';
            }
        } else {
            $item['post_content'] = isset( $mapped['description'] ) ? wp_kses_post( (string) $mapped['description'] ) : '';
        }

        if ( $target_id ) {
            $item['ID'] = $target_id;
        }

        $post_id = wp_insert_post( $item, true );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( $post_id->get_error_message() ? $post_id->get_error_message() : _x( "Couldn't save post", 'Bulk Import', 'wps-team' ), 400 );
        }

        wp_send_json_success();

    }

    /**
     * @param array $raw_row Whitelisted keys only.
     */
    protected function get_update_post_id( array $raw_row ) {
        if ( empty( $raw_row['post_id'] ) || ! is_numeric( $raw_row['post_id'] ) ) {
            return 0;
        }
        $id = (int) $raw_row['post_id'];
        return $id > 0 ? $id : 0;
    }

    public function map_row_data( $data ) {

        if ( ! is_array( $data ) ) {
            $data = [];
        }

        $data['groups']      = $this->parse_to_array( $data['groups'] ?? '' );
        $data['locations']   = $this->parse_to_array( $data['locations'] ?? '' );
        $data['languages']   = $this->parse_to_array( $data['languages'] ?? '' );
        $data['specialties'] = $this->parse_to_array( $data['specialties'] ?? '' );
        $data['genders']     = $this->parse_to_array( $data['genders'] ?? '' );
        $data['extra_one']   = $this->parse_to_array( $data['extra_one'] ?? '' );
        $data['extra_two']   = $this->parse_to_array( $data['extra_two'] ?? '' );
        $data['extra_three'] = $this->parse_to_array( $data['extra_three'] ?? '' );
        $data['extra_four']  = $this->parse_to_array( $data['extra_four'] ?? '' );
        $data['extra_five']  = $this->parse_to_array( $data['extra_five'] ?? '' );

        $skills_data                = $this->parse_skills_field( $data['skills'] ?? '' );
        $data['skills']             = $skills_data['items'];
        $data['skills_with_value']  = $skills_data['with_value'];

        $data['social_links'] = $this->parse_to_array_deep( $data['social_links'] ?? '', [ 'social_icon', 'social_link' ] );
        $data['social_links'] = array_values(
            array_filter(
                array_map( [ $this, 'build_icon_data' ], $data['social_links'] )
            )
        );

        return $data;

    }

    /**
     * @param array<string,mixed> $icon
     * @return array<string,mixed>|null
     */
    public function build_icon_data( $icon ) {

        if ( ! is_array( $icon ) || empty( $icon['social_icon'] ) || ! is_string( $icon['social_icon'] ) ) {
            return null;
        }

        $_icon = $icon['social_icon'];

        if ( strpos( $_icon, 'far' ) !== false ) {
            $library = 'fa-regular';
        } elseif ( strpos( $_icon, 'fas' ) !== false ) {
            $library = 'fa-solid';
        } else {
            $library = 'fa-brands';
        }

        $icon['social_icon'] = [
            'icon'    => $_icon,
            'library' => $library,
        ];

        return $icon;

    }

    /**
     * Skills CSV supports:
     * - With level: Skill=>80 (comma-separated list).
     * - Tag-style: Skill name only (comma-separated); saved as 100% when no level is given.
     * - Mixed: UX=>60, Communication (Communication becomes 100%).
     *
     * @param mixed $data Raw cell string.
     * @return array{items: array<int, array{skill_name: string, skill_val: int}>, with_value: bool}
     */
    public function parse_skills_field( $data ) {

        if ( $this->empty( $data ) ) {
            return [
                'items'      => [],
                'with_value' => true,
            ];
        }

        $items  = array_map( 'trim', explode( ',', str_replace( ', ', ',', (string) $data ) ) );
        $skills = [];
        $has_paired_item = false;

        foreach ( $items as $item ) {
            if ( $item === '' ) {
                continue;
            }

            $pos = strpos( $item, '=>' );

            if ( false !== $pos ) {
                $name = trim( substr( $item, 0, $pos ) );
                $val  = trim( substr( $item, $pos + 2 ) );
                if ( $name === '' ) {
                    continue;
                }
                $has_paired_item = true;
                if ( $val === '' || ! is_numeric( $val ) ) {
                    $val = 100;
                } else {
                    $val = (int) $val;
                }
            } else {
                $name = $item;
                $val  = 100;
            }

            $val = max( 0, min( 100, (int) $val ) );

            $skills[] = [
                'skill_name' => sanitize_text_field( $name ),
                'skill_val'  => $val,
            ];
        }

        return [
            'items'      => $skills,
            'with_value' => $has_paired_item,
        ];

    }

    /**
     * @param array $row Parsed row.
     * @param string[] $present_keys Column keys present in this CSV row (after whitelist).
     */
    public function get_row_tax_input( $row, $present_keys = null ) {

        if ( null === $present_keys ) {
            $present_keys = array_keys( is_array( $row ) ? $row : [] );
        }

        $tax_input = [];

        $map = [
            'groups'      => Utils::get_taxonomy_name( 'group' ),
            'locations'   => Utils::get_taxonomy_name( 'location' ),
            'languages'   => Utils::get_taxonomy_name( 'language' ),
            'specialties' => Utils::get_taxonomy_name( 'specialty' ),
            'genders'     => Utils::get_taxonomy_name( 'gender' ),
            'extra_one'   => Utils::get_taxonomy_name( 'extra-one' ),
            'extra_two'   => Utils::get_taxonomy_name( 'extra-two' ),
            'extra_three' => Utils::get_taxonomy_name( 'extra-three' ),
            'extra_four'  => Utils::get_taxonomy_name( 'extra-four' ),
            'extra_five'  => Utils::get_taxonomy_name( 'extra-five' ),
        ];

        foreach ( $map as $row_key => $taxonomy ) {
            if ( ! in_array( $row_key, $present_keys, true ) ) {
                continue;
            }
            if ( empty( $row[ $row_key ] ) ) {
                continue;
            }
            // Disabled taxonomies are not registered; never pass them in tax_input (WP 4.4+ notice).
            if ( ! taxonomy_exists( $taxonomy ) ) {
                continue;
            }
            $tax_input[ $taxonomy ] = $this->get_row_term_ids( $row[ $row_key ], $taxonomy );
        }

        return $tax_input;

    }

    public function get_row_term_ids( $terms, $taxonomy ) {

        $term_ids = [];

        if ( ! taxonomy_exists( $taxonomy ) ) {
            return $term_ids;
        }

        foreach ( $terms as $term ) {

            $_term = get_term_by( 'name', $term, $taxonomy );

            if ( $_term ) {
                $term_ids[] = $_term->term_id;
            } else {
                $response = wp_insert_term( $term, $taxonomy );
                if ( ! is_wp_error( $response ) ) {
                    $term_ids[] = $response['term_id'];
                }
            }

        }

        return array_values( array_unique( $term_ids ) );

    }

    public function empty( $data ) {

        if ( empty( $data ) ) {
            return true;
        }
        if ( is_array( $data ) ) {
            return false;
        }

        $str = trim( (string) $data );
        if ( $str === '' ) {
            return true;
        }

        if ( strtolower( $str ) === 'na' ) {
            return true;
        }

        return false;

    }

    /**
     * @param array $row Mapped row.
     * @param string[]|null $present_keys Keys from CSV for this row.
     */
    public function get_row_meta_input( $row, $present_keys = null ) {

        if ( null === $present_keys ) {
            $present_keys = array_keys( is_array( $row ) ? $row : [] );
        }

        $meta_input = [];

        $set = function( $csv_key, $meta_key, $sanitize_cb ) use ( &$meta_input, $row, $present_keys ) {
            if ( ! in_array( $csv_key, $present_keys, true ) ) {
                return;
            }
            if ( ! isset( $row[ $csv_key ] ) || $this->empty( $row[ $csv_key ] ) ) {
                return;
            }
            $meta_input[ $meta_key ] = call_user_func( $sanitize_cb, $row[ $csv_key ] );
        };

        $set( 'first_name', '_first_name', 'sanitize_text_field' );
        $set( 'last_name', '_last_name', 'sanitize_text_field' );
        $set( 'designation', '_designation', 'sanitize_text_field' );
        $set( 'email', '_email', 'sanitize_email' );
        $set( 'mobile', '_mobile', 'sanitize_text_field' );
        $set( 'telephone', '_telephone', 'sanitize_text_field' );
        $set( 'fax', '_fax', 'sanitize_text_field' );
        $set( 'experience', '_experience', 'sanitize_text_field' );
        $set( 'website', '_website', 'esc_url_raw' );
        $set( 'company', '_company', 'sanitize_text_field' );
        $set( 'address', '_address', 'sanitize_text_field' );
        $set( 'ribbon', '_ribbon', 'sanitize_text_field' );
        $set( 'link_one', '_link_1', 'esc_url_raw' );
        $set( 'link_two', '_link_2', 'esc_url_raw' );
        $set( 'color', '_color', 'sanitize_text_field' );
        $set( 'education', '_education', 'wp_kses_post' );
        $set( 'custom_url', '_custom_url', 'esc_url_raw' );

        if ( in_array( 'thumbnail', $present_keys, true ) && isset( $row['thumbnail'] ) && ! $this->empty( $row['thumbnail'] ) ) {
            $tid = (int) $this->get_thumbnail_id( $row['thumbnail'] );
            if ( $tid > 0 ) {
                $meta_input['_thumbnail_id'] = $tid;
            }
        }

        if ( in_array( 'gallery', $present_keys, true ) && isset( $row['gallery'] ) && ! $this->empty( $row['gallery'] ) ) {
            $gids = (array) $this->get_gallery_ids( $row['gallery'] );
            if ( ! empty( $gids ) ) {
                $meta_input[ Utils::member_gallery_meta_key() ] = $gids;
            }
        }

        if ( in_array( 'social_links', $present_keys, true ) && ! empty( $row['social_links'] ) ) {
            $meta_input['_social_links'] = (array) $row['social_links'];
        }

        if ( in_array( 'skills', $present_keys, true ) && ! empty( $row['skills'] ) ) {
            $meta_input['_skills'] = (array) $row['skills'];
            $meta_input['_skills_with_value'] = ! empty( $row['skills_with_value'] );
        }

        return $meta_input;

    }

    /**
     * @return array|WP_Error On success: [ 'rows' => array<int,array>, 'ignored_headers' => string[] ]
     */
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

        $file = new \SplFileObject( $import_file['tmp_name'] );
        $file->setFlags( \SplFileObject::READ_CSV | \SplFileObject::SKIP_EMPTY );
        $file->setCsvControl( ',', '"', '\\' );

        $raw_header = $file->fgetcsv();
        if ( false === $raw_header || ! is_array( $raw_header ) ) {
            return new WP_Error( 'invalid_file', _x( 'Could not read CSV header', 'Bulk Import', 'wps-team' ) );
        }

        $column_map = [];
        foreach ( $raw_header as $i => $cell ) {
            $san = $this->sanitize_header_row( isset( $cell ) ? (string) $cell : '' );
            if ( $san === '' ) {
                continue;
            }
            $column_map[] = [
                'i'   => (int) $i,
                'key' => $san,
            ];
        }

        if ( empty( $column_map ) ) {
            return new WP_Error( 'invalid_file', _x( 'No valid columns in CSV header', 'Bulk Import', 'wps-team' ) );
        }

        $ignored_headers_global = [];

        foreach ( $file as $index => $csv_data ) {

            if ( $index === 0 ) {
                continue;
            }

            if ( empty( $csv_data ) || ( count( $csv_data ) === 1 && $csv_data[0] === null ) ) {
                continue;
            }

            if ( ! is_array( $csv_data ) ) {
                continue;
            }

            $row = [];
            foreach ( $column_map as $col ) {
                $cell        = isset( $csv_data[ $col['i'] ] ) ? $csv_data[ $col['i'] ] : '';
                $row[ $col['key'] ] = _wp_json_convert_string( trim( (string) $cell ) );
            }

            [ $row, $ignored ] = $this->normalize_import_row( $row );
            if ( ! empty( $ignored ) ) {
                $ignored_headers_global = array_merge( $ignored_headers_global, $ignored );
            }

            $items[] = $row;

        }

        return [
            'rows'             => $items,
            'ignored_headers'  => array_values( array_unique( $ignored_headers_global ) ),
        ];

    }

    public function sanitize_header_row( $string ) {
        $string = (string) $string;
        $string = preg_replace( '/^\xEF\xBB\xBF/', '', $string );
        $string = str_replace( [ ' ', '-' ], '_', $string );
        $string = strtolower( _wp_json_convert_string( trim( $string ) ) );
        return preg_replace( '/[^A-Za-z0-9\_]/', '', $string );
    }

    public function parse_to_array( $data ) {
        if ( $this->empty( $data ) ) {
            return [];
        }
        return array_map( 'trim', explode( ',', str_replace( ', ', ',', (string) $data ) ) );
    }

    public function parse_to_array_deep( $data, $columns ) {

        if ( $this->empty( $data ) ) {
            return [];
        }

        $data   = $this->parse_to_array( $data );
        $count  = count( $columns );
        $parsed = [];

        foreach ( $data as $single_data ) {
            $single_data = trim( (string) $single_data );
            $pos         = strpos( $single_data, '=>' );
            if ( false === $pos ) {
                continue;
            }
            $left  = trim( substr( $single_data, 0, $pos ) );
            $right = trim( substr( $single_data, $pos + 2 ) );
            if ( $left === '' && $right === '' ) {
                continue;
            }
            if ( 2 === $count ) {
                $parsed[] = [
                    $columns[0] => $left,
                    $columns[1] => $right,
                ];
            }
        }

        return $parsed;

    }

    protected function is_valid_attachment_id( $id ) {
        $id = (int) $id;
        if ( $id <= 0 ) {
            return false;
        }
        $post = get_post( $id );
        return ( $post && $post->post_type === 'attachment' );
    }

    public function get_thumbnail_id( $thumbnail ) {

        if ( is_numeric( $thumbnail ) && intval( $thumbnail ) > 0 ) {
            $id = intval( $thumbnail );
            if ( $this->is_valid_attachment_id( $id ) ) {
                return $id;
            }
            // Invalid attachment ID; continue to URL/path checks.
        }

        $thumbnail = trim( (string) $thumbnail );
        if ( $thumbnail === '' ) {
            return '';
        }

        if ( strpos( $thumbnail, '/wp-content/' ) === 0 ) {

            $absolute_path = ABSPATH . ltrim( $thumbnail, '/' );

            if ( file_exists( $absolute_path ) ) {
                $upload_dir = wp_upload_dir();
                $real_file  = realpath( $absolute_path );
                $real_base  = realpath( $upload_dir['basedir'] );
                if ( $real_file && $real_base && strpos( $real_file, $real_base ) === 0 ) {
                    $url = str_replace( $real_base, $upload_dir['baseurl'], $real_file );
                    $id  = attachment_url_to_postid( $url );
                    if ( $id ) {
                        return $id;
                    }
                }
            }

            return '';
        }

        if ( filter_var( $thumbnail, FILTER_VALIDATE_URL ) ) {

            // If the URL belongs to this site, try to reuse the existing attachment instead of sideloading.
            $site_host  = wp_parse_url( home_url(), PHP_URL_HOST );
            $thumb_host = wp_parse_url( $thumbnail, PHP_URL_HOST );
            if ( $site_host && $thumb_host && strtolower( (string) $site_host ) === strtolower( (string) $thumb_host ) ) {
                $existing = attachment_url_to_postid( $thumbnail );
                if ( $existing ) {
                    return $existing;
                }

                // Fallback: some sites have mixed http/https or the attachment stored under a different scheme.
                $alt = preg_replace( '#^https?://#i', '', $thumbnail );
                if ( $alt ) {
                    $existing = attachment_url_to_postid( 'https://' . $alt );
                    if ( $existing ) {
                        return $existing;
                    }
                    $existing = attachment_url_to_postid( 'http://' . $alt );
                    if ( $existing ) {
                        return $existing;
                    }
                }
            }

            $id = media_sideload_image( $thumbnail, 0, null, 'id' );

            if ( is_wp_error( $id ) ) {
                return '';
            }

            return $id;
        }

        return '';
    }

    public function get_gallery_ids( $thumbnail_ids ) {

        $thumbnail_ids = $this->parse_to_array( $thumbnail_ids );
        $thumbnail_ids = array_filter( $thumbnail_ids );

        if ( empty( $thumbnail_ids ) ) {
            return [];
        }

        $ids = [];

        foreach ( $thumbnail_ids as $thumb ) {

            $id = $this->get_thumbnail_id( $thumb );

            if ( ! empty( $id ) && is_numeric( $id ) ) {
                $ids[] = intval( $id );
            }

        }

        return $ids;

    }

}
