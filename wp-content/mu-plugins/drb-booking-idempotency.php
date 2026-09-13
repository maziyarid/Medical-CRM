<?php
/**
 * Preserve a booking submission UUID across ambiguous WordPress -> dashboard
 * retries without changing the live app.drbastaninejad.com patient application.
 */
defined('ABSPATH') || exit;

add_filter('http_request_args', static function (array $args, string $url): array {
    $path = (string) wp_parse_url($url, PHP_URL_PATH);
    if (!str_ends_with(rtrim($path, '/'), '/api/v1/bookings')) {
        return $args;
    }

    $headers = $args['headers'] ?? array();
    $hasBridgeHeader = false;
    foreach ((array) $headers as $name => $value) {
        if (0 === strcasecmp((string) $name, 'X-WordPress-Bridge-Secret')) {
            $hasBridgeHeader = true;
            break;
        }
    }
    if (!$hasBridgeHeader || empty($args['body']) || !is_string($args['body'])) {
        return $args;
    }

    $payload = json_decode($args['body'], true);
    if (!is_array($payload)) {
        return $args;
    }
    $mobile = preg_replace('/\D+/', '', (string) ($payload['mobile'] ?? ''));
    if ($mobile === '') {
        return $args;
    }

    $identity = $mobile . '|' . (string) ($payload['national_id'] ?? '') . '|' . (string) ($payload['procedure_key'] ?? $payload['procedure'] ?? '');
    $transientKey = 'drb_booking_uuid_' . substr(hash('sha256', $identity), 0, 40);
    $uuid = (string) get_transient($transientKey);
    if ($uuid === '') {
        $uuid = wp_generate_uuid4();
        // The existing timeout safety lock is 10 minutes. Keep the UUID slightly
        // longer so a retry after that ambiguity window resolves idempotently,
        // while remaining shorter than the normal 30-minute booking cooldown.
        set_transient($transientKey, $uuid, 15 * MINUTE_IN_SECONDS);
    }

    $payload['submission_uuid'] = $uuid;
    $args['body'] = wp_json_encode($payload);
    $args['headers']['X-DRB-Submission-UUID'] = $uuid;
    return $args;
}, 20, 2);
