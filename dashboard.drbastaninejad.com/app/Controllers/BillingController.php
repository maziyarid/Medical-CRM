<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * BillingController
 * Invoice management — list, create, update status.
 */
final class BillingController extends Controller
{
    /**
     * GET /api/v1/billing/invoices
     * ?q=&status=&gateway=&page=&per_page=
     */
    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        $q        = trim((string)($req->query['q']       ?? ''));
        $status   = $req->query['status']  ?? '';
        $gateway  = $req->query['gateway'] ?? '';
        $page     = max(1, (int)($req->query['page']     ?? 1));
        $perPage  = min(100, max(1, (int)($req->query['per_page'] ?? 20)));
        $offset   = ($page - 1) * $perPage;

        $where = 'i.clinic_id = :clinic';
        $params = [':clinic' => $clinicId];

        if ($q !== '') {
            $where .= ' AND (p.first_name LIKE :q OR p.last_name LIKE :q)';
            $params[':q'] = "%{$q}%";
        }
        if ($status !== '') {
            $where .= ' AND i.status = :status';
            $params[':status'] = $status;
        }
        if ($gateway !== '') {
            $where .= ' AND i.gateway = :gateway';
            $params[':gateway'] = $gateway;
        }

        $countStmt = $db->prepare(
            "SELECT COUNT(*) FROM invoices i
             LEFT JOIN patients p ON i.patient_id = p.id
             WHERE {$where}"
        );
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $params[':limit']  = $perPage;
        $params[':offset'] = $offset;

        $rowsStmt = $db->prepare(
            "SELECT i.id, i.patient_id, i.amount_rials, i.status, i.gateway, i.notes,
                    i.created_at,
                    p.first_name, p.last_name
             FROM invoices i
             LEFT JOIN patients p ON i.patient_id = p.id
             WHERE {$where}
             ORDER BY i.created_at DESC
             LIMIT :limit OFFSET :offset"
        );
        $rowsStmt->execute($params);
        $rows = array_map(function ($r) {
            return [
                'id'           => (int)$r['id'],
                'patient_id'   => (int)$r['patient_id'],
                'patient_name' => trim($r['first_name'] . ' ' . $r['last_name']),
                'amount_rials' => (float)$r['amount_rials'],
                'status'       => $r['status'],
                'gateway'      => $r['gateway'] ?? '—',
                'notes'        => $r['notes'] ?? null,
                'created_at'   => $r['created_at'],
            ];
        }, $rowsStmt->fetchAll());

        // Summary KPIs for billing page header cards
        $summaryStmt = $db->prepare(
            "SELECT
               COUNT(*) AS total_all,
               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
               SUM(CASE WHEN status = 'pending' THEN amount_rials ELSE 0 END) AS pending_amount,
               SUM(CASE WHEN status = 'failed'  THEN 1 ELSE 0 END) AS failed_count,
               AVG(CASE WHEN status = 'paid'    THEN amount_rials END) AS avg_paid
             FROM invoices WHERE clinic_id = ?"
        );
        $summaryStmt->execute([$clinicId]);
        $s = $summaryStmt->fetch();

        $pendingAmt  = (float)($s['pending_amount'] ?? 0);
        $avgPaid     = (float)($s['avg_paid'] ?? 0);

        return $this->success([
            'rows'     => $rows,
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'summary'  => [
                'total'               => (int)($s['total_all']   ?? $total),
                'pending_count'       => (int)($s['pending_count'] ?? 0),
                'pending_amount_label' => $pendingAmt > 0 ? round($pendingAmt / 10_000_000, 0) . 'م' : '—',
                'failed_count'        => (int)($s['failed_count'] ?? 0),
                'avg_label'           => $avgPaid > 0 ? round($avgPaid / 10_000_000, 0) . 'م' : '—',
            ],
        ]);
    }

    /**
     * GET /api/v1/billing/invoices/{id}
     */
    public function show(Request $req, string $id): array
    {
        $clinicId  = (int)($req->user['clinic_id'] ?? 1);
        $invoiceId = (int)$id;
        $db = Database::conn();

        $stmt = $db->prepare(
            "SELECT i.*, p.first_name, p.last_name, p.mobile
             FROM invoices i
             LEFT JOIN patients p ON i.patient_id = p.id
             WHERE i.id = ? AND i.clinic_id = ?"
        );
        $stmt->execute([$invoiceId, $clinicId]);
        $row = $stmt->fetch();

        if (!$row) {
            return $this->error('فاکتور یافت نشد', 404);
        }

        return $this->success([
            'id'           => (int)$row['id'],
            'patient_id'   => (int)$row['patient_id'],
            'patient_name' => trim($row['first_name'] . ' ' . $row['last_name']),
            'patient_mobile' => $row['mobile'],
            'amount_rials' => (float)$row['amount_rials'],
            'status'       => $row['status'],
            'gateway'      => $row['gateway'] ?? null,
            'notes'        => $row['notes'] ?? null,
            'created_at'   => $row['created_at'],
            'updated_at'   => $row['updated_at'] ?? null,
        ]);
    }

    /**
     * POST /api/v1/billing/invoices
     * Body: { patient_id, amount_rials, gateway?, notes? }
     */
    public function store(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;

        if (empty($in['patient_id']) || empty($in['amount_rials'])) {
            return $this->error('patient_id و amount_rials الزامی است', 422);
        }
        if ((float)$in['amount_rials'] <= 0) {
            return $this->error('مبلغ باید بیشتر از صفر باشد', 422);
        }

        $db = Database::conn();
        $stmt = $db->prepare(
            "INSERT INTO invoices (clinic_id, patient_id, amount_rials, status, gateway, notes, created_at, updated_at)
             VALUES (?, ?, ?, 'pending', ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $clinicId,
            (int)$in['patient_id'],
            (float)$in['amount_rials'],
            $in['gateway'] ?? null,
            $in['notes']   ?? null,
        ]);
        $invoiceId = (int)$db->lastInsertId();

        return $this->success(['id' => $invoiceId], 201);
    }

    /**
     * PATCH /api/v1/billing/invoices/{id}/status
     * Body: { status }
     */
    public function updateStatus(Request $req, string $id): array
    {
        $clinicId  = (int)($req->user['clinic_id'] ?? 1);
        $invoiceId = (int)$id;
        $status    = $req->body['status'] ?? null;

        $allowed = ['pending', 'paid', 'failed', 'insurance_pending'];
        if (!in_array($status, $allowed, true)) {
            return $this->error('وضعیت نامعتبر است', 422);
        }

        $db = Database::conn();
        $stmt = $db->prepare(
            "SELECT id FROM invoices WHERE id = ? AND clinic_id = ?"
        );
        $stmt->execute([$invoiceId, $clinicId]);
        if (!$stmt->fetch()) {
            return $this->error('فاکتور یافت نشد', 404);
        }

        $db->prepare("UPDATE invoices SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$status, $invoiceId]);

        return $this->success(['id' => $invoiceId, 'status' => $status]);
    }
}
