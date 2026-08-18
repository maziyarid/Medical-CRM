<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Controller — base class for all dashboard.drbastaninejad.com controllers.
 *
 * Response envelope (docs/API_CONTRACT.md §Dashboard Response Envelope):
 *   success: { "ok": true,  "status": 200, "data": {...} }
 *   error:   { "ok": false, "status": 4xx, "errors": [{"field": null|string, "message": "..."}] }
 *
 * The Router (App\Core\Router) reads the "status" key to set the HTTP status
 * code, then strips it before JSON-encoding the payload.
 */
abstract class Controller
{
    // -------------------------------------------------------------------------
    // Success
    // -------------------------------------------------------------------------

    protected function success(array $data, int $status = 200): array
    {
        return [
            'ok'     => true,
            'status' => $status,
            'data'   => $data,
            'errors' => null,
            'meta'   => null,
        ];
    }

    // -------------------------------------------------------------------------
    // Errors
    // -------------------------------------------------------------------------

    protected function error(string $message, int $status = 400): array
    {
        return [
            'ok'     => false,
            'status' => $status,
            'data'   => null,
            'errors' => [['field' => null, 'message' => $message]],
            'meta'   => null,
        ];
    }

    /**
     * Return a 422 Unprocessable Entity with an array of field-level errors.
     *
     * @param array<int, array{field: string|null, message: string}> $fields
     */
    protected function validationError(array $fields, int $status = 422): array
    {
        return [
            'ok'     => false,
            'status' => $status,
            'data'   => null,
            'errors' => $fields,
            'meta'   => null,
        ];
    }
}
