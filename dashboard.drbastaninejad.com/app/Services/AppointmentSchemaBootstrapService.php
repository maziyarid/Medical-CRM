<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Narrow, idempotent safety net for deployments that synchronise files but do
 * not execute SQL migrations. Base appointment-v2 tables come from 024-026;
 * later additive columns/indexes are checked directly before ALTERing so an
 * already-running production database is never asked to re-apply a migration.
 */
final class AppointmentSchemaBootstrapService
{
    private static bool $ready = false;

    public function ensure(): void
    {
        if (self::$ready) {
            return;
        }

        $db = Database::conn();
        if ($this->isReady($db)) {
            self::$ready = true;
            return;
        }

        $lockName = 'crm:schema:appointment-v2';
        $lock = $db->prepare('SELECT GET_LOCK(?, 10)');
        $lock->execute([$lockName]);
        if ((int)$lock->fetchColumn() !== 1) {
            throw new RuntimeException('appointment schema migration lock timeout');
        }

        try {
            if (!$this->baseTablesReady($db)) {
                $root = dirname(__DIR__, 2);
                foreach ([
                    $root . '/database/migrations/024_appointment_booking_payments_calendar.sql',
                    $root . '/database/migrations/025_grant_booking_permissions.sql',
                    $root . '/database/migrations/026_calendar_sync_cursor.sql',
                ] as $file) {
                    $this->runSqlFile($db, $file);
                }
            }

            $this->ensureBookingPermissions($db);
            $this->ensureScheduledVisitSyncSchema($db);
            $this->ensureFollowupAuditSchema($db);

            if (!$this->isReady($db)) {
                throw new RuntimeException('appointment schema bootstrap incomplete');
            }
            self::$ready = true;
        } finally {
            try {
                $release = $db->prepare('SELECT RELEASE_LOCK(?)');
                $release->execute([$lockName]);
            } catch (\Throwable $e) {
                error_log('[AppointmentSchemaBootstrapService] lock release failed: ' . $e->getMessage());
            }
        }
    }

    private function isReady(PDO $db): bool
    {
        return $this->baseTablesReady($db)
            && $this->columnExists($db, 'appointment_booking_requests', 'sheet_sync_status')
            && $this->columnExists($db, 'appointment_booking_requests', 'sheet_synced_at')
            && $this->columnExists($db, 'appointment_booking_requests', 'sheet_sync_error')
            && $this->indexExists($db, 'appointment_booking_requests', 'idx_booking_sheet_sync')
            && $this->columnExists($db, 'appointment_booking_requests', 'followup_completed_at')
            && $this->columnExists($db, 'appointment_booking_requests', 'followup_completed_by')
            && $this->indexExists($db, 'appointment_booking_requests', 'idx_booking_followup_queue');
    }

    private function baseTablesReady(PDO $db): bool
    {
        $expected = [
            'appointment_open_days',
            'appointment_booking_requests',
            'appointment_payment_attempts',
            'calendar_event_links',
            'calendar_sync_outbox',
            'calendar_sync_cursors',
        ];
        $placeholders = implode(',', array_fill(0, count($expected), '?'));
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ({$placeholders})"
        );
        $stmt->execute($expected);
        return (int)$stmt->fetchColumn() === count($expected);
    }

    private function ensureBookingPermissions(PDO $db): void
    {
        $db->exec("INSERT IGNORE INTO permissions (name, description) VALUES
            ('booking.manage', 'View and manage appointment booking requests'),
            ('booking.availability.manage', 'Manage clinic open days, slots and quotas'),
            ('booking.payments.reconcile', 'Reconcile appointment payment attempts'),
            ('calendar.sync.manage', 'Manage Google Calendar synchronisation'),
            ('booking.integrations.manage', 'Manage appointment integration credentials')");
        $db->exec("INSERT IGNORE INTO permission_role (role_id, permission_id)
            SELECT r.id, p.id FROM roles r
            JOIN permissions p ON p.name IN (
                'booking.manage','booking.availability.manage','booking.payments.reconcile',
                'calendar.sync.manage','booking.integrations.manage'
            ) WHERE r.name = 'super_admin'");
        $db->exec("INSERT IGNORE INTO permission_role (role_id, permission_id)
            SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name = 'booking.manage'
            WHERE r.name IN ('receptionist','doctor')");
    }

    private function ensureScheduledVisitSyncSchema(PDO $db): void
    {
        if (!$this->columnExists($db, 'appointment_booking_requests', 'sheet_sync_status')) {
            $db->exec(
                "ALTER TABLE appointment_booking_requests
                 ADD COLUMN sheet_sync_status ENUM('pending','synced','error','skipped')
                 NOT NULL DEFAULT 'pending' AFTER staff_followup_required"
            );
        }
        if (!$this->columnExists($db, 'appointment_booking_requests', 'sheet_synced_at')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD COLUMN sheet_synced_at DATETIME NULL AFTER sheet_sync_status'
            );
        }
        if (!$this->columnExists($db, 'appointment_booking_requests', 'sheet_sync_error')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD COLUMN sheet_sync_error VARCHAR(500) NULL AFTER sheet_synced_at'
            );
        }
        if (!$this->indexExists($db, 'appointment_booking_requests', 'idx_booking_sheet_sync')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD INDEX idx_booking_sheet_sync (sheet_sync_status, updated_at)'
            );
        }
    }

    private function ensureFollowupAuditSchema(PDO $db): void
    {
        if (!$this->columnExists($db, 'appointment_booking_requests', 'followup_completed_at')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD COLUMN followup_completed_at DATETIME NULL AFTER staff_followup_required'
            );
        }
        if (!$this->columnExists($db, 'appointment_booking_requests', 'followup_completed_by')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD COLUMN followup_completed_by INT UNSIGNED NULL AFTER followup_completed_at'
            );
        }
        if (!$this->indexExists($db, 'appointment_booking_requests', 'idx_booking_followup_queue')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD INDEX idx_booking_followup_queue (clinic_id, staff_followup_required, confirmation_status)'
            );
        }
        if (!$this->constraintExists($db, 'appointment_booking_requests', 'fk_booking_followup_user')) {
            $db->exec(
                'ALTER TABLE appointment_booking_requests
                 ADD CONSTRAINT fk_booking_followup_user FOREIGN KEY (followup_completed_by)
                 REFERENCES users(id) ON DELETE SET NULL'
            );
        }
    }

    private function columnExists(PDO $db, string $table, string $column): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table, $column]);
        return (int)$stmt->fetchColumn() === 1;
    }

    private function indexExists(PDO $db, string $table, string $index): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        $stmt->execute([$table, $index]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function constraintExists(PDO $db, string $table, string $constraint): bool
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?'
        );
        $stmt->execute([$table, $constraint]);
        return (int)$stmt->fetchColumn() > 0;
    }

    private function runSqlFile(PDO $db, string $file): void
    {
        $sql = @file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('migration file missing: ' . basename($file));
        }

        $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
        $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }
            $db->exec($statement);
        }
    }
}
