<?php
// Analytics routes (dashboard.drbastaninejad.com)

use App\Controllers\AnalyticsController;
use App\Controllers\SeoReportingController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;
use App\Middleware\SeoNoStoreMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/analytics/summary', [AnalyticsController::class, 'summary'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('analytics.view'),
]);

// Separate from clinical reports. Controller additionally requires owner/admin.
$router->get('/api/v1/analytics/seo', [SeoReportingController::class, 'summary'], [
    SeoNoStoreMiddleware::class,
    AuthMiddleware::class,
    fn() => new RbacMiddleware('analytics.view'),
]);
