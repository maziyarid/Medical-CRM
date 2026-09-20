<?php

use App\Controllers\AppointmentBookingController;
use App\Controllers\AppointmentFeeController;
use App\Controllers\BookingController;
use App\Controllers\BookingBlacklistController;
use App\Controllers\BookingSessionController;
use App\Middleware\AuthMiddleware;
use App\Middleware\RbacMiddleware;

/** @var App\Core\Router $router */

$router->post('/api/v1/bookings', [BookingController::class, 'store']);
$router->post('/api/v1/bookings/otp/send', [BookingController::class, 'sendOtp']);
$router->post('/api/v1/bookings/otp/verify', [BookingController::class, 'verifyOtp']);
$router->post('/api/v1/bookings/session', [BookingSessionController::class, 'create']);
$router->post('/api/v1/bookings/resume', [BookingSessionController::class, 'resume']);
$router->get('/api/v1/bookings/stats', [BookingController::class, 'stats']);
$router->post('/api/v1/bookings/eligibility', [BookingController::class, 'eligibility']);

$router->get('/api/v1/appointment-bookings/availability', [AppointmentBookingController::class, 'availability']);
$router->post('/api/v1/appointment-bookings/holds', [AppointmentBookingController::class, 'hold'], [AuthMiddleware::class]);
$router->post('/api/v1/appointment-bookings/{id}/payment', [AppointmentBookingController::class, 'startPayment'], [AuthMiddleware::class]);
$router->get('/api/v1/appointment-bookings/payment/callback/{gateway}', [AppointmentBookingController::class, 'paymentCallback']);

$router->post('/api/v1/admin/appointment-bookings', [AppointmentBookingController::class, 'adminCreate'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->post('/api/v1/admin/appointment-bookings/{id}/reconcile-slot', [AppointmentBookingController::class, 'reconcilePaidSlot'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.payments.reconcile')]);
$router->post('/api/v1/admin/appointment-bookings/{id}/confirm', [AppointmentBookingController::class, 'confirm'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->patch('/api/v1/admin/appointment-bookings/{id}', [AppointmentBookingController::class, 'updateBooking'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->delete('/api/v1/admin/appointment-bookings/{id}', [AppointmentBookingController::class, 'cancelBooking'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->post('/api/v1/admin/appointment-open-days', [AppointmentBookingController::class, 'saveOpenDay'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.availability.manage')]);
$router->get('/api/v1/admin/appointment-bookings', [AppointmentBookingController::class, 'adminBookings'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->get('/api/v1/admin/booking-blacklist', [BookingBlacklistController::class, 'index'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->post('/api/v1/admin/booking-blacklist', [BookingBlacklistController::class, 'store'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->delete('/api/v1/admin/booking-blacklist/{id}', [BookingBlacklistController::class, 'destroy'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->get('/api/v1/admin/appointment-open-days', [AppointmentBookingController::class, 'adminOpenDays'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->get('/api/v1/admin/appointment-payments', [AppointmentBookingController::class, 'adminPayments'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.payments.reconcile')]);
$router->get('/api/v1/admin/appointment-integrations', [AppointmentBookingController::class, 'integrationStatus'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.availability.manage')]);
$router->patch('/api/v1/admin/appointment-integrations', [AppointmentBookingController::class, 'updateIntegrationSettings'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.integrations.manage')]);
$router->post('/api/v1/admin/appointment-bookings/{id}/followup-complete', [AppointmentBookingController::class, 'completeFollowup'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->post('/api/v1/admin/appointment-payments/{id}/reconcile', [AppointmentBookingController::class, 'reconcilePayment'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.payments.reconcile')]);
$router->post('/api/v1/admin/appointment-bookings/{id}/sheet-sync', [AppointmentBookingController::class, 'syncScheduledVisit'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.payments.reconcile')]);

$router->get('/api/v1/admin/appointment-fee', [AppointmentFeeController::class, 'show'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.fee.manage')]);
$router->patch('/api/v1/admin/appointment-fee', [AppointmentFeeController::class, 'update'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.fee.manage')]);

$router->get('/api/v1/admin/appointment-booking-settings', [AppointmentBookingController::class, 'bookingSettings'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->patch('/api/v1/admin/appointment-booking-settings', [AppointmentBookingController::class, 'updateBookingSettings'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.availability.manage')]);
$router->get('/api/v1/admin/booking-finalisation/pending', [AppointmentBookingController::class, 'pendingFinalisation'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
$router->post('/api/v1/admin/booking-finalisation/notify', [AppointmentBookingController::class, 'notifyPendingFinalisation'], [AuthMiddleware::class, fn() => new RbacMiddleware('booking.manage')]);
