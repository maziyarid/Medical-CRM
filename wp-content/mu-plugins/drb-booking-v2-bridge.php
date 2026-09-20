<?php
/**
 * DRB Booking v2 continuation bridge.
 * Keeps WordPress as the same-origin public UI while dashboard.drbastaninejad.com
 * remains authoritative for patient sessions, slot holds and payments.
 */
defined('ABSPATH') || exit;

function drb_v2_nonce_ok(WP_REST_Request $request): bool {
    if (function_exists('drb_verify_public_form_nonce')) return drb_verify_public_form_nonce($request);
    $nonce = (string)$request->get_header('X-DRB-Form-Nonce');
    return $nonce !== '' && wp_verify_nonce($nonce, 'drb_public_form');
}

function drb_v2_api_base(): string {
    return function_exists('drb_dashboard_api_base') ? drb_dashboard_api_base() : 'https://dashboard.drbastaninejad.com/api/v1';
}

function drb_v2_bridge_secret(): string {
    if (function_exists('drb_booking_bridge_secret')) return drb_booking_bridge_secret();
    return (string)getenv('WORDPRESS_BRIDGE_SECRET');
}

function drb_v2_remote(string $method, string $path, ?array $body = null, array $headers = array()) {
    $url = drb_v2_api_base() . $path;
    $args = array(
        'method' => $method,
        'timeout' => 20,
        'redirection' => 0,
        'sslverify' => true,
        'reject_unsafe_urls' => true,
        'httpversion' => '1.1',
        'headers' => array_merge(array('Accept' => 'application/json'), $headers),
    );
    if (null !== $body) {
        $args['headers']['Content-Type'] = 'application/json; charset=utf-8';
        $args['body'] = wp_json_encode($body);
    }
    $response = wp_remote_request($url, $args);
    if (is_wp_error($response)) {
        return new WP_Error('booking_v2_transport', 'ارتباط با سامانه نوبت‌دهی برقرار نشد.', array('status' => 503));
    }
    $status = (int)wp_remote_retrieve_response_code($response);
    $decoded = json_decode((string)wp_remote_retrieve_body($response), true);
    if (!is_array($decoded)) {
        return new WP_Error('booking_v2_invalid_response', 'پاسخ سامانه نوبت‌دهی معتبر نبود.', array('status' => 502));
    }
    if ($status < 200 || $status >= 300 || empty($decoded['ok'])) {
        $message = (string)($decoded['errors'][0]['message'] ?? 'عملیات نوبت‌دهی انجام نشد.');
        return new WP_Error('booking_v2_rejected', sanitize_text_field($message), array('status' => $status >= 400 ? $status : 502));
    }
    return new WP_REST_Response(array('success' => true, 'data' => $decoded['data'] ?? array()), $status);
}

add_filter('rest_request_after_callbacks', function($response, $handler, $request) {
    if (!($request instanceof WP_REST_Request) || '/drb/v1/appointment' !== $request->get_route()) return $response;
    if (is_wp_error($response)) return $response;
    $rest = rest_ensure_response($response);
    $status = $rest->get_status();
    $data = $rest->get_data();
    if ($status < 200 || $status >= 300 || !is_array($data) || empty($data['success']) || empty($data['bookingId'])) return $response;
    $params = (array)$request->get_json_params();
    $mobile = function_exists('drb_normalize_mobile') ? drb_normalize_mobile($params['mobile'] ?? '') : preg_replace('/\D+/', '', (string)($params['mobile'] ?? ''));
    if (!$mobile) return $response;
    try { $token = bin2hex(random_bytes(32)); }
    catch (Throwable $e) { return $response; }
    set_transient('drb_v2_continue_' . hash('sha256', $token), array(
        'booking_id' => (int)$data['bookingId'],
        'mobile' => $mobile,
    ), 15 * MINUTE_IN_SECONDS);
    $data['continuationToken'] = $token;
    $data['continuationExpiresIn'] = 900;
    $rest->set_data($data);
    return $rest;
}, 10, 3);

add_action('rest_api_init', function() {
    register_rest_route('drb/v1', '/booking-v2/availability', array(
        'methods' => WP_REST_Server::READABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            $qs = array();
            foreach (array('from','to') as $key) {
                $v = sanitize_text_field((string)$request->get_param($key));
                if ($v !== '') $qs[$key] = $v;
            }
            $path = '/appointment-bookings/availability' . ($qs ? '?' . http_build_query($qs, '', '&', PHP_QUERY_RFC3986) : '');
            return drb_v2_remote('GET', $path);
        },
    ));

    register_rest_route('drb/v1', '/booking-v2/eligibility', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            if (!drb_v2_nonce_ok($request)) return new WP_Error('invalid_nonce','نشست فرم منقضی شده است.',array('status'=>403));
            $in = (array)$request->get_json_params();
            $nationalId = sanitize_text_field((string)($in['nationalId'] ?? $in['national_id'] ?? ''));
            if ($nationalId === '') return new WP_Error('invalid_identifier','کد ملی معتبر نیست.',array('status'=>422));
            $secret = drb_v2_bridge_secret();
            if ($secret === '') return new WP_Error('bridge_unconfigured','سامانه نوبت‌دهی موقتاً در دسترس نیست.',array('status'=>503));
            return drb_v2_remote('POST', '/bookings/eligibility', array('national_id'=>$nationalId), array('X-WordPress-Bridge-Secret'=>$secret));
        },
    ));

    register_rest_route('drb/v1', '/booking-v2/session', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            if (!drb_v2_nonce_ok($request)) return new WP_Error('invalid_nonce','نشست فرم منقضی شده است.',array('status'=>403));
            $in = (array)$request->get_json_params();
            $token = sanitize_text_field((string)($in['continuationToken'] ?? ''));
            if (!preg_match('/^[a-f0-9]{64}$/', $token)) return new WP_Error('invalid_continuation','نشست ادامه رزرو معتبر نیست.',array('status'=>401));
            $key = 'drb_v2_continue_' . hash('sha256', $token);
            $state = get_transient($key);
            if (!is_array($state)) return new WP_Error('expired_continuation','مهلت انتخاب نوبت تمام شده است؛ فرم را دوباره ارسال کنید.',array('status'=>410));
            $secret = drb_v2_bridge_secret();
            if ($secret === '') return new WP_Error('bridge_unconfigured','سامانه نوبت‌دهی موقتاً در دسترس نیست.',array('status'=>503));
            $result = drb_v2_remote('POST', '/bookings/session', array(
                'booking_id' => (int)$state['booking_id'],
                'mobile' => (string)$state['mobile'],
            ), array('X-WordPress-Bridge-Secret' => $secret));
            if (!is_wp_error($result)) delete_transient($key);
            return $result;
        },
    ));

    register_rest_route('drb/v1', '/booking-v2/resume', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            if (!drb_v2_nonce_ok($request)) return new WP_Error('invalid_nonce','نشست فرم منقضی شده است.',array('status'=>403));
            $in = (array)$request->get_json_params();
            $resume = sanitize_text_field((string)($in['resumeToken'] ?? ''));
            if ($resume === '' || strlen($resume) > 200) return new WP_Error('invalid_resume','لینک تکمیل نوبت معتبر نیست.',array('status'=>422));
            $secret = drb_v2_bridge_secret();
            if ($secret === '') return new WP_Error('bridge_unconfigured','سامانه نوبت‌دهی موقتاً در دسترس نیست.',array('status'=>503));
            return drb_v2_remote('POST', '/bookings/resume', array('resume_token'=>$resume), array('X-WordPress-Bridge-Secret'=>$secret));
        },
    ));

    register_rest_route('drb/v1', '/booking-v2/hold', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            if (!drb_v2_nonce_ok($request)) return new WP_Error('invalid_nonce','نشست فرم منقضی شده است.',array('status'=>403));
            $in = (array)$request->get_json_params();
            $session = sanitize_text_field((string)($in['sessionToken'] ?? ''));
            if ($session === '') return new WP_Error('missing_session','نشست بیمار معتبر نیست.',array('status'=>401));
            return drb_v2_remote('POST', '/appointment-bookings/holds', array(
                'open_day_id' => (int)($in['openDayId'] ?? 0),
                'start_at' => sanitize_text_field((string)($in['startAt'] ?? '')),
                'gateway' => 'zarinpal',
                'intake_id' => (int)($in['intakeId'] ?? 0),
            ), array('Authorization' => 'Bearer ' . $session));
        },
    ));

    register_rest_route('drb/v1', '/booking-v2/payment', array(
        'methods' => WP_REST_Server::CREATABLE,
        'permission_callback' => '__return_true',
        'callback' => function(WP_REST_Request $request) {
            if (!drb_v2_nonce_ok($request)) return new WP_Error('invalid_nonce','نشست فرم منقضی شده است.',array('status'=>403));
            $in = (array)$request->get_json_params();
            $session = sanitize_text_field((string)($in['sessionToken'] ?? ''));
            $bookingId = (int)($in['bookingId'] ?? 0);
            if ($session === '' || $bookingId < 1) return new WP_Error('invalid_payment_session','اطلاعات پرداخت معتبر نیست.',array('status'=>401));
            return drb_v2_remote('POST', '/appointment-bookings/' . $bookingId . '/payment', array(), array('Authorization' => 'Bearer ' . $session));
        },
    ));
});
