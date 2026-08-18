<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * InquiryModel — persistence for the inquiries table.
 *
 * Table: inquiries (migration 009)
 * Backs the public POST /api/v1/inquiries endpoint (drbastaninejad.com contact form).
 * No authentication required; rate-limiting handled in InquiryController.
 */
final class InquiryModel extends Model
{
    /**
     * Insert a new inquiry. Returns the new auto-increment id.
     */
    public function insert(array $fields): int
    {
        $this->db()->prepare(
            'INSERT INTO inquiries (name, phone, message, ip_address, status, created_at)
             VALUES (?, ?, ?, ?, "new", UTC_TIMESTAMP())'
        )->execute([
            $fields['name'],
            $fields['phone'],
            $fields['message'],
            $fields['ip_address'] ?? null,
        ]);
        return (int)$this->db()->lastInsertId();
    }

    /**
     * Count recent submissions from a given phone number within $windowMinutes.
     * Used by InquiryController to enforce per-phone rate-limiting.
     */
    public function countRecentByPhone(string $phone, int $windowMinutes = 30): int
    {
        $stmt = $this->db()->prepare(
            'SELECT COUNT(*) FROM inquiries
             WHERE phone = ?
               AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? MINUTE)'
        );
        $stmt->execute([$phone, $windowMinutes]);
        return (int)$stmt->fetchColumn();
    }
}
