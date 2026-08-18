<?php

use App\Controllers\BookingController;

/** @var App\Core\Router $router */
$router->post('/api/v1/bookings', [BookingController::class, 'store']);
$router->get('/api/v1/bookings/stats', [BookingController::class, 'stats']);
