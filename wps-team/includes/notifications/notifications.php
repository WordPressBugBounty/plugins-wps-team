<?php

namespace WPSpeedo_Team;

if ( ! defined('ABSPATH') ) exit;

class Notifications {

    use Date_Methods;

    public $manager;

    public $conflict_days = 5;

    public $slug;

    public function __construct() {

        $this->manager = new Notification_Manager();

        $this->slug = 'wps_team';

        add_action('wps_team_display_notice', [$this, 'display_notice'], 10, 2);
        add_action('wps_team_display_popup', [$this, 'display_popup'], 10, 2);

        add_action('wp_ajax_wps_team_notification_action', [$this, 'notification_action']);
        add_action( 'in_admin_header', [ $this, 'init_notifications' ], 9999999999 );
    }

    public function init_notifications() {

        global $parent_file;

        if ( $parent_file === 'edit.php?post_type=wps-team-members' ) {
            remove_all_actions( 'admin_notices' );
            remove_all_actions( 'all_admin_notices' );
        }

        add_action('admin_notices', [$this, 'setup_notifications'], 999999999999);
    }

    public function notification_action() {

        $this->check_security();

        $action_type = sanitize_key($_REQUEST['action_type']);
        $notification_type = sanitize_key($_REQUEST['notification_type']);
        $trigger_time = sanitize_text_field($_REQUEST['trigger_time']);

        $exec_notifications = $this->manager->get_exec_notifications($trigger_time, $notification_type);

        // No Executable Notifications found
        if (empty($exec_notifications)) die(0);

        $count = 0;

        foreach ($exec_notifications as $index => $notification) {

            if ($index == 0) {

                if ($action_type == 'disable') $notification->is_active = false;
                $notification->fire($trigger_time, $notification_type)->save();
            } else {

                $count++;
                $notification->maybe_delay($this->date_increment($trigger_time, $this->conflict_days * $count))->save();
            }
        }

        die(0);
    }

    public function check_security() {
        check_ajax_referer('wps_team_notification_nonce');
    }

    public function setup_notifications_by_type( $type ) {

        // $trigger_time should be today
        $trigger_time = $this->current_time();

        // Block if necessary
        $notification_last_fired = get_option("wps_team_{$type}_last_interact");
        if ($notification_last_fired) {
            $notification_enable_date = $this->date_increment($notification_last_fired, $this->conflict_days);
            if ($this->date_is_prev($trigger_time, $notification_enable_date)) return;
        }

        // Get Executable Notifications
        $exec_notifications = $this->manager->get_exec_notifications($trigger_time, $type);

        // No Executable Notifications found
        if (empty($exec_notifications)) return;

        $notification = $exec_notifications[0];

        do_action("wps_team_display_{$type}", $notification, $trigger_time);
    }

    public function setup_notifications() {
        if ( wps_team_fs()->is_activation_mode() ) return;
        $this->setup_notifications_by_type('notice');
        $this->setup_notifications_by_type('popup');
    }

    public function display_notice( $notice, $trigger_time ) {

        $notice->notice_header();
        $notice->notice_content();
        $notice->notice_footer();

        $notice->core_style();

        $notice->core_script($trigger_time);

    }

    public function display_popup( $popup, $trigger_time ) {
    }
}
