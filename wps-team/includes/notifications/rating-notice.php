<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Rating_Notice extends Notice {

    public function notice_content() {

        ?>

        <h3 class="notice-title">Enjoying <strong>WPS Team</strong> Plugin!</h3>

        <p>Hey - We hope you are satisfied with our plugin. If you have any issues or questions please leave us a support message <a href="https://wordpress.org/support/plugin/wps-team/" target="_blank">here</a>.<br>If you like us, please consider leaving a positive review to spread the word, It is important for us to improve the plugin more.<br>Thank you so much - <b>WPS Team</b></p>

        <p class="wpspeedo--notice-actions">

            <a class="button button-primary rate-plugin-button wps-team--notice-disable wps--notice-allow-default" href="<?php echo esc_url( $this->plugin_rate_url() ); ?>" rel="nofollow" target="_blank">
                <?php echo esc_html__('Rate WPS Team', 'wpspeedo-team'); ?>
            </a>
    
            <a class="button button-flat wps-team--notice-dismiss" href="#">
                <span class="dashicons dashicons-clock"></span>
                <?php echo esc_html__('Remind me later', 'wpspeedo-team'); ?>
            </a>
    
            <a class="button button-flat wps-team--notice-disable" href="#">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php echo esc_html__('I already did', 'wpspeedo-team'); ?>
            </a>

        </p>


        <?php

    }

    public function plugin_rate_url() {
        return 'https://wordpress.org/support/plugin/wps-team/reviews/?rate=5#new-post';
    }

    public function intervals() {
        return [ 10, 20, 30, 40, 50, 60, 70, 80 ];
    }

}