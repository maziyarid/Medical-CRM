<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Services\RecordingSessionService;
use RuntimeException;

final class RecordingController extends Controller
{
    private RecordingSessionService $service;

    public function __construct()
    {
        $this->service = new RecordingSessionService();
    }

    public function readiness(Request $req, string $patientId): array
    {
        return $this->run(function () use ($req, $patientId) {
            $clinicId = $this->clinicId($req);
            $data = $this->service->readiness($clinicId, $this->id($patientId));
            $data['consent_text'] = trim((string)($_ENV['RECORDING_CONSENT_TEXT_FA'] ?? ''));
            return $this->success($data);
        });
    }

    public function create(Request $req, string $patientId): array
    {
        return $this->run(function () use ($req, $patientId) {
            if (($req->body['consent_acknowledged'] ?? false) !== true) {
                return $this->error('رضایت صریح برای ضبط و تبدیل گفتار باید ثبت شود', 422);
            }
            $consentText = trim((string)($_ENV['RECORDING_CONSENT_TEXT_FA'] ?? ''));
            if ($consentText === '') {
                throw new RuntimeException('RECORDING_NOT_READY');
            }
            $participants = $req->body['participants'] ?? [];
            if (!is_array($participants)) throw new RuntimeException('INVALID_CONSENT');

            $data = $this->service->create(
                $this->clinicId($req),
                $this->id($patientId),
                $this->actorId($req),
                trim((string)($req->body['policy_version'] ?? '')),
                $consentText,
                $participants
            );
            return $this->success($data, 201);
        });
    }

    public function chunk(Request $req, string $patientId, string $sessionId, string $sequence): array
    {
        return $this->run(function () use ($req, $patientId, $sessionId, $sequence) {
            if (!preg_match('/^[0-9]{1,4}$/', $sequence)) throw new RuntimeException('INVALID_CHUNK');
            $file = $req->files['audio'] ?? null;
            if (!is_array($file)) throw new RuntimeException('INVALID_CHUNK');

            $data = $this->service->storeChunk(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId),
                (int)$sequence,
                $file,
                $req->body
            );
            return $this->success($data, 201);
        });
    }

    public function finalize(Request $req, string $patientId, string $sessionId): array
    {
        return $this->run(function () use ($req, $patientId, $sessionId) {
            $expected = $this->unsigned($req->body['expected_chunks'] ?? null);
            $duration = $this->unsigned($req->body['audio_duration_ms'] ?? null);
            $data = $this->service->finalize(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId),
                $expected,
                $duration
            );
            return $this->success($data, 202);
        });
    }

    public function show(Request $req, string $patientId, string $sessionId): array
    {
        return $this->run(function () use ($req, $patientId, $sessionId) {
            return $this->success($this->service->show(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId)
            ));
        });
    }

    public function withdraw(Request $req, string $patientId, string $sessionId): array
    {
        return $this->run(function () use ($req, $patientId, $sessionId) {
            return $this->success($this->service->withdraw(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId),
                $this->actorId($req)
            ));
        });
    }

    public function patchSegment(
        Request $req,
        string $patientId,
        string $sessionId,
        string $segmentId
    ): array {
        return $this->run(function () use ($req, $patientId, $sessionId, $segmentId) {
            $hasText = array_key_exists('text', $req->body);
            $hasSpeaker = array_key_exists('speaker_role', $req->body);
            if (!$hasText && !$hasSpeaker) throw new RuntimeException('INVALID_SEGMENT');

            $text = $hasText ? (string)$req->body['text'] : null;
            $role = $hasSpeaker ? (string)$req->body['speaker_role'] : null;
            $name = $hasSpeaker ? (string)($req->body['speaker_name'] ?? '') : null;
            $version = $this->unsigned($req->body['expected_version'] ?? null);
            if ($version < 1) throw new RuntimeException('VERSION_CONFLICT');

            return $this->success($this->service->patchSegment(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId),
                $this->actorId($req),
                $segmentId,
                $text,
                $role,
                $name,
                $version
            ));
        });
    }

    public function approve(Request $req, string $patientId, string $sessionId): array
    {
        return $this->run(function () use ($req, $patientId, $sessionId) {
            $version = $this->unsigned($req->body['expected_version'] ?? null);
            if ($version < 1) throw new RuntimeException('VERSION_CONFLICT');
            return $this->success($this->service->approve(
                $this->clinicId($req),
                $this->id($patientId),
                strtolower($sessionId),
                $this->actorId($req),
                $version
            ));
        });
    }

    private function clinicId(Request $req): int
    {
        $id = (int)($req->user['clinic_id'] ?? 0);
        if ($id < 1) throw new RuntimeException('ACCESS_DENIED');
        return $id;
    }

    private function actorId(Request $req): int
    {
        $id = (int)($req->user['id'] ?? 0);
        if ($id < 1) throw new RuntimeException('ACCESS_DENIED');
        return $id;
    }

    private function id(string $value): int
    {
        if (!preg_match('/^[1-9][0-9]{0,15}$/', $value)) throw new RuntimeException('PATIENT_NOT_FOUND');
        return (int)$value;
    }

    private function unsigned(mixed $value): int
    {
        if (is_int($value)) return $value >= 0 ? $value : -1;
        if (is_string($value) && preg_match('/^[0-9]{1,12}$/', $value)) return (int)$value;
        return -1;
    }

    /** @param callable():array $fn */
    private function run(callable $fn): array
    {
        try {
            return $fn();
        } catch (RuntimeException $e) {
            $code = $e->getMessage();
            [$message, $status] = match ($code) {
                'PATIENT_NOT_FOUND' => ['بیمار یافت نشد', 404],
                'SESSION_NOT_FOUND' => ['جلسه ضبط یافت نشد', 404],
                'ACCESS_DENIED' => ['دسترسی کافی ندارید', 403],
                'RECORDING_NOT_READY', 'STORAGE_NOT_READY', 'TRANSCRIPTION_UNCONFIGURED' =>
                    ['سرویس ضبط و تبدیل گفتار هنوز برای استفاده واقعی آماده نیست', 503],
                'CHUNK_CONFLICT', 'VERSION_CONFLICT' =>
                    ['نسخه یا قطعه ارسالی با داده ثبت‌شده تعارض دارد؛ صفحه را تازه‌سازی کنید', 409],
                'SESSION_NOT_WRITABLE', 'SESSION_NOT_FINALIZABLE',
                'TRANSCRIPT_NOT_EDITABLE', 'TRANSCRIPT_NOT_APPROVABLE' =>
                    ['وضعیت فعلی جلسه این عملیات را اجازه نمی‌دهد', 409],
                'MISSING_CHUNKS', 'CAPTURE_GAP_TOO_LARGE' =>
                    ['بخشی از صدای جلسه ناقص است؛ جلسه بدون بررسی نهایی نمی‌شود', 422],
                'CHECKSUM_MISMATCH' => ['اعتبار قطعه صوتی تأیید نشد؛ دوباره ارسال کنید', 422],
                'UNSUPPORTED_AUDIO' => ['قالب فایل صوتی پشتیبانی نمی‌شود', 415],
                'SESSION_SIZE_LIMIT' => ['حجم کل جلسه از حد مجاز بیشتر شده است', 413],
                'INVALID_CONSENT', 'CONSENT_POLICY_MISMATCH' =>
                    ['اطلاعات رضایت معتبر نیست یا نسخه سیاست تغییر کرده است', 422],
                'INVALID_CHUNK', 'INVALID_MANIFEST', 'INVALID_SEGMENT', 'INVALID_TRANSCRIPT' =>
                    ['داده ارسالی معتبر نیست', 422],
                default => ['خطای داخلی در سرویس ضبط جلسه', 500],
            };
            if ($status >= 500) {
                error_log('[RecordingController] ' . $code);
            }
            return $this->error($message, $status);
        } catch (\Throwable $e) {
            error_log('[RecordingController] Unexpected: ' . $e->getMessage());
            return $this->error('خطای داخلی در سرویس ضبط جلسه', 500);
        }
    }
}
