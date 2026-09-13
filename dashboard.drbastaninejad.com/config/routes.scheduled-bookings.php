<?php

use App\Controllers\ScheduledBookingController;

/** @var App\Core\Router $router */

// Protected inside the controller by the server-held WordPress bridge secret.
$router->post('/api/v1/bookings/scheduled/config', [ScheduledBookingController::class, 'config']);
$router->post('/api/v1/bookings/scheduled/availability', [ScheduledBookingController::class, 'availability']);
$router->post('/api/v1/bookings/scheduled/start', [ScheduledBookingController::class, 'start']);
$router->post('/api/v1/bookings/scheduled/payment/verify', [ScheduledBookingController::class, 'verifyPayment']);
