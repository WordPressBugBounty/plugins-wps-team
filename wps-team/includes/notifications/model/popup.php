<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

abstract class Popup extends Notification {

    public $type = 'popup';

    final public function get_key() {
        return 'wps_team_popup_' . $this->get_id();
    }

}