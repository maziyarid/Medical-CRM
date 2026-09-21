<?php
use App\Controllers\CommunicationsController;
use App\Middleware\AuthMiddleware;

/** @var App\Core\Router $router */

$router->post('/api/v1/communications/contact', [CommunicationsController::class, 'publicContact']);

$router->get('/api/v1/communications/summary', [CommunicationsController::class, 'summary'], [AuthMiddleware::class]);
$router->get('/api/v1/communications/settings', [CommunicationsController::class, 'settings'], [AuthMiddleware::class]);
$router->patch('/api/v1/communications/settings', [CommunicationsController::class, 'updateSettings'], [AuthMiddleware::class]);
$router->post('/api/v1/communications/sync', [CommunicationsController::class, 'sync'], [AuthMiddleware::class]);
$router->get('/api/v1/communications/labels', [CommunicationsController::class, 'labels'], [AuthMiddleware::class]);
$router->post('/api/v1/communications/labels', [CommunicationsController::class, 'createLabel'], [AuthMiddleware::class]);
$router->delete('/api/v1/communications/labels/{id}', [CommunicationsController::class, 'deleteLabel'], [AuthMiddleware::class]);

$router->get('/api/v1/communications', [CommunicationsController::class, 'index'], [AuthMiddleware::class]);
$router->get('/api/v1/communications/{id}', [CommunicationsController::class, 'show'], [AuthMiddleware::class]);
$router->post('/api/v1/communications/{id}/reply', [CommunicationsController::class, 'reply'], [AuthMiddleware::class]);
$router->patch('/api/v1/communications/{id}/read', [CommunicationsController::class, 'markRead'], [AuthMiddleware::class]);
$router->patch('/api/v1/communications/{id}/status', [CommunicationsController::class, 'updateStatus'], [AuthMiddleware::class]);
$router->patch('/api/v1/communications/{id}/meta', [CommunicationsController::class, 'updateMeta'], [AuthMiddleware::class]);