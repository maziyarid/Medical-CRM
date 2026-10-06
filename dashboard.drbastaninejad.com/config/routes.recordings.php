<?php
// RPH-137: private in-person recording/transcription routes.

use App\Controllers\RecordingController;
use App\Middleware\AuthMiddleware;
use App\Middleware\SuperAdminOnlyMiddleware;

/** @var App\Core\Router $router */

$recordingScope = [
    AuthMiddleware::class,
    SuperAdminOnlyMiddleware::class,
];

$router->get('/api/v1/patients/{patientId}/recording-sessions/readiness', [RecordingController::class, 'readiness'], $recordingScope);
$router->post('/api/v1/patients/{patientId}/recording-sessions', [RecordingController::class, 'create'], $recordingScope);
$router->post('/api/v1/patients/{patientId}/recording-sessions/{sessionId}/chunks/{sequence}', [RecordingController::class, 'chunk'], $recordingScope);
$router->post('/api/v1/patients/{patientId}/recording-sessions/{sessionId}/finalize', [RecordingController::class, 'finalize'], $recordingScope);
$router->get('/api/v1/patients/{patientId}/recording-sessions/{sessionId}', [RecordingController::class, 'show'], $recordingScope);
$router->post('/api/v1/patients/{patientId}/recording-sessions/{sessionId}/withdraw', [RecordingController::class, 'withdraw'], $recordingScope);
$router->patch('/api/v1/patients/{patientId}/recording-sessions/{sessionId}/segments/{segmentId}', [RecordingController::class, 'patchSegment'], $recordingScope);
$router->post('/api/v1/patients/{patientId}/recording-sessions/{sessionId}/approve', [RecordingController::class, 'approve'], $recordingScope);
