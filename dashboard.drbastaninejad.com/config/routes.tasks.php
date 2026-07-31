<?php
// Task routes (dashboard.drbastaninejad.com)

use App\Controllers\TaskController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/tasks', [TaskController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('tasks.view'),
]);

$router->post('/api/v1/tasks', [TaskController::class, 'store'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('tasks.manage'),
]);

$router->patch('/api/v1/tasks/{id}/status', [TaskController::class, 'updateStatus'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('tasks.manage'),
]);

$router->delete('/api/v1/tasks/{id}', [TaskController::class, 'destroy'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('tasks.manage'),
]);
