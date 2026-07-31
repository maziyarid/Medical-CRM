<?php
// Settings routes (dashboard.drbastaninejad.com)

use App\Controllers\SettingsController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->get('/api/v1/settings/clinic', [SettingsController::class, 'show'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('settings.view'),
]);

$router->patch('/api/v1/settings/clinic', [SettingsController::class, 'update'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('settings.manage'),
]);
