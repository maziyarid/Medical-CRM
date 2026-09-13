<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Chain-of-responsibility SMS dispatcher with typed results.
 *
 * A provider is successful only when it returns a validated provider response
 * (HTTP + body). Absence of an exception is never treated as delivery.
 *
 * Provider order:
 *   1. SMS_PROVIDERS env (comma-separated), if set
 *   2. Otherwise auto-detect from configured credentials (tsms first)
 *
 * Usage:
 *   $r = (new SmsProviderChain())->sendMessage($mobile, $message);
 *   // $r = ['ok'=>bool,'provider'=>?string,'message_id'=>?string,'error'=>?string]
 */
final class SmsProviderChain
{
    /** @var SmsProvider[] */
    private array $providers;

    public function __construct(?array $providers = null)
    {
        if ($providers !== null) {
            $this->providers = $providers;
            return;
        }

        $order = $this->resolveOrder();
        $this->providers = [];
        foreach ($order as $name) {
            $provider = $this->makeProvider(strtolower($name));
            if ($provider !== null) {
                $this->providers[] = $provider;
            }
        }
        if ((($_ENV['APP_ENV'] ?? 'production') !== 'production') && $this->providers === []) {
            $this->providers[] = new LogSmsProvider();
        }
    }

    /** @return array{ok:bool,provider:?string,message_id:?string,error:?string} */
    public function sendOtp(string $mobile, string $code): array
    {
        return $this->dispatch($mobile, $code, true);
    }

    /** @return array{ok:bool,provider:?string,message_id:?string,error:?string} */
    public function sendReminder(string $mobile, string $message): array
    {
        return $this->dispatch($mobile, $message, false);
    }

    /** @return array{ok:bool,provider:?string,message_id:?string,error:?string} */
    public function sendMessage(string $mobile, string $message): array
    {
        return $this->sendReminder($mobile, $message);
    }

    /** @return array{ok:bool,provider:?string,message_id:?string,error:?string} */
    private function dispatch(string $mobile, string $payload, bool $otp): array
    {
        if ($this->providers === []) {
            return ['ok' => false, 'provider' => null, 'message_id' => null, 'error' => 'No SMS provider configured'];
        }

        $lastError = null;
        foreach ($this->providers as $provider) {
            $short = (new \ReflectionClass($provider))->getShortName();
            try {
                $result = $otp ? $provider->sendOtp($mobile, $payload) : $provider->sendReminder($mobile, $payload);
                if (!is_array($result) || empty($result['ok'])) {
                    $lastError = is_array($result) ? (string)($result['error'] ?? 'provider rejected') : 'invalid provider result';
                    error_log('[SmsProviderChain] ' . $short . ' rejected: ' . $lastError);
                    continue;
                }
                return [
                    'ok' => true,
                    'provider' => $short,
                    'message_id' => isset($result['message_id']) ? (string)$result['message_id'] : null,
                    'error' => null,
                ];
            } catch (\Throwable $e) {
                $lastError = $e->getMessage();
                error_log('[SmsProviderChain] ' . $short . ' failed: ' . $lastError);
            }
        }
        error_log('[SmsProviderChain] All configured SMS providers failed for ' . substr($mobile, 0, 4) . '***');
        return ['ok' => false, 'provider' => null, 'message_id' => null, 'error' => $lastError ?? 'All SMS providers failed'];
    }

    /** @return list<string> */
    private function resolveOrder(): array
    {
        $configured = array_values(array_filter(array_map('trim', explode(',', (string)($_ENV['SMS_PROVIDERS'] ?? '')))));
        if ($configured !== []) {
            return $configured;
        }
        $auto = [];
        if (trim((string)($_ENV['TSMS_USERNAME'] ?? '')) !== '' && trim((string)($_ENV['TSMS_PASSWORD'] ?? '')) !== '') {
            $auto[] = 'tsms';
        }
        if (trim((string)($_ENV['KAVENEGAR_API_KEY'] ?? '')) !== '') {
            $auto[] = 'kavenegar';
        }
        if (trim((string)($_ENV['GHASEDAK_API_KEY'] ?? '')) !== '') {
            $auto[] = 'ghasedak';
        }
        if (trim((string)($_ENV['FARAZSMS_USERNAME'] ?? '')) !== '' && trim((string)($_ENV['FARAZSMS_PASSWORD'] ?? '')) !== '') {
            $auto[] = 'farazsms';
        }
        return $auto;
    }

    private function makeProvider(string $name): ?SmsProvider
    {
        return match ($name) {
            'kavenegar' => new KavenegarSmsProvider(),
            'ghasedak'  => new GhasedakSmsProvider(),
            'farazsms'  => new FarazSmsSmsProvider(),
            'tsms'      => new TsmsSmsProvider(),
            'log'       => (($_ENV['APP_ENV'] ?? 'production') !== 'production') ? new LogSmsProvider() : null,
            default     => null,
        };
    }
}

interface SmsProvider
{
    /** @return array{ok:bool,message_id:?string,error:?string} */
    public function sendOtp(string $mobile, string $code): array;

    /** @return array{ok:bool,message_id:?string,error:?string} */
    public function sendReminder(string $mobile, string $message): array;
}

final class SmsHttp
{
    /**
     * POST a body. Never place secrets in the URL.
     *
     * @param array<string,string> $headers
     * @return array{status:int,body:string}
     */
    public static function post(string $url, string $body, array $headers = [], int $timeout = 12): array
    {
        $headerLines = [];
        foreach ($headers as $name => $value) {
            $headerLines[] = $name . ': ' . $value;
        }
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new \RuntimeException('SMS HTTP client unavailable');
            }
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => $headerLines,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);
            $resp = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                throw new \RuntimeException('SMS HTTP request failed: ' . $err);
            }
            return ['status' => $status, 'body' => (string)$resp];
        }

        $ctx = stream_context_create(['http' => [
            'method' => 'POST',
            'header' => implode("\r\n", $headerLines),
            'content' => $body,
            'timeout' => $timeout,
            'ignore_errors' => true,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('SMS HTTP request failed (network)');
        }
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
        return ['status' => $status, 'body' => (string)$resp];
    }
}

final class KavenegarSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): array
    {
        $apiKey = trim((string)($_ENV['KAVENEGAR_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'KAVENEGAR_API_KEY not set'];
        }
        $url = 'https://api.kavenegar.com/v1/' . rawurlencode($apiKey) . '/verify/lookup.json';
        $body = http_build_query(['receptor' => $mobile, 'token' => $code, 'template' => $_ENV['KAVENEGAR_OTP_TEMPLATE'] ?? 'verify']);
        return $this->parse(SmsHttp::post($url, $body, ['Content-Type' => 'application/x-www-form-urlencoded']));
    }

    public function sendReminder(string $mobile, string $message): array
    {
        $apiKey = trim((string)($_ENV['KAVENEGAR_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'KAVENEGAR_API_KEY not set'];
        }
        $params = ['receptor' => $mobile, 'message' => $message];
        $sender = trim((string)($_ENV['KAVENEGAR_SENDER'] ?? ''));
        if ($sender !== '') {
            $params['sender'] = $sender;
        }
        $url = 'https://api.kavenegar.com/v1/' . rawurlencode($apiKey) . '/sms/send.json';
        return $this->parse(SmsHttp::post($url, http_build_query($params), ['Content-Type' => 'application/x-www-form-urlencoded']));
    }

    /** @param array{status:int,body:string} $http */
    private function parse(array $http): array
    {
        $json = json_decode($http['body'], true);
        $status = (int)($json['return']['status'] ?? 0);
        if ($http['status'] >= 400 || $status !== 200) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Kavenegar status ' . $status];
        }
        $messageId = $json['entries'][0]['messageid'] ?? $json['entries'][0]['messageId'] ?? null;
        if ($messageId === null || $messageId === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'Kavenegar missing message id'];
        }
        return ['ok' => true, 'message_id' => (string)$messageId, 'error' => null];
    }
}

final class GhasedakSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): array
    {
        $apiKey = trim((string)($_ENV['GHASEDAK_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'GHASEDAK_API_KEY not set'];
        }
        $body = http_build_query([
            'receptor' => $mobile,
            'type' => 1,
            'template' => $_ENV['GHASEDAK_TEMPLATE'] ?? 'verify',
            'param1' => $code,
        ]);
        $http = SmsHttp::post(
            'https://api.ghasedak.me/v2/verification/send/simple',
            $body,
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Apikey' => $apiKey]
        );
        return $this->parse($http);
    }

    public function sendReminder(string $mobile, string $message): array
    {
        $apiKey = trim((string)($_ENV['GHASEDAK_API_KEY'] ?? ''));
        if ($apiKey === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'GHASEDAK_API_KEY not set'];
        }
        $body = http_build_query([
            'receptor' => $mobile,
            'message' => $message,
            'linenumber' => $_ENV['GHASEDAK_LINE'] ?? '',
        ]);
        $http = SmsHttp::post(
            'https://api.ghasedak.me/v2/sms/send/simple',
            $body,
            ['Content-Type' => 'application/x-www-form-urlencoded', 'Apikey' => $apiKey]
        );
        return $this->parse($http);
    }

    /** @param array{status:int,body:string} $http */
    private function parse(array $http): array
    {
        $json = json_decode($http['body'], true);
        $code = (int)($json['result']['code'] ?? 0);
        if ($http['status'] >= 400 || $code !== 200) {
            return ['ok' => false, 'message_id' => null, 'error' => 'Ghasedak code ' . $code];
        }
        $messageId = $json['items'][0] ?? $json['result']['messageid'] ?? null;
        return ['ok' => true, 'message_id' => $messageId !== null ? (string)$messageId : 'ghasedak', 'error' => null];
    }
}

final class FarazSmsSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): array
    {
        return $this->sendReminder($mobile, 'کد تأیید شما: ' . $code);
    }

    public function sendReminder(string $mobile, string $message): array
    {
        $user = trim((string)($_ENV['FARAZSMS_USERNAME'] ?? ''));
        $pass = trim((string)($_ENV['FARAZSMS_PASSWORD'] ?? ''));
        if ($user === '' || $pass === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'FARAZSMS credentials not set'];
        }
        $payload = json_encode([
            'op' => 'send',
            'uname' => $user,
            'pass' => $pass,
            'message' => $message,
            'from' => $_ENV['FARAZSMS_FROM'] ?? '',
            'to' => [$mobile],
        ], JSON_UNESCAPED_UNICODE);
        $http = SmsHttp::post('https://ippanel.com/api/select', (string)$payload, ['Content-Type' => 'application/json']);
        if ($http['status'] >= 400) {
            return ['ok' => false, 'message_id' => null, 'error' => 'FarazSMS HTTP ' . $http['status']];
        }
        $decoded = json_decode($http['body'], true);
        if (is_array($decoded) && isset($decoded[0]) && is_numeric($decoded[0]) && (int)$decoded[0] < 0) {
            return ['ok' => false, 'message_id' => null, 'error' => 'FarazSMS rejected: ' . $decoded[0]];
        }
        $id = is_array($decoded) ? ($decoded['messageids'][0] ?? $decoded[0] ?? 'farazsms') : trim($http['body']);
        if ($id === '' || $id === false) {
            return ['ok' => false, 'message_id' => null, 'error' => 'FarazSMS empty response'];
        }
        return ['ok' => true, 'message_id' => (string)$id, 'error' => null];
    }
}

final class TsmsSmsProvider implements SmsProvider
{
    /** Error codes documented by the TSMS URL API. */
    private const ERRORS = [
        '1'  => 'TSMS server error',
        '2'  => 'TSMS UDH/message length error',
        '3'  => 'TSMS invalid destination mobile',
        '4'  => 'TSMS invalid send parameters',
        '5'  => 'TSMS empty message',
        '6'  => 'TSMS empty destination mobile',
        '7'  => 'TSMS username or password is invalid',
        '8'  => 'TSMS temporary server error',
        '9'  => 'TSMS sending service is disabled',
        '14' => 'TSMS account credit is insufficient',
    ];

    public function sendOtp(string $mobile, string $code): array
    {
        return $this->sendReminder($mobile, 'کد تأیید: ' . $code);
    }

    public function sendReminder(string $mobile, string $message): array
    {
        $user = trim((string)($_ENV['TSMS_USERNAME'] ?? ''));
        $pass = trim((string)($_ENV['TSMS_PASSWORD'] ?? ''));
        $from = trim((string)($_ENV['TSMS_FROM'] ?? ''));
        if ($user === '' || $pass === '' || $from === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS username/password/from are not configured'];
        }

        // Canonical production contract: TSMS URL API. TSMS_URL is accepted only
        // as a backwards-compatible fallback for servers that have not renamed it yet.
        $endpoint = trim((string)($_ENV['TSMS_API_URL'] ?? ''));
        if ($endpoint === '') {
            $endpoint = trim((string)($_ENV['TSMS_URL'] ?? ''));
        }
        if ($endpoint === '') {
            $endpoint = 'https://tsms.ir/url/tsmshttp.php';
        }

        $scheme = strtolower((string)parse_url($endpoint, PHP_URL_SCHEME));
        $host = strtolower((string)parse_url($endpoint, PHP_URL_HOST));
        $path = (string)parse_url($endpoint, PHP_URL_PATH);
        if (!in_array($scheme, ['http', 'https'], true)
            || !in_array($host, ['tsms.ir', 'www.tsms.ir'], true)
            || $path !== '/url/tsmshttp.php') {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS_API_URL is invalid'];
        }

        $query = http_build_query([
            'from' => $from,
            'to' => $mobile,
            'username' => $user,
            'password' => $pass,
            'message' => trim($message),
        ], '', '&', PHP_QUERY_RFC3986);

        try {
            $http = $this->get($endpoint . (str_contains($endpoint, '?') ? '&' : '?') . $query, $scheme);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message_id' => null, 'error' => $e->getMessage()];
        }

        if ($http['status'] < 200 || $http['status'] >= 400) {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS HTTP ' . $http['status']];
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $http['body']) ?? $http['body'];
        $result = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $result = preg_replace('/\s+/u', '', $result) ?? $result;
        if ($result === '') {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS empty response'];
        }
        if (isset(self::ERRORS[$result])) {
            return ['ok' => false, 'message_id' => null, 'error' => self::ERRORS[$result]];
        }
        if (str_starts_with($result, '-')) {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS rejected with code ' . $result];
        }
        if (!preg_match('/^[0-9A-Za-z,._:\-]{1,190}$/', $result)) {
            return ['ok' => false, 'message_id' => null, 'error' => 'TSMS returned an unrecognised response'];
        }

        return ['ok' => true, 'message_id' => $result, 'error' => null];
    }

    /** @return array{status:int,body:string} */
    private function get(string $url, string $scheme): array
    {
        $timeout = 20;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                throw new \RuntimeException('TSMS HTTP client unavailable');
            }
            $protocols = CURLPROTO_HTTPS;
            if (defined('CURLPROTO_HTTP')) {
                $protocols |= CURLPROTO_HTTP;
            }
            curl_setopt_array($ch, [
                CURLOPT_HTTPGET => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => $timeout,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_PROTOCOLS => $protocols,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => ['Accept: text/plain,*/*'],
            ]);
            $resp = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);
            if ($resp === false) {
                throw new \RuntimeException('TSMS connection failed: ' . $err);
            }
            return ['status' => $status, 'body' => (string)$resp];
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Accept: text/plain,*/*\r\n",
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('TSMS connection failed');
        }
        $status = 0;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
            $status = (int)$m[1];
        }
        return ['status' => $status, 'body' => (string)$resp];
    }
}

final class LogSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): array
    {
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env === 'production') {
            return ['ok' => false, 'message_id' => null, 'error' => 'LogSmsProvider disabled in production'];
        }
        error_log('[LogSmsProvider][' . $env . '] OTP for ' . substr($mobile, 0, 4) . '***');
        return ['ok' => true, 'message_id' => 'log-' . bin2hex(random_bytes(4)), 'error' => null];
    }

    public function sendReminder(string $mobile, string $message): array
    {
        unset($message);
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env === 'production') {
            return ['ok' => false, 'message_id' => null, 'error' => 'LogSmsProvider disabled in production'];
        }
        error_log('[LogSmsProvider][' . $env . '] message for ' . substr($mobile, 0, 4) . '***');
        return ['ok' => true, 'message_id' => 'log-' . bin2hex(random_bytes(4)), 'error' => null];
    }
}
