<?php

namespace WPSpeedo_Team;

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Control_Upgrade_Notice extends Base_Data_Control {
    public function get_type() {
        return 'upgrade_notice';
    }

    protected function get_notice_text() {
        return _x( 'Upgrade to Pro', 'Editor: Upgrade Notice', 'wpspeedo-team' );
    }

    protected function get_default_settings() {
        return [
            'text'  => $this->get_notice_text(),
            'class' => 'wps-field--upgrade-notice',
        ];
    }

}
