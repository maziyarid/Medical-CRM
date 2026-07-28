<?php
// Patients module routes — appended to shared router (dashboard.drbastaninejad.com)

use App\Controllers\PatientController;
use App\Controllers\PatientPortalController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

// ── Staff CRM routes (existing) ───────────────────────────────────────────────

$router->get('/api/v1/patients', [PatientController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.view'),
]);

$router->get('/api/v1/patients/{id}', [PatientController::class, 'show'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.view'),
]);

$router->post('/api/v1/patients', [PatientController::class, 'store'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

$router->put('/api/v1/patients/{id}', [PatientController::class, 'update'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

// ── Patient portal routes (Phase C) ──────────────────────────────────────────
// All require a patient-scoped bearer token (user_type = 'patient').
// RbacMiddleware('patient.self') is satisfied by any authenticated patient.

$router->get('/api/v1/patient/overview', [PatientPortalController::class, 'overview'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->get('/api/v1/patient/profile', [PatientPortalController::class, 'profile'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->patch('/api/v1/patient/profile', [PatientPortalController::class, 'updateProfile'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->get('/api/v1/patient/appointments', [PatientPortalController::class, 'appointments'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->get('/api/v1/patient/documents', [PatientPortalController::class, 'documents'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->get('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'notificationPreferences'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);

$router->patch('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'updateNotificationPreferences'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.self'),
]);
