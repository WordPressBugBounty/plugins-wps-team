<?php
namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

abstract class Notification {

    use Date_Methods;
    
    public $type;
    public $control;
    public $current_interval = 0;
    public $intervals = [];
    public $is_active = true;
    public $next_exec_time = '';

    public function __construct() {
        $this->init();
    }

    final public function init() {

        $data = $this->get();

        if ( $data !== false ) { // Found Data from Database.
            $this->is_active = isset( $data['is_active'] ) ? $data['is_active'] : true;
            $this->intervals = isset( $data['intervals'] ) ? $data['intervals'] : [];
            $this->current_interval = isset( $data['current_interval'] ) ? $data['current_interval'] : 0;
        } else { // No Data From Database, So Build & Save it.
            $this->build();
            $this->save();
        }

        if ( $this->is_active && !empty( $this->intervals ) ) {
            $intervals = wp_list_filter( $this->intervals, [ 'fired' => false ] );
            if ( !empty( $intervals ) ) {
                $first = array_shift( $intervals );
                $this->next_exec_time = $first['date'];
            }
        }

        return $this;

    }

    final public function build() {

        foreach( $this->get_intervals() as $index => $day ) {

            $data = [ 'days' => $day, 'fired' => false ];
            
            if ( $index == 0 ) {
                $data['date'] = date( "Y-m-d", time() + ( DAY_IN_SECONDS * $day ) );
            } else {
                $data['date'] = $this->date_increment( $this->intervals[ $index-1 ]['date'], $day );
            }

            $this->intervals[] = $data;

        }
        
        return $this->intervals;
    }

    final public function fire( $trigger_time, $notification_type ) {

        update_option( "wps_team_{$notification_type}_last_interact", $trigger_time );
        
        // Current Interval is completed
        $this->intervals[ $this->current_interval ][ 'fired' ] = true;
        
        // Set the next interval as Current Interval
        $this->current_interval++;

        // Return if notification is inactive
        if ( ! $this->is_active ) return $this;

        if ( $this->current_interval >= count( $this->intervals ) ) {
            // Stop the Notification if We reach the end interval
            $this->is_active = false;
        } else {
            // Delay the next intervals if needed.
            $this->maybe_delay( $trigger_time );
        }

        return $this;
    }

    final public function maybe_delay( $trigger_time ) {

        $diff = abs( $this->date_diff( $this->next_exec_time, $trigger_time ) );

        if ( ! $diff ) return $this;

        foreach ( $this->intervals as &$interval ) {
            if ( $interval['fired'] ) continue;
            $interval['date'] = $this->date_increment( $interval['date'], $diff );
        }

        return $this;
    }

    final public function save() {
        update_option( $this->get_key(), [
            'is_active' => $this->is_active,
            'intervals' => $this->intervals,
            'current_interval' => $this->current_interval
        ]);
    }

    final public function get() {
        return get_option( $this->get_key() );
    }

    final public function delete() {
        delete_option( $this->get_key() );
    }

    final public function get_id() {
        $calss_name = ( new \ReflectionClass($this) )->getShortName();
        return strtolower( $calss_name );
    }

    final public function get_intervals() {
        $interval = (array) $this->intervals();
        sort( $interval );
        return $interval;
    }

    abstract public function get_key();
    
    abstract public function intervals();
    
}