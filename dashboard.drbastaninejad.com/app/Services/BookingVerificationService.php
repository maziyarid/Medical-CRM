<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

final class BookingVerificationService
{
    private const TTL_SECONDS = 1800;

    public function issue(string $mobile): array
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expiresAt = gmdate('Y-m-d H:i:s', time() + self::TTL_SECONDS);
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $db->prepare(
                'UPDATE booking_verifications SET consumed_at = UTC_TIMESTAMP()
                 WHERE mobile = ? AND consumed_at IS NULL'
            )->execute([$mobile]);
            $db->prepare(
                'INSERT INTO booking_verifications (mobile, token_hash, expires_at, created_at)
                 VALUES (?, ?, ?, UTC_TIMESTAMP())'
            )->execute([$mobile, $hash, $expiresAt]);
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
        return ['verification_token' => $token, 'expires_in' => self::TTL_SECONDS];
    }

    /** Consume inside the caller's booking transaction. */
    public function consume(\PDO $db, string $mobile, string $rawToken): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $rawToken)) {
            return false;
        }
        $hash = hash('sha256', $rawToken);
        $stmt = $db->prepare(
            'SELECT id FROM booking_verifications
             WHERE token_hash = ? AND mobile = ? AND consumed_at IS NULL
               AND expires_at >= UTC_TIMESTAMP()
             LIMIT 1 FOR UPDATE'
        );
        $stmt->execute([$hash, $mobile]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            return false;
        }
        $update = $db->prepare(
            'UPDATE booking_verifications SET consumed_at = UTC_TIMESTAMP()
             WHERE id = ? AND consumed_at IS NULL'
        );
        $update->execute([(int)$id]);
        return $update->rowCount() === 1;
    }
}
