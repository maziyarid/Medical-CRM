<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * AnalyticsController
 * Powers the Analytics & Reports screen.
 * All queries are read-only and scoped by clinic_id from the authenticated user.
 */
final class AnalyticsController extends Controller
{
    /**
     * GET /api/v1/analytics/summary?range=30d|90d|1y&date_from=&date_to=
     *
     * Returns:
     *   kpis[]: { key, label, value, delta_pct, delta_dir }
     *   chart_new_patients[]: { week_label, count }  (4–52 data points)
     *   referral_sources[]: { source, count, pct }
     */
    public function summary(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $req->query['range']     ?? '30d',
            $req->query['date_from'] ?? null,
            $req->query['date_to']   ?? null
        );

        // ── KPI 1: new patients in range ────────────────────────────
        $newStmt = $db->prepare(
            "SELECT COUNT(*) FROM patients
             WHERE clinic_id = ? AND created_at BETWEEN ? AND ?"
        );
        $newStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $newPatients = (int)$newStmt->fetchColumn();

        // Previous period for delta
        $prevDays = (int)round((strtotime($dateTo) - strtotime($dateFrom)) / 86400);
        $prevFrom = date('Y-m-d H:i:s', strtotime($dateFrom) - $prevDays * 86400);
        $prevTo   = date('Y-m-d H:i:s', strtotime($dateTo)   - $prevDays * 86400);

        $prevStmt = $db->prepare(
            "SELECT COUNT(*) FROM patients WHERE clinic_id = ? AND created_at BETWEEN ? AND ?"
        );
        $prevStmt->execute([$clinicId, $prevFrom, $prevTo]);
        $prevNew = (int)$prevStmt->fetchColumn();
        $newDelta = $prevNew > 0 ? round(($newPatients - $prevNew) / $prevNew * 100, 1) : 0;

        // ── KPI 2: conversion rate (intake → appointment) ───────────
        $intakeStmt = $db->prepare(
            "SELECT COUNT(*) FROM intakes WHERE clinic_id = ? AND created_at BETWEEN ? AND ?"
        );
        $intakeStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $intakes = (int)$intakeStmt->fetchColumn();

        $apptStmt = $db->prepare(
            "SELECT COUNT(DISTINCT patient_id) FROM appointments
             WHERE clinic_id = ? AND scheduled_at BETWEEN ? AND ? AND status != 'cancelled'"
        );
        $apptStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $bookedPatients = (int)$apptStmt->fetchColumn();

        $convRate = $intakes > 0 ? round($bookedPatients / $intakes * 100, 1) : 0;

        // ── KPI 3: total revenue ────────────────────────────────────
        $revStmt = $db->prepare(
            "SELECT COALESCE(SUM(amount_rials),0) FROM invoices
             WHERE clinic_id = ? AND status = 'paid' AND created_at BETWEEN ? AND ?"
        );
        $revStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $revenue = (float)$revStmt->fetchColumn();

        $prevRevStmt = $db->prepare(
            "SELECT COALESCE(SUM(amount_rials),0) FROM invoices
             WHERE clinic_id = ? AND status = 'paid' AND created_at BETWEEN ? AND ?"
        );
        $prevRevStmt->execute([$clinicId, $prevFrom, $prevTo]);
        $prevRevenue = (float)$prevRevStmt->fetchColumn();
        $revDelta = $prevRevenue > 0 ? round(($revenue - $prevRevenue) / $prevRevenue * 100, 1) : 0;

        // ── KPI 4: return rate (patients with 2+ visits in period) ──
        $returnStmt = $db->prepare(
            "SELECT COUNT(*) FROM (
                 SELECT patient_id FROM appointments
                 WHERE clinic_id = ? AND scheduled_at BETWEEN ? AND ? AND status = 'completed' AND deleted_at IS NULL
                 GROUP BY patient_id HAVING COUNT(*) >= 2
             ) returning_patients"
        );
        $returnStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $returningCount = (int)$returnStmt->fetchColumn();
        $returnRate = $bookedPatients > 0 ? round($returningCount / $bookedPatients * 100, 1) : 0;

        // ── Chart: weekly new patients ───────────────────────────────
        $chartStmt = $db->prepare(
            "SELECT YEARWEEK(created_at, 1) AS yw, COUNT(*) AS cnt
             FROM patients
             WHERE clinic_id = ? AND created_at BETWEEN ? AND ?
             GROUP BY yw ORDER BY yw ASC LIMIT 52"
        );
        $chartStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $chartRows = $chartStmt->fetchAll();

        $chart = array_map(function ($r) {
            return ['week' => (string)$r['yw'], 'count' => (int)$r['cnt']];
        }, $chartRows);

        // ── Referral sources ─────────────────────────────────────────
        $refStmt = $db->prepare(
            "SELECT CASE source_type WHEN 'booking' THEN 'رزرو وب‌سایت' ELSE 'فرم پذیرش' END AS source, COUNT(*) AS cnt
             FROM intakes
             WHERE clinic_id = ? AND created_at BETWEEN ? AND ?
             GROUP BY source ORDER BY cnt DESC LIMIT 10"
        );
        $refStmt->execute([$clinicId, $dateFrom, $dateTo]);
        $refRows = $refStmt->fetchAll();
        $refTotal = array_sum(array_column($refRows, 'cnt'));
        $referrals = array_map(function ($r) use ($refTotal) {
            return [
                'source' => $r['source'],
                'count'  => (int)$r['cnt'],
                'pct'    => $refTotal > 0 ? round((int)$r['cnt'] / $refTotal * 100, 1) : 0,
            ];
        }, $refRows);

        return $this->success([
            'range' => ['from' => $dateFrom, 'to' => $dateTo],
            'kpis' => [
                ['key' => 'new_patients',    'label' => 'مراجعین جدید',              'value' => $newPatients,    'delta_pct' => $newDelta, 'delta_dir' => $newDelta >= 0 ? 'up' : 'down'],
                ['key' => 'conversion_rate', 'label' => 'نرخ تبدیل مشاوره → عمل',   'value' => $convRate,       'delta_pct' => 0, 'delta_dir' => ''],
                ['key' => 'revenue',         'label' => 'درآمد (ریال)',              'value' => $revenue,        'delta_pct' => $revDelta, 'delta_dir' => $revDelta >= 0 ? 'up' : 'down'],
                ['key' => 'return_rate',     'label' => 'نرخ بازگشت بیمار',         'value' => $returnRate,     'delta_pct' => 0, 'delta_dir' => ''],
            ],
            'chart_new_patients' => $chart,
            'referral_sources'   => $referrals,
        ]);
    }

    private function resolveDateRange(string $range, ?string $from, ?string $to): array
    {
        if ($from && $to) {
            return [
                date('Y-m-d 00:00:00', strtotime($from)),
                date('Y-m-d 23:59:59', strtotime($to)),
            ];
        }
        $days = match($range) {
            '90d' => 90,
            '1y'  => 365,
            default => 30,
        };
        return [
            date('Y-m-d 00:00:00', strtotime("-{$days} days")),
            date('Y-m-d 23:59:59'),
        ];
    }
}
