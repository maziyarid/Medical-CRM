<?php
/**
 * Plugin Name: DRB Appointment v2 Public Readiness
 * Description: Adds non-secret payment readiness to the same-origin availability response.
 */

defined('ABSPATH') || exit;

add_filter('rest_post_dispatch', function ($response, $server, $request) {
    if (!$request instanceof WP_REST_Request || '/drb/v1/appointment-v2/availability' !== $request->get_route()) {
        return $response;
    }
    if (!is_object($response) || !method_exists($response, 'get_data') || !method_exists($response, 'set_data')) {
        return $response;
    }
    $body = $response->get_data();
    if (!is_array($body) || empty($body['success']) || !function_exists('drb_v2_dashboard_request')) {
        return $response;
    }

    $status = drb_v2_dashboard_request('GET', 'bookings/v2/status');
    if (is_wp_error($status)) {
        $body['payment'] = array('ready' => false, 'amountRials' => 0, 'gateways' => array());
        $response->set_data($body);
        return $response;
    }

    $gateways = array();
    foreach (array('zarinpal', 'vandar') as $gateway) {
        if (!empty($status['gateways'][$gateway])) {
            $gateways[] = $gateway;
        }
    }
    $body['payment'] = array(
        'ready' => !empty($status['public_booking_ready']) && !empty($gateways),
        'amountRials' => absint($status['deposit_rials'] ?? 0),
        'gateways' => $gateways,
    );
    $response->set_data($body);
    return $response;
}, 30, 3);
