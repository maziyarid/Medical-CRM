<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * Standard success envelope.
     * Optional $meta is forwarded to the response envelope (pagination etc.).
     */
    protected function success(array $data, int $status = 200, array $meta = []): array
    {
        return ['ok' => true, 'status' => $status, 'data' => $data, 'meta' => $meta ?: null];
    }

    protected function error(string $message, int $status = 400): array
    {
        return ['ok' => false, 'status' => $status, 'data' => null, 'errors' => [['field' => null, 'message' => $message]]];
    }

    /**
     * Field-level validation errors — matches API_CONTRACT.md error envelope.
     * @param array<array{field:string,message:string}> $errors
     */
    protected function validationError(array $errors): array
    {
        return ['ok' => false, 'status' => 422, 'data' => null, 'errors' => $errors];
    }
}
