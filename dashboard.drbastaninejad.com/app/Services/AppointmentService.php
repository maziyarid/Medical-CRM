<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;

/**
 * AppointmentService
 * Wraps creation so ReminderService can hook in without touching the controller.
 * Phase 5 (UNIFIED_MASTER_PLAN.md §7): reminder scheduling is now wired.
 * Offsets configurable via SMS_REMINDER_OFFSETS env var (default: 60,1440 minutes).
 */
final class AppointmentService
{
    public function create(int $clinicId, array $data): int
    {
        $appointment = new Appointment();
        $id = $appointment->create([
            'uuid'             => bin2hex(random_bytes(16)),
            'clinic_id'        => $clinicId,
            'patient_id'       => $data['patient_id'],
            'provider_id'      => $data['provider_id'],
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'],
            'visit_reason'     => $data['visit_reason'],
            'room'             => $data['room'],
            'notes'            => $data['notes'],
            'status'           => 'scheduled',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);

        // Phase 5 — schedule SMS (and optionally email) reminders.
        // ReminderService::scheduleForAppointment() is idempotent and never throws.
        // Offsets are read from SMS_REMINDER_OFFSETS env var (default: "60,1440").
        // Set SMS_REMINDER_OFFSETS="" to disable reminders without changing code.
        (new ReminderService())->scheduleForAppointment(
            $id,
            (int)$data['patient_id'],
            $clinicId,
            $data['scheduled_at']
        );

        return $id;
    }

    /**
     * Cancel pending reminders and reschedule them for the new time.
     * Called by AppointmentController::reschedule() after a drag-and-drop move.
     */
    public function reschedule(int $appointmentId, int $patientId, int $clinicId, string $newScheduledAt): void
    {
        $rs = new ReminderService();
        $rs->cancelForAppointment($appointmentId);
        $rs->scheduleForAppointment($appointmentId, $patientId, $clinicId, $newScheduledAt);
    }
}
