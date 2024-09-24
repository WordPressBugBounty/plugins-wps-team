<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

abstract class Notice extends Notification {

    public $type = 'notice';

    final public function get_key() {
        return 'wps_team_notice_' . $this->get_id();
    }

    public function notice_header() {
        ?>
        <div class="wpspeedo--notice wpspeedo-team--notice wpspeedo-team--<?php echo esc_attr( $this->get_id() ); ?>">
            <div class="wpspeedo--notice-inner">
                <div class="wpspeedo--notice-col logo-area"><img src="<?php echo WPS_TEAM_URL . 'images/thumbnail.svg'; ?>" alt=""></div>
                <div class="wpspeedo--notice-col content-area">
        <?php
    }

    abstract public function notice_content();

    public function notice_footer() {
        echo "</div></div></div>";
    }

    public function core_script( $trigger_time ) {
        
        ?>

        <script>

            function wps_team_notice_action( evt, $this, action_type ) {

                if ( ! $this.hasClass('wps--notice-allow-default') && evt ) evt.preventDefault();

                $this.closest('.wpspeedo-team--notice').slideUp(200);

                jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'wps_team_notification_action',
                    _wpnonce: '<?php echo wp_create_nonce( 'wps_team_notification_nonce' ) ?>',
                    action_type: action_type,
                    notification_type: 'notice',
                    trigger_time: '<?php echo esc_html( $trigger_time ); ?>'
                });

            }

            // Notice Dismiss
            jQuery('body').on('click', '.wpspeedo-team--notice .wps-team--notice-dismiss', function(evt) {
                wps_team_notice_action(evt, jQuery(this), 'dismiss');
            });

            // Notice Disable
            jQuery('body').on('click', '.wpspeedo-team--notice .wps-team--notice-disable', function(evt) {
                wps_team_notice_action(evt, jQuery(this), 'disable');
            });

        </script>

        <?php

    }

    public function core_style() {

        ?>

        <style>
            .wpspeedo--notice {
                margin: 20px 20px 20px 0;
            }
            .wpspeedo--notice-inner {
                display: flex;
                width: 100%;
                padding: 24px;
                gap: 30px;
                box-sizing: border-box;
                background: #fff;
                border-radius: 4px;
                box-shadow: 0px 2px 4px #d4d6e340;
            }
            .wpspeedo--notice .logo-area {
                display: flex;
                justify-content: center;
            }
            .wpspeedo--notice .logo-area img {
                width: 110px;
            }
            .wpspeedo--notice .notice-title {
                font-size: 24px;
                margin-top: 0px;
                margin-bottom: 6px;
                color: #1d2327;
                font-weight: 400;
            }
            .wpspeedo--notice .content-area p {
                font-size: 14px;
                line-height: 1.7;
            }
            .wpspeedo--notice .content-area .wpspeedo--notice-actions {
                margin-top: 16px;
                margin-bottom: 0px;
                display: flex;
                gap: 16px;
            }
            .wpspeedo--notice .content-area .button {
                display: inline-flex;
                justify-content: center;
                align-items: center;
                gap: 4px;
            }
            .wpspeedo--notice .content-area .button-flat {
                border: none !important;
                box-shadow: none !important;
                background: none !important;
                padding-left: 0;
                padding-right: 0;
                outline: none !important;
                color: #4c9d6b;
            }
            .wpspeedo--notice .content-area .button-flat:hover {
                color: #238b4b;
            }
        </style>

        <?php
    }

}