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
            "SELECT COUNT(*) FROM intakes WHERE clinic_id = ? AND status = 'verified'"
        );
        $pendingIntakes->execute([$clinicId]);
        $pending = (int)$pendingIntakes->fetchColumn();

        $revenueToday = $db->prepare(
            "SELECT COALESCE(SUM(amount_rials),0) FROM invoices
             WHERE clinic_id = ? AND status = 'paid' AND DATE(created_at) = CURDATE()"
        );
        $revenueToday->execute([$clinicId]);
        $revenue = (float)$revenueToday->fetchColumn();

        $openTasks = $db->prepare(
            "SELECT COUNT(*) FROM tasks WHERE clinic_id = ? AND status != 'done'"
        );
        $openTasks->execute([$clinicId]);
        $tasks = (int)$openTasks->fetchColumn();

        return $this->success([
            'metrics' => [
                ['label' => 'نوبت‌های امروز', 'value' => (string)$appointmentsToday, 'icon' => 'calendar', 'href' => '/appointments'],
                ['label' => 'پذیرش‌های در انتظار', 'value' => (string)$pending, 'icon' => 'clipboard-account', 'href' => '/intakes', 'deltaDir' => $pending > 0 ? 'down' : ''],
                ['label' => 'درآمد امروز', 'value' => number_format($revenue) . ' تومان', 'icon' => 'finance', 'href' => '/billing'],
                ['label' => 'وظایف باز', 'value' => (string)$tasks, 'icon' => 'check-circle-outline', 'href' => '/tasks'],
            ],
            'timeline' => $this->getTimelineEvents($db, $clinicId),
        ]);
    }

    /**
     * getTimelineEvents
     * Fetches and merges event streams from different tables into a unified,
     * chronologically sorted array, conforming to the DashboardTimelineEvent shape.
     */
    private function getTimelineEvents(\PDO $db, int $clinicId, int $limit = 20): array
    {
        // 1. Fetch recent intakes
        $intakeStmt = $db->prepare(
            "SELECT i.id, i.patient_id, i.created_at, i.status, i.referral_source,
                    p.first_name, p.last_name
             FROM intakes i
             LEFT JOIN patients p ON i.patient_id = p.id
             WHERE i.clinic_id = ?
             ORDER BY i.created_at DESC LIMIT ?"
        );
        $intakeStmt->execute([$clinicId, $limit]);
        $intakes = array_map(function ($r) {
            $statusMap = [
                'new' => ['label' => 'جدید', 'badge' => 'info'],
                'verified' => ['label' => 'در انتظار بررسی', 'badge' => 'warning'],
                'triaged' => ['label' => 'بررسی شده', 'badge' => 'primary'],
                'scheduled' => ['label' => 'نوبت‌دهی شده', 'badge' => 'success'],
                'archived' => ['label' => 'آرشیو شده', 'badge' => 'muted'],
            ];
            return [
                'id' => 'intake-' . $r['id'],
                'type' => 'intake',
                'timestamp' => $r['created_at'],
                'patient' => [
                    'id' => $r['patient_id'],
                    'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                    'href' => '/patients/' . $r['patient_id']
                ],
                'title' => 'پذیرش جدید دریافت شد',
                'description' => 'از طریق ' . ($r['referral_source'] ?? 'نامشخص') . ' ارسال شده.',
                'status' => $statusMap[$r['status']] ?? ['label' => $r['status'], 'badge' => 'secondary'],
                'actors' => [['type' => 'system', 'name' => 'فرم وب']],
                'href' => '/intakes/' . $r['id'],
            ];
        }, $intakeStmt->fetchAll());

        // 2. Fetch recent and upcoming appointments (no providers/users join — not in schema)
        $apptStmt = $db->prepare(
            "SELECT a.id, a.patient_id, a.scheduled_at, a.status, a.visit_reason,
                    p.first_name, p.last_name
             FROM appointments a
             JOIN patients p ON a.patient_id = p.id
             WHERE a.clinic_id = ? AND a.deleted_at IS NULL
               AND a.scheduled_at BETWEEN NOW() - INTERVAL 7 DAY AND NOW() + INTERVAL 7 DAY
             ORDER BY a.scheduled_at DESC LIMIT ?"
        );
        $apptStmt->execute([$clinicId, $limit]);
        $apptStatusMap = [
            'scheduled'  => ['label' => 'زمان‌بندی شده', 'badge' => 'info'],
            'confirmed'  => ['label' => 'تایید شده',      'badge' => 'success'],
            'cancelled'  => ['label' => 'لغو شده',        'badge' => 'error'],
            'completed'  => ['label' => 'تکمیل شده',      'badge' => 'muted'],
        ];
        $appointments = array_map(function ($r) use ($apptStatusMap) {
            return [
                'id'        => 'appointment-' . $r['id'],
                'type'      => 'appointment',
                'timestamp' => $r['scheduled_at'],
                'patient'   => [
                    'id'   => $r['patient_id'],
                    'name' => trim($r['first_name'] . ' ' . $r['last_name']),
                    'href' => '/patients/' . $r['patient_id'],
                ],
                'title'       => 'نوبت ' . ($r['visit_reason'] ?? 'معاینه'),
                'description' => date('H:i', strtotime($r['scheduled_at'])),
                'status'      => $apptStatusMap[$r['status']] ?? ['label' => $r['status'], 'badge' => 'secondary'],
                'href'        => '/appointments/' . $r['id'],
            ];
        }, $apptStmt->fetchAll());

        // 3. Merge and sort
        $timeline = array_merge($intakes, $appointments);
        usort($timeline, function ($a, $b) {
            return strtotime($b['timestamp']) - strtotime($a['timestamp']);
        });

        return array_slice($timeline, 0, $limit);
    }
}
