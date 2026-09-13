<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Appointment;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use PDO;
use RuntimeException;

final class CalendarSyncService
{
    private GoogleCalendarClient $google;
    private DateTimeZone $utc;
    private DateTimeZone $tehran;

    public function __construct(?GoogleCalendarClient $google = null)
    {
        $this->google = $google ?? new GoogleCalendarClient();
        $this->utc = new DateTimeZone('UTC');
        $this->tehran = new DateTimeZone('Asia/Tehran');
    }

    /** @return array{processed:int,succeeded:int,failed:int} */
    public function pushOutbox(int $limit = 25): array
    {
        $limit = max(1, min(100, $limit));
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT id FROM calendar_sync_outbox
             WHERE status IN ("pending","failed")
               AND (next_attempt_at IS NULL OR next_attempt_at <= UTC_TIMESTAMP())
             ORDER BY id ASC LIMIT ' . $limit
        );
        $stmt->execute();
        $ids = array_map('intval', array_column($stmt->fetchAll(), 'id'));
        $succeeded = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $row = $this->claimOutbox($id);
                if (!$row) {
                    continue;
                }
                $this->processOutboxRow($row);
                $db->prepare(
                    'UPDATE calendar_sync_outbox
                     SET status = "succeeded", locked_at = NULL, last_error = NULL, updated_at = UTC_TIMESTAMP()
                     WHERE id = ?'
                )->execute([$id]);
                $succeeded++;
            } catch (\Throwable $e) {
                $failed++;
                $attempts = (int)($row['attempts'] ?? 1);
                $dead = $attempts >= 10;
                $delay = min(3600, 30 * (2 ** min(7, max(0, $attempts - 1))));
                $db->prepare(
                    'UPDATE calendar_sync_outbox
                     SET status = ?, locked_at = NULL, last_error = ?,
                         next_attempt_at = DATE_ADD(UTC_TIMESTAMP(), INTERVAL ? SECOND), updated_at = UTC_TIMESTAMP()
                     WHERE id = ?'
                )->execute([$dead ? 'dead' : 'failed', mb_substr($e->getMessage(), 0, 2000), $delay, $id]);
                error_log('[CalendarSyncService] outbox ' . $id . ' failed: ' . $e->getMessage());
            }
        }
        return ['processed' => count($ids), 'succeeded' => $succeeded, 'failed' => $failed];
    }

    /** @return array{seen:int,applied:int,conflicts:int,reset:bool} */
    public function pullChanges(int $clinicId): array
    {
        $db = Database::conn();
        $calendarId = $this->google->calendarId();
        $cursorStmt = $db->prepare(
            'SELECT sync_token FROM calendar_sync_cursors WHERE clinic_id = ? AND google_calendar_id = ? LIMIT 1'
        );
        $cursorStmt->execute([$clinicId, $calendarId]);
        $syncToken = $cursorStmt->fetchColumn();
        $syncToken = $syncToken !== false && $syncToken !== null ? (string)$syncToken : null;

        $changes = $this->google->listChanges($calendarId, $syncToken);
        if ($changes['reset']) {
            $db->prepare(
                'INSERT INTO calendar_sync_cursors (clinic_id, google_calendar_id, sync_token, last_sync_at)
                 VALUES (?, ?, NULL, UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE sync_token = NULL, last_sync_at = UTC_TIMESTAMP()'
            )->execute([$clinicId, $calendarId]);
            $changes = $this->google->listChanges($calendarId, null);
            $reset = true;
        } else {
            $reset = false;
        }

        $seen = 0;
        $applied = 0;
        $conflicts = 0;
        foreach ($changes['events'] as $event) {
            $seen++;
            $eventId = trim((string)($event['id'] ?? ''));
            if ($eventId === '') {
                continue;
            }
            $linkStmt = $db->prepare(
                'SELECT * FROM calendar_event_links
                 WHERE clinic_id = ? AND google_calendar_id = ? AND google_event_id = ? LIMIT 1'
            );
            $linkStmt->execute([$clinicId, $calendarId, $eventId]);
            $link = $linkStmt->fetch();
            if (!$link || empty($link['appointment_id'])) {
                // Unknown external events are deliberately ignored. The desktop bridge
                // must modify CRM-created Google events so patient identity is never
                // inferred from calendar text.
                continue;
            }
            try {
                $ok = $this->applyRemoteAppointmentChange($clinicId, (int)$link['appointment_id'], $event);
                if ($ok) {
                    $applied++;
                    $db->prepare(
                        'UPDATE calendar_event_links
                         SET google_etag = ?, sync_status = ?, remote_updated_at = ?, last_synced_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                         WHERE id = ?'
                    )->execute([
                        $event['etag'] ?? null,
                        (($event['status'] ?? '') === 'cancelled') ? 'deleted' : 'synced',
                        $this->googleUpdatedToUtc($event['updated'] ?? null),
                        (int)$link['id'],
                    ]);
                } else {
                    $conflicts++;
                    $db->prepare('UPDATE calendar_event_links SET sync_status = "conflict", updated_at = UTC_TIMESTAMP() WHERE id = ?')
                        ->execute([(int)$link['id']]);
                }
            } catch (\Throwable $e) {
                $conflicts++;
                $db->prepare('UPDATE calendar_event_links SET sync_status = "conflict", updated_at = UTC_TIMESTAMP() WHERE id = ?')
                    ->execute([(int)$link['id']]);
                error_log('[CalendarSyncService] pull conflict event ' . $eventId . ': ' . $e->getMessage());
            }
            if (!empty($link['booking_request_id'])) {
                (new ScheduledVisitSheetService())->queue((int)$link['booking_request_id']);
            }
        }

        $nextToken = $changes['next_sync_token'];
        $db->prepare(
            'INSERT INTO calendar_sync_cursors
             (clinic_id, google_calendar_id, sync_token, last_full_sync_at, last_sync_at)
             VALUES (?, ?, ?, IF(? = 1, UTC_TIMESTAMP(), NULL), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               sync_token = VALUES(sync_token),
               last_full_sync_at = IF(? = 1, UTC_TIMESTAMP(), last_full_sync_at),
               last_sync_at = UTC_TIMESTAMP()'
        )->execute([$clinicId, $calendarId, $nextToken, $syncToken === null ? 1 : 0, $syncToken === null ? 1 : 0]);

        return ['seen' => $seen, 'applied' => $applied, 'conflicts' => $conflicts, 'reset' => $reset];
    }

    /** @return array<string,mixed>|null */
    private function claimOutbox(int $id): ?array
    {
        $db = Database::conn();
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT * FROM calendar_sync_outbox
                 WHERE id = ? AND status IN ("pending","failed") FOR UPDATE'
            );
            $stmt->execute([$id]);
            $row = $stmt->fetch();
            if (!$row) {
                $db->commit();
                return null;
            }
            $db->prepare(
                'UPDATE calendar_sync_outbox
                 SET status = "processing", attempts = attempts + 1, locked_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
                 WHERE id = ?'
            )->execute([$id]);
            $row['attempts'] = (int)$row['attempts'] + 1;
            $db->commit();
            return $row;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function processOutboxRow(array $row): void
    {
        $clinicId = (int)$row['clinic_id'];
        $calendarId = $this->google->calendarId();
        if ((string)$row['aggregate_type'] !== 'appointment') {
            // Open-day/booking events are retained in the outbox contract for future
            // use, but only confirmed appointments are sent to the clinical calendar.
            return;
        }
        $appointmentId = (int)$row['aggregate_id'];
        $db = Database::conn();
        $stmt = $db->prepare(
            'SELECT a.*, p.first_name, p.last_name
             FROM appointments a JOIN patients p ON p.id = a.patient_id
             WHERE a.id = ? AND a.clinic_id = ? AND a.deleted_at IS NULL LIMIT 1'
        );
        $stmt->execute([$appointmentId, $clinicId]);
        $appointment = $stmt->fetch();
        if (!$appointment) {
            throw new RuntimeException('appointment not found for calendar sync');
        }

        $linkStmt = $db->prepare('SELECT * FROM calendar_event_links WHERE appointment_id = ? LIMIT 1');
        $linkStmt->execute([$appointmentId]);
        $link = $linkStmt->fetch();
        $eventId = $link ? (string)$link['google_event_id'] : $this->eventId((string)$appointment['uuid']);

        if ((string)$row['action'] === 'delete' || (string)$appointment['status'] === 'cancelled') {
            if ($link) {
                $this->google->deleteEvent($calendarId, $eventId);
                $db->prepare(
                    'UPDATE calendar_event_links SET sync_status = "deleted", last_synced_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP() WHERE id = ?'
                )->execute([(int)$link['id']]);
            }
            return;
        }

        $event = $this->appointmentEvent($appointment, $row);
        $saved = $this->google->upsertEvent($calendarId, $eventId, $event, $link['google_etag'] ?? null);
        $bookingId = $this->bookingIdFromPayload((string)$row['payload']);
        $db->prepare(
            'INSERT INTO calendar_event_links
             (clinic_id, appointment_id, booking_request_id, google_calendar_id, google_event_id,
              google_etag, sync_status, remote_updated_at, last_synced_at)
             VALUES (?, ?, ?, ?, ?, ?, "synced", ?, UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE
               google_event_id = VALUES(google_event_id), google_etag = VALUES(google_etag),
               sync_status = "synced", remote_updated_at = VALUES(remote_updated_at),
               last_synced_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()'
        )->execute([
            $clinicId,
            $appointmentId,
            $bookingId,
            $calendarId,
            (string)($saved['id'] ?? $eventId),
            $saved['etag'] ?? null,
            $this->googleUpdatedToUtc($saved['updated'] ?? null),
        ]);
        if ($bookingId > 0) {
            (new ScheduledVisitSheetService())->queue($bookingId);
        }
    }

    /** @return array<string,mixed> */
    private function appointmentEvent(array $appointment, array $outbox): array
    {
        $startUtc = new DateTimeImmutable((string)$appointment['scheduled_at'], $this->utc);
        $start = $startUtc->setTimezone($this->tehran);
        $end = $start->add(new DateInterval('PT' . max(1, (int)$appointment['duration_minutes']) . 'M'));
        $bookingId = $this->bookingIdFromPayload((string)$outbox['payload']);
        return [
            'summary' => 'نوبت کلینیک - ' . trim((string)$appointment['first_name'] . ' ' . (string)$appointment['last_name']),
            'description' => 'CRM appointment #' . (int)$appointment['id'],
            'start' => ['dateTime' => $start->format(DateTimeInterface::RFC3339), 'timeZone' => 'Asia/Tehran'],
            'end' => ['dateTime' => $end->format(DateTimeInterface::RFC3339), 'timeZone' => 'Asia/Tehran'],
            'status' => 'confirmed',
            'transparency' => 'opaque',
            'visibility' => 'private',
            'extendedProperties' => [
                'private' => array_filter([
                    'crm_appointment_id' => (string)(int)$appointment['id'],
                    'crm_booking_request_id' => $bookingId ? (string)$bookingId : null,
                ]),
            ],
        ];
    }

    private function applyRemoteAppointmentChange(int $clinicId, int $appointmentId, array $event): bool
    {
        $appointment = (new Appointment())->findForClinic($appointmentId, $clinicId);
        if (!$appointment) {
            return false;
        }
        $service = new AppointmentService();
        if (($event['status'] ?? '') === 'cancelled') {
            $result = $service->updateStatusLocked($clinicId, $appointment, 'cancelled');
            return (bool)($result['ok'] ?? false);
        }
        $startRaw = (string)($event['start']['dateTime'] ?? '');
        $endRaw = (string)($event['end']['dateTime'] ?? '');
        if ($startRaw === '' || $endRaw === '') {
            return false;
        }
        $start = new DateTimeImmutable($startRaw);
        $end = new DateTimeImmutable($endRaw);
        $duration = max(1, (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60));
        $startUtc = $start->setTimezone($this->utc)->format('Y-m-d H:i:s');
        $result = $service->rescheduleLocked(
            $clinicId,
            $appointmentId,
            $startUtc,
            $duration,
            $appointment['room'] ?? null,
            (int)($appointment['provider_id'] ?? 0),
            (int)$appointment['patient_id']
        );
        if (!($result['ok'] ?? false)) {
            return false;
        }
        if ((string)$appointment['status'] === 'cancelled') {
            $fresh = (new Appointment())->findForClinic($appointmentId, $clinicId) ?? $appointment;
            $reactivate = $service->updateStatusLocked($clinicId, $fresh, 'confirmed');
            return (bool)($reactivate['ok'] ?? false);
        }
        return true;
    }

    private function eventId(string $uuid): string
    {
        $candidate = strtolower(preg_replace('/[^0-9a-v]/i', '', $uuid) ?? '');
        if (strlen($candidate) >= 5) {
            return substr($candidate, 0, 64);
        }
        return substr(hash('sha256', $uuid), 0, 32);
    }

    private function bookingIdFromPayload(string $payload): ?int
    {
        $decoded = json_decode($payload, true);
        $id = is_array($decoded) ? (int)($decoded['booking_request_id'] ?? 0) : 0;
        return $id > 0 ? $id : null;
    }

    private function googleUpdatedToUtc(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return (new DateTimeImmutable((string)$value))->setTimezone($this->utc)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }
}
