<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;

/**
 * AuthMiddleware — validates Bearer token for app.drbastaninejad.com
 *
 * Reads Authorization: Bearer {token} header.
 * Looks up SHA-256 hash in auth_tokens table.
 * Populates $_REQUEST['_auth_user'] on success.
 * Returns 401 JSON on failure using docs/API_CONTRACT.md envelope.
 */
final class AuthMiddleware
{
    public function handle(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($header, 'Bearer ')) {
            $this->unauthorized();
        }

        $token = substr($header, 7);
        if ($token === '') {
            $this->unauthorized();
        }

        $db   = Database::conn();
        $stmt = $db->prepare(
            'SELECT at.id, at.user_id, at.user_type, at.expires_at,
                    p.uuid, p.first_name, p.last_name, p.mobile
             FROM auth_tokens at
             JOIN patients p ON p.id = at.user_id
             WHERE at.token_hash = ?
               AND at.revoked_at IS NULL
               AND at.expires_at > UTC_TIMESTAMP()
             LIMIT 1'
        );
        $stmt->execute([hash('sha256', $token)]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->unauthorized();
        }

        $_REQUEST['_auth_user'] = $user;
    }

    private function unauthorized(): never
    {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error'   => ['code' => 'UNAUTHORIZED', 'message' => 'احراز هویت الزامی است'],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
