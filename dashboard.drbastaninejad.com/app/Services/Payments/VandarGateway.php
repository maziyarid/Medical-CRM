<?php
declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class VandarGateway implements AppointmentPaymentGateway
{
    private string $apiKey;
    private HttpJsonClient $http;

    public function __construct(?HttpJsonClient $http = null)
    {
        $this->apiKey = trim((string)($_ENV['VANDAR_API_KEY'] ?? $_ENV['VANDAR_API_TOKEN'] ?? ''));
        $this->http = $http ?? new HttpJsonClient();
    }

    public function request(int $amountRials, string $callbackUrl, array $context): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('Vandar is not configured');
        }
        if ($amountRials < 10000) {
            throw new RuntimeException('Vandar amount must be at least 10000 rials');
        }
        $payload = array_filter([
            'amount' => $amountRials,
            'callback_url' => $callbackUrl,
            'mobile' => $context['mobile'] ?? null,
            'national_code' => $context['national_code'] ?? null,
            'checkout_number' => isset($context['checkout_number']) ? (string)$context['checkout_number'] : null,
            'description' => mb_substr((string)($context['description'] ?? 'رزرو نوبت کلینیک دکتر شاهین بستانی نژاد'), 0, 255),
        ], static fn($v) => $v !== null && $v !== '');
        $response = $this->http->request('POST', 'https://api.vandar.io/pbv/v1/send', $payload, ['x-api-key' => $this->apiKey]);
        $body = $response['body'];
        $token = trim((string)($body['data']['token'] ?? ''));
        if ($response['status'] < 200 || $response['status'] >= 300 || $token === '') {
            throw new RuntimeException((string)($body['message'] ?? 'Vandar payment request failed'));
        }
        return ['authority' => $token, 'redirect_url' => $this->redirectUrl($token), 'raw' => $body];
    }

    public function verify(string $authority, int $amountRials, array $callback): array
    {
        $callbackStatus = strtoupper(trim((string)($callback['status'] ?? '')));
        if ($callbackStatus !== 'SUCCEED') {
            return ['verified' => false, 'reference' => null, 'code' => $callbackStatus ?: 'FAILED', 'raw' => $callback];
        }
        if ($this->apiKey === '') {
            throw new RuntimeException('Vandar is not configured');
        }
        $response = $this->http->request('POST', 'https://api.vandar.io/pbv/v1/verify', [
            'checkout_id' => $authority,
        ], ['x-api-key' => $this->apiKey]);
        $verified = $response['status'] >= 200 && $response['status'] < 300;
        return [
            'verified' => $verified,
            'reference' => $verified ? $authority : null,
            'code' => $verified ? 'SUCCEED' : (string)($response['body']['code'] ?? 'VERIFY_FAILED'),
            'raw' => $response['body'],
        ];
    }

    public function redirectUrl(string $authority): string
    {
        return 'https://pbv.vandar.io/checkouts/' . rawurlencode($authority);
    }
}
