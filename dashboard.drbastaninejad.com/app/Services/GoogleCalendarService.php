<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Appointment;
use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use RuntimeException;

/**
 * Google Calendar bridge between the web CRM and the clinic's desktop
 * DevExpress scheduler. CRM-created events carry a private appointment id;
 * unrelated Calendar events are imported as availability blocks.
 *
 * Remote edits to CRM events are never applied blindly. They become explicit
 * conflicts which staff can resolve by choosing either the CRM time or the
 * Google/desktop time.
 */
final class GoogleCalendarService
{
    private static ?string $cachedAccessToken = null;
    private static int $accessTokenExpiresAt = 0;
    private DateTimeZone $utc;

    public function __construct()
    {
        $this->utc = new DateTimeZone('UTC');
    }

    public function isConfigured(): bool
    {
        foreach (['GOOGLE_CALENDAR_CLIENT_ID', 'GOOGLE_CALENDAR_CLIENT_SECRET', 'GOOGLE_CALENDAR_REFRESH_TOKEN', 'GOOGLE_CALENDAR_ID'] as $key) {
            $value = trim((string)($_ENV[$key] ?? ''));
            if ($value === '' || str_starts_with($value, 'CHANGE_ME')) {
                return false;
            }
        }
        return true;
    }

    /** @return array{ok:bool,status:string,event_id?:string,error?:string} */
    public function pushAppointment(int $appointmentId): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => true, 'status' => 'skipped'];
        }
        $db = Database::conn();
        $stmt = $db->prepare(
            "SELECT a.*, CONCAT_WS(' ', p.first_name, p.last_name) AS patient_name,
                    p.mobile, i.id AS booking_id
             FROM appointments a
             JOIN patients p ON p.id = a.patient_id
             LEFT JOIN intakes i ON i.id = a.booking_intake_id
             WHERE a.id = ? AND a.deleted_at IS NULL LIMIT 1"
        );
        $stmt->execute([$appointmentId]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'status' => 'error', 'error' => 'appointment_not_found'];
        }
        if ((string)$row['status'] === 'cancelled') {
            return $this->deleteAppointmentEvent($appointmentId);
        }

        $start = new DateTimeImmutable((string)$row['scheduled_at'], $this->utc);
        $end = $start->add(new DateInterval('PT' . max(5, (int)$row['duration_minutes']) . 'M'));
        $payload = [
            'summary' => 'نوبت - ' . trim((string)$row['patient_name']),
            'description' => 'Medical CRM appointment #' . $appointmentId,
            'start' => ['dateTime' => $start->format(DATE_RFC3339), 'timeZone' => 'UTC'],
            'end' => ['dateTime' => $end->format(DATE_RFC3339), 'timeZone' => 'UTC'],
            'transparency' => 'opaque',
            'extendedProperties' => [
                'private' => array_filter([
                    'crmAppointmentId' => (string)$appointmentId,
                    'crmBookingId' => !empty($row['booking_id']) ? (string)$row['booking_id'] : null,
                    'crmSource' => (string)($row['source_type'] ?? 'staff'),
                ]),
            ],
        ];

        $stateStmt = $db->prepare('SELECT google_event_id FROM calendar_sync_state WHERE appointment_id = ? LIMIT 1');
        $stateStmt->execute([$appointmentId]);
        $eventId = trim((string)($stateStmt->fetchColumn() ?: ''));
        $calendar = rawurlencode($this->calendarId());
        if ($eventId !== '') {
            $r = $this->request('PUT', '/calendar/v3/calendars/' . $calendar . '/events/' . rawurlencode($eventId) . '?sendUpdates=none', $payload);
            if ($r['status'] === 404) {
                $eventId = '';
            }
        }
        if ($eventId === '') {
            $r = $this->request('POST', '/calendar/v3/calendars/' . $calendar . '/events?sendUpdates=none', $payload);
        }
        if (!$r['ok']) {
            $this->writeSyncState($appointmentId, $eventId !== '' ? $eventId : null, 'error', 'push', null, null, (string)($r['error'] ?? 'calendar_api_error'), null);
            return ['ok' => false, 'status' => 'error', 'error' => (string)($r['error'] ?? 'calendar_api_error')];
        }
        $event = $r['json'];
        $eventId = trim((string)($event['id'] ?? $eventId));
        $this->writeSyncState(
            $appointmentId,
            $eventId,
            'synced',
            'push',
            null,
            null,
            null,
            isset($event['etag']) ? (string)$event['etag'] : null
        );
        return ['ok' => true, 'status' => 'synced', 'event_id' => $eventId];
    }

    /**
     * Pull busy events and detect CRM-event divergence.
     * @return array{ok:bool,status:string,external_blocks:int,conflicts:int,events_seen:int,error?:string}
     */
    public function syncRange(?string $fromUtc = null, ?string $toUtc = null): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => true, 'status' => 'skipped', 'external_blocks' => 0, 'conflicts' => 0, 'events_seen' => 0];
        }
        $clinicId = (int)($_ENV['DEFAULT_CLINIC_ID'] ?? 1);
        $from = $this->parseUtc($fromUtc) ?? (new DateTimeImmutable('now', $this->utc))->sub(new DateInterval('P7D'));
        $to = $this->parseUtc($toUtc) ?? (new DateTimeImmutable('now', $this->utc))->add(new DateInterval('P90D'));
        if ($to <= $from) {
            throw new RuntimeException('invalid calendar sync range');
        }
        $calendar = rawurlencode($this->calendarId());
        $query = http_build_query([
            'timeMin' => $from->format(DATE_RFC3339),
            'timeMax' => $to->format(DATE_RFC3339),
            'singleEvents' => 'true',
            'orderBy' => 'startTime',
            'maxResults' => 2500,
            'showDeleted' => 'true',
        ], '', '&', PHP_QUERY_RFC3986);
        $r = $this->request('GET', '/calendar/v3/calendars/' . $calendar . '/events?' . $query);
        if (!$r['ok']) {
            return ['ok' => false, 'status' => 'error', 'external_blocks' => 0, 'conflicts' => 0, 'events_seen' => 0, 'error' => (string)($r['error'] ?? 'calendar_api_error')];
        }

        $db = Database::conn();
        $db->prepare(
            'UPDATE calendar_blocks SET cancelled_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()
             WHERE clinic_id = ? AND starts_at < ? AND ends_at > ?'
        )->execute([$clinicId, $to->format('Y-m-d H:i:s'), $from->format('Y-m-d H:i:s')]);

        $external = 0;
        $conflicts = 0;
        $seen = 0;
        foreach ((array)($r['json']['items'] ?? []) as $event) {
            if (!is_array($event)) {
                continue;
            }
            $seen++;
            $eventId = trim((string)($event['id'] ?? ''));
            if ($eventId === '') {
                continue;
            }
            $private = is_array($event['extendedProperties']['private'] ?? null) ? $event['extendedProperties']['private'] : [];
            $crmId = (int)($private['crmAppointmentId'] ?? 0);
            $cancelled = (string)($event['status'] ?? '') === 'cancelled';
            $times = $this->eventTimes($event);

            if ($crmId > 0) {
                $appt = (new Appointment())->find($crmId);
                if (!$appt || (int)$appt['clinic_id'] !== $clinicId) {
                    continue;
                }
                if ($cancelled || !$times) {
                    $conflicts++;
                    $this->writeSyncState($crmId, $eventId, 'conflict', 'pull', null, null, 'Remote calendar event was cancelled or lost its timed range.', (string)($event['etag'] ?? ''));
                    continue;
                }
                [$remoteStart, $remoteEnd] = $times;
                $localStart = new DateTimeImmutable((string)$appt['scheduled_at'], $this->utc);
                $localEnd = $localStart->add(new DateInterval('PT' . max(5, (int)$appt['duration_minutes']) . 'M'));
                $same = $localStart->format('Y-m-d H:i:s') === $remoteStart->format('Y-m-d H:i:s')
                    && $localEnd->format('Y-m-d H:i:s') === $remoteEnd->format('Y-m-d H:i:s');
                if (!$same) {
                    $conflicts++;
                    $this->writeSyncState(
                        $crmId, $eventId, 'conflict', 'pull',
                        $remoteStart->format('Y-m-d H:i:s'), $remoteEnd->format('Y-m-d H:i:s'),
                        'Google/Desktop time differs from CRM time.', (string)($event['etag'] ?? '')
                    );
                } else {
                    $this->writeSyncState($crmId, $eventId, 'synced', 'pull', null, null, null, (string)($event['etag'] ?? ''));
                }
                continue;
            }

            if ($cancelled || !$times) {
                continue;
            }
            [$start, $end] = $times;
            $external++;
            $db->prepare(
                "INSERT INTO calendar_blocks
                 (clinic_id, google_event_id, starts_at, ends_at, summary, google_updated_at, cancelled_at, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, NULL, UTC_TIMESTAMP(), UTC_TIMESTAMP())
                 ON DUPLICATE KEY UPDATE starts_at = VALUES(starts_at), ends_at = VALUES(ends_at),
                   summary = VALUES(summary), google_updated_at = VALUES(google_updated_at), cancelled_at = NULL,
                   updated_at = UTC_TIMESTAMP()"
            )->execute([
                $clinicId,
                $eventId,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                mb_substr((string)($event['summary'] ?? 'Google Calendar'), 0, 255),
                $this->googleUpdatedToSql((string)($event['updated'] ?? '')),
            ]);
        }
        return ['ok' => true, 'status' => 'synced', 'external_blocks' => $external, 'conflicts' => $conflicts, 'events_seen' => $seen];
    }

    /** @return array{ok:bool,status:int,message:string} */
    public function resolveConflict(int $appointmentId, string $strategy): array
    {
        if (!in_array($strategy, ['keep_crm', 'accept_google'], true)) {
            return ['ok' => false, 'status' => 422, 'message' => 'روش حل تعارض معتبر نیست.'];
        }
        $db = Database::conn();
        $stmt = $db->prepare(
            "SELECT s.*, a.clinic_id, a.patient_id, a.provider_id, a.room, a.duration_minutes, a.status AS appointment_status
             FROM calendar_sync_state s
             JOIN appointments a ON a.id = s.appointment_id
             WHERE s.appointment_id = ? AND s.sync_status = 'conflict' LIMIT 1"
        );
        $stmt->execute([$appointmentId]);
        $row = $stmt->fetch();
        if (!$row) {
            return ['ok' => false, 'status' => 404, 'message' => 'تعارض تقویم یافت نشد.'];
        }
        if ($strategy === 'keep_crm') {
            $push = $this->pushAppointment($appointmentId);
            return $push['ok']
                ? ['ok' => true, 'status' => 200, 'message' => 'زمان CRM در Google Calendar اعمال شد.']
                : ['ok' => false, 'status' => 502, 'message' => 'اعمال زمان CRM در Google Calendar ناموفق بود.'];
        }
        if (!$row['remote_starts_at'] || !$row['remote_ends_at']) {
            return ['ok' => false, 'status' => 409, 'message' => 'زمان جایگزین Google Calendar در دسترس نیست.'];
        }
        $start = new DateTimeImmutable((string)$row['remote_starts_at'], $this->utc);
        $end = new DateTimeImmutable((string)$row['remote_ends_at'], $this->utc);
        $duration = max(5, (int)round(($end->getTimestamp() - $start->getTimestamp()) / 60));
        $service = new AppointmentService();
        $result = $service->rescheduleLocked(
            (int)$row['clinic_id'],
            $appointmentId,
            $start->format('Y-m-d H:i:s'),
            $duration,
            $row['room'] !== null ? (string)$row['room'] : null,
            (int)$row['provider_id'],
            (int)$row['patient_id']
        );
        if (!$result['ok']) {
            return ['ok' => false, 'status' => (int)$result['status'], 'message' => (string)$result['message']];
        }
        $this->writeSyncState(
            $appointmentId,
            (string)$row['google_event_id'],
            'synced', 'pull', null, null, null,
            $row['google_etag'] !== null ? (string)$row['google_etag'] : null
        );
        return ['ok' => true, 'status' => 200, 'message' => 'زمان Google/Desktop در CRM پذیرفته شد.'];
    }

    /** @return array{ok:bool,status:string,error?:string} */
    private function deleteAppointmentEvent(int $appointmentId): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => true, 'status' => 'skipped'];
        }
        $db = Database::conn();
        $stmt = $db->prepare('SELECT google_event_id FROM calendar_sync_state WHERE appointment_id = ? LIMIT 1');
        $stmt->execute([$appointmentId]);
        $eventId = trim((string)($stmt->fetchColumn() ?: ''));
        if ($eventId === '') {
            return ['ok' => true, 'status' => 'skipped'];
        }
        $r = $this->request('DELETE', '/calendar/v3/calendars/' . rawurlencode($this->calendarId()) . '/events/' . rawurlencode($eventId) . '?sendUpdates=none');
        if (!$r['ok'] && $r['status'] !== 404) {
            $this->writeSyncState($appointmentId, $eventId, 'error', 'push', null, null, (string)($r['error'] ?? 'calendar_delete_failed'), null);
            return ['ok' => false, 'status' => 'error', 'error' => (string)($r['error'] ?? 'calendar_delete_failed')];
        }
        $this->writeSyncState($appointmentId, $eventId, 'deleted', 'push', null, null, null, null);
        return ['ok' => true, 'status' => 'deleted'];
    }

    /** @return array{ok:bool,status:int,json:array,error:?string} */
    private function request(string $method, string $path, ?array $body = null): array
    {
        $token = $this->accessToken();
        if ($token === null) {
            return ['ok' => false, 'status' => 0, 'json' => [], 'error' => 'calendar_auth_failed'];
        }
        $url = 'https://www.googleapis.com' . $path;
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $token];
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
        ];
        if ($body !== null) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $headers[] = 'Content-Type: application/json';
            $options[CURLOPT_HTTPHEADER] = $headers;
            $options[CURLOPT_POSTFIELDS] = $json === false ? '{}' : $json;
        }
        curl_setopt_array($ch, $options);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $errno !== 0) {
            return ['ok' => false, 'status' => $status, 'json' => [], 'error' => 'calendar_transport_error'];
        }
        $decoded = $raw === '' ? [] : json_decode((string)$raw, true);
        $decoded = is_array($decoded) ? $decoded : [];
        $ok = ($status >= 200 && $status < 300) || ($method === 'DELETE' && $status === 204);
        $error = !$ok ? (string)($decoded['error']['message'] ?? ('calendar_http_' . $status)) : null;
        return ['ok' => $ok, 'status' => $status, 'json' => $decoded, 'error' => $error];
    }

    private function accessToken(): ?string
    {
        if (self::$cachedAccessToken && self::$accessTokenExpiresAt > time() + 60) {
            return self::$cachedAccessToken;
        }
        $body = http_build_query([
            'client_id' => trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_ID'] ?? '')),
            'client_secret' => trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] ?? '')),
            'refresh_token' => trim((string)($_ENV['GOOGLE_CALENDAR_REFRESH_TOKEN'] ?? '')),
            'grant_type' => 'refresh_token',
        ], '', '&', PHP_QUERY_RFC3986);
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        if ($raw === false || $status < 200 || $status >= 300) {
            return null;
        }
        $decoded = json_decode((string)$raw, true);
        $token = is_array($decoded) ? trim((string)($decoded['access_token'] ?? '')) : '';
        if ($token === '') {
            return null;
        }
        self::$cachedAccessToken = $token;
        self::$accessTokenExpiresAt = time() + max(300, (int)($decoded['expires_in'] ?? 3600));
        return $token;
    }

    /** @return array{0:DateTimeImmutable,1:DateTimeImmutable}|null */
    private function eventTimes(array $event): ?array
    {
        $startRaw = $event['start']['dateTime'] ?? null;
        $endRaw = $event['end']['dateTime'] ?? null;
        if (!$startRaw || !$endRaw) {
            if (($_ENV['GOOGLE_CALENDAR_BLOCK_ALL_DAY'] ?? '0') !== '1') {
                return null;
            }
            $startDate = $event['start']['date'] ?? null;
            $endDate = $event['end']['date'] ?? null;
            if (!$startDate || !$endDate) {
                return null;
            }
            $tz = new DateTimeZone('Asia/Tehran');
            return [
                (new DateTimeImmutable((string)$startDate . ' 00:00:00', $tz))->setTimezone($this->utc),
                (new DateTimeImmutable((string)$endDate . ' 00:00:00', $tz))->setTimezone($this->utc),
            ];
        }
        try {
            return [
                (new DateTimeImmutable((string)$startRaw))->setTimezone($this->utc),
                (new DateTimeImmutable((string)$endRaw))->setTimezone($this->utc),
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function writeSyncState(
        int $appointmentId,
        ?string $eventId,
        string $status,
        string $direction,
        ?string $remoteStart,
        ?string $remoteEnd,
        ?string $error,
        ?string $etag
    ): void {
        $db = Database::conn();
        $appt = (new Appointment())->find($appointmentId);
        if (!$appt) {
            return;
        }
        $db->prepare(
            "INSERT INTO calendar_sync_state
             (clinic_id, appointment_id, google_event_id, google_etag, sync_status, last_direction,
              last_error, remote_starts_at, remote_ends_at, last_synced_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP(), UTC_TIMESTAMP())
             ON DUPLICATE KEY UPDATE google_event_id = VALUES(google_event_id), google_etag = VALUES(google_etag),
                sync_status = VALUES(sync_status), last_direction = VALUES(last_direction), last_error = VALUES(last_error),
                remote_starts_at = VALUES(remote_starts_at), remote_ends_at = VALUES(remote_ends_at),
                last_synced_at = UTC_TIMESTAMP(), updated_at = UTC_TIMESTAMP()"
        )->execute([
            (int)$appt['clinic_id'], $appointmentId, $eventId, $etag, $status, $direction,
            $error !== null ? mb_substr($error, 0, 1000) : null, $remoteStart, $remoteEnd,
        ]);
    }

    private function parseUtc(?string $value): ?DateTimeImmutable
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($value, $this->utc))->setTimezone($this->utc);
        } catch (\Throwable) {
            return null;
        }
    }

    private function googleUpdatedToSql(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($value))->setTimezone($this->utc)->format('Y-m-d H:i:s');
        } catch (\Throwable) {
            return null;
        }
    }

    private function calendarId(): string
    {
        return trim((string)($_ENV['GOOGLE_CALENDAR_ID'] ?? ''));
    }
}
