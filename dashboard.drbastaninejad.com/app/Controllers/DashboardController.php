<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * DashboardController
 * Aggregates data for the Admin/CRM overview screen shown at dashboard.drbastaninejad.com
 * All queries are read-only and scoped by clinic_id from the authenticated user (RBAC-safe).
 * No section here duplicates WordPress functionality — this is the single-admin,
 * multi-subdomain control surface described in the platform roadmap.
 */
final class DashboardController extends Controller
{
    public function overview(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        $todayCount = $db->prepare(
            "SELECT COUNT(*) FROM appointments
             WHERE clinic_id = ? AND DATE(scheduled_at) = CURDATE() AND status != 'cancelled'"
        );
        $todayCount->execute([$clinicId]);
        $appointmentsToday = (int)$todayCount->fetchColumn();

        $pendingIntakes = $db->prepare(
            "SELECT COUNT(*) FROM intakes WHERE clinic_id = ? AND status = 'verified' AND reviewed_at IS NULL"
        );
        $pendingIntakes->execute([$clinicId]);
        $pending = (int)$pendingIntakes->fetchColumn();

        $revenueToday = $db->prepare(
            "SELECT COALESCE(SUM(paid_amount),0) FROM invoices
             WHERE clinic_id = ? AND DATE(paid_at) = CURDATE()"
        );
        $revenueToday->execute([$clinicId]);
        $revenue = (float)$revenueToday->fetchColumn();

        $openTasks = $db->prepare(
            "SELECT COUNT(*) FROM tasks WHERE clinic_id = ? AND status != 'done'"
        );
        $openTasks->execute([$clinicId]);
        $tasks = (int)$openTasks->fetchColumn();

        $attentionStmt = $db->prepare(
            "SELECT p.first_name, p.last_name, 'پذیرش بررسی‌نشده' AS item, 'warning' AS badge, 'در انتظار' AS status
             FROM intakes i JOIN patients p ON p.id = i.patient_id
             WHERE i.clinic_id = ? AND i.status = 'verified' AND i.reviewed_at IS NULL
             ORDER BY i.created_at DESC LIMIT 8"
        );
        $attentionStmt->execute([$clinicId]);
        $attention = array_map(function ($r) {
            return [
                'patient' => trim($r['first_name'] . ' ' . $r['last_name']),
                'item'    => $r['item'],
                'badge'   => $r['badge'],
                'status'  => $r['status'],
            ];
        }, $attentionStmt->fetchAll());

        $todayStmt = $db->prepare(
            "SELECT p.first_name, p.last_name, a.scheduled_at, a.visit_reason, a.status
             FROM appointments a JOIN patients p ON p.id = a.patient_id
             WHERE a.clinic_id = ? AND DATE(a.scheduled_at) = CURDATE()
             ORDER BY a.scheduled_at ASC"
        );
        $todayStmt->execute([$clinicId]);
        $today = array_map(function ($r) {
            $badgeMap = ['scheduled' => 'info', 'confirmed' => 'success', 'cancelled' => 'error', 'completed' => 'muted'];
            return [
                'patient' => trim($r['first_name'] . ' ' . $r['last_name']),
                'time'    => date('H:i', strtotime($r['scheduled_at'])),
                'reason'  => $r['visit_reason'] ?? '—',
                'status'  => $r['status'],
                'badge'   => $badgeMap[$r['status']] ?? 'muted',
            ];
        }, $todayStmt->fetchAll());

        return $this->success([
            'metrics' => [
                ['label' => 'نوبت‌های امروز', 'value' => (string)$appointmentsToday, 'href' => '#calendar'],
                ['label' => 'پذیرش‌های در انتظار', 'value' => (string)$pending, 'href' => '#patients', 'deltaDir' => $pending > 0 ? 'down' : ''],
                ['label' => 'درآمد امروز', 'value' => number_format($revenue) . ' تومان', 'href' => '#billing'],
                ['label' => 'وظایف باز', 'value' => (string)$tasks, 'href' => '#tasks'],
            ],
            'attention' => $attention,
            'today' => $today,
        ]);
    }
}
