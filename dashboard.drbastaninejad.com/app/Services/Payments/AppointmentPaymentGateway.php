<?php
declare(strict_types=1);

namespace App\Services\Payments;

interface AppointmentPaymentGateway
{
    /**
     * @param array<string,mixed> $context
     * @return array{authority:string,redirect_url:string,raw:array<string,mixed>}
     */
    public function request(int $amountRials, string $callbackUrl, array $context): array;

    /**
     * @param array<string,mixed> $callback
     * @return array{verified:bool,reference:?string,code:?string,raw:array<string,mixed>}
     */
    public function verify(string $authority, int $amountRials, array $callback): array;

    public function redirectUrl(string $authority): string;
}
