<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SmsProviderChain — chain-of-responsibility SMS dispatcher
 *
 * Tries providers in order: Kavenegar → Ghasedak → FarazSMS → TSMS → Log.
 * Never throws — if all providers fail, LogSmsProvider writes to error_log.
 * Configured via .env SMS_PROVIDERS (comma-separated list of provider IDs).
 */
final class SmsProviderChain
{
    /**
     * Send an OTP SMS. Returns true if a provider accepted the message.
     */
    public function sendOtp(string $mobile, string $code): bool
    {
        $providers = $this->buildChain();
        foreach ($providers as $provider) {
            try {
                if ($provider->sendOtp($mobile, $code)) {
                    return true;
                }
            } catch (\Throwable $e) {
                error_log('[SmsProviderChain] provider ' . get_class($provider) . ' threw: ' . $e->getMessage());
            }
        }
        // Final fallback already logged by LogSmsProvider; belt-and-suspenders log here.
        error_log('[SmsProviderChain] all providers exhausted for ' . $mobile);
        return false;
    }

    // -------------------------------------------------------------------------
    // Chain construction from SMS_PROVIDERS env var
    // -------------------------------------------------------------------------

    /** @return SmsProviderInterface[] */
    private function buildChain(): array
    {
        $names = array_filter(array_map(
            'trim',
            explode(',', $_ENV['SMS_PROVIDERS'] ?? 'log')
        ));

        $map = [
            'kavenegar' => KavenegarSmsProvider::class,
            'ghasedak'  => GhasedakSmsProvider::class,
            'farazsms'  => FarazSmsSmsProvider::class,
            'tsms'      => TsmsSmsProvider::class,
            'log'       => LogSmsProvider::class,
        ];

        $chain = [];
        foreach ($names as $name) {
            $class = $map[strtolower($name)] ?? null;
            if ($class !== null) {
                $chain[] = new $class();
            }
        }

        // Always end with LogSmsProvider if not already present
        $lastClass = end($chain) ? get_class(end($chain)) : '';
        if ($lastClass !== LogSmsProvider::class) {
            $chain[] = new LogSmsProvider();
        }

        return $chain;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Provider interface
// ─────────────────────────────────────────────────────────────────────────────

interface SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool;
}

// ─────────────────────────────────────────────────────────────────────────────
// Kavenegar
// ─────────────────────────────────────────────────────────────────────────────

final class KavenegarSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool
    {
        $apiKey   = $_ENV['KAVENEGAR_API_KEY']  ?? '';
        $sender   = $_ENV['KAVENEGAR_SENDER']   ?? '';
        if ($apiKey === '') {
            return false;
        }

        $message = "کد تایید شما: {$code}\nاعتبار: ۵ دقیقه";
        $url  = "https://api.kavenegar.com/v1/{$apiKey}/sms/send.json";
        $body = http_build_query([
            'receptor' => $mobile,
            'message'  => $message,
            'sender'   => $sender,
        ]);

        $ctx = stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/x-www-form-urlencoded', 'content' => $body, 'timeout' => 6],
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return false;
        }
        $json = json_decode($response, true);
        return isset($json['return']['status']) && (int)$json['return']['status'] === 200;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Ghasedak
// ─────────────────────────────────────────────────────────────────────────────

final class GhasedakSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool
    {
        $apiKey   = $_ENV['GHASEDAK_API_KEY']   ?? '';
        $template = $_ENV['GHASEDAK_TEMPLATE']  ?? 'verify';
        $line     = $_ENV['GHASEDAK_LINE']      ?? '';
        if ($apiKey === '') {
            return false;
        }

        $url  = 'https://api.ghasedak.me/v2/verification/send/simple';
        $body = http_build_query([
            'receptor' => $mobile,
            'type'     => 1,
            'template' => $template,
            'param1'   => $code,
            'linenumber' => $line,
        ]);

        $ctx = stream_context_create([
            'http' => ['method' => 'POST', 'header' => ["apikey: {$apiKey}", 'Content-Type: application/x-www-form-urlencoded'], 'content' => $body, 'timeout' => 6],
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return false;
        }
        $json = json_decode($response, true);
        return isset($json['result']['code']) && (int)$json['result']['code'] === 200;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// FarazSMS
// ─────────────────────────────────────────────────────────────────────────────

final class FarazSmsSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool
    {
        $username = $_ENV['FARAZSMS_USERNAME'] ?? '';
        $password = $_ENV['FARAZSMS_PASSWORD'] ?? '';
        $from     = $_ENV['FARAZSMS_FROM']     ?? '';
        if ($username === '') {
            return false;
        }

        $message = "کد تایید: {$code}";
        $url  = 'https://ippanel.com/api/select';
        $body = json_encode([
            'op'       => 'send',
            'uname'    => $username,
            'pass'     => $password,
            'message'  => $message,
            'from'     => $from,
            'to'       => [$mobile],
        ]);

        $ctx = stream_context_create([
            'http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $body, 'timeout' => 6],
        ]);
        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            return false;
        }
        $json = json_decode($response, true);
        return is_array($json) && isset($json[0]) && (int)$json[0] > 0;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// TSMS
// ─────────────────────────────────────────────────────────────────────────────

final class TsmsSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool
    {
        $username = $_ENV['TSMS_USERNAME'] ?? '';
        $password = $_ENV['TSMS_PASSWORD'] ?? '';
        $from     = $_ENV['TSMS_FROM']     ?? '';
        if ($username === '') {
            return false;
        }

        $message = "کد تایید: {$code}";
        $url = sprintf(
            'https://www.tsms.ir/api/send?username=%s&password=%s&from=%s&to=%s&text=%s',
            urlencode($username), urlencode($password), urlencode($from),
            urlencode($mobile), urlencode($message)
        );

        $ctx = stream_context_create(['http' => ['timeout' => 6]]);
        $response = @file_get_contents($url, false, $ctx);
        return $response !== false && str_contains((string)$response, 'OK');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// Log (final fallback — never returns false)
// ─────────────────────────────────────────────────────────────────────────────

final class LogSmsProvider implements SmsProviderInterface
{
    public function sendOtp(string $mobile, string $code): bool
    {
        error_log("[LogSmsProvider] OTP {$code} → {$mobile} (all real providers failed or unconfigured)");
        return true; // Consumed the message — chain stops here
    }
}
