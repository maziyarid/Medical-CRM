<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * NotificationPreferenceModel — get/upsert per-patient notification flags.
 *
 * Table: notification_preferences (migration 006)
 * One row per patient_id. Missing row = all defaults (true/false per contract).
 *
 * Per docs/API_CONTRACT.md §GET|PATCH /patient/notification-preferences
 */
final class NotificationPreferenceModel extends Model
{
    private const DEFAULTS = [
        'sms_appointment_reminder'   => 1,
        'sms_status_change'          => 1,
        'email_appointment_reminder' => 0,
        'email_marketing'            => 0,
    ];

    /**
     * Return the preferences row for $patientId, or defaults if no row exists.
     */
    public function getForPatient(int $patientId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT sms_appointment_reminder, sms_status_change,
                    email_appointment_reminder, email_marketing
             FROM notification_preferences
             WHERE patient_id = ?
             LIMIT 1'
        );
        $stmt->execute([$patientId]);
        $row = $stmt->fetch();

        if (!$row) {
            // No row yet — return booleans from defaults
            return array_map(fn($v) => (bool)$v, self::DEFAULTS);
        }

        return [
            'sms_appointment_reminder'   => (bool)$row['sms_appointment_reminder'],
            'sms_status_change'          => (bool)$row['sms_status_change'],
            'email_appointment_reminder' => (bool)$row['email_appointment_reminder'],
            'email_marketing'            => (bool)$row['email_marketing'],
        ];
    }

    /**
     * Upsert only the keys present in $patch.
     * Allowed keys: sms_appointment_reminder, sms_status_change,
     *               email_appointment_reminder, email_marketing.
     */
    public function patchForPatient(int $patientId, array $patch): void
    {
        $allowed = array_keys(self::DEFAULTS);
        $cols    = array_intersect(array_keys($patch), $allowed);

        if ($cols === []) {
            return;
        }

        // Upsert: INSERT … ON DUPLICATE KEY UPDATE
        $colList = implode(', ', $cols);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $onDup = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $cols));
        $values = array_map(fn($c) => (int)(bool)$patch[$c], $cols);

        $this->db()->prepare(
            "INSERT INTO notification_preferences (patient_id, {$colList})
             VALUES (?, {$placeholders})
             ON DUPLICATE KEY UPDATE {$onDup}"
        )->execute(array_merge([$patientId], $values));
    }
}
