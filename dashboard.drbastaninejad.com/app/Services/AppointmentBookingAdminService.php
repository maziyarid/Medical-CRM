<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

final class AppointmentBookingAdminService
{
    /** @return array<int,array<string,mixed>> */
    public function bookings(int $clinicId, array $filters = []): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $where = ['b.clinic_id = ?'];
        $params = [$clinicId];
        foreach ([
            'confirmation_status' => 'b.confirmation_status',
            'payment_status' => 'b.payment_status',
            'source' => 'b.source',
        ] as $key => $column) {
            $value = trim((string)($filters[$key] ?? ''));
            if ($value !== '') {
                $where[] = $column . ' = ?';
                $params[] = $value;
            }
        }
        $from = trim((string)($filters['from'] ?? ''));
        $to = trim((string)($filters['to'] ?? ''));
        if ($from !== '') {
            $where[] = 'b.requested_start_at >= ?';
            $params[] = $from . ' 00:00:00';
        }
        if ($to !== '') {
            $where[] = 'b.requested_start_at < DATE_ADD(?, INTERVAL 1 DAY)';
            $params[] = $to . ' 00:00:00';
        }
        $limit = max(1, min(200, (int)($filters['limit'] ?? 100)));
        $sql = 'SELECT b.id, b.uuid, b.appointment_id, b.patient_id, b.open_day_id,
                       b.requested_start_at, b.duration_minutes, b.source, b.payment_gateway,
                       b.payment_status, b.confirmation_status, b.amount_rials,
                       b.staff_followup_required, b.followup_completed_at, b.followup_completed_by,
                       b.receptionist_user_id, b.hold_expires_at,
                       b.confirmed_at, b.created_at, b.updated_at, b.sheet_sync_status,
                       p.first_name, p.last_name, p.mobile,
                       u.full_name AS receptionist_name,
                       (SELECT GROUP_CONCAT(r.name ORDER BY r.id SEPARATOR ",")
                          FROM role_user ru JOIN roles r ON r.id = ru.role_id
                         WHERE ru.user_id = u.id) AS receptionist_roles,
                       pa.status AS payment_attempt_status, pa.transaction_ref, pa.verified_at,
                       cel.google_event_id, cel.sync_status AS calendar_sync_status
                FROM appointment_booking_requests b
                JOIN patients p ON p.id = b.patient_id
                LEFT JOIN users u ON u.id = b.receptionist_user_id
                LEFT JOIN appointment_payment_attempts pa ON pa.id = (
                    SELECT pa2.id FROM appointment_payment_attempts pa2
                    WHERE pa2.booking_request_id = b.id ORDER BY pa2.id DESC LIMIT 1
                )
                LEFT JOIN calendar_event_links cel ON cel.booking_request_id = b.id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY b.requested_start_at DESC, b.id DESC LIMIT ' . $limit;
        $stmt = Database::conn()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['appointment_id'] = $row['appointment_id'] === null ? null : (int)$row['appointment_id'];
            $row['patient_id'] = (int)$row['patient_id'];
            $row['open_day_id'] = (int)$row['open_day_id'];
            $row['duration_minutes'] = (int)$row['duration_minutes'];
            $row['amount_rials'] = (int)$row['amount_rials'];
            $row['staff_followup_required'] = (bool)$row['staff_followup_required'];
            $row['patient_name'] = trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);
            unset($row['first_name'], $row['last_name']);
        }
        unset($row);
        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    public function openDays(int $clinicId, string $from = '', string $to = ''): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $where = ['clinic_id = ?'];
        $params = [$clinicId];
        if ($from !== '') { $where[] = 'open_date >= ?'; $params[] = $from; }
        if ($to !== '') { $where[] = 'open_date <= ?'; $params[] = $to; }
        $stmt = Database::conn()->prepare(
            'SELECT id, open_date, capacity, held_count, booked_count,
                    GREATEST(capacity - held_count - booked_count, 0) AS remaining,
                    slot_duration_minutes, opens_at, closes_at, status, created_by, created_at, updated_at
             FROM appointment_open_days WHERE ' . implode(' AND ', $where) . '
             ORDER BY open_date ASC LIMIT 120'
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            foreach (['id','capacity','held_count','booked_count','remaining','slot_duration_minutes'] as $key) {
                $row[$key] = (int)$row[$key];
            }
        }
        unset($row);
        return $rows;
    }

    /** @return array<int,array<string,mixed>> */
    public function payments(int $clinicId, array $filters = []): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $where = ['b.clinic_id = ?'];
        $params = [$clinicId];
        $status = trim((string)($filters['status'] ?? ''));
        if ($status !== '') { $where[] = 'a.status = ?'; $params[] = $status; }
        $gateway = trim((string)($filters['gateway'] ?? ''));
        if ($gateway !== '') { $where[] = 'a.gateway = ?'; $params[] = $gateway; }
        $limit = max(1, min(200, (int)($filters['limit'] ?? 100)));
        $stmt = Database::conn()->prepare(
            'SELECT a.id, a.booking_request_id, a.gateway, a.amount_rials, a.currency, a.status,
                    a.authority, a.transaction_ref, a.gateway_code, a.verified_at, a.created_at, a.updated_at,
                    b.uuid AS booking_uuid, b.payment_status AS booking_payment_status,
                    b.confirmation_status, b.requested_start_at,
                    p.first_name, p.last_name, p.mobile
             FROM appointment_payment_attempts a
             JOIN appointment_booking_requests b ON b.id = a.booking_request_id
             JOIN patients p ON p.id = b.patient_id
             WHERE ' . implode(' AND ', $where) . '
             ORDER BY a.id DESC LIMIT ' . $limit
        );
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['booking_request_id'] = (int)$row['booking_request_id'];
            $row['amount_rials'] = (int)$row['amount_rials'];
            $row['patient_name'] = trim((string)$row['first_name'] . ' ' . (string)$row['last_name']);
            unset($row['first_name'], $row['last_name'], $row['authority']);
        }
        unset($row);
        return $rows;
    }

    /** @return array<string,mixed> */
    public function completeFollowup(int $clinicId, int $bookingId, int $staffId): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id, confirmation_status FROM appointment_booking_requests
             WHERE id = ? AND clinic_id = ? LIMIT 1'
        );
        $stmt->execute([$bookingId, $clinicId]);
        $row = $stmt->fetch();
        if (!$row) {
            throw new RuntimeException('booking not found');
        }
        if (!in_array((string)$row['confirmation_status'], ['paid_pending_staff','confirmed'], true)) {
            throw new RuntimeException('booking is not ready for follow-up');
        }
        $db->prepare(
            'UPDATE appointment_booking_requests
             SET staff_followup_required = 0, followup_completed_at = UTC_TIMESTAMP(), followup_completed_by = ?,
                 sheet_sync_status = "pending", sheet_sync_error = NULL, updated_at = UTC_TIMESTAMP()
             WHERE id = ?'
        )->execute([$staffId, $bookingId]);
        (new ScheduledVisitSheetService())->queue($bookingId);
        return ['booking_id' => $bookingId, 'staff_followup_required' => false, 'completed_by' => $staffId, 'completed_at' => gmdate('Y-m-d H:i:s')];
    }

    /** @return array<string,mixed> */
    public function integrationStatus(int $clinicId): array
    {
        (new AppointmentSchemaBootstrapService())->ensure();
        $env = static fn(string $key): bool => trim((string)($_ENV[$key] ?? '')) !== '';
        $openDays = Database::conn()->prepare(
            'SELECT COUNT(*) FROM appointment_open_days WHERE clinic_id = ? AND status = "open" AND open_date >= CURDATE()'
        );
        $openDays->execute([$clinicId]);
        return [
            'deposit_configured' => (int)($_ENV['BOOKING_APPOINTMENT_DEPOSIT_RIALS'] ?? 0) > 0,
            'zarinpal_configured' => $env('ZARINPAL_MERCHANT_ID'),
            'vandar_configured' => $env('VANDAR_API_KEY') || $env('VANDAR_API_TOKEN'),
            'sheet_enabled' => (($_ENV['BOOKING_SHEET_WRITE_ENABLED'] ?? '0') === '1'),
            'sheet_configured' => $env('BOOKING_SHEET_WEBHOOK_URL') && $env('BOOKING_SHEET_SHARED_SECRET'),
            'sheet_name' => trim((string)($_ENV['BOOKING_VISIT_SHEET_NAME'] ?? 'ScheduledVisits')) ?: 'ScheduledVisits',
            'calendar_configured' => $env('GOOGLE_CALENDAR_ID') && $env('GOOGLE_CALENDAR_CLIENT_ID')
                && $env('GOOGLE_CALENDAR_CLIENT_SECRET') && $env('GOOGLE_CALENDAR_REFRESH_TOKEN'),
            'future_open_days' => (int)$openDays->fetchColumn(),
            'timezone' => 'Asia/Tehran',
        ];
    }
}
