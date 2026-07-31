<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Database;

/**
 * TaskController
 * Kanban task management for clinic staff.
 */
final class TaskController extends Controller
{
    /**
     * GET /api/v1/tasks?status=&assignee_id=
     * Returns tasks grouped by status for the Kanban board.
     */
    public function index(Request $req): array
    {
        $clinicId   = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();

        $where  = 'clinic_id = :clinic';
        $params = [':clinic' => $clinicId];

        if (!empty($req->query['status'])) {
            $where .= ' AND status = :status';
            $params[':status'] = $req->query['status'];
        }
        if (!empty($req->query['assignee_id'])) {
            $where .= ' AND assignee_id = :assignee';
            $params[':assignee'] = (int)$req->query['assignee_id'];
        }

        $stmt = $db->prepare(
            "SELECT t.id, t.title, t.priority, t.status,
                    t.assignee_id, u.full_name AS assignee_name,
                    t.due_date, t.notes, t.created_at
             FROM tasks t
             LEFT JOIN users u ON t.assignee_id = u.id
             WHERE {$where}
             ORDER BY FIELD(t.priority,'high','medium','low'), t.created_at DESC"
        );
        $stmt->execute($params);
        $rows = array_map(function ($r) {
            return [
                'id'            => (int)$r['id'],
                'title'         => $r['title'],
                'priority'      => $r['priority'],
                'status'        => $r['status'],
                'assignee_id'   => $r['assignee_id'] ? (int)$r['assignee_id'] : null,
                'assignee_name' => $r['assignee_name'] ?? null,
                'due_date'      => $r['due_date'] ?? null,
                'notes'         => $r['notes'] ?? null,
                'created_at'    => $r['created_at'],
            ];
        }, $stmt->fetchAll());

        // Group into kanban columns
        $grouped = ['todo' => [], 'in_progress' => [], 'done' => []];
        foreach ($rows as $r) {
            $col = $r['status'];
            if (!isset($grouped[$col])) $grouped[$col] = [];
            $grouped[$col][] = $r;
        }

        return $this->success([
            'columns' => $grouped,
            'total'   => count($rows),
        ]);
    }

    /**
     * POST /api/v1/tasks
     * Body: { title, priority, assignee_id?, due_date?, notes? }
     */
    public function store(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $in = $req->body;

        if (empty($in['title'])) {
            return $this->error('عنوان وظیفه الزامی است', 422);
        }

        $priority = $in['priority'] ?? 'medium';
        if (!in_array($priority, ['high', 'medium', 'low'], true)) {
            return $this->error('اولویت نامعتبر است', 422);
        }

        $db = Database::conn();
        $stmt = $db->prepare(
            "INSERT INTO tasks (clinic_id, title, priority, status, assignee_id, due_date, notes, created_at, updated_at)
             VALUES (?, ?, ?, 'todo', ?, ?, ?, NOW(), NOW())"
        );
        $stmt->execute([
            $clinicId,
            trim($in['title']),
            $priority,
            !empty($in['assignee_id']) ? (int)$in['assignee_id'] : null,
            $in['due_date'] ?? null,
            $in['notes']    ?? null,
        ]);
        $taskId = (int)$db->lastInsertId();

        return $this->success(['id' => $taskId], 201);
    }

    /**
     * PATCH /api/v1/tasks/{id}/status
     * Body: { status }
     */
    public function updateStatus(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $taskId   = (int)$id;
        $status   = $req->body['status'] ?? null;

        $allowed = ['todo', 'in_progress', 'done'];
        if (!in_array($status, $allowed, true)) {
            return $this->error('وضعیت نامعتبر است', 422);
        }

        $db = Database::conn();
        $check = $db->prepare("SELECT id FROM tasks WHERE id = ? AND clinic_id = ?");
        $check->execute([$taskId, $clinicId]);
        if (!$check->fetch()) {
            return $this->error('وظیفه یافت نشد', 404);
        }

        $db->prepare("UPDATE tasks SET status = ?, updated_at = NOW() WHERE id = ?")
           ->execute([$status, $taskId]);

        return $this->success(['id' => $taskId, 'status' => $status]);
    }

    /**
     * DELETE /api/v1/tasks/{id}
     */
    public function destroy(Request $req, string $id): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $taskId   = (int)$id;
        $db = Database::conn();

        $check = $db->prepare("SELECT id FROM tasks WHERE id = ? AND clinic_id = ?");
        $check->execute([$taskId, $clinicId]);
        if (!$check->fetch()) {
            return $this->error('وظیفه یافت نشد', 404);
        }

        $db->prepare("DELETE FROM tasks WHERE id = ?")->execute([$taskId]);
        return $this->success(['id' => $taskId]);
    }
}
