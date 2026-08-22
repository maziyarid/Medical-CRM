<?php
/**
 * Plugin Name: DRB Booking SMS (disabled fallback)
 * Description: Intentionally inert. WordPress booking SMS is owned by the dashboard BookingController. This MU plugin exists only so a previously corrupted file cannot take down WordPress.
 * Version: 2.0.0
 *
 * WP-RECOVERY / SMS-01:
 * The previous rest_post_dispatch fallback caused a production PHP parse error
 * (unexpected identifier DRB_APP_SMS_NOTIFY_URL) and duplicated SMS ownership.
 * Dashboard POST /api/v1/bookings is the single idempotent SMS owner.
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// Do not hook rest_post_dispatch. Do not send SMS from WordPress.
