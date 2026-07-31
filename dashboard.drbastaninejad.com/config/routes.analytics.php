<?php
// Analytics routes (dashboard.drbastaninejad.com)

use App\Controllers\AnalyticsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/analytics/summary', [AnalyticsController::class, 'summary'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('analytics.view'),
]);
