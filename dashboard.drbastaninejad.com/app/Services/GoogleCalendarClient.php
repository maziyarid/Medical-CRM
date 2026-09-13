<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class GoogleCalendarClient
{
    private ?string $accessToken = null;

    public function calendarId(): string
    {
        $id = trim((string)($_ENV['GOOGLE_CALENDAR_ID'] ?? ''));
        if ($id === '') {
            throw new RuntimeException('Google Calendar ID is not configured');
        }
        return $id;
    }

    /** @return array<string,mixed> */
    public function upsertEvent(string $calendarId, string $eventId, array $event, ?string $etag = null): array
    {
        $path = '/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId);
        $headers = $etag ? ['If-Match: ' . $etag] : [];
        $update = $this->api('PUT', $path, $event, [], $headers);
        if ($update['status'] >= 200 && $update['status'] < 300) {
            return $update['body'];
        }
        if ($update['status'] !== 404) {
            throw new RuntimeException('Google Calendar update failed: HTTP ' . $update['status']);
        }
        $event['id'] = $eventId;
        // Do not send sendUpdates=none: Google warns that it can prevent events
        // propagating to external calendar clients, and this calendar is the
        // bridge used by the clinic's DevExpress scheduler.
        $insert = $this->api(
            'POST',
            '/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
            $event
        );
        if ($insert['status'] < 200 || $insert['status'] >= 300) {
            throw new RuntimeException('Google Calendar insert failed: HTTP ' . $insert['status']);
        }
        return $insert['body'];
    }

    public function deleteEvent(string $calendarId, string $eventId): void
    {
        $result = $this->api(
            'DELETE',
            '/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events/' . rawurlencode($eventId)
        );
        if (!in_array($result['status'], [200, 204, 404, 410], true)) {
            throw new RuntimeException('Google Calendar delete failed: HTTP ' . $result['status']);
        }
    }

    /**
     * @return array{events:array<int,array<string,mixed>>,next_sync_token:?string,reset:bool}
     */
    public function listChanges(string $calendarId, ?string $syncToken): array
    {
        $events = [];
        $pageToken = null;
        do {
            $query = [
                'maxResults' => 2500,
                'showDeleted' => 'true',
                'singleEvents' => 'true',
            ];
            if ($syncToken !== null && $syncToken !== '') {
                $query = ['syncToken' => $syncToken, 'maxResults' => 2500, 'showDeleted' => 'true'];
            }
            if ($pageToken) {
                $query['pageToken'] = $pageToken;
            }
            $response = $this->api(
                'GET',
                '/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events',
                null,
                $query
            );
            if ($response['status'] === 410 && $syncToken) {
                return ['events' => [], 'next_sync_token' => null, 'reset' => true];
            }
            if ($response['status'] < 200 || $response['status'] >= 300) {
                throw new RuntimeException('Google Calendar list failed: HTTP ' . $response['status']);
            }
            foreach (($response['body']['items'] ?? []) as $event) {
                if (is_array($event)) {
                    $events[] = $event;
                }
            }
            $pageToken = isset($response['body']['nextPageToken']) ? (string)$response['body']['nextPageToken'] : null;
            $nextSyncToken = isset($response['body']['nextSyncToken']) ? (string)$response['body']['nextSyncToken'] : null;
        } while ($pageToken);

        return ['events' => $events, 'next_sync_token' => $nextSyncToken ?? $syncToken, 'reset' => false];
    }

    /** @return array{status:int,body:array<string,mixed>} */
    private function api(string $method, string $path, ?array $body = null, array $query = [], array $extraHeaders = []): array
    {
        $url = 'https://www.googleapis.com' . $path;
        if ($query) {
            $url .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        $headers = array_merge([
            'Authorization: Bearer ' . $this->token(),
            'Accept: application/json',
        ], $extraHeaders);
        return $this->curlJson($method, $url, $body, $headers);
    }

    private function token(): string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }
        $clientId = trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_ID'] ?? ''));
        $clientSecret = trim((string)($_ENV['GOOGLE_CALENDAR_CLIENT_SECRET'] ?? ''));
        $refreshToken = trim((string)($_ENV['GOOGLE_CALENDAR_REFRESH_TOKEN'] ?? ''));
        if ($clientId === '' || $clientSecret === '' || $refreshToken === '') {
            throw new RuntimeException('Google Calendar OAuth is not configured');
        }
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for Google Calendar');
        }
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $refreshToken,
                'grant_type' => 'refresh_token',
            ], '', '&', PHP_QUERY_RFC3986),
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 20,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        $decoded = is_string($raw) ? json_decode($raw, true) : null;
        $token = is_array($decoded) ? trim((string)($decoded['access_token'] ?? '')) : '';
        if ($status < 200 || $status >= 300 || $token === '') {
            throw new RuntimeException('Google OAuth refresh failed' . ($error ? ': ' . $error : ''));
        }
        return $this->accessToken = $token;
    }

    /** @return array{status:int,body:array<string,mixed>} */
    private function curlJson(string $method, string $url, ?array $body, array $headers): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('cURL extension is required for Google Calendar');
        }
        $ch = curl_init($url);
        if ($body !== null) {
            $encoded = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 25,
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($raw === false || $errno !== 0) {
            throw new RuntimeException('Google Calendar transport failed: ' . $error);
        }
        $decoded = $raw !== '' ? json_decode((string)$raw, true) : [];
        return ['status' => $status, 'body' => is_array($decoded) ? $decoded : []];
    }
}
