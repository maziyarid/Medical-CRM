<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * ReminderService — Phase 5 (Scheduling & Communications)
 *
 * Manages outbound appointment reminders stored in reminder_log.
 *
 * Product-owner decisions required before enabling real reminders
 * (UNIFIED_MASTER_PLAN.md §9 "Open Product Decisions"):
 *   - SMS_REMINDER_OFFSETS env var (comma-separated minutes before appointment)
 *     Default: "60,1440" (1 hour + 24 hours before)
 *   - EMAIL_REMINDER_ENABLED env var ("1" to enable email reminders; default off)
 *   - Production SMS provider API keys (KAVENEGAR_API_KEY or equivalent)
 *
 * Usage:
 *   // At booking time (called from AppointmentService::create()):
 *   (new ReminderService())->scheduleForAppointment($appointmentId, $patientId, $clinicId, $scheduledAt);
 *
 *   // From a cron job (e.g. every 5 minutes):
 *   (new ReminderService())->sendDue();
 *
 *   // When an appointment is rescheduled or cancelled:
 *   (new ReminderService())->cancelForAppointment($appointmentId);
 *
 * Design decisions:
 *   - scheduleForAppointment() is idempotent — INSERT IGNORE means re-booking the same
 *     appointment_id (e.g. after a reschedule that reuses the same row) will not
 *     create duplicate rows once cancel+re-insert is used. Callers should call
 *     cancelForAppointment() before scheduleForAppointment() on reschedule.
 *   - sendDue() dispatches only SMS for now; email is behind EMAIL_REMINDER_ENABLED.
 *   - sendDue() processes a bounded batch (default 50) per cron invocation to avoid
 *     overloading the SMS provider in a single call.
 *   - Never throws — all exceptions are caught and logged.
 */
final class ReminderService
{
    /** Default reminder offsets in minutes before the appointment */
    private const DEFAULT_OFFSETS = [60, 1440]; // 1h + 24h

    private const SEND_BATCH_SIZE = 50;

    // -------------------------------------------------------------------------
    // Schedule reminders for a new appointment
    // -------------------------------------------------------------------------

    /**
     * Insert pending reminder_log rows for all configured offset windows.
     * Safe to call multiple times for the same appointment_id (INSERT IGNORE).
     *
     * @param string $scheduledAt UTC datetime of the appointment (Y-m-d H:i:s)
     */
    public function scheduleForAppointment(
        int    $appointmentId,
        int    $patientId,
        int    $clinicId,
        string $scheduledAt
    ): void {
        $offsets = $this->resolveOffsets();

        try {
            $db   = Database::conn();
            $stmt = $db->prepare(
                "INSERT IGNORE INTO reminder_log
                     (appointment_id, patient_id, clinic_id, channel,
                      remind_at, remind_offset_minutes, status, created_at, updated_at)
                 VALUES (?, ?, ?, 'sms', ?, ?, 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
            );

            foreach ($offsets as $offsetMinutes) {
                $remindAt = date(
                    'Y-m-d H:i:s',
                    strtotime($scheduledAt) - ($offsetMinutes * 60)
                );
                // Only schedule if the reminder time is in the future
                if (strtotime($remindAt) > time()) {
                    $stmt->execute([$appointmentId, $patientId, $clinicId, $remindAt, $offsetMinutes]);
                }
            }

            // Email reminders — gated on product-owner decision
            if (($_ENV['EMAIL_REMINDER_ENABLED'] ?? '0') === '1') {
                $emailStmt = $db->prepare(
                    "INSERT IGNORE INTO reminder_log
                         (appointment_id, patient_id, clinic_id, channel,
                          remind_at, remind_offset_minutes, status, created_at, updated_at)
                     VALUES (?, ?, ?, 'email', ?, ?, 'pending', UTC_TIMESTAMP(), UTC_TIMESTAMP())"
                );
                foreach ($offsets as $offsetMinutes) {
                    $remindAt = date(
                        'Y-m-d H:i:s',
                        strtotime($scheduledAt) - ($offsetMinutes * 60)
                    );
                    if (strtotime($remindAt) > time()) {
                        $emailStmt->execute([$appointmentId, $patientId, $clinicId, $remindAt, $offsetMinutes]);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[ReminderService] scheduleForAppointment failed for appointment #'
                . $appointmentId . ': ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Cancel pending reminders (on reschedule or cancellation)
    // -------------------------------------------------------------------------

    /**
     * Mark all pending reminders for an appointment as cancelled.
     * Call before scheduleForAppointment() when rescheduling.
     */
    public function cancelForAppointment(int $appointmentId): void
    {
        try {
            Database::conn()
                ->prepare(
                    "UPDATE reminder_log SET status = 'cancelled', updated_at = UTC_TIMESTAMP()
                     WHERE appointment_id = ? AND status = 'pending'"
                )
                ->execute([$appointmentId]);
        } catch (\Throwable $e) {
            error_log('[ReminderService] cancelForAppointment failed for appointment #'
                . $appointmentId . ': ' . $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Dispatch due reminders (called from cron)
    // -------------------------------------------------------------------------

    /**
     * Find all pending reminders whose remind_at <= NOW() and dispatch them.
     * Intended to be called from a cron job every 1–5 minutes.
     *
     * Returns the number of reminders successfully sent.
     */
    public function sendDue(): int
    {
        $sent = 0;

        try {
            $db   = Database::conn();
            $rows = $db->prepare(
                "SELECT rl.id, rl.appointment_id, rl.patient_id, rl.channel,
                        rl.attempts, rl.max_attempts,
                        p.mobile,
                        a.scheduled_at, a.visit_reason
                 FROM reminder_log rl
                 JOIN patients     p ON p.id = rl.patient_id
                 JOIN appointments a ON a.id = rl.appointment_id
                 WHERE rl.status = 'pending'
                   AND rl.remind_at <= UTC_TIMESTAMP()
                   AND rl.attempts < rl.max_attempts
                   AND a.status NOT IN ('cancelled','completed')
                   AND a.deleted_at IS NULL
                 ORDER BY rl.remind_at ASC
                 LIMIT " . self::SEND_BATCH_SIZE
            );
            $rows->execute();
            $due = $rows->fetchAll();
        } catch (\Throwable $e) {
            error_log('[ReminderService] sendDue: DB fetch failed: ' . $e->getMessage());
            return 0;
        }

        foreach ($due as $row) {
            try {
                $db->prepare(
                    "UPDATE reminder_log SET attempts = attempts + 1, updated_at = UTC_TIMESTAMP()
                     WHERE id = ?"
                )->execute([$row['id']]);

                if ($row['channel'] === 'sms') {
                    $this->dispatchSms($row);
                }
                // email channel — intentionally a no-op until product owner approves
                // email provider and template. ReminderService::dispatchEmail() will be
                // added in Phase 5b once the decision is made (UNIFIED_MASTER_PLAN §9).

                $db->prepare(
                    "UPDATE reminder_log
                     SET status = 'sent', sent_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                     WHERE id = ?"
                )->execute([$row['id']]);
                $sent++;

            } catch (\Throwable $e) {
                error_log('[ReminderService] sendDue: dispatch failed for reminder #'
                    . $row['id'] . ': ' . $e->getMessage());
                try {
                    $maxAttempts = (int)$row['max_attempts'];
                    $newAttempts = (int)$row['attempts'] + 1; // already incremented above
                    $finalStatus = $newAttempts >= $maxAttempts ? 'failed' : 'pending';
                    $db->prepare(
                        "UPDATE reminder_log
                         SET status = ?, error_message = ?, updated_at = UTC_TIMESTAMP()
                         WHERE id = ?"
                    )->execute([
                        $finalStatus,
                        substr($e->getMessage(), 0, 512),
                        $row['id'],
                    ]);
                } catch (\Throwable) {
                    // DB unavailable — nothing more we can do
                }
            }
        }

        return $sent;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve configured reminder offsets from SMS_REMINDER_OFFSETS env var.
     * Falls back to the two default offsets (60 min + 1440 min).
     *
     * @return int[]
     */
    private function resolveOffsets(): array
    {
        $raw = $_ENV['SMS_REMINDER_OFFSETS'] ?? '';
        if ($raw === '') {
            return self::DEFAULT_OFFSETS;
        }
        $parsed = array_filter(
            array_map('intval', explode(',', $raw)),
            fn(int $v) => $v > 0
        );
        return count($parsed) > 0 ? array_values($parsed) : self::DEFAULT_OFFSETS;
    }

    /**
     * Dispatch a single SMS reminder via SmsProviderChain.
     * Never throws — exceptions propagate to sendDue()'s catch block.
     */
    private function dispatchSms(array $row): void
    {
        $scheduled = date('H:i', strtotime($row['scheduled_at']));
        $reason    = $row['visit_reason'] ? ' (' . $row['visit_reason'] . ')' : '';
        $message   = "یادآوری: نوبت شما ساعت {$scheduled}{$reason} مقرر است. لغو: با مطب تماس بگیرید.";

        $chain = new SmsProviderChain();
        $chain->sendReminder($row['mobile'], $message);
    }
}
