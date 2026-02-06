<?php

namespace WPSpeedo_Team;

use WP_Error;

if ( ! defined('ABSPATH') ) exit;

class Erase_Reset_Manager {

    use AJAX_Handler, Taxonomy;

    public $ajax_key = 'wpspeedo_team';

    public $ajax_scope = '_erase_reset_handler';

    private $is_pro;

    public function __construct() {

        $this->is_pro = wps_team_fs()->can_use_premium_code__premium_only();

        $this->set_ajax_scope_hooks();

    }
    
    public function ajax_erase_reset_data() {
        // Check user capability
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error(
                __( 'You do not have permission to perform this action', 'wps-team' ),
                403
            );
        }

        // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
        $options = isset( $_REQUEST['options'] ) ? (array) wp_unslash( $_REQUEST['options'] ) : [];

        // Set default values
        $defaults = [
            'terms'        => false,
            'attachments'  => false,
            'team_members' => false,
            'shortcodes'   => false,
            'settings'     => false,
        ];

        $options = wp_parse_args( $options, $defaults );
        $options = array_map( 'wp_validate_boolean', $options );

        // Ensure at least one option is selected
        if ( ! count( array_filter( $options ) ) ) {
            wp_send_json_error( __( 'No erase option selected', 'wps-team' ), 400 );
        }

        // Map options to corresponding methods
        $erase_methods = [
            'terms'        => 'erase__terms',
            'attachments'  => 'erase__attachments',
            'team_members' => 'erase__team_members',
            'shortcodes'   => 'erase__shortcodes',
            'settings'     => 'erase__settings',
        ];

        // Prepare for heavy operation
        Utils::prepare_heavy_operation();

        // Execute erase functions
        foreach ( $erase_methods as $key => $method ) {
            if ( ! empty( $options[ $key ] ) ) {
                $result = $this->{$method}();
                if ( is_wp_error( $result ) ) {
                    wp_send_json_error( $result->get_error_message(), 400 );
                }
                wp_cache_flush_group( 'wps_team' );
            }
            
        }

        wp_send_json_success( __( 'Data wiped successfully', 'wps-team' ) );
    }

    private function erase__terms() {

        global $wpdb;

        $roots = Utils::get_taxonomy_roots( true );
        $taxonomies = array_map( '\WPSpeedo_Team\Utils::get_taxonomy_name', $roots );
        
        // Prepare placeholders for query
        $placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );

        // Get term IDs for these taxonomies
        $sql = "SELECT t.term_id
                FROM {$wpdb->terms} AS t
                INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy IN ($placeholders)";
        $term_ids = $wpdb->get_col( $wpdb->prepare( $sql, ...$taxonomies ) );

        if ( $term_ids === null ) {
            return new WP_Error( 'db_error', __( 'Database query failed while fetching term IDs.', 'wps-team' ) );
        }

        if ( empty( $term_ids ) ) {
            // Nothing to delete, return true
            return true;
        }

        // Sanitize IDs for query
        $term_ids = array_map( 'absint', $term_ids );
        $ids_placeholder = implode( ',', $term_ids );

        // Delete term meta
        $wpdb->query( "DELETE FROM {$wpdb->termmeta} WHERE term_id IN ($ids_placeholder)" );

        // Delete term taxonomy
        $wpdb->query( "DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ($ids_placeholder)" );

        // Delete terms
        $wpdb->query( "DELETE FROM {$wpdb->terms} WHERE term_id IN ($ids_placeholder)" );

        // Check for DB errors
        if ( $wpdb->last_error ) {
            return new WP_Error( 'db_error', $wpdb->last_error );
        }

        return true;

    }

    private function erase__attachments() {

        global $wpdb;

        $post_type = Utils::post_type_name();

        // Step 1: Get all team member post IDs
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
                $post_type
            )
        );

        if ( empty( $post_ids ) ) {
            return true; // Nothing to delete
        }

        $post_ids_placeholders = implode( ',', array_map( 'absint', $post_ids ) );

        // Step 2: Get all featured image IDs (_thumbnail_id)
        $attachment_ids = $wpdb->get_col(
            "SELECT meta_value FROM {$wpdb->postmeta} 
            WHERE meta_key IN ('_thumbnail_id', '_gallery') 
            AND post_id IN ($post_ids_placeholders)"
        );

        $all_attachment_ids = [];
        foreach ( $attachment_ids as $val ) {
            $arr = maybe_unserialize( $val );
            if ( is_array( $arr ) ) {
                $all_attachment_ids = array_merge( $all_attachment_ids, $arr );
            } else {
                $all_attachment_ids[] = $val;
            }
        }

        $all_attachment_ids = array_map( 'absint', array_unique( $all_attachment_ids ) );
        $all_attachment_ids = array_filter( $all_attachment_ids );

        if ( empty( $all_attachment_ids ) ) {
            return true; // No attachments found
        }

        // Step 3: Delete attachments safely
        foreach ( $all_attachment_ids as $att_id ) {
            // Delete attachment
            if ( ! wp_delete_attachment( $att_id, true ) ) {
                return new WP_Error( 'delete_failed', sprintf( 'Failed to delete attachment ID %d', $att_id ) );
            }
        }

        // Step 4: Delete _gallery meta to prevent broken images
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} 
            WHERE meta_key = '_gallery' 
            AND post_id IN ($post_ids_placeholders)"
        );

        // Step 5: Clear cache for affected posts
        foreach ( $post_ids as $post_id ) {
            clean_post_cache( $post_id );
        }

        return true;

    }

    private function erase__team_members() {

        global $wpdb;

        $post_type = Utils::post_type_name();

        // Step 1: Get all team member post IDs
        $post_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
                $post_type
            )
        );

        if ( empty( $post_ids ) ) {
            return true; // Nothing to delete
        }

        $post_ids_placeholders = implode( ',', array_map( 'absint', $post_ids ) );

        // Step 2: Delete all post meta for team members
        $wpdb->query(
            "DELETE FROM {$wpdb->postmeta} WHERE post_id IN ($post_ids_placeholders)"
        );

        // Step 3: Delete the posts themselves
        $wpdb->query(
            "DELETE FROM {$wpdb->posts} WHERE ID IN ($post_ids_placeholders)"
        );

        // Step 4: Clean up term relationships
        $wpdb->query(
            "DELETE FROM {$wpdb->term_relationships} WHERE object_id IN ($post_ids_placeholders)"
        );

        // Step 5: Clear caches for the deleted posts
        foreach ( $post_ids as $post_id ) {
            clean_post_cache( $post_id );
        }

        // Clear object cache globally
        wp_cache_flush();

        return true;
    }

    public function erase__shortcodes() {

        global $wpdb;

        $table_name = $wpdb->prefix . 'wps_team';

        // Step 1: Delete all rows
        $deleted = $wpdb->query( "DELETE FROM {$table_name}" );

        if ( $deleted === false ) {
            return new WP_Error( 'delete_failed', sprintf( 'Failed to delete rows from %s.', $table_name ) );
        }

        // Step 2: Reset auto-increment
        $reset = $wpdb->query( "ALTER TABLE {$table_name} AUTO_INCREMENT = 1" );

        if ( $reset === false ) {
            return new WP_Error( 'reset_failed', sprintf( 'Failed to reset auto-increment for %s.', $table_name ) );
        }

        return true;
    }

    public function erase__settings() {

        // Delete main plugin option if it exists
        if ( get_option( Utils::get_option_name() ) !== false ) {
            if ( ! delete_option( Utils::get_option_name() ) ) {
                return new WP_Error(
                    'delete_option_failed',
                    sprintf( 'Failed to delete option: %s', Utils::get_option_name() )
                );
            }
        }

        // Delete taxonomies option
        if ( get_option( Utils::get_taxonomies_option_name() ) !== false ) {
            if ( ! delete_option( Utils::get_taxonomies_option_name() ) ) {
                return new WP_Error(
                    'delete_option_failed',
                    sprintf( 'Failed to delete option: %s', Utils::get_taxonomies_option_name() )
                );
            }
        }

        return true;
    }

}