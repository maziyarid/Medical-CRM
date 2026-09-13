<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Narrow, idempotent safety net for deployments that synchronise files but do
 * not execute SQL migrations. It only applies appointment-v2 migrations
 * 024-026, which are CREATE TABLE IF NOT EXISTS / INSERT IGNORE operations.
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
            if (!$this->isReady($db)) {
                $root = dirname(__DIR__, 2);
                foreach ([
                    $root . '/database/migrations/024_appointment_booking_payments_calendar.sql',
                    $root . '/database/migrations/025_grant_booking_permissions.sql',
                    $root . '/database/migrations/026_calendar_sync_cursor.sql',
                ] as $file) {
                    $this->runSqlFile($db, $file);
                }
            }

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
