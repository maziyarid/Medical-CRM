<?php
declare(strict_types=1);

namespace App\Services;

/**
 * SmsService — stub / Phase B
 *
 * Minimal implementation using Kavenegar REST API.
 * Full provider chain (Ghasedak → FarazSMS → TSMS fallback) is
 * documented in BACKEND_PLAN.md §5.1 and is a Phase C deliverable.
 *
 * ENV keys required:
 *   KAVENEGAR_API_KEY   — your Kavenegar API key
 *   KAVENEGAR_SENDER    — sender line number (optional; default auto)
 */
final class SmsService
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
            error_log("[SmsService] KAVENEGAR_API_KEY not set — OTP $code not sent to $mobile");
            return;
        }

        $params = [
            'receptor' => $mobile,
            'token'    => $code,
            'template' => 'verify', // configure in Kavenegar panel
        ];

        $url = 'https://api.kavenegar.com/v1/' . urlencode($this->apiKey) . '/verify/lookup.json';
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
                'timeout' => 6,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        if ($response === false) {
            throw new \RuntimeException('Kavenegar API request failed');
        }

        $json = json_decode($response, true);
        $status = $json['return']['status'] ?? 0;
        if ((int)$status !== 200) {
            throw new \RuntimeException('Kavenegar returned status ' . $status . ': ' . $response);
        }
    }

    public function sendReminder(string $mobile, string $message): void
    {
        if ($this->apiKey === '') {
            error_log("[SmsService] KAVENEGAR_API_KEY not set — reminder not sent to $mobile");
            return;
        }

        $params = [
            'receptor' => $mobile,
            'message'  => $message,
            'sender'   => $this->sender ?: null,
        ];
        // Remove null values
        $params = array_filter($params, fn($v) => $v !== null);

        $url = 'https://api.kavenegar.com/v1/' . urlencode($this->apiKey) . '/sms/send.json';
        $ctx = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => http_build_query($params),
                'timeout' => 6,
            ],
        ]);

        @file_get_contents($url, false, $ctx);
    }
}
