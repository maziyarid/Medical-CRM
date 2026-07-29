<?php
declare(strict_types=1);

// config/routes.php — all routes for app.drbastaninejad.com
// Registered by public/index.php (deployment-gated).

use App\Controllers\IntakeController;
use App\Controllers\OtpController;
use App\Controllers\PatientPortalController;
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

// ── Patient Portal (Phase C) — all require Bearer auth ───────────────────────
$router->get('/api/v1/patient/overview', [PatientPortalController::class, 'overview'], [
    AuthMiddleware::class,
]);

$router->get('/api/v1/patient/profile', [PatientPortalController::class, 'profile'], [
    AuthMiddleware::class,
]);

$router->get('/api/v1/patient/appointments', [PatientPortalController::class, 'appointments'], [
    AuthMiddleware::class,
]);

$router->get('/api/v1/patient/documents', [PatientPortalController::class, 'documents'], [
    AuthMiddleware::class,
]);

$router->get('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'getNotificationPrefs'], [
    AuthMiddleware::class,
]);

$router->patch('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'patchNotificationPrefs'], [
    AuthMiddleware::class,
]);
