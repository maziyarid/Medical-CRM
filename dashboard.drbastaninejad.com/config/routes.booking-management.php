<?php

use App\Controllers\BookingManagementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$view = [AuthMiddleware::class, fn() => new RbacMiddleware('appointments.view')];
$manage = [AuthMiddleware::class, fn() => new RbacMiddleware('appointments.manage')];

$router->get('/api/v1/booking-management', [BookingManagementController::class, 'index'], $view);
$router->post('/api/v1/booking-management/admin-free', [BookingManagementController::class, 'createAdminFree'], $manage);
$router->post('/api/v1/booking-management/{id}/confirm', [BookingManagementController::class, 'confirm'], $manage);
$router->post('/api/v1/booking-management/open-days', [BookingManagementController::class, 'upsertOpenDay'], $manage);
$router->delete('/api/v1/booking-management/open-days/{id}', [BookingManagementController::class, 'deleteOpenDay'], $manage);
$router->post('/api/v1/booking-management/calendar/sync', [BookingManagementController::class, 'syncCalendar'], $manage);
$router->post('/api/v1/booking-management/calendar/conflicts/{appointmentId}/resolve', [BookingManagementController::class, 'resolveCalendarConflict'], $manage);
$router->post('/api/v1/booking-management/{id}/ledger/resync', [BookingManagementController::class, 'resyncLedger'], $manage);
