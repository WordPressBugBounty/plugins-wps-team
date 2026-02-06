<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
trait AJAX_Template_Methods
{
    public function get_paging_type() {
        return false;
    }

    public function is_filter_ajax() {
        if ( $this->get_setting( 'display_type' ) !== 'filter' ) {
            return false;
        }
        return $this->get_setting( 'is_filter_ajax' );
    }

}