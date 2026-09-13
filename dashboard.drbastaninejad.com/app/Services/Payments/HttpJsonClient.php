<?php
declare(strict_types=1);

namespace App\Services\Payments;

use RuntimeException;

final class HttpJsonClient
{
    /** @return array{status:int,body:array<string,mixed>} */
    public function request(string $method, string $url, ?array $payload = null, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for payment gateways');
        }
        $ch = curl_init($url);
        if ($ch === false) {
            throw new RuntimeException('Unable to initialise payment transport');
        }
        $httpHeaders = ['Accept: application/json'];
        foreach ($headers as $name => $value) {
            $httpHeaders[] = $name . ': ' . $value;
        }
        if ($payload !== null) {
            $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $httpHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $httpHeaders,
            CURLOPT_USERAGENT => 'DRB-Medical-CRM/1.0',
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $errno !== 0) {
            throw new RuntimeException('Payment gateway transport error: ' . $error);
        }
        $decoded = json_decode((string)$raw, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('Payment gateway returned invalid JSON');
        }
        return ['status' => $status, 'body' => $decoded];
    }
}
