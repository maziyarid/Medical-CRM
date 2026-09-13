<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Server-side payment adapter for the two clinic gateways.
 *
 * Gateway callbacks are never trusted as proof of payment. The caller must
 * persist the amount/authority first, then call verify() and only transition
 * the booking after a successful server-to-server verification response.
 */
final class PaymentGatewayService
{
    public const ZARINPAL = 'zarinpal';
    public const VANDAR = 'vandar';

    public function isConfigured(string $gateway): bool
    {
        return match ($gateway) {
            self::ZARINPAL => $this->env('ZARINPAL_MERCHANT_ID') !== '',
            self::VANDAR => $this->env('VANDAR_API_KEY') !== '',
            default => false,
        };
    }

    /**
     * @return array{ok:bool,authority:?string,redirect_url:?string,payload:array,error:?string,retryable:bool}
     */
    public function create(
        string $gateway,
        int $amountRial,
        string $callbackUrl,
        string $mobile,
        string $email,
        string $description
    ): array {
        if ($amountRial < 1 || !$this->isConfigured($gateway)) {
            return $this->failure('gateway_not_configured', false);
        }

        return match ($gateway) {
            self::ZARINPAL => $this->createZarinpal($amountRial, $callbackUrl, $mobile, $email, $description),
            self::VANDAR => $this->createVandar($amountRial, $callbackUrl, $mobile, $description),
            default => $this->failure('unsupported_gateway', false),
        };
    }

    /**
     * @return array{ok:bool,already_verified:bool,reference_id:?string,payload:array,error:?string,retryable:bool}
     */
    public function verify(string $gateway, string $authority, int $amountRial): array
    {
        if ($authority === '' || $amountRial < 1 || !$this->isConfigured($gateway)) {
            return $this->verifyFailure('invalid_payment_verification', false);
        }

        return match ($gateway) {
            self::ZARINPAL => $this->verifyZarinpal($authority, $amountRial),
            self::VANDAR => $this->verifyVandar($authority),
            default => $this->verifyFailure('unsupported_gateway', false),
        };
    }

    private function createZarinpal(int $amount, string $callback, string $mobile, string $email, string $description): array
    {
        $body = [
            'merchant_id' => $this->env('ZARINPAL_MERCHANT_ID'),
            'amount' => $amount,
            'description' => mb_substr($description, 0, 255),
            'callback_url' => $callback,
            'metadata' => array_filter(['mobile' => $mobile, 'email' => $email]),
        ];
        $r = $this->postJson('https://api.zarinpal.com/pg/v4/payment/request.json', $body);
        $code = (int)($r['json']['data']['code'] ?? 0);
        $authority = trim((string)($r['json']['data']['authority'] ?? ''));
        if ($r['transport_ok'] && $code === 100 && $authority !== '') {
            return [
                'ok' => true,
                'authority' => $authority,
                'redirect_url' => 'https://www.zarinpal.com/pg/StartPay/' . rawurlencode($authority),
                'payload' => $r['json'],
                'error' => null,
                'retryable' => false,
            ];
        }
        return $this->failure($this->gatewayError($r, 'zarinpal_request_failed'), $r['retryable'], $r['json']);
    }

    private function verifyZarinpal(string $authority, int $amount): array
    {
        $r = $this->postJson('https://api.zarinpal.com/pg/v4/payment/verify.json', [
            'merchant_id' => $this->env('ZARINPAL_MERCHANT_ID'),
            'amount' => $amount,
            'authority' => $authority,
        ]);
        $code = (int)($r['json']['data']['code'] ?? 0);
        if ($r['transport_ok'] && ($code === 100 || $code === 101)) {
            return [
                'ok' => true,
                'already_verified' => $code === 101,
                'reference_id' => isset($r['json']['data']['ref_id']) ? (string)$r['json']['data']['ref_id'] : null,
                'payload' => $r['json'],
                'error' => null,
                'retryable' => false,
            ];
        }
        return $this->verifyFailure($this->gatewayError($r, 'zarinpal_verify_failed'), $r['retryable'], $r['json']);
    }

    private function createVandar(int $amount, string $callback, string $mobile, string $description): array
    {
        $body = [
            'api_key' => $this->env('VANDAR_API_KEY'),
            'amount' => $amount,
            'callback_url' => $callback,
            'mobile_number' => $mobile,
            'description' => mb_substr($description, 0, 255),
        ];
        $r = $this->postJson('https://ipg.vandar.io/api/v4/send', $body);
        $status = $r['json']['status'] ?? null;
        $token = trim((string)($r['json']['token'] ?? $r['json']['data']['token'] ?? ''));
        if ($r['transport_ok'] && in_array($status, [1, true, '1', 'true'], true) && $token !== '') {
            return [
                'ok' => true,
                'authority' => $token,
                'redirect_url' => 'https://ipg.vandar.io/v4/' . rawurlencode($token),
                'payload' => $r['json'],
                'error' => null,
                'retryable' => false,
            ];
        }
        return $this->failure($this->gatewayError($r, 'vandar_request_failed'), $r['retryable'], $r['json']);
    }

    private function verifyVandar(string $token): array
    {
        $r = $this->postJson('https://ipg.vandar.io/api/v4/verify', [
            'api_key' => $this->env('VANDAR_API_KEY'),
            'token' => $token,
        ]);
        $status = $r['json']['status'] ?? null;
        if ($r['transport_ok'] && in_array($status, [1, true, '1', 'true'], true)) {
            $data = is_array($r['json']['data'] ?? null) ? $r['json']['data'] : $r['json'];
            $reference = $data['ref_id'] ?? $data['tracking_code'] ?? $data['transaction_id'] ?? $data['transId'] ?? null;
            return [
                'ok' => true,
                'already_verified' => false,
                'reference_id' => $reference !== null ? (string)$reference : null,
                'payload' => $r['json'],
                'error' => null,
                'retryable' => false,
            ];
        }
        return $this->verifyFailure($this->gatewayError($r, 'vandar_verify_failed'), $r['retryable'], $r['json']);
    }

    /** @return array{transport_ok:bool,http_status:int,json:array,retryable:bool,error:?string} */
    private function postJson(string $url, array $body): array
    {
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['transport_ok' => false, 'http_status' => 0, 'json' => [], 'retryable' => false, 'error' => 'json_encode_failed'];
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => 'Bastaninejad-Medical-CRM/1.0',
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = $errno ? curl_error($ch) : null;
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($raw === false || $errno !== 0) {
            return ['transport_ok' => false, 'http_status' => $status, 'json' => [], 'retryable' => true, 'error' => 'transport_error'];
        }
        $decoded = json_decode((string)$raw, true);
        $decoded = is_array($decoded) ? $decoded : [];
        $transportOk = $status >= 200 && $status < 300;
        return [
            'transport_ok' => $transportOk,
            'http_status' => $status,
            'json' => $decoded,
            'retryable' => $status === 0 || $status === 408 || $status === 429 || $status >= 500,
            'error' => $error,
        ];
    }

    private function gatewayError(array $response, string $fallback): string
    {
        $json = $response['json'] ?? [];
        $message = $json['errors']['message'] ?? $json['errors']['code'] ?? $json['message'] ?? $json['error'] ?? null;
        return is_scalar($message) && (string)$message !== '' ? mb_substr((string)$message, 0, 180) : $fallback;
    }

    /** @return array{ok:false,authority:null,redirect_url:null,payload:array,error:string,retryable:bool} */
    private function failure(string $error, bool $retryable, array $payload = []): array
    {
        return ['ok' => false, 'authority' => null, 'redirect_url' => null, 'payload' => $payload, 'error' => $error, 'retryable' => $retryable];
    }

    /** @return array{ok:false,already_verified:false,reference_id:null,payload:array,error:string,retryable:bool} */
    private function verifyFailure(string $error, bool $retryable, array $payload = []): array
    {
        return ['ok' => false, 'already_verified' => false, 'reference_id' => null, 'payload' => $payload, 'error' => $error, 'retryable' => $retryable];
    }

    private function env(string $key): string
    {
        $value = trim((string)($_ENV[$key] ?? ''));
        return str_starts_with($value, 'CHANGE_ME') ? '' : $value;
    }
}
