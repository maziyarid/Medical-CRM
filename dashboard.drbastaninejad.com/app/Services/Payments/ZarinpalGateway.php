<?php
declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class ZarinpalGateway implements AppointmentPaymentGateway
{
    private string $merchantId;
    private HttpJsonClient $http;

    public function __construct(?HttpJsonClient $http = null)
    {
        $this->merchantId = trim((string)($_ENV['ZARINPAL_MERCHANT_ID'] ?? ''));
        $this->http = $http ?? new HttpJsonClient();
    }

    public function request(int $amountRials, string $callbackUrl, array $context): array
    {
        if ($this->merchantId === '') {
            throw new RuntimeException('Zarinpal is not configured');
        }
        $payload = [
            'merchant_id' => $this->merchantId,
            'amount' => $amountRials,
            'callback_url' => $callbackUrl,
            'description' => mb_substr((string)($context['description'] ?? 'رزرو نوبت کلینیک دکتر شاهین بستانی نژاد'), 0, 255),
            'metadata' => array_filter([
                'mobile' => $context['mobile'] ?? null,
                'email' => $context['email'] ?? null,
            ], static fn($v) => $v !== null && $v !== ''),
        ];
        $response = $this->http->request('POST', 'https://api.zarinpal.com/pg/v4/payment/request.json', $payload);
        $body = $response['body'];
        $code = (int)($body['data']['code'] ?? 0);
        $authority = trim((string)($body['data']['authority'] ?? ''));
        if ($response['status'] < 200 || $response['status'] >= 300 || $code !== 100 || $authority === '') {
            $message = (string)($body['errors']['message'] ?? 'Zarinpal payment request failed');
            throw new RuntimeException($message);
        }
        return ['authority' => $authority, 'redirect_url' => $this->redirectUrl($authority), 'raw' => $body];
    }

    public function verify(string $authority, int $amountRials, array $callback): array
    {
        $callbackStatus = strtoupper(trim((string)($callback['Status'] ?? $callback['status'] ?? '')));
        if ($callbackStatus !== 'OK') {
            return ['verified' => false, 'reference' => null, 'code' => $callbackStatus ?: 'CANCELLED', 'raw' => $callback];
        }
        if ($this->merchantId === '') {
            throw new RuntimeException('Zarinpal is not configured');
        }
        $response = $this->http->request('POST', 'https://api.zarinpal.com/pg/v4/payment/verify.json', [
            'merchant_id' => $this->merchantId,
            'amount' => $amountRials,
            'authority' => $authority,
        ]);
        $body = $response['body'];
        $code = (int)($body['data']['code'] ?? 0);
        $verified = $response['status'] >= 200 && $response['status'] < 300 && in_array($code, [100, 101], true);
        return [
            'verified' => $verified,
            'reference' => $verified ? (string)($body['data']['ref_id'] ?? $authority) : null,
            'code' => $code !== 0 ? (string)$code : (string)($body['errors']['code'] ?? ''),
            'raw' => $body,
        ];
    }

    public function redirectUrl(string $authority): string
    {
        return 'https://www.zarinpal.com/pg/StartPay/' . rawurlencode($authority);
    }
}
