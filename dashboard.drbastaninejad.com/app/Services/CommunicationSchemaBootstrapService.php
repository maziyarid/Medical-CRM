<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class CommunicationSchemaBootstrapService
{
    public function ensure(): void
    {
        $db = Database::conn();

        $db->exec(
            'CREATE TABLE IF NOT EXISTS communication_threads (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                clinic_id BIGINT UNSIGNED NOT NULL,
                source VARCHAR(32) NOT NULL DEFAULT "email",
                external_key VARCHAR(191) NULL,
                contact_name VARCHAR(190) NOT NULL DEFAULT "",
                contact_email VARCHAR(254) NULL,
                contact_phone VARCHAR(32) NULL,
                subject VARCHAR(255) NOT NULL DEFAULT "",
                status VARCHAR(20) NOT NULL DEFAULT "open",
                unread_count INT UNSIGNED NOT NULL DEFAULT 0,
                assigned_user_id BIGINT UNSIGNED NULL,
                last_message_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_communication_external (clinic_id, external_key),
                KEY idx_communication_status (clinic_id, status, last_message_at),
                KEY idx_communication_email (clinic_id, contact_email),
                KEY idx_communication_unread (clinic_id, unread_count, last_message_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $this->ensureColumn($db, 'communication_threads', 'category', 'VARCHAR(32) NOT NULL DEFAULT "contact" AFTER source');
        $this->ensureColumn($db, 'communication_threads', 'is_starred', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER status');

        $db->exec(
            'CREATE TABLE IF NOT EXISTS communication_messages (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                thread_id BIGINT UNSIGNED NOT NULL,
                direction VARCHAR(16) NOT NULL,
                channel VARCHAR(24) NOT NULL DEFAULT "email",
                sender_name VARCHAR(190) NOT NULL DEFAULT "",
                sender_email VARCHAR(254) NULL,
                recipient_email VARCHAR(254) NULL,
                subject VARCHAR(255) NOT NULL DEFAULT "",
                body_text MEDIUMTEXT NOT NULL,
                body_html MEDIUMTEXT NULL,
                external_message_id VARCHAR(255) NULL,
                in_reply_to VARCHAR(255) NULL,
                delivery_status VARCHAR(24) NOT NULL DEFAULT "received",
                created_by BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_communication_message_external (external_message_id),
                KEY idx_communication_message_thread (thread_id, created_at),
                CONSTRAINT fk_communication_message_thread
                    FOREIGN KEY (thread_id) REFERENCES communication_threads(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS communication_labels (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                clinic_id BIGINT UNSIGNED NOT NULL,
                name VARCHAR(80) NOT NULL,
                color_key VARCHAR(24) NOT NULL DEFAULT "green",
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY uq_communication_label_name (clinic_id, name),
                KEY idx_communication_label_clinic (clinic_id, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS communication_thread_labels (
                thread_id BIGINT UNSIGNED NOT NULL,
                label_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (thread_id, label_id),
                KEY idx_communication_thread_label (label_id, thread_id),
                CONSTRAINT fk_communication_thread_label_thread
                    FOREIGN KEY (thread_id) REFERENCES communication_threads(id)
                    ON DELETE CASCADE,
                CONSTRAINT fk_communication_thread_label_label
                    FOREIGN KEY (label_id) REFERENCES communication_labels(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $db->exec(
            'CREATE TABLE IF NOT EXISTS communication_settings (
                clinic_id BIGINT UNSIGNED NOT NULL,
                inbox_email VARCHAR(254) NOT NULL DEFAULT "contact@drbastaninejad.com",
                from_name VARCHAR(190) NOT NULL DEFAULT "مطب دکتر شاهین باستانی‌نژاد",
                auto_response_enabled TINYINT(1) NOT NULL DEFAULT 1,
                auto_response_subject VARCHAR(255) NOT NULL DEFAULT "پیام شما دریافت شد",
                auto_response_body TEXT NOT NULL,
                signature_text TEXT NOT NULL,
                booking_url VARCHAR(500) NOT NULL DEFAULT "https://drbastaninejad.com/booking/",
                footer_phone VARCHAR(255) NOT NULL DEFAULT "",
                footer_address VARCHAR(500) NOT NULL DEFAULT "",
                last_inbox_sync_at DATETIME NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (clinic_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );

        $body = 'سلام، پیام شما دریافت شد. لطفا در صورتی که قصد تعیین وقت دارید وارد لینک زیر شده و فرم رزرو نوبت تکمیل کنید.';
        $signature = "با احترام\nمطب دکتر شاهین باستانی‌نژاد";
        $phone = '۰۲۱۸۶۰۸۷۲۵۰ | ۰۲۱۸۸۲۰۵۶۰۶ | ۰۹۹۱۲۴۹۶۶۵۹';
        $address = 'تهران، خیابان نلسون ماندلا، خیابان صانعی، ساختمان نور، پلاک ۱، واحد ۶';
        $stmt = $db->prepare(
            'INSERT IGNORE INTO communication_settings
             (clinic_id, inbox_email, from_name, auto_response_enabled, auto_response_subject,
              auto_response_body, signature_text, booking_url, footer_phone, footer_address, updated_at)
             VALUES (?, "contact@drbastaninejad.com", "مطب دکتر شاهین باستانی‌نژاد", 1,
                     "پیام شما دریافت شد", ?, ?, "https://drbastaninejad.com/booking/", ?, ?, UTC_TIMESTAMP())'
        );
        $stmt->execute([1, $body, $signature, $phone, $address]);

        $seed = $db->prepare(
            'INSERT IGNORE INTO communication_labels (clinic_id,name,color_key,created_at)
             VALUES (?,?,?,UTC_TIMESTAMP())'
        );
        foreach ([
            ['نیاز به پیگیری','amber'],
            ['رزرو و نوبت','blue'],
            ['پاسخ داده شد','green'],
            ['بازیابی‌شده','purple'],
        ] as [$name,$color]) {
            $seed->execute([1,$name,$color]);
        }
    }

    private function ensureColumn(PDO $db, string $table, string $column, string $definition): void
    {
        $stmt = $db->prepare(
            'SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        $stmt->execute([$table,$column]);
        if ((int)$stmt->fetchColumn() === 0) {
            $db->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
        }
    }
}