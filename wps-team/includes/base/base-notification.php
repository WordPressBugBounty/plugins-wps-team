<?php
namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

abstract class Base_Notification {

    use Date_Methods;

    public $notifications = [];

    public function get_notification( $instance_key ) {
        if ( array_key_exists( $instance_key, $this->notifications ) ) {
            return $this->notifications[ $instance_key ];
        }
        return null;
    }

    public function get_notifications( $type = null ) {

        $notifications = $this->notifications;

        if ( $type ) {
            $notifications = wp_list_filter( $notifications, ['type' => $type] );
        }

        return $notifications;

    }

    public function get_active_notifications( $type = null ) {
        $notifications = wp_list_filter( $this->get_notifications( $type ), [ 'is_active' => true ] );
        return wp_list_sort( $notifications, 'next_exec_time' );
    }

    public function get_exec_notifications( $date = null, $type = null ) {

        if ( ! $date ) $date = $this->current_time();

        $notifications = $this->get_active_notifications( $type );

        $_notifications = [];

        foreach ( $notifications as $notification ) {

            if ( empty( $notification->next_exec_time ) ) continue;

            if ( $this->date_is_current_or_prev( $notification->next_exec_time, $date ) ) {
                $_notifications[] = $notification;
            }
            
        }

        return $_notifications;

    }

    public function get_upcoming_notifications( $date = null, $type = null ) {

        if ( ! $date ) $date = $this->current_time();

        $notifications = $this->get_active_notifications( $type );

        $_notifications = [];

        foreach ( $notifications as $notification ) {

            if ( empty( $notification->next_exec_time ) ) continue;

            if ( $this->date_is_next( $notification->next_exec_time, $date ) ) {
                $_notifications[] = $notification;
            }
            
        }

        return $_notifications;

    }

    public function register( $instance ) {
        if ( ! array_key_exists( $instance->get_id(), $this->notifications ) ) {
            $this->notifications[ $instance->get_id() ] = $instance;
        }
    }

    public function get( $id ) {
        if ( array_key_exists( $id, $this->notifications ) ) {
            return $this->notifications[ $id ];
        }
        return false;
    }

}