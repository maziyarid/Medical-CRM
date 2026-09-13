<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class StaffSchemaBootstrapService
{
    private static bool $ready = false;

    public function ensure(): void
    {
        if (self::$ready) return;
        $db = Database::conn();
        if ($this->isReady($db)) { self::$ready = true; return; }

        $lockName = 'crm:schema:staff-onboarding';
        $stmt = $db->prepare('SELECT GET_LOCK(?, 10)');
        $stmt->execute([$lockName]);
        if ((int)$stmt->fetchColumn() !== 1) {
            throw new RuntimeException('staff schema migration lock timeout');
        }
        try {
            $root = dirname(__DIR__, 2);
            $this->runSqlFile($db, $root . '/database/migrations/029_staff_onboarding_notifications.sql');
            $this->ensureInviteForeignKey($db);
            if (!$this->isReady($db)) {
                throw new RuntimeException('staff schema bootstrap incomplete');
            }
            self::$ready = true;
        } finally {
            try { $r=$db->prepare('SELECT RELEASE_LOCK(?)'); $r->execute([$lockName]); } catch (\Throwable) {}
        }
    }

    private function isReady(PDO $db): bool
    {
        foreach ([
            ['users','invited_by'],['users','invited_at'],['users','activated_at'],['users','last_login_at'],
            ['patients','registration_sms_status'],['patients','registration_email_status'],['patients','registration_notified_at'],
        ] as [$table,$column]) {
            if (!$this->columnExists($db,$table,$column)) return false;
        }
        $q=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
        $q->execute(['staff_login_attempts']);
        if ((int)$q->fetchColumn() !== 1) return false;
        $q=$db->prepare('SELECT COUNT(*) FROM permissions WHERE name=?');
        $q->execute(['staff.manage']);
        return (int)$q->fetchColumn() === 1;
    }

    private function ensureInviteForeignKey(PDO $db): void
    {
        $q=$db->prepare('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=?');
        $q->execute(['users','fk_users_invited_by']);
        if ((int)$q->fetchColumn() === 0) {
            try {
                $db->exec('ALTER TABLE users ADD CONSTRAINT fk_users_invited_by FOREIGN KEY (invited_by) REFERENCES users(id) ON DELETE SET NULL');
            } catch (\Throwable $e) {
                error_log('[StaffSchemaBootstrapService] invite FK skipped: '.$e->getMessage());
            }
        }
    }

    private function columnExists(PDO $db,string $table,string $column): bool
    {
        $q=$db->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->execute([$table,$column]);
        return (int)$q->fetchColumn() === 1;
    }

    private function runSqlFile(PDO $db,string $file): void
    {
        $sql=@file_get_contents($file);
        if ($sql===false) throw new RuntimeException('migration file missing: '.basename($file));
        $sql=preg_replace('/^\s*--.*$/m','',$sql) ?? $sql;
        foreach (preg_split('/;\s*(?:\r?\n|$)/',$sql) ?: [] as $statement) {
            $statement=trim($statement);
            if ($statement!=='') $db->exec($statement);
        }
    }
}
