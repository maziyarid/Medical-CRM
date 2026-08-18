<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;

/**
 * HealthController — lightweight liveness probe.
 *
 * GET /api/v1/health
 *
 * Returns 200 {"success":true,"data":{"status":"ok"}} when the application
 * and database connection are reachable. Returns 503 if the DB ping fails.
 *
 * Used by:
 *   - pages/errors/offline.html — retry button HEAD probe
 *   - docs/DEPLOYMENT_GATE.md §8 "Public health check passes"
 *   - Nginx/load-balancer uptime monitors
 *
 * SECURITY: No authentication required. No patient data, no tokens,
 * no internal paths are returned. Timing is not disclosed.
 */
final class HealthController extends Controller
{
    public function ping(): void
    {
        $dbOk = false;
        try {
            $dbOk = Database::conn()->query('SELECT 1')->fetchColumn() === '1'
                 || Database::conn()->query('SELECT 1')->fetchColumn() === 1;
        } catch (\Throwable) {
            $dbOk = false;
        }

        if (!$dbOk) {
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error'   => ['code' => 'DB_UNAVAILABLE', 'message' => 'database unreachable'],
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $this->json(['status' => 'ok']);
    }
}
