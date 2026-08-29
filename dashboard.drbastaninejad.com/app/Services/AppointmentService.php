<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Appointment;
use PDO;
use RuntimeException;

/**
 * AppointmentService
 * Wraps creation so ReminderService can hook in without touching the controller.
 * Writes are serialized per clinic (GET_LOCK) and conflict-checked inside the
 * transaction, including room collisions and cancelled/completed reactivation.
 */
final class AppointmentService
{
    /**
     * Serialize appointment writes for one clinic, then run $fn inside a transaction.
     *
     * @template T
     * @param callable(PDO):T $fn
     * @return T
     */
    public function withClinicLock(int $clinicId, callable $fn): mixed
    {
        $db = Database::conn();
        $lockName = 'crm:appointments:' . $clinicId;
        $stmt = $db->prepare('SELECT GET_LOCK(?, 8)');
        $stmt->execute([$lockName]);
        if ((int)$stmt->fetchColumn() !== 1) {
            throw new RuntimeException('clinic lock timeout');
        }
        try {
            $db->beginTransaction();
            try {
                $result = $fn($db);
                $db->commit();
                return $result;
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                throw $e;
            }
        } finally {
            $release = $db->prepare('SELECT RELEASE_LOCK(?)');
            $release->execute([$lockName]);
        }
    }

    /**
     * @return array{ok: true, id: int}|array{ok: false, status: int, message: string}
     */
    public function createLocked(int $clinicId, array $data): array
    {
        try {
            $id = $this->withClinicLock($clinicId, function () use ($clinicId, $data): int {
                $appointment = new Appointment();
                $duration = (int)$data['duration_minutes'];
                $room = $data['room'] ?? null;
                if ($appointment->hasConflict($clinicId, (int)$data['provider_id'], $data['scheduled_at'], $duration)) {
                    throw new RuntimeException('provider conflict');
                }
                if ($appointment->hasRoomConflict($clinicId, $room, $data['scheduled_at'], $duration)) {
                    throw new RuntimeException('room conflict');
                }
                return $this->insertRow($clinicId, $data);
            });
        } catch (RuntimeException $e) {
            return $this->conflictOrLock($e);
        }

        (new ReminderService())->scheduleForAppointment(
            $id,
            (int)$data['patient_id'],
            $clinicId,
            $data['scheduled_at']
        );

        return ['ok' => true, 'id' => $id];
    }

    public function create(int $clinicId, array $data): int
    {
        $result = $this->createLocked($clinicId, $data);
        if (!$result['ok']) {
            throw new RuntimeException($result['message']);
        }
        return $result['id'];
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

    /**
     * @return array{ok: true, id: int}|array{ok: false, status: int, message: string}
     */
    public function rescheduleLocked(
        int $clinicId,
        int $appointmentId,
        string $newStart,
        int $newDuration,
        ?string $room,
        int $providerId,
        int $patientId
    ): array {
        try {
            $this->withClinicLock($clinicId, function () use ($clinicId, $appointmentId, $newStart, $newDuration, $room, $providerId): void {
                $appointment = new Appointment();
                if ($appointment->hasConflict($clinicId, $providerId, $newStart, $newDuration, $appointmentId)) {
                    throw new RuntimeException('provider conflict');
                }
                if ($appointment->hasRoomConflict($clinicId, $room, $newStart, $newDuration, $appointmentId)) {
                    throw new RuntimeException('room conflict');
                }
                $appointment->reschedule($appointmentId, $newStart, $newDuration, $room);
            });
        } catch (RuntimeException $e) {
            return $this->conflictOrLock($e);
        }

        $this->reschedule($appointmentId, $patientId, $clinicId, $newStart);
        return ['ok' => true, 'id' => $appointmentId];
    }

    /**
     * Reactivate cancelled/completed only after a fresh conflict check.
     *
     * @return array{ok: true, id: int, status: string}|array{ok: false, status: int, message: string}
     */
    public function updateStatusLocked(int $clinicId, array $existing, string $status): array
    {
        $appointmentId = (int)$existing['id'];
        $reactivating = in_array($status, Appointment::OCCUPYING_STATUSES, true)
            && !in_array((string)$existing['status'], Appointment::OCCUPYING_STATUSES, true);

        try {
            $this->withClinicLock($clinicId, function () use ($clinicId, $existing, $status, $appointmentId, $reactivating): void {
                $appointment = new Appointment();
                if ($reactivating) {
                    $duration = (int)$existing['duration_minutes'];
                    $start = (string)$existing['scheduled_at'];
                    $providerId = (int)$existing['provider_id'];
                    $room = $existing['room'] ?? null;
                    if ($appointment->hasConflict($clinicId, $providerId, $start, $duration, $appointmentId)) {
                        throw new RuntimeException('provider conflict');
                    }
                    if ($appointment->hasRoomConflict($clinicId, $room, $start, $duration, $appointmentId)) {
                        throw new RuntimeException('room conflict');
                    }
                }
                $appointment->update($appointmentId, ['status' => $status]);
            });
        } catch (RuntimeException $e) {
            return $this->conflictOrLock($e);
        }

        if ($status === 'cancelled') {
            (new ReminderService())->cancelForAppointment($appointmentId);
        } elseif ($reactivating) {
            (new ReminderService())->scheduleForAppointment(
                $appointmentId,
                (int)$existing['patient_id'],
                $clinicId,
                (string)$existing['scheduled_at']
            );
        }

        return ['ok' => true, 'id' => $appointmentId, 'status' => $status];
    }

    /** @param array<string, mixed> $data */
    private function insertRow(int $clinicId, array $data): int
    {
        $appointment = new Appointment();
        return $appointment->create([
            'uuid'             => bin2hex(random_bytes(16)),
            'clinic_id'        => $clinicId,
            'patient_id'       => $data['patient_id'],
            'provider_id'      => $data['provider_id'],
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => $data['duration_minutes'],
            'visit_reason'     => $data['visit_reason'] ?? null,
            'room'             => $data['room'] ?? null,
            'notes'            => $data['notes'] ?? null,
            'status'           => 'scheduled',
            'created_at'       => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array{ok: false, status: int, message: string}
     */
    private function conflictOrLock(RuntimeException $e): array
    {
        return match ($e->getMessage()) {
            'provider conflict' => [
                'ok' => false,
                'status' => 409,
                'message' => 'این بازه زمانی با نوبت دیگری تداخل دارد',
            ],
            'room conflict' => [
                'ok' => false,
                'status' => 409,
                'message' => 'این اتاق در بازه زمانی انتخاب‌شده اشغال است',
            ],
            'clinic lock timeout' => [
                'ok' => false,
                'status' => 503,
                'message' => 'تقویم در حال به‌روزرسانی است؛ دوباره تلاش کنید',
            ],
            default => throw $e,
        };
    }
}
