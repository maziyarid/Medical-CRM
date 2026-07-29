<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * PatientMediaModel — read metadata for patient-uploaded documents.
 *
 * Table: patient_media (migration 005)
 *
 * SECURITY RULE: signed_url is generated at query time with a 1-hour TTL.
 * The physical storage path MUST NOT be exposed to the client.
 * Only the signed CDN URL (or a server-generated proxy URL) is returned.
 *
 * Per docs/API_CONTRACT.md §GET /patient/documents
 */
final class PatientMediaModel extends Model
{
    /**
     * List all documents for a patient.
     * Returns only safe metadata — no raw storage_path.
     */
    public function listForPatient(int $patientId): array
    {
        $stmt = $this->db()->prepare(
            'SELECT media_uuid, file_name, mime_type, size_bytes, created_at AS uploaded_at
             FROM patient_media
             WHERE patient_id = ?
               AND deleted_at IS NULL
             ORDER BY created_at DESC'
        );
        $stmt->execute([$patientId]);
        $rows = $stmt->fetchAll();

        // Generate signed URLs server-side.
        // In local/dev mode, return a placeholder path.
        $cdnBase   = rtrim($_ENV['CDN_BASE_URL'] ?? '', '/');
        $ttlSeconds = 3600;
        $expiresAt  = gmdate('Y-m-d\TH:i:s\Z', time() + $ttlSeconds);

        return array_map(function (array $row) use ($cdnBase, $expiresAt): array {
            // Signed URL generation: in production, replace this stub with
            // an HMAC-signed ArvanCloud / S3-compatible presigned URL.
            // The media_uuid is safe to expose; it is not the storage path.
            $signedUrl = $cdnBase !== ''
                ? "{$cdnBase}/documents/{$row['media_uuid']}?expires=" . urlencode($expiresAt)
                : null;

            return [
                'media_uuid'   => $row['media_uuid'],
                'file_name'    => $row['file_name'],
                'mime_type'    => $row['mime_type'],
                'size_bytes'   => (int)$row['size_bytes'],
                'uploaded_at'  => $row['uploaded_at'],
                'signed_url'   => $signedUrl,
                'url_expires'  => $expiresAt,
            ];
        }, $rows);
    }
}
