<?php
// Patients module routes — appended to shared router (dashboard.drbastaninejad.com)

use App\Controllers\PatientController;
use App\Controllers\BookingBlacklistController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

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

$router->delete('/api/v1/patients/{id}', [PatientController::class, 'destroy'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

$router->post('/api/v1/patients/{id}/blacklist', [BookingBlacklistController::class, 'blockPatient'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

$router->post('/api/v1/patients/{id}/sms', [PatientController::class, 'sendSms'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

$router->post('/api/v1/patients/bulk-sms', [PatientController::class, 'bulkSms'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patients.manage'),
]);

// Patient portal — same patient identity; protected by patient-scoped RBAC.
use App\Controllers\PatientPortalController;

$patientScope = [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('patient.portal'),
];

$router->get('/api/v1/patient/overview', [PatientPortalController::class, 'overview'], $patientScope);
$router->get('/api/v1/patient/profile', [PatientPortalController::class, 'profile'], $patientScope);
$router->patch('/api/v1/patient/profile', [PatientPortalController::class, 'updateProfile'], $patientScope);
$router->get('/api/v1/patient/appointments', [PatientPortalController::class, 'appointments'], $patientScope);
$router->get('/api/v1/patient/documents', [PatientPortalController::class, 'documents'], $patientScope);
$router->get('/api/v1/patient/records', [PatientPortalController::class, 'records'], $patientScope);
$router->get('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'notificationPreferences'], $patientScope);
$router->patch('/api/v1/patient/notification-preferences', [PatientPortalController::class, 'updateNotificationPreferences'], $patientScope);
