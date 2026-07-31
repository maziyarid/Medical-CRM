<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * SettingsController
 * Clinic-level settings — information, working hours.
 */
final class SettingsController extends Controller
{
    /**
     * GET /api/v1/settings/clinic
     */
    public function show(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        $stmt = $db->prepare(
            "SELECT id, name, phone, address, timezone, working_hours_json,
                    created_at, updated_at
             FROM clinics WHERE id = ?"
        );
        $stmt->execute([$clinicId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->error('تنظیمات کلینیک یافت نشد', 404);
        }

        $workingHours = null;
        if ($row['working_hours_json']) {
            $workingHours = json_decode($row['working_hours_json'], true);
        }

        return $this->success([
            'id'            => (int)$row['id'],
            'name'          => $row['name'],
            'phone'         => $row['phone'] ?? null,
            'address'       => $row['address'] ?? null,
            'timezone'      => $row['timezone'] ?? 'Asia/Tehran',
            'working_hours' => $workingHours,
            'updated_at'    => $row['updated_at'],
        ]);
    }

    /**
     * PATCH /api/v1/settings/clinic
     * Body (all optional): { name, phone, address, timezone, working_hours }
     */
    public function update(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;
        $db = Database::conn();

        // Verify clinic exists and belongs to user
        $check = $db->prepare("SELECT id FROM clinics WHERE id = ?");
        $check->execute([$clinicId]);
        if (!$check->fetch()) {
            return $this->error('کلینیک یافت نشد', 404);
        }

        $sets   = [];
        $params = [];

        $allowed = ['name', 'phone', 'address', 'timezone'];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $in)) {
                $sets[]   = "{$field} = ?";
                $params[] = $in[$field];
            }
        }

        if (array_key_exists('working_hours', $in)) {
            $sets[]   = 'working_hours_json = ?';
            $params[] = json_encode($in['working_hours'], JSON_UNESCAPED_UNICODE);
        }

        if (empty($sets)) {
            return $this->error('هیچ فیلدی برای به‌روزرسانی ارسال نشده', 422);
        }

        $sets[]   = 'updated_at = NOW()';
        $params[] = $clinicId;

        $db->prepare("UPDATE clinics SET " . implode(', ', $sets) . " WHERE id = ?")
           ->execute($params);

        return $this->success(['id' => $clinicId]);
    }
}
