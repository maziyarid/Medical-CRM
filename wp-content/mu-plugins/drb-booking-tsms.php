<?php
/**
 * Plugin Name: DRB Booking TSMS
 * Description: After a successful /booking form submit, send confirmation SMS via the working app TSMS client.
 */
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

add_filter('rest_post_dispatch', 'drb_booking_tsms_after_appointment', 20, 3);

function drb_booking_tsms_after_appointment($response, $server, $request)
{
    unset($server);
    if (!($request instanceof WP_REST_Request) || !($response instanceof WP_REST_Response)) {
        return $response;
    }
    if ($request->get_route() !== '/drb/v1/appointment') {
        return $response;
    }
    if ($response->get_status() >= 400) {
        return $response;
    }

    $payload = $response->get_data();
    if (!is_array($payload) || empty($payload['success'])) {
        return $response;
    }

    $already = sanitize_key((string) ($payload['sms_status'] ?? ($payload['data']['sms_status'] ?? '')));
    if (in_array($already, array('sent', 'delivered', 'success', 'successful'), true)) {
        return $response;
    }

    $result = drb_booking_tsms_send($request, $payload);
    if (!is_array($result)) {
        return $response;
    }

    $payload['sms_status'] = $result['sms_status'] ?? 'failed';
    if (!empty($result['success'])) {
        $payload['message'] = 'درخواست نوبت ثبت شد و پیامک تأیید ارسال شد.';
    } elseif (empty($payload['message'])) {
        $payload['message'] = 'درخواست نوبت ثبت شد، اما ارسال پیامک تأیید ناموفق بود.';
    }
    $response->set_data($payload);
    return $response;
}

function drb_booking_tsms_send(WP_REST_Request $request, array $payload): array
{
    $phone = drb_booking_tsms_normalize_phone((string) ($request->get_param('phone') ?: $request->get_param('mobile') ?: ''));
    if ($phone === '') {
        return array('success' => false, 'sms_status' => 'failed');
    }

    $name = sanitize_text_field((string) ($request->get_param('name') ?: $request->get_param('firstName') ?: $request->get_param('first_name') ?: ''));
    $template = function_exists('drb_booking_sms_template')
        ? (string) drb_booking_sms_template()
        : 'درخواست نوبت شما ثبت شد. همکاران کلینیک برای هماهنگی تماس می‌گیرند. پنل بیمار: {login_url}';
    $login_url = home_url('/');
    $endpoint = defined('DRB_APP_SMS_NOTIFY_URL') && DRB_APP_SMS_NOTIFY_URL
        ? (string) DRB_APP_SMS_NOTIFY_URL
        : 'https://app.drbastaninejad.com/api/sms/notify.php';

    $headers = array('Content-Type' => 'application/json; charset=utf-8');
    $secret = '';
    if (defined('DRB_BOOKING_SMS_SECRET') && DRB_BOOKING_SMS_SECRET) {
        $secret = (string) DRB_BOOKING_SMS_SECRET;
    } elseif (function_exists('drb_booking_bridge_secret')) {
        $secret = (string) drb_booking_bridge_secret();
    } elseif (defined('DRB_WORDPRESS_BRIDGE_SECRET') && DRB_WORDPRESS_BRIDGE_SECRET) {
        $secret = (string) DRB_WORDPRESS_BRIDGE_SECRET;
    }
    if ($secret !== '') {
        $headers['X-Booking-Sms-Secret'] = $secret;
        $headers['X-WordPress-Bridge-Secret'] = $secret;
    }

    $response = wp_remote_post($endpoint, array(
        'timeout' => 12,
        'redirection' => 0,
        'sslverify' => true,
        'headers' => $headers,
        'body' => wp_json_encode(array(
            'phone' => $phone,
            'name' => $name,
            'sms_template' => $template,
            'login_url' => $login_url,
            'source' => 'wordpress_booking',
            'reference' => $payload['submissionId'] ?? ($payload['data']['id'] ?? ''),
        )),
    ));

    if (is_wp_error($response)) {
        return array('success' => false, 'sms_status' => 'failed');
    }
    $decoded = json_decode((string) wp_remote_retrieve_body($response), true);
    if (!is_array($decoded) || empty($decoded['success'])) {
        return array('success' => false, 'sms_status' => 'failed');
    }
    return array('success' => true, 'sms_status' => 'sent');
}

function drb_booking_tsms_normalize_phone(string $raw): string
{
    $map = array('۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9');
    $digits = preg_replace('/\D+/', '', strtr($raw, $map));
    if (!is_string($digits) || $digits === '') {
        return '';
    }
    if (str_starts_with($digits, '0098')) {
        $digits = substr($digits, 4);
    } elseif (str_starts_with($digits, '98')) {
        $digits = substr($digits, 2);
    }
    if (strlen($digits) === 10 && str_starts_with($digits, '9')) {
        $digits = '0' . $digits;
    }
    return preg_match('/^09\d{9}$/', $digits) ? $digits : '';
}
