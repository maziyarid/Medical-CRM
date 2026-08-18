<?php
declare(strict_types=1);

require dirname(__DIR__) . '/_bootstrap.php';
require_post();

try {
    $data = request_json(20000);
    assert_booking_sms_authorized();

    $phone = App\Security::mobile((string) ($data['phone'] ?? $data['mobile'] ?? ''));
    assert_booking_sms_rate_limit($phone);

    $name = App\Security::text($data['name'] ?? $data['firstName'] ?? $data['first_name'] ?? '', 60);
    $loginUrl = App\Security::text($data['login_url'] ?? env('APP_URL', 'https://app.drbastaninejad.com'), 180);
    $template = App\Security::text(
        $data['message'] ?? $data['sms_template'] ?? 'درخواست نوبت شما ثبت شد. همکاران کلینیک برای هماهنگی تماس می‌گیرند. پنل بیمار: {login_url}',
        800
    );
    $message = strtr($template, [
        '{name}' => $name !== '' ? $name : 'مراجع',
        '{login_url}' => $loginUrl,
    ]);

    $sent = (new App\TsmsClient())->send($phone, $message);
    app_log('booking_sms_sent', ['phone' => $phone, 'message_id' => $sent['messageId'] ?? '']);
    json_response([
        'success' => true,
        'sms_status' => 'sent',
        'messageId' => $sent['messageId'] ?? '',
    ]);
} catch (Throwable $e) {
    app_log('booking_sms_error', ['error' => $e->getMessage()]);
    json_response(['success' => false, 'sms_status' => 'failed', 'message' => $e->getMessage()], 422);
}

function assert_booking_sms_authorized(): void
{
    $provided = (string) (
        $_SERVER['HTTP_X_BOOKING_SMS_SECRET']
        ?? $_SERVER['HTTP_X_WORDPRESS_BRIDGE_SECRET']
        ?? ''
    );
    $accepted = array_values(array_filter([
        (string) env('BOOKING_SMS_SECRET', ''),
        (string) env('WORDPRESS_BRIDGE_SECRET', ''),
    ]));

    if ($accepted !== []) {
        foreach ($accepted as $secret) {
            if ($provided !== '' && hash_equals($secret, $provided)) {
                return;
            }
        }
        json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $remote = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $server = (string) ($_SERVER['SERVER_ADDR'] ?? '');
    $allowed = ['127.0.0.1', '::1'];
    if ($server !== '') {
        $allowed[] = $server;
    }
    if (!in_array($remote, $allowed, true)) {
        json_response(['success' => false, 'message' => 'Unauthorized'], 401);
    }
}

function assert_booking_sms_rate_limit(string $phone): void
{
    $dir = APP_ROOT . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $file = $dir . '/booking-sms-' . hash('sha256', $phone) . '.json';
    $now = time();
    $last = 0;
    if (is_file($file)) {
        $raw = json_decode((string) file_get_contents($file), true);
        $last = (int) ($raw['ts'] ?? 0);
    }
    if ($last > 0 && ($now - $last) < 120) {
        throw new RuntimeException('پیامک تأیید به‌تازگی ارسال شده است.');
    }
    @file_put_contents($file, json_encode(['ts' => $now], JSON_UNESCAPED_UNICODE), LOCK_EX);
}
