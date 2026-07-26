<?php
// Dynamic EMR Editor routes — appended to shared router (dashboard.drbastaninejad.com)

use App\Controllers\EmrController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/patients/{id}/emr', [EmrController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('emr.view'),
]);

$router->get('/api/v1/emr/templates', [EmrController::class, 'templates'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('emr.view'),
]);

$router->post('/api/v1/patients/{id}/emr', [EmrController::class, 'store'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('emr.edit'),
]);

$router->post('/api/v1/ai/emr-draft', [EmrController::class, 'draftNote'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('emr.edit'),
]);
