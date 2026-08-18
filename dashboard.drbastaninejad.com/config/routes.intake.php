<?php

use App\Controllers\IntakeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->post('/api/v1/intakes', [IntakeController::class, 'store']);
$router->get('/api/v1/intakes', [IntakeController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('intakes.view'),
]);
$router->patch('/api/v1/intakes/{id}/status', [IntakeController::class, 'updateStatus'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('intakes.manage'),
]);
