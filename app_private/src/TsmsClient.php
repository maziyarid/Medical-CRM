<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class TsmsClient
{
    /** Error codes documented by Tooba/TSMS URL API. */
    private const ERRORS = [
        '1'  => 'خطا در سرورهای طوبی اس‌ام‌اس.',
        '2'  => 'متن UDH بیش از یک پیامک است.',
        '3'  => 'شماره همراه مقصد اشتباه است.',
        '4'  => 'متغیرهای ارسال نامعتبر هستند.',
        '5'  => 'متن پیامک خالی است.',
        '6'  => 'شماره همراه مقصد خالی است.',
        '7'  => 'نام کاربری یا گذرواژه TSMS اشتباه است.',
        '8'  => 'خطای موقت سرور پیامک؛ دوباره تلاش کنید.',
        '9'  => 'سرویس ارسال پیام کوتاه قطع است.',
        '14' => 'اعتبار پنل پیامک کافی نیست.',
    ];

    /** @return array{messageId:string,raw:string} */
    public function send(string $phone, string $message): array
    {
        $phone = Security::mobile($phone);
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('متن پیامک خالی است.');
        }

        if (strtolower((string) env('SMS_PROVIDER', 'tsms')) === 'demo') {
            app_log('demo_sms', ['phone' => $phone, 'message' => $message]);
            return ['messageId' => 'demo', 'raw' => 'demo'];
        }

        [$url, $username, $password, $from] = $this->configuration();
        $query = http_build_query([
            'from'     => $from,
            'to'       => $phone,
            'username' => $username,
            'password' => $password,
            'message'  => $message,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = Http::request(
            'GET',
            $url . (str_contains($url, '?') ? '&' : '?') . $query,
            ['headers' => ['Accept: text/plain,*/*'], 'timeout' => 30]
        );

        if ($response['error'] !== '') {
            throw new RuntimeException('خطای اتصال TSMS: ' . $response['error']);
        }
        if ($response['status'] < 200 || $response['status'] >= 400) {
            throw new RuntimeException('TSMS پاسخ HTTP ' . $response['status'] . ' برگرداند.');
        }

        $result = $this->parseResponse($response['body']);
        if (isset(self::ERRORS[$result])) {
            throw new RuntimeException(self::ERRORS[$result]);
        }
        if (str_starts_with($result, '-')) {
            throw new RuntimeException('TSMS خطای ' . $result . ' برگرداند.');
        }

        // The supplied TSMS documentation calls a successful result "smsid".
        // Accept a conservative printable identifier rather than assuming digits only.
        if (!preg_match('/^[0-9A-Za-z,._:\-]{1,190}$/', $result)) {
            throw new RuntimeException('پاسخ TSMS قابل تشخیص نبود: ' . mb_substr($result, 0, 80));
        }

        return ['messageId' => $result, 'raw' => $result];
    }

    public function credit(): string
    {
        [$url, $username, $password, $from] = $this->configuration();
        $query = http_build_query([
            'from'     => $from,
            'username' => $username,
            'password' => $password,
            'credit'   => 'what',
        ], '', '&', PHP_QUERY_RFC3986);

        $response = Http::request(
            'GET',
            $url . (str_contains($url, '?') ? '&' : '?') . $query,
            ['headers' => ['Accept: text/plain,*/*'], 'timeout' => 25]
        );
        if ($response['error'] !== '') {
            throw new RuntimeException('خطای اتصال TSMS: ' . $response['error']);
        }
        if ($response['status'] < 200 || $response['status'] >= 400) {
            throw new RuntimeException('TSMS پاسخ HTTP ' . $response['status'] . ' برگرداند.');
        }

        $result = $this->parseResponse($response['body']);
        if (isset(self::ERRORS[$result])) {
            throw new RuntimeException(self::ERRORS[$result]);
        }
        return $result;
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function configuration(): array
    {
        $url = trim((string) env('TSMS_API_URL', 'https://tsms.ir/url/tsmshttp.php'));
        $username = trim((string) env('TSMS_USERNAME', ''));
        $password = (string) env('TSMS_PASSWORD', '');
        $from = trim((string) env('TSMS_FROM', ''));

        if ($username === '' || str_starts_with($username, 'CHANGE_') ||
            $password === '' || str_starts_with($password, 'CHANGE_')) {
            throw new RuntimeException('نام کاربری یا گذرواژه TSMS در .env تنظیم نشده است.');
        }
        if ($from === '' || str_starts_with($from, 'CHANGE_')) {
            throw new RuntimeException('شماره خط ارسال‌کننده TSMS (TSMS_FROM) تنظیم نشده است.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (!in_array($scheme, ['https', 'http'], true) ||
            !in_array($host, ['tsms.ir', 'www.tsms.ir'], true) ||
            $path !== '/url/tsmshttp.php') {
            throw new RuntimeException('آدرس API TSMS معتبر نیست.');
        }

        return [$url, $username, $password, $from];
    }

    private function parseResponse(string $raw): string
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $result = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // The documented responses are one compact code/id. Remove surrounding whitespace,
        // CR/LF and accidental spaces introduced by HTML output.
        $result = preg_replace('/\s+/u', '', $result) ?? $result;
        if ($result === '') {
            throw new RuntimeException('پاسخ TSMS خالی بود.');
        }
        return $result;
    }
}
