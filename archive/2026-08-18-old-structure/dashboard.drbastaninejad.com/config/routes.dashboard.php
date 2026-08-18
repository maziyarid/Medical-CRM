<?php
// dashboard.drbastaninejad.com routes — appended to the shared MVC router
// Reuses AuthMiddleware + RbacMiddleware from app.drbastaninejad.com backend (shared codebase)

use App\Controllers\DashboardController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/dashboard/overview', [DashboardController::class, 'overview'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('dashboard.view'),
]);
