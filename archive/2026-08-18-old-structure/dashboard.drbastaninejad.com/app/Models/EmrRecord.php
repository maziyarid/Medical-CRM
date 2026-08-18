<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

/**
 * EmrRecord
 * Maps to `emr_records` table. Dynamic EMR Editor (Medical CRM.md §5.7):
 * fixed fields (chief complaint, diagnosis, plan) + specialty-specific JSON blocks.
 * AI-drafted content is stored separately in `ai_draft` and never auto-saved into
 * the canonical fields without explicit staff acceptance.
 */
final class EmrRecord extends Model
{
    protected string $table = 'emr_records';

    public function forPatient(int $patientId, int $clinicId): array
    {
        $stmt = $this->db()->prepare(
            "SELECT e.*, u.full_name AS author_name
             FROM emr_records e
             LEFT JOIN users u ON u.id = e.author_id
             WHERE e.patient_id = ? AND e.clinic_id = ? AND e.deleted_at IS NULL
             ORDER BY e.created_at DESC"
        );
        $stmt->execute([$patientId, $clinicId]);
        return $stmt->fetchAll();
    }

    public function templatesForSpecialty(string $specialty): array
    {
        $stmt = $this->db()->prepare(
            "SELECT id, name, schema_json FROM emr_templates WHERE specialty = ? OR specialty = 'general' ORDER BY name"
        );
        $stmt->execute([$specialty]);
        return $stmt->fetchAll();
    }
}
