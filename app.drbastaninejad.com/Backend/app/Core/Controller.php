<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Controller — base class for all app.drbastaninejad.com controllers.
 *
 * Response envelope (docs/API_CONTRACT.md §Shared Response Envelope):
 *   success: { "success": true,  "data": {...} }
 *   error:   { "success": false, "error": { "code": "CODE", "message": "...", "fields": {...} } }
 *
 * NOTE: This is intentionally different from the dashboard envelope ("ok"/"errors").
 * The two subdomains are served by separate backends with separate contracts.
 */
abstract class Controller
{
    // -------------------------------------------------------------------------
    // JSON response helpers
    // -------------------------------------------------------------------------

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function error(string $code, string $message, int $status = 400, array $fields = []): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $error = ['code' => $code, 'message' => $message];
        if ($fields !== []) {
            $error['fields'] = $fields;
        }
        echo json_encode(['success' => false, 'error' => $error], JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function validationError(array $fields): void
    {
        $this->error('VALIDATION_FAILED', 'اطلاعات ورودی نامعتبر است', 422, $fields);
    }

    // -------------------------------------------------------------------------
    // Input helpers
    // -------------------------------------------------------------------------

    protected function jsonBody(): array
    {
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }
}
