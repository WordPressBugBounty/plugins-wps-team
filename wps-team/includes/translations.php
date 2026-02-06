<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Translations {

    public $strings = [];

    public function get( $local, $international ) {
        $is_international = Utils::get_setting( 'enable_multilingual' );
        return esc_html( $is_international ? $international : Utils::get_setting( $local ) );
    }

}