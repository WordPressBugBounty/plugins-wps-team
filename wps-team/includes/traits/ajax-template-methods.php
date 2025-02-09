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
        if ( $this->get_setting( 'enable_paging' ) && !$this->get_setting( 'show_all' ) ) {
            return true;
        }
        return $this->get_setting( 'is_filter_ajax' );
    }

    public function should_load_ajax_template() {
        return in_array( $this->get_paging_type(), [
            'pagination-ajax',
            'load-more-ajax',
            'infinite-scroll-ajax',
            'infinite-carousel-ajax'
        ] ) || $this->is_filter_ajax();
    }

}