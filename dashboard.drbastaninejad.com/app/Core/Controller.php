<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    protected function success(array $data, int $status = 200): array
    {
        return ['ok' => true, 'status' => $status, 'data' => $data];
    }

    protected function error(string $message, int $status = 400): array
    {
        return ['ok' => false, 'status' => $status, 'error' => $message];
    }
}
