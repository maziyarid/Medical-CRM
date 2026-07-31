<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;

/**
 * AppointmentService
 * Wraps creation so future reminder-scheduling (email/SMS X time before scheduled_at,
 * per ROADMAP.md §6) can hook in without touching the controller.
 */
final class AppointmentService
{
    public function create(int $clinicId, array $data): int
    {
        $appointment = new Appointment();
        return $appointment->create([
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
        // Reminder scheduling hook (email/SMS) intentionally deferred — see ROADMAP.md
        // "Reminder timing" open question. Do not add a reminder call here until that
        // decision is confirmed; emaillog/reminder_sent_at columns already support it.
    }
}
