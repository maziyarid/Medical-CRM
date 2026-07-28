<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SmsProviderChain — Phase C
 *
 * Chain-of-responsibility SMS dispatcher.
 * Tries each provider in order; moves to the next if the current one throws.
 *
 * Provider order (configurable via SMS_PROVIDERS env var, comma-separated):
 *   kavenegar → ghasedak → farazsms → tsms
 *
 * Each provider must implement the SmsProvider interface below.
 * The existing SmsService (Kavenegar-only stub) is wrapped as the first link.
 *
 * Usage:
 *   (new SmsProviderChain())->sendOtp($mobile, $code);
 *   (new SmsProviderChain())->sendReminder($mobile, $message);
 */
final class SmsProviderChain
{
    /** @var SmsProvider[] */
    private array $providers;

    public function __construct()
    {
        $order = array_filter(
            array_map('trim', explode(',', $_ENV['SMS_PROVIDERS'] ?? 'kavenegar'))
        );

        $this->providers = [];
        foreach ($order as $name) {
            $provider = $this->makeProvider($name);
            if ($provider !== null) {
                $this->providers[] = $provider;
            }
        }

        // Always fall back to the log provider so OTPs surface in error_log
        // when no real provider is configured (development / staging).
        $this->providers[] = new LogSmsProvider();
    }

    public function sendOtp(string $mobile, string $code): void
    {
        $lastErr = null;
        foreach ($this->providers as $provider) {
            try {
                $provider->sendOtp($mobile, $code);
                return; // success — stop chain
            } catch (\Throwable $e) {
                $lastErr = $e;
                error_log('[SmsProviderChain] ' . get_class($provider) . ' failed: ' . $e->getMessage());
            }
        }
        // All providers failed — still don't crash; OTP is logged by LogSmsProvider
        error_log('[SmsProviderChain] All SMS providers failed for ' . substr($mobile, 0, 7) . '***');
    }

    public function sendReminder(string $mobile, string $message): void
    {
        $lastErr = null;
        foreach ($this->providers as $provider) {
            try {
                $provider->sendReminder($mobile, $message);
                return;
            } catch (\Throwable $e) {
                $lastErr = $e;
                error_log('[SmsProviderChain] ' . get_class($provider) . ' sendReminder failed: ' . $e->getMessage());
            }
        }
    }

    // -------------------------------------------------------------------------

    private function makeProvider(string $name): ?SmsProvider
    {
        return match ($name) {
            'kavenegar' => new KavenegarSmsProvider(),
            'ghasedak'  => new GhasedakSmsProvider(),
            'farazsms'  => new FarazSmsSmsProvider(),
            'tsms'      => new TsmsSmsProvider(),
            default     => null,
        };
    }
}

// =============================================================================
// Interface
// =============================================================================

interface SmsProvider
{
    public function sendOtp(string $mobile, string $code): void;
    public function sendReminder(string $mobile, string $message): void;
}

// =============================================================================
// Kavenegar — primary (wraps the existing SmsService stub)
// =============================================================================

final class KavenegarSmsProvider implements SmsProvider
{
    private string $apiKey;
    private string $sender;

    public function __construct()
    {
        $this->apiKey = $_ENV['KAVENEGAR_API_KEY'] ?? '';
        $this->sender = $_ENV['KAVENEGAR_SENDER']  ?? '';
    }

    public function sendOtp(string $mobile, string $code): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('KAVENEGAR_API_KEY not set');
        }

        $url  = 'https://api.kavenegar.com/v1/' . urlencode($this->apiKey) . '/verify/lookup.json';
        $body = http_build_query(['receptor' => $mobile, 'token' => $code, 'template' => 'verify']);
        $this->post($url, $body);
    }

    public function sendReminder(string $mobile, string $message): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('KAVENEGAR_API_KEY not set');
        }

        $params = ['receptor' => $mobile, 'message' => $message];
        if ($this->sender !== '') {
            $params['sender'] = $this->sender;
        }
        $url = 'https://api.kavenegar.com/v1/' . urlencode($this->apiKey) . '/sms/send.json';
        $this->post($url, http_build_query($params));
    }

    private function post(string $url, string $body): void
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => $body,
            'timeout' => 6,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('Kavenegar request failed (network)');
        }
        $json   = json_decode($resp, true);
        $status = (int)($json['return']['status'] ?? 0);
        if ($status !== 200) {
            throw new \RuntimeException("Kavenegar status $status: $resp");
        }
    }
}

// =============================================================================
// Ghasedak — fallback #1
// ENV: GHASEDAK_API_KEY
// =============================================================================

final class GhasedakSmsProvider implements SmsProvider
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = $_ENV['GHASEDAK_API_KEY'] ?? '';
    }

    public function sendOtp(string $mobile, string $code): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GHASEDAK_API_KEY not set');
        }

        $url  = 'https://api.ghasedak.me/v2/verification/send/simple';
        $body = http_build_query([
            'receptor' => $mobile,
            'type'     => 1,
            'template' => $_ENV['GHASEDAK_TEMPLATE'] ?? 'verify',
            'param1'   => $code,
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nApikey: {$this->apiKey}",
            'content' => $body,
            'timeout' => 6,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('Ghasedak request failed (network)');
        }
        $json = json_decode($resp, true);
        if (($json['result']['code'] ?? 0) !== 200) {
            throw new \RuntimeException('Ghasedak error: ' . $resp);
        }
    }

    public function sendReminder(string $mobile, string $message): void
    {
        if ($this->apiKey === '') {
            throw new \RuntimeException('GHASEDAK_API_KEY not set');
        }

        $url  = 'https://api.ghasedak.me/v2/sms/send/simple';
        $body = http_build_query([
            'receptor' => $mobile,
            'message'  => $message,
            'linenumber' => $_ENV['GHASEDAK_LINE'] ?? '',
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\nApikey: {$this->apiKey}",
            'content' => $body,
            'timeout' => 6,
        ]]);
        @file_get_contents($url, false, $ctx);
    }
}

// =============================================================================
// FarazSMS — fallback #2
// ENV: FARAZSMS_USERNAME, FARAZSMS_PASSWORD, FARAZSMS_FROM
// =============================================================================

final class FarazSmsSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): void
    {
        $user = $_ENV['FARAZSMS_USERNAME'] ?? '';
        $pass = $_ENV['FARAZSMS_PASSWORD'] ?? '';
        if ($user === '' || $pass === '') {
            throw new \RuntimeException('FARAZSMS credentials not set');
        }

        $url  = 'https://ippanel.com/api/select';
        $body = json_encode([
            'op'      => 'send',
            'uname'   => $user,
            'pass'    => $pass,
            'message' => "کد تأیید شما: $code",
            'from'    => $_ENV['FARAZSMS_FROM'] ?? '',
            'to'      => [$mobile],
        ]);
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/json',
            'content' => $body,
            'timeout' => 6,
        ]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('FarazSMS request failed (network)');
        }
    }

    public function sendReminder(string $mobile, string $message): void
    {
        // Same endpoint — reuse sendOtp logic with arbitrary message
        $this->sendOtp($mobile, $message);
    }
}

// =============================================================================
// TSMS — fallback #3
// ENV: TSMS_USERNAME, TSMS_PASSWORD, TSMS_FROM
// =============================================================================

final class TsmsSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): void
    {
        $user = $_ENV['TSMS_USERNAME'] ?? '';
        $pass = $_ENV['TSMS_PASSWORD'] ?? '';
        if ($user === '' || $pass === '') {
            throw new \RuntimeException('TSMS credentials not set');
        }

        $url = 'https://www.tsms.ir/tsms/wsrv.ashx?' . http_build_query([
            'action'  => 'send',
            'usr'     => $user,
            'pwd'     => $pass,
            'msg'     => "کد تأیید: $code",
            'from'    => $_ENV['TSMS_FROM'] ?? '',
            'to'      => $mobile,
        ]);
        $ctx = stream_context_create(['http' => ['timeout' => 6]]);
        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false) {
            throw new \RuntimeException('TSMS request failed (network)');
        }
    }

    public function sendReminder(string $mobile, string $message): void
    {
        $this->sendOtp($mobile, $message);
    }
}

// =============================================================================
// LogSmsProvider — always-last safety net (dev / no-keys mode)
// Logs OTP to PHP error_log so developers can find it during testing.
// Never throws — ensures the chain always ends cleanly.
// =============================================================================

final class LogSmsProvider implements SmsProvider
{
    public function sendOtp(string $mobile, string $code): void
    {
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env !== 'production') {
            // Safe to log in dev/staging — never log in production
            error_log("[LogSmsProvider][{$env}] OTP for $mobile: $code");
        }
    }

    public function sendReminder(string $mobile, string $message): void
    {
        $env = $_ENV['APP_ENV'] ?? 'production';
        if ($env !== 'production') {
            error_log("[LogSmsProvider][{$env}] Reminder for $mobile: $message");
        }
    }
}
