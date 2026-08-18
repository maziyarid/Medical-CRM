<?php
// Intake routes — public (no auth) + staff (auth)

use App\Controllers\IntakeController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

// PUBLIC — intake form submission (no token required)
$router->post('/api/v1/intakes', [IntakeController::class, 'store']);

// STAFF — paginated review queue
$router->get('/api/v1/intakes', [IntakeController::class, 'index'], [
    AuthMiddleware::class,
    fn() => new RbacMiddleware('intakes.view'),
]);
