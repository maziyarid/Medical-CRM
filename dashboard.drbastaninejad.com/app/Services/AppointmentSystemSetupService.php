<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use Throwable;

/**
 * Idempotent production-readiness checks for appointment booking v2.
 *
 * This intentionally does not touch patient-app files or credentials. It only
 * ensures the additive database objects introduced by migrations 021-026 exist.
 */
final class AppointmentSystemSetupService
{
    /** @return array<string,mixed> */
    public function readiness(int $clinicId): array
    {
        $tables = [
            'booking_verifications',
            'appointment_open_days',
            'appointment_booking_requests',
            'appointment_payment_attempts',
            'calendar_event_links',
            'calendar_sync_outbox',
            'calendar_sync_cursors',
        ];
        $tableState = [];
        foreach ($tables as $table) {
            $tableState[$table] = $this->tableExists($table);
        }

        $intakeColumns = [
            'booking_sheet_status' => $this->columnExists('intakes', 'booking_sheet_status'),
            'booking_email_status' => $this->columnExists('intakes', 'booking_email_status'),
        ];

        $enabled = array_values(array_filter(array_map(
            'trim',
            explode(',', strtolower((string)($_ENV['BOOKING_PAYMENT_GATEWAYS'] ?? 'zarinpal,vandar')))
        )));
        $gatewayState = [
            'zarinpal' => in_array('zarinpal', $enabled, true)
                && trim((string)($_ENV['ZARINPAL_MERCHANT_ID'] ?? '')) !== '',
            'vandar' => in_array('vandar', $enabled, true)
                && trim((string)($_ENV['VANDAR_API_KEY'] ?? '')) !== '',
        ];

        $openDays = 0;
        if ($tableState['appointment_open_days']) {
            try {
                $stmt = Database::conn()->prepare(
                    'SELECT COUNT(*) FROM appointment_open_days
                     WHERE clinic_id = ? AND status = "open"
                       AND open_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 60 DAY)'
                );
                $stmt->execute([$clinicId]);
                $openDays = (int)$stmt->fetchColumn();
            } catch (Throwable) {
                $openDays = 0;
            }
        }

        $schemaReady = !in_array(false, $tableState, true)
            && !in_array(false, $intakeColumns, true);
        $deposit = (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0);
        $callback = trim((string)($_ENV['BOOKING_PAYMENT_CALLBACK_BASE'] ?? ''));
        $anyGateway = in_array(true, $gatewayState, true);

        return [
            'schema_ready' => $schemaReady,
            'tables' => $tableState,
            'intake_columns' => $intakeColumns,
            'deposit_configured' => $deposit > 0,
            'deposit_rials' => max(0, $deposit),
            'callback_configured' => $callback !== '',
            'gateways' => $gatewayState,
            'open_days_next_60' => $openDays,
            'calendar_configured' => $this->calendarConfigured(),
            'public_booking_ready' => $schemaReady && $deposit > 0 && $callback !== '' && $anyGateway,
        ];
    }

    /** @return array{ok:bool,applied:string[],errors:array<int,array{step:string,message:string}>,readiness:array<string,mixed>} */
    public function ensureSchema(int $clinicId): array
    {
        $applied = [];
        $errors = [];

        $steps = [
            '021_indexes' => function (): void {
                $this->ensureIndex('appointments', 'idx_appointments_clinic_room_sched',
                    'ALTER TABLE appointments ADD INDEX idx_appointments_clinic_room_sched (clinic_id, room, scheduled_at)');
                $this->ensureIndex('intakes', 'uk_submission_uuid',
                    'ALTER TABLE intakes ADD UNIQUE INDEX uk_submission_uuid (submission_uuid)');
                $this->ensureIndex('otp_codes', 'idx_otp_codes_expires',
                    'ALTER TABLE otp_codes ADD INDEX idx_otp_codes_expires (expires_at)');
                $this->ensureIndex('reminder_log', 'idx_reminder_appt_status',
                    'ALTER TABLE reminder_log ADD INDEX idx_reminder_appt_status (appointment_id, status, remind_at)');
            },
            '022_foreign_keys' => function (): void {
                $this->ensureForeignKey('intakes', 'fk_intakes_clinic',
                    'ALTER TABLE intakes ADD CONSTRAINT fk_intakes_clinic FOREIGN KEY (clinic_id) REFERENCES clinics(id) ON DELETE CASCADE');
            },
            '023_booking_verification' => function (): void {
                Database::conn()->exec(
                    'CREATE TABLE IF NOT EXISTS booking_verifications (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                        mobile VARCHAR(15) NOT NULL,
                        token_hash CHAR(64) NOT NULL,
                        expires_at DATETIME NOT NULL,
                        consumed_at DATETIME NULL,
                        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                        PRIMARY KEY (id),
                        UNIQUE KEY uq_booking_verification_token (token_hash),
                        KEY idx_booking_verification_mobile (mobile, consumed_at, expires_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
                );
                $this->ensureColumn('intakes', 'booking_sheet_status',
                    'ALTER TABLE intakes ADD COLUMN booking_sheet_status ENUM("pending","attempting","submitted","failed_confirmed","outcome_unknown","skipped") NOT NULL DEFAULT "pending" AFTER sheets_sync_status');
                $this->ensureColumn('intakes', 'booking_email_status',
                    'ALTER TABLE intakes ADD COLUMN booking_email_status ENUM("pending","sent","failed","skipped") NOT NULL DEFAULT "pending" AFTER booking_sheet_status');
            },
            '024_booking_payments_calendar' => fn() => $this->executeMigrationFile('024_appointment_booking_payments_calendar.sql'),
            '025_permissions' => fn() => $this->executeMigrationFile('025_grant_booking_permissions.sql'),
            '026_calendar_cursor' => fn() => $this->executeMigrationFile('026_calendar_sync_cursor.sql'),
        ];

        foreach ($steps as $name => $step) {
            try {
                $step();
                $applied[] = $name;
            } catch (Throwable $e) {
                $errors[] = ['step' => $name, 'message' => $e->getMessage()];
            }
        }

        $readiness = $this->readiness($clinicId);
        return [
            'ok' => empty($errors) && !empty($readiness['schema_ready']),
            'applied' => $applied,
            'errors' => $errors,
            'readiness' => $readiness,
        ];
    }

    private function calendarConfigured(): bool
    {
        foreach (['GOOGLE_CALENDAR_ID', 'GOOGLE_CALENDAR_CLIENT_ID', 'GOOGLE_CALENDAR_CLIENT_SECRET', 'GOOGLE_CALENDAR_REFRESH_TOKEN'] as $key) {
            if (trim((string)($_ENV[$key] ?? '')) === '') {
                return false;
            }
        }
        return true;
    }

    private function tableExists(string $table): bool
    {
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
        );
        $stmt->execute([$table]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!$this->tableExists($table)) {
            return false;
        }
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function ensureColumn(string $table, string $column, string $ddl): void
    {
        if (!$this->columnExists($table, $column)) {
            Database::conn()->exec($ddl);
        }
    }

    private function ensureIndex(string $table, string $index, string $ddl): void
    {
        if (!$this->tableExists($table)) {
            return;
        }
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        if ((int)$stmt->fetchColumn() === 0) {
            Database::conn()->exec($ddl);
        }
    }

    private function ensureForeignKey(string $table, string $name, string $ddl): void
    {
        if (!$this->tableExists($table)) {
            return;
        }
        $stmt = Database::conn()->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ?
               AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = "FOREIGN KEY"'
        );
        $stmt->execute([$table, $name]);
        if ((int)$stmt->fetchColumn() === 0) {
            Database::conn()->exec($ddl);
        }
    }

    private function executeMigrationFile(string $filename): void
    {
        $path = defined('BASE_PATH') ? BASE_PATH . '/database/migrations/' . $filename : '';
        if ($path === '' || !is_readable($path)) {
            throw new \RuntimeException('migration file not readable: ' . $filename);
        }
        $sql = (string)file_get_contents($path);
        $sql = (string)preg_replace('/^\s*--.*$/m', '', $sql);
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                Database::conn()->exec($statement);
            }
        }
    }
}
