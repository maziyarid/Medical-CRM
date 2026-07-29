<?php
declare(strict_types=1);

// config/routes.php — all routes for app.drbastaninejad.com
// Registered by public/index.php (deployment-gated).

use App\Controllers\IntakeController;
use App\Controllers\OtpController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

// ── Auth ──────────────────────────────────────────────────────────────────────
$router->post('/api/v1/auth/otp/send',   [OtpController::class, 'send']);
$router->post('/api/v1/auth/otp/verify', [OtpController::class, 'verify']);

// ── Intake ────────────────────────────────────────────────────────────────────
// Public — no auth required
$router->post('/api/v1/intakes', [IntakeController::class, 'store']);

// Staff — auth required
$router->get('/api/v1/intakes', [IntakeController::class, 'index'], [
    AuthMiddleware::class,
]);
