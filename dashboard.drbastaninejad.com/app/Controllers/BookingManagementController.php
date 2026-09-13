<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Services\BookingLedgerService;
use App\Services\BookingWorkflowService;
use App\Services\GoogleCalendarService;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PDO;
use RuntimeException;

/** Authenticated receptionist/admin booking operations. */
final class BookingManagementController extends Controller
{
    public function index(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();
        $status = trim((string)($req->query['paid_status'] ?? ''));
        $source = trim((string)($req->query['source'] ?? ''));
        $page = max(1, (int)($req->query['page'] ?? 1));
        $perPage = min(100, max(10, (int)($req->query['per_page'] ?? 30)));
        $offset = ($page - 1) * $perPage;

        $where = ["i.clinic_id = ?", "i.source_type = 'booking'", 'i.deleted_at IS NULL'];
        $params = [$clinicId];
        if (in_array($status, ['paid', 'free', 'unpaid'], true)) {
            $where[] = 'i.paid_status = ?';
            $params[] = $status;
        }
        if (in_array($source, ['online', 'admin'], true)) {
            $where[] = 'i.booking_source = ?';
            $params[] = $source;
        }
        $whereSql = implode(' AND ', $where);
        $count = $db->prepare("SELECT COUNT(*) FROM intakes i WHERE $whereSql");
        $count->execute($params);
        $total = (int)$count->fetchColumn();

        $stmt = $db->prepare(
            "SELECT i.id, i.first_name, i.last_name, i.mobile, i.slot_start, i.slot_duration_minutes,
                    i.booking_source, i.paid_status, i.payment_gateway, i.clinic_confirmation_status,
                    i.appointment_id, i.booking_ledger_status, i.booking_sheet_status, i.created_at,
                    p.status AS payment_status, p.reference_id, p.amount_rial,
                    u.full_name AS receptionist_name,
                    cs.sync_status AS calendar_sync_status
             FROM intakes i
             LEFT JOIN booking_payments p ON p.id = i.payment_id
             LEFT JOIN users u ON u.id = i.receptionist_user_id
             LEFT JOIN calendar_sync_state cs ON cs.appointment_id = i.appointment_id
             WHERE $whereSql
             ORDER BY i.created_at DESC, i.id DESC
             LIMIT $perPage OFFSET $offset"
        );
        $stmt->execute($params);
        $bookings = $stmt->fetchAll();

        $days = $db->prepare(
            "SELECT id, open_date, opens_at, closes_at, slot_duration_minutes, capacity, booked_count, status, notes,
                    GREATEST(capacity - booked_count, 0) AS remaining_quota
             FROM appointment_open_days
             WHERE clinic_id = ? AND open_date >= CURDATE() - INTERVAL 7 DAY
             ORDER BY open_date ASC LIMIT 90"
        );
        $days->execute([$clinicId]);

        $attention = $db->prepare(
            "SELECT COUNT(*) FROM booking_event_log
             WHERE clinic_id = ? AND event_status IN ('attention','unknown') AND created_at >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 DAY)"
        );
        $attention->execute([$clinicId]);

        $conflicts = $db->prepare(
            "SELECT s.appointment_id, s.google_event_id, s.remote_starts_at, s.remote_ends_at, s.last_error,
                    a.scheduled_at AS crm_starts_at, a.duration_minutes,
                    CONCAT_WS(' ', p.first_name, p.last_name) AS patient_name
             FROM calendar_sync_state s
             JOIN appointments a ON a.id = s.appointment_id
             JOIN patients p ON p.id = a.patient_id
             WHERE s.clinic_id = ? AND s.sync_status = 'conflict'
             ORDER BY s.updated_at DESC LIMIT 50"
        );
        $conflicts->execute([$clinicId]);

        return $this->success([
            'bookings' => $bookings,
            'open_days' => $days->fetchAll(),
            'calendar_conflicts' => $conflicts->fetchAll(),
            'attention_count' => (int)$attention->fetchColumn(),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => max(1, (int)ceil($total / $perPage)),
            ],
        ]);
    }

    public function createAdminFree(Request $req): array
    {
        $result = (new BookingWorkflowService())->createAdminFree((array)$req->body, (array)$req->user);
        return $this->fromWorkflow($result);
    }

    public function confirm(Request $req, string $id): array
    {
        if (!ctype_digit($id) || (int)$id < 1) {
            return $this->error('شناسه رزرو معتبر نیست.', 422);
        }
        return $this->fromWorkflow((new BookingWorkflowService())->confirmByStaff((int)$id, (array)$req->user));
    }

    public function upsertOpenDay(Request $req): array
    {
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $id = (int)($req->body['id'] ?? 0);
        $date = trim((string)($req->body['open_date'] ?? ''));
        $opensAt = $this->normaliseTime((string)($req->body['opens_at'] ?? '09:00'));
        $closesAt = $this->normaliseTime((string)($req->body['closes_at'] ?? '18:00'));
        $duration = (int)($req->body['slot_duration_minutes'] ?? 20);
        $capacity = (int)($req->body['capacity'] ?? 20);
        $status = strtolower(trim((string)($req->body['status'] ?? 'open')));
        $notes = trim((string)($req->body['notes'] ?? ''));
        $tz = new DateTimeZone('Asia/Tehran');
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date, $tz);
        if (!$day || $day->format('Y-m-d') !== $date || !$opensAt || !$closesAt || $opensAt >= $closesAt
            || $duration < 5 || $duration > 240 || $capacity < 1 || $capacity > 100
            || !in_array($status, ['open', 'closed'], true)) {
            return $this->error('تنظیمات روز کاری معتبر نیست.', 422);
        }

        $db = Database::conn();
        $db->beginTransaction();
        try {
            if ($status === 'open') {
                $monthStart = $day->modify('first day of this month')->format('Y-m-d');
                $monthEnd = $day->modify('last day of this month')->format('Y-m-d');
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM appointment_open_days
                     WHERE clinic_id = ? AND status = 'open' AND open_date BETWEEN ? AND ? AND id <> ? FOR UPDATE"
                );
                $stmt->execute([$clinicId, $monthStart, $monthEnd, $id]);
                if ((int)$stmt->fetchColumn() >= 8) {
                    throw new RuntimeException('monthly_limit');
                }

                // Clinic week is Saturday–Friday. Enforce no more than two open days in each such week.
                $weekday = (int)$day->format('w'); // Sunday=0 ... Saturday=6
                $daysSinceSaturday = ($weekday + 1) % 7;
                $weekStart = $day->sub(new DateInterval('P' . $daysSinceSaturday . 'D'));
                $weekEnd = $weekStart->add(new DateInterval('P6D'));
                $stmt = $db->prepare(
                    "SELECT COUNT(*) FROM appointment_open_days
                     WHERE clinic_id = ? AND status = 'open' AND open_date BETWEEN ? AND ? AND id <> ? FOR UPDATE"
                );
                $stmt->execute([$clinicId, $weekStart->format('Y-m-d'), $weekEnd->format('Y-m-d'), $id]);
                if ((int)$stmt->fetchColumn() >= 2) {
                    throw new RuntimeException('weekly_limit');
                }
            }

            if ($id > 0) {
                $existing = $db->prepare('SELECT booked_count FROM appointment_open_days WHERE id = ? AND clinic_id = ? LIMIT 1 FOR UPDATE');
                $existing->execute([$id, $clinicId]);
                $booked = $existing->fetchColumn();
                if ($booked === false) {
                    throw new RuntimeException('not_found');
                }
                if ($capacity < (int)$booked) {
                    throw new RuntimeException('capacity_below_booked');
                }
                $db->prepare(
                    'UPDATE appointment_open_days SET open_date = ?, opens_at = ?, closes_at = ?, slot_duration_minutes = ?, capacity = ?, status = ?, notes = ?, updated_at = UTC_TIMESTAMP() WHERE id = ? AND clinic_id = ?'
                )->execute([$date, $opensAt, $closesAt, $duration, $capacity, $status, mb_substr($notes, 0, 255), $id, $clinicId]);
            } else {
                $db->prepare(
                    'INSERT INTO appointment_open_days (clinic_id, open_date, opens_at, closes_at, slot_duration_minutes, capacity, booked_count, status, notes, created_by, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())'
                )->execute([$clinicId, $date, $opensAt, $closesAt, $duration, $capacity, $status, mb_substr($notes, 0, 255), (int)($req->user['id'] ?? 0)]);
                $id = (int)$db->lastInsertId();
            }
            $db->commit();
        } catch (RuntimeException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            return match ($e->getMessage()) {
                'monthly_limit' => $this->error('حداکثر ۸ روز باز در هر ماه مجاز است.', 409),
                'weekly_limit' => $this->error('حداکثر ۲ روز باز در هر هفته مجاز است.', 409),
                'capacity_below_booked' => $this->error('ظرفیت نمی‌تواند از تعداد رزروهای قطعی کمتر باشد.', 409),
                'not_found' => $this->error('روز کاری یافت نشد.', 404),
                default => throw $e,
            };
        } catch (\PDOException $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            if ((string)$e->getCode() === '23000') {
                return $this->error('برای این تاریخ قبلاً روز کاری ثبت شده است.', 409);
            }
            throw $e;
        }
        return $this->success(['id' => $id, 'open_date' => $date, 'status' => $status], $id ? 200 : 201);
    }

    public function deleteOpenDay(Request $req, string $id): array
    {
        if (!ctype_digit($id)) {
            return $this->error('شناسه روز کاری معتبر نیست.', 422);
        }
        $clinicId = (int)($req->user['clinic_id'] ?? 1);
        $db = Database::conn();
        $stmt = $db->prepare('SELECT booked_count FROM appointment_open_days WHERE id = ? AND clinic_id = ? LIMIT 1');
        $stmt->execute([(int)$id, $clinicId]);
        $booked = $stmt->fetchColumn();
        if ($booked === false) {
            return $this->error('روز کاری یافت نشد.', 404);
        }
        if ((int)$booked > 0) {
            return $this->error('روز دارای رزرو قطعی است و حذف نمی‌شود؛ آن را بسته کنید.', 409);
        }
        $db->prepare('DELETE FROM appointment_open_days WHERE id = ? AND clinic_id = ?')->execute([(int)$id, $clinicId]);
        return $this->success(['deleted' => true, 'id' => (int)$id]);
    }

    public function syncCalendar(Request $req): array
    {
        $result = (new GoogleCalendarService())->syncRange(
            isset($req->body['from']) ? (string)$req->body['from'] : null,
            isset($req->body['to']) ? (string)$req->body['to'] : null
        );
        if (!$result['ok']) {
            return $this->error('همگام‌سازی Google Calendar ناموفق بود.', 502);
        }
        return $this->success($result);
    }

    public function resolveCalendarConflict(Request $req, string $appointmentId): array
    {
        if (!ctype_digit($appointmentId)) {
            return $this->error('شناسه نوبت معتبر نیست.', 422);
        }
        $strategy = trim((string)($req->body['strategy'] ?? ''));
        $result = (new GoogleCalendarService())->resolveConflict((int)$appointmentId, $strategy);
        return $result['ok']
            ? $this->success(['message' => $result['message'], 'appointment_id' => (int)$appointmentId])
            : $this->error((string)$result['message'], (int)$result['status']);
    }

    public function resyncLedger(Request $req, string $id): array
    {
        if (!ctype_digit($id)) {
            return $this->error('شناسه رزرو معتبر نیست.', 422);
        }
        $status = (new BookingLedgerService())->syncBooking((int)$id);
        return $this->success(['booking_id' => (int)$id, 'ledger_status' => $status]);
    }

    private function normaliseTime(string $value): ?string
    {
        $value = trim($value);
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value)) {
            return $value . ':00';
        }
        if (preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d:[0-5]\d$/', $value)) {
            return $value;
        }
        return null;
    }

    private function fromWorkflow(array $result): array
    {
        if (!empty($result['ok'])) {
            return $this->success((array)($result['data'] ?? []), (int)($result['status'] ?? 200));
        }
        return $this->error((string)($result['message'] ?? 'انجام عملیات ممکن نشد.'), (int)($result['status'] ?? 400));
    }
}
