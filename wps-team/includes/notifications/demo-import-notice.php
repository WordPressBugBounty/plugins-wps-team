<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Demo_Import_Notice extends Notice {

    public function notice_content() {

        ?>

        <h3 class="notice-title">Import <strong>Dummy Data</strong>!</h3>

        <p>You can Import the <strong>Dummy Data</strong> to get the taste of <strong>WPS Team</strong> Plugin quickly. You can <strong>remove</strong> the dummy data anytime you want, with just one click.</p>

        <p class="wpspeedo--notice-actions">
            <a href="<?php echo admin_url( 'edit.php?post_type=' . Utils::post_type_name() . '&page=wps-team#/tools/demo-import' ); ?>" class="button button-primary wps-team--notice-disable wps--notice-allow-default">Import Dummy Data</a>
            <a href="#" class="wps-team--notice-disable button button-flat"><span class="dashicons dashicons-dismiss"></span><?php echo _x( "Never show again", 'Settings: Tools', 'wpspeedo-team'); ?></a>
        </p>

        <?php

    }

    public function intervals() {
        return [ 0 ];
    }

}