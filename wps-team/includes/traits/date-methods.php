<?php
namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

trait Date_Methods {

    public function current_time() {
        return current_time('Y-m-d');
    }

    public function date_compare( $date_1, $date_2 = null, $compare = null ) {
        
        if ( ! $compare ) $compare = '==';
        if ( ! $date_2 ) $date_2 = $this->current_time();

        if ( $compare == '<' ) {
            return strtotime( $date_1 ) < strtotime( $date_2 );
        } else if ( $compare == '>' ) {
            return strtotime( $date_1 ) > strtotime( $date_2 );
        } else if ( $compare == '<=' ) {
            return strtotime( $date_1 ) <= strtotime( $date_2 );
        } else if ( $compare == '>=' ) {
            return strtotime( $date_1 ) >= strtotime( $date_2 );
        } else {
            return strtotime( $date_1 ) == strtotime( $date_2 );
        }

    }

    public function date_diff( $date_1, $date_2 = null ) {
        if ( ! $date_2 ) $date_2 = $this->current_time();
        $diff = date_diff( date_create($date_2), date_create($date_1) );
        return $diff->format("%R%a");
    }

    public function date_is_current( $date_1 ) {
        return $this->date_compare( $date_1 );
    }

    public function date_is_prev( $date_1, $date_2 = null ) {
        return $this->date_compare( $date_1, $date_2, '<' );
    }

    public function date_is_current_or_prev( $date_1, $date_2 = null ) {
        return $this->date_compare( $date_1, $date_2, '<=' );
    }

    public function date_is_next( $date_1, $date_2 = null ) {
        return $this->date_compare( $date_1, $date_2, '>' );
    }

    public function date_is_current_or_next( $date_1, $date_2 = null ) {
        return $this->date_compare( $date_1, $date_2, '>=' );
    }

    public function date_increment( $date, $days ) {
        return date( 'Y-m-d', strtotime( $date. " + $days days" ) );
    }

}