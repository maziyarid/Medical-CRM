<?php
// Patients module routes — appended to shared router (dashboard.drbastaninejad.com)

use App\Controllers\PatientController;
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
