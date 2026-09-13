<?php

use App\Controllers\StaffManagementController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/admin/staff', [StaffManagementController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('staff.manage'),
]);
$router->post('/api/v1/admin/staff', [StaffManagementController::class, 'create'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('staff.manage'),
]);
$router->patch('/api/v1/admin/staff/{id}', [StaffManagementController::class, 'update'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('staff.manage'),
]);
$router->post('/api/v1/admin/staff/{id}/resend-invite', [StaffManagementController::class, 'resendInvite'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('staff.manage'),
]);
