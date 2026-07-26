<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Models\Appointment;
use App\Services\AppointmentService;

/**
 * AppointmentController
 * Powers the Smart Scheduling Calendar (Medical CRM.md §5.6):
 * day/week/month/agenda views, drag-to-reschedule, quick-create, conflict detection,
 * provider/room/status filters, reminder-status indicator.
 */
final class AppointmentController extends Controller
{
    private Appointment $appointments;
    private AppointmentService $service;

    public function __construct()
    {
        $this->appointments = new Appointment();
        $this->service = new AppointmentService();
    }

    /** GET /api/v1/appointments?from=&to=&provider_id= */
    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $from = $req->query['from'] ?? date('Y-m-d 00:00:00');
        $to = $req->query['to'] ?? date('Y-m-d 00:00:00', strtotime('+1 day'));
        $providerId = isset($req->query['provider_id']) ? (int)$req->query['provider_id'] : null;

        $rows = $this->appointments->inRange($clinicId, $from, $to, $providerId);

        $badgeMap = ['scheduled' => 'info', 'confirmed' => 'success', 'cancelled' => 'error', 'completed' => 'muted'];
        $events = array_map(function ($r) use ($badgeMap) {
            return [
                'id' => (int)$r['id'],
                'patient_id' => (int)$r['patient_id'],
                'patient_name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'provider_id' => (int)$r['provider_id'],
                'start' => $r['scheduled_at'],
                'duration_minutes' => (int)$r['duration_minutes'],
                'reason' => $r['visit_reason'],
                'status' => $r['status'],
                'badge' => $badgeMap[$r['status']] ?? 'muted',
                'room' => $r['room'],
                'notes' => $r['notes'],
            ];
        }, $rows);

        return $this->success(['events' => $events]);
    }

    /** POST /api/v1/appointments — quick-create from empty slot click */
    public function store(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;

        $required = ['patient_id', 'provider_id', 'scheduled_at'];
        foreach ($required as $field) {
            if (empty($in[$field])) {
                return $this->error("فیلد $field الزامی است", 422);
            }
        }

        $duration = (int)($in['duration_minutes'] ?? 20);

        if ($this->appointments->hasConflict($clinicId, (int)$in['provider_id'], $in['scheduled_at'], $duration)) {
            return $this->error('این بازه زمانی با نوبت دیگری تداخل دارد', 409);
        }

        $id = $this->service->create($clinicId, [
            'patient_id' => (int)$in['patient_id'],
            'provider_id' => (int)$in['provider_id'],
            'scheduled_at' => $in['scheduled_at'],
            'duration_minutes' => $duration,
            'visit_reason' => $in['visit_reason'] ?? null,
            'room' => $in['room'] ?? null,
            'notes' => $in['notes'] ?? null,
        ]);

        return $this->success(['id' => $id], 201);
    }

    /** PATCH /api/v1/appointments/{id}/reschedule — drag-and-drop reschedule */
    public function reschedule(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $appointmentId = (int)$id;
        $in = $req->body;

        $existing = $this->appointments->find($appointmentId);
        if (!$existing || (int)$existing['clinic_id'] !== $clinicId) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $newStart = $in['scheduled_at'] ?? $existing['scheduled_at'];
        $newDuration = isset($in['duration_minutes']) ? (int)$in['duration_minutes'] : (int)$existing['duration_minutes'];

        if ($this->appointments->hasConflict($clinicId, (int)$existing['provider_id'], $newStart, $newDuration, $appointmentId)) {
            return $this->error('این بازه زمانی با نوبت دیگری تداخل دارد', 409);
        }

        $this->appointments->reschedule($appointmentId, $newStart, $newDuration);
        return $this->success(['id' => $appointmentId]);
    }

    /** PATCH /api/v1/appointments/{id}/status */
    public function updateStatus(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $appointmentId = (int)$id;
        $status = $req->body['status'] ?? null;

        $allowed = ['scheduled', 'confirmed', 'cancelled', 'completed'];
        if (!in_array($status, $allowed, true)) {
            return $this->error('وضعیت نامعتبر است', 422);
        }

        $existing = $this->appointments->find($appointmentId);
        if (!$existing || (int)$existing['clinic_id'] !== $clinicId) {
            return $this->error('نوبت یافت نشد', 404);
        }

        $this->appointments->update($appointmentId, ['status' => $status]);
        return $this->success(['id' => $appointmentId, 'status' => $status]);
    }
}
