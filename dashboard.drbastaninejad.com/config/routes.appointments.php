<?php
// Scheduling Calendar routes — appended to shared router (dashboard.drbastaninejad.com)

use App\Controllers\AppointmentController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/appointments', [AppointmentController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('appointments.view'),
]);

$router->post('/api/v1/appointments', [AppointmentController::class, 'store'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('appointments.manage'),
]);

$router->patch('/api/v1/appointments/{id}/reschedule', [AppointmentController::class, 'reschedule'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('appointments.manage'),
]);

$router->patch('/api/v1/appointments/{id}/status', [AppointmentController::class, 'updateStatus'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('appointments.manage'),
]);
