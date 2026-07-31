<?php
// Billing routes (dashboard.drbastaninejad.com)

use App\Controllers\BillingController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/billing/invoices', [BillingController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('billing.view'),
]);

$router->get('/api/v1/billing/invoices/{id}', [BillingController::class, 'show'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('billing.view'),
]);

$router->post('/api/v1/billing/invoices', [BillingController::class, 'store'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('billing.manage'),
]);

$router->patch('/api/v1/billing/invoices/{id}/status', [BillingController::class, 'updateStatus'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('billing.manage'),
]);
