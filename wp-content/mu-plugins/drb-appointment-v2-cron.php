<?php
/**
 * Plugin Name: DRB Appointment v2 Calendar Scheduler
 * Description: Runs the protected Calendar/outbox reconciliation every five minutes through WP-Cron.
 */

defined('ABSPATH') || exit;

add_filter('cron_schedules', function ($schedules) {
    if (!isset($schedules['drb_five_minutes'])) {
        $schedules['drb_five_minutes'] = array(
            'interval' => 5 * MINUTE_IN_SECONDS,
            'display' => 'Every five minutes (DRB appointments)',
        );
    }
    return $schedules;
});

add_action('init', function () {
    if (!wp_next_scheduled('drb_appointment_v2_calendar_sync')) {
        wp_schedule_event(time() + 90, 'drb_five_minutes', 'drb_appointment_v2_calendar_sync');
    }
});

add_action('drb_appointment_v2_calendar_sync', function () {
    if (!function_exists('drb_v2_dashboard_request')) {
        return;
    }
    $status = drb_v2_dashboard_request('GET', 'bookings/v2/status');
    if (is_wp_error($status) || empty($status['calendar_configured']) || empty($status['schema_ready'])) {
        return;
    }
    $result = drb_v2_dashboard_request('POST', 'bookings/v2/calendar-sync', array());
    if (is_wp_error($result)) {
        error_log('[DRB Appointment v2] calendar sync failed: ' . $result->get_error_message());
    }
});
