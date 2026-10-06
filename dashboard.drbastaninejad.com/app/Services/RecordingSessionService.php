<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;
use RuntimeException;

/**
 * Private persistence and state transitions for RPH-137.
 *
 * This service never trusts a browser-supplied clinic/actor id and never writes
 * a transcript into EMR automatically. All paths are generated server-side.
 */
final class RecordingSessionService
{
    public const MAX_SESSION_MS = 10800000;
    public const MAX_SESSION_BYTES = 134217728;
    public const MAX_CHUNK_BYTES = 2097152;
    public const MAX_CHUNKS = 720;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::conn();
    }

    /** @return array<string,mixed> */
    public function readiness(int $clinicId, int $patientId): array
    {
        $this->assertScope($clinicId, $patientId);
        $schemaReady = $this->tablesReady();
        $enabled = (($_ENV['RECORDING_ENABLED'] ?? '0') === '1');
        $storage = $this->storageRoot();
        $storageReady = $storage !== null && $this->storageRootSafe($storage) && $this->ensureStorage($storage);
        $retentionDays = (int)($_ENV['RECORDING_RETENTION_DAYS'] ?? 0);
        $provider = $this->providerConfig();
        $policyVersion = trim((string)($_ENV['RECORDING_CONSENT_POLICY_VERSION'] ?? ''));

        $blockers = [];
        if (!$enabled) $blockers[] = 'feature_disabled';
        if (!$schemaReady) $blockers[] = 'schema_not_ready';
        if (!$storageReady) $blockers[] = 'private_storage_not_ready';
        if ($retentionDays < 1 || $retentionDays > 3650) $blockers[] = 'retention_not_configured';
        if ($policyVersion === '') $blockers[] = 'consent_policy_not_configured';
        if (!$provider['configured']) $blockers[] = 'transcription_provider_not_ready';

        return [
            'available' => $blockers === [],
            'blockers' => $blockers,
            'policy_version' => $policyVersion !== '' ? $policyVersion : null,
            'provider' => [
                'name' => $provider['name'],
                'model' => $provider['model'],
                'external_audio' => $provider['external_audio'],
            ],
            'limits' => [
                'max_session_ms' => self::MAX_SESSION_MS,
                'max_session_bytes' => self::MAX_SESSION_BYTES,
                'max_chunk_bytes' => self::MAX_CHUNK_BYTES,
                'max_chunks' => self::MAX_CHUNKS,
            ],
        ];
    }

    /** @param list<string> $participants */
    public function create(
        int $clinicId,
        int $patientId,
        int $actorId,
        string $policyVersion,
        string $consentText,
        array $participants
    ): array {
        $ready = $this->readiness($clinicId, $patientId);
        if (!$ready['available']) {
            throw new RuntimeException('RECORDING_NOT_READY');
        }
        if (!hash_equals((string)$ready['policy_version'], $policyVersion)) {
            throw new RuntimeException('CONSENT_POLICY_MISMATCH');
        }
        $consentText = trim($consentText);
        if ($consentText === '' || mb_strlen($consentText) > 4000) {
            throw new RuntimeException('INVALID_CONSENT');
        }
        $participants = array_values(array_unique(array_filter(array_map(
            static fn($v) => trim((string)$v),
            $participants
        ), static fn($v) => $v !== '')));
        if ($participants === [] || count($participants) > 8) {
            throw new RuntimeException('INVALID_CONSENT');
        }
        foreach ($participants as $participant) {
            if (mb_strlen($participant) > 120) throw new RuntimeException('INVALID_CONSENT');
        }

        $uuid = bin2hex(random_bytes(16));
        $days = (int)($_ENV['RECORDING_RETENTION_DAYS'] ?? 0);
        $retentionUntil = gmdate('Y-m-d H:i:s', time() + ($days * 86400));
        $provider = $this->providerConfig();

        $stmt = $this->db->prepare(
            'INSERT INTO recording_sessions
             (uuid,clinic_id,patient_id,created_by,status,consent_scope,consent_policy_version,
              consent_text_sha256,consent_participants_json,consent_recorded_at,retention_until,provider,provider_model)
             VALUES (?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP(),?,?,?)'
        );
        $stmt->execute([
            $uuid, $clinicId, $patientId, $actorId, 'ready', 'recording_transcription',
            $policyVersion, hash('sha256', $consentText),
            json_encode($participants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $retentionUntil, $provider['name'], $provider['model'],
        ]);

        return [
            'session_id' => $uuid,
            'status' => 'ready',
            'consent' => [
                'scope' => 'recording_transcription',
                'policy_version' => $policyVersion,
                'recorded_at' => gmdate('c'),
                'participants' => $participants,
            ],
            'retention_until' => gmdate('c', strtotime($retentionUntil) ?: time()),
            'provider' => ['name' => $provider['name'], 'model' => $provider['model']],
        ];
    }

    /**
     * @param array{name:string,type:string,tmp_name:string,error:int,size:int} $file
     * @param array<string,mixed> $meta
     */
    public function storeChunk(
        int $clinicId,
        int $patientId,
        string $uuid,
        int $sequence,
        array $file,
        array $meta
    ): array {
        if ($sequence < 0 || $sequence >= self::MAX_CHUNKS) {
            throw new RuntimeException('INVALID_CHUNK');
        }
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, true);
        if (!in_array($session['status'], ['ready','recording','paused'], true) || $session['consent_withdrawn_at'] !== null) {
            throw new RuntimeException('SESSION_NOT_WRITABLE');
        }
        if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] < 1 || $file['size'] > self::MAX_CHUNK_BYTES) {
            throw new RuntimeException('INVALID_CHUNK');
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new RuntimeException('INVALID_CHUNK');
        }

        $startMs = $this->positiveOrZeroInt($meta['start_ms'] ?? null);
        $endMs = $this->positiveOrZeroInt($meta['end_ms'] ?? null);
        if ($endMs <= $startMs || $endMs > self::MAX_SESSION_MS || ($endMs - $startMs) > 60000) {
            throw new RuntimeException('INVALID_CHUNK');
        }

        $claimedSha = strtolower(trim((string)($meta['sha256'] ?? '')));
        if (!preg_match('/^[a-f0-9]{64}$/', $claimedSha)) throw new RuntimeException('INVALID_CHUNK');
        $actualSha = hash_file('sha256', $file['tmp_name']);
        if (!is_string($actualSha) || !hash_equals($claimedSha, $actualSha)) {
            throw new RuntimeException('CHECKSUM_MISMATCH');
        }

        $mime = $this->detectMime($file['tmp_name']);
        if ($mime === null) throw new RuntimeException('UNSUPPORTED_AUDIO');
        $extension = match ($mime) {
            'audio/webm','video/webm' => 'webm',
            'audio/ogg','application/ogg' => 'ogg',
            'audio/mp4','video/mp4' => 'mp4',
            default => 'bin',
        };

        $existing = $this->db->prepare(
            'SELECT sequence_no,byte_length,sha256,start_ms,end_ms,mime_type
             FROM recording_chunks WHERE session_id=? AND sequence_no=? LIMIT 1'
        );
        $existing->execute([(int)$session['id'], $sequence]);
        $row = $existing->fetch();
        if ($row) {
            if ((int)$row['byte_length'] === $file['size'] && hash_equals((string)$row['sha256'], $actualSha)) {
                return $this->receipt($uuid, $patientId, $sequence, $row);
            }
            throw new RuntimeException('CHUNK_CONFLICT');
        }

        $sum = $this->db->prepare('SELECT COALESCE(SUM(byte_length),0) FROM recording_chunks WHERE session_id=?');
        $sum->execute([(int)$session['id']]);
        if (((int)$sum->fetchColumn() + $file['size']) > self::MAX_SESSION_BYTES) {
            throw new RuntimeException('SESSION_SIZE_LIMIT');
        }

        $root = $this->storageRoot();
        if ($root === null || !$this->storageRootSafe($root)) throw new RuntimeException('STORAGE_NOT_READY');
        $dir = $root . '/clinic-' . $clinicId . '/patient-' . $patientId . '/session-' . $uuid;
        if (!$this->ensureStorage($dir)) throw new RuntimeException('STORAGE_NOT_READY');
        $path = $dir . '/chunk-' . str_pad((string)$sequence, 4, '0', STR_PAD_LEFT) . '.' . $extension;
        $tmp = $path . '.upload-' . bin2hex(random_bytes(6));
        if (!move_uploaded_file($file['tmp_name'], $tmp)) throw new RuntimeException('STORAGE_WRITE_FAILED');
        @chmod($tmp, 0640);
        if (!rename($tmp, $path)) {
            @unlink($tmp);
            throw new RuntimeException('STORAGE_WRITE_FAILED');
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO recording_chunks
                 (session_id,sequence_no,start_ms,end_ms,mime_type,byte_length,sha256,storage_path)
                 VALUES (?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                (int)$session['id'], $sequence, $startMs, $endMs, $mime,
                $file['size'], $actualSha, $path,
            ]);
            $this->db->prepare(
                "UPDATE recording_sessions
                 SET status=IF(status='ready','recording',status), source_mime=COALESCE(source_mime,?)
                 WHERE id=?"
            )->execute([$mime, (int)$session['id']]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            @unlink($path);
            if ($this->isDuplicate($e)) {
                return $this->duplicateReceiptOrConflict((int)$session['id'], $uuid, $patientId, $sequence, $actualSha, $file['size']);
            }
            throw $e;
        }

        return $this->receipt($uuid, $patientId, $sequence, [
            'sequence_no' => $sequence,
            'byte_length' => $file['size'],
            'sha256' => $actualSha,
            'start_ms' => $startMs,
            'end_ms' => $endMs,
            'mime_type' => $mime,
        ]);
    }

    public function finalize(
        int $clinicId,
        int $patientId,
        string $uuid,
        int $expectedChunks,
        int $audioDurationMs
    ): array {
        if ($expectedChunks < 1 || $expectedChunks > self::MAX_CHUNKS ||
            $audioDurationMs < 1 || $audioDurationMs > self::MAX_SESSION_MS) {
            throw new RuntimeException('INVALID_MANIFEST');
        }
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, true);
        if (!in_array($session['status'], ['ready','recording','paused'], true) || $session['consent_withdrawn_at'] !== null) {
            throw new RuntimeException('SESSION_NOT_FINALIZABLE');
        }

        $q = $this->db->prepare(
            'SELECT sequence_no,start_ms,end_ms,byte_length,sha256,mime_type,storage_path
             FROM recording_chunks WHERE session_id=? ORDER BY sequence_no ASC'
        );
        $q->execute([(int)$session['id']]);
        $chunks = $q->fetchAll();
        if (count($chunks) !== $expectedChunks) throw new RuntimeException('MISSING_CHUNKS');

        $previousEnd = 0;
        $totalBytes = 0;
        $gaps = [];
        $maxGap = max(0, min(30000, (int)($_ENV['RECORDING_MAX_GAP_MS'] ?? 3000)));
        foreach ($chunks as $i => $chunk) {
            if ((int)$chunk['sequence_no'] !== $i) throw new RuntimeException('MISSING_CHUNKS');
            $start = (int)$chunk['start_ms'];
            $end = (int)$chunk['end_ms'];
            if ($end <= $start || $start < $previousEnd) throw new RuntimeException('INVALID_MANIFEST');
            $gap = $start - $previousEnd;
            if ($i > 0 && $gap > 0) {
                if ($gap > $maxGap) throw new RuntimeException('CAPTURE_GAP_TOO_LARGE');
                $gaps[] = ['after_sequence' => $i - 1, 'gap_ms' => $gap];
            }
            if (!is_file((string)$chunk['storage_path'])) throw new RuntimeException('MISSING_CHUNKS');
            $previousEnd = $end;
            $totalBytes += (int)$chunk['byte_length'];
        }
        if ($totalBytes > self::MAX_SESSION_BYTES || abs($previousEnd - $audioDurationMs) > max(2000, $maxGap)) {
            throw new RuntimeException('INVALID_MANIFEST');
        }

        $provider = $this->providerConfig();
        if (!$provider['configured']) throw new RuntimeException('TRANSCRIPTION_UNCONFIGURED');
        $jobKey = 'transcribe:' . $uuid . ':' . $provider['name'] . ':' . $provider['model'] . ':v1';

        $this->db->beginTransaction();
        try {
            $this->db->prepare(
                "UPDATE recording_sessions SET status='queued',stopped_at=UTC_TIMESTAMP(),
                 audio_duration_ms=?,expected_chunks=?,total_bytes=?,provider=?,provider_model=?
                 WHERE id=? AND consent_withdrawn_at IS NULL"
            )->execute([
                $audioDurationMs, $expectedChunks, $totalBytes,
                $provider['name'], $provider['model'], (int)$session['id'],
            ]);
            $this->db->prepare(
                "INSERT IGNORE INTO recording_jobs
                 (session_id,job_key,kind,status,provider,model,available_at)
                 VALUES (?,?, 'transcribe','queued',?,?,UTC_TIMESTAMP())"
            )->execute([(int)$session['id'], $jobKey, $provider['name'], $provider['model']]);
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }

        return [
            'session_id' => $uuid,
            'status' => 'queued',
            'audio_duration_ms' => $audioDurationMs,
            'expected_chunks' => $expectedChunks,
            'total_bytes' => $totalBytes,
            'capture_gaps' => $gaps,
            'provider' => ['name' => $provider['name'], 'model' => $provider['model']],
        ];
    }

    /** @return array<string,mixed> */
    public function show(int $clinicId, int $patientId, string $uuid): array
    {
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, false);
        $result = [
            'session_id' => $session['uuid'],
            'status' => $session['status'],
            'audio_duration_ms' => (int)$session['audio_duration_ms'],
            'expected_chunks' => (int)$session['expected_chunks'],
            'total_bytes' => (int)$session['total_bytes'],
            'provider' => ['name' => $session['provider'], 'model' => $session['provider_model']],
            'consent' => [
                'scope' => $session['consent_scope'],
                'policy_version' => $session['consent_policy_version'],
                'recorded_at' => $this->iso((string)$session['consent_recorded_at']),
                'withdrawn_at' => $session['consent_withdrawn_at'] ? $this->iso((string)$session['consent_withdrawn_at']) : null,
            ],
            'retention_until' => $session['retention_until'] ? $this->iso((string)$session['retention_until']) : null,
            'approved_at' => $session['approved_at'] ? $this->iso((string)$session['approved_at']) : null,
            'processing_error_code' => $session['processing_error_code'],
            'transcript' => null,
        ];

        if ($session['consent_withdrawn_at'] === null) {
            $q = $this->db->prepare(
                'SELECT version_no,kind,provider,model,transcript_json,created_at
                 FROM recording_transcript_versions WHERE session_id=? ORDER BY version_no DESC LIMIT 1'
            );
            $q->execute([(int)$session['id']]);
            $version = $q->fetch();
            if ($version) {
                $decoded = json_decode((string)$version['transcript_json'], true);
                $result['transcript'] = [
                    'version' => (int)$version['version_no'],
                    'kind' => $version['kind'],
                    'provider' => $version['provider'],
                    'model' => $version['model'],
                    'created_at' => $this->iso((string)$version['created_at']),
                    'data' => is_array($decoded) ? $decoded : null,
                ];
            }
        }
        return $result;
    }

    public function withdraw(int $clinicId, int $patientId, string $uuid, int $actorId): array
    {
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, true);
        if ($session['consent_withdrawn_at'] === null) {
            $this->db->beginTransaction();
            try {
                $this->db->prepare(
                    "UPDATE recording_sessions SET status='withdrawn',consent_withdrawn_at=UTC_TIMESTAMP(),approved_by=NULL,approved_at=NULL WHERE id=?"
                )->execute([(int)$session['id']]);
                $this->db->prepare(
                    "UPDATE recording_jobs SET status='cancelled',lease_token=NULL,lease_expires_at=NULL
                     WHERE session_id=? AND status IN ('queued','running','retry')"
                )->execute([(int)$session['id']]);
                $this->db->commit();
            } catch (\Throwable $e) {
                if ($this->db->inTransaction()) $this->db->rollBack();
                throw $e;
            }

            if (($_ENV['RECORDING_DELETE_AUDIO_ON_WITHDRAW'] ?? '1') === '1') {
                $q = $this->db->prepare('SELECT storage_path FROM recording_chunks WHERE session_id=?');
                $q->execute([(int)$session['id']]);
                foreach ($q->fetchAll() as $chunk) {
                    $path = (string)$chunk['storage_path'];
                    if ($this->isPathInsideStorage($path) && is_file($path)) @unlink($path);
                }
            }
        }
        return ['session_id' => $uuid, 'status' => 'withdrawn', 'actor_id' => $actorId];
    }

    public function patchSegment(
        int $clinicId,
        int $patientId,
        string $uuid,
        int $actorId,
        string $segmentId,
        ?string $text,
        ?string $speakerRole,
        ?string $speakerName,
        int $expectedVersion
    ): array {
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, true);
        if (!in_array($session['status'], ['review_required','approved'], true) || $session['consent_withdrawn_at'] !== null) {
            throw new RuntimeException('TRANSCRIPT_NOT_EDITABLE');
        }
        if (!preg_match('/^[A-Za-z0-9_.:-]{1,128}$/', $segmentId)) throw new RuntimeException('INVALID_SEGMENT');
        if ($text !== null && mb_strlen($text) > 20000) throw new RuntimeException('INVALID_SEGMENT');
        if ($speakerRole !== null && !in_array($speakerRole, ['unknown','doctor','patient','other'], true)) throw new RuntimeException('INVALID_SEGMENT');
        if ($speakerName !== null && mb_strlen($speakerName) > 200) throw new RuntimeException('INVALID_SEGMENT');

        $latest = $this->latestVersion((int)$session['id'], true);
        if (!$latest || (int)$latest['version_no'] !== $expectedVersion) {
            throw new RuntimeException('VERSION_CONFLICT');
        }
        $doc = json_decode((string)$latest['transcript_json'], true);
        if (!is_array($doc) || !isset($doc['segments']) || !is_array($doc['segments'])) {
            throw new RuntimeException('INVALID_TRANSCRIPT');
        }

        $found = false;
        foreach ($doc['segments'] as &$segment) {
            if (!is_array($segment) || (string)($segment['id'] ?? '') !== $segmentId) continue;
            $found = true;
            if ($text !== null) $segment['text'] = $text;
            if ($speakerRole !== null) {
                $segment['speaker'] = [
                    'role' => $speakerRole,
                    'name' => $speakerRole === 'unknown' ? '' : trim((string)$speakerName),
                    'source' => 'manual',
                ];
                if ($speakerRole !== 'unknown' && $segment['speaker']['name'] === '') {
                    throw new RuntimeException('INVALID_SEGMENT');
                }
            }
            $segment['last_edited_by'] = $actorId;
            break;
        }
        unset($segment);
        if (!$found) throw new RuntimeException('INVALID_SEGMENT');

        $newVersion = $this->insertTranscriptVersion(
            (int)$session['id'],
            'manual',
            'human',
            'manual-review',
            $doc,
            $actorId,
            (int)$latest['version_no']
        );
        if ($session['status'] === 'approved') {
            $this->db->prepare(
                "UPDATE recording_sessions SET status='review_required',approved_by=NULL,approved_at=NULL WHERE id=?"
            )->execute([(int)$session['id']]);
        }

        return ['session_id' => $uuid, 'version' => $newVersion, 'status' => 'review_required'];
    }

    public function approve(int $clinicId, int $patientId, string $uuid, int $actorId, int $expectedVersion): array
    {
        $session = $this->sessionForScope($clinicId, $patientId, $uuid, true);
        if ($session['status'] !== 'review_required' || $session['consent_withdrawn_at'] !== null) {
            throw new RuntimeException('TRANSCRIPT_NOT_APPROVABLE');
        }
        $latest = $this->latestVersion((int)$session['id'], true);
        if (!$latest || (int)$latest['version_no'] !== $expectedVersion) throw new RuntimeException('VERSION_CONFLICT');

        $doc = json_decode((string)$latest['transcript_json'], true);
        if (!is_array($doc)) throw new RuntimeException('INVALID_TRANSCRIPT');
        $newVersion = $this->insertTranscriptVersion(
            (int)$session['id'],
            'approved',
            'human',
            'manual-approval',
            $doc,
            $actorId,
            (int)$latest['version_no']
        );
        $this->db->prepare(
            "UPDATE recording_sessions SET status='approved',approved_by=?,approved_at=UTC_TIMESTAMP() WHERE id=?"
        )->execute([$actorId, (int)$session['id']]);

        return [
            'session_id' => $uuid,
            'status' => 'approved',
            'version' => $newVersion,
            'auto_write_emr' => false,
        ];
    }

    /** @return array<string,mixed> */
    public function providerConfig(): array
    {
        $name = strtolower(trim((string)($_ENV['TRANSCRIPTION_PROVIDER'] ?? '')));
        if ($name === 'groq') {
            $model = trim((string)($_ENV['GROQ_TRANSCRIPTION_MODEL'] ?? 'whisper-large-v3'));
            $key = trim((string)($_ENV['GROQ_API_KEY'] ?? ''));
            $external = (($_ENV['TRANSCRIPTION_EXTERNAL_AUDIO_ALLOWED'] ?? '0') === '1');
            return [
                'name' => 'groq',
                'model' => $model,
                'external_audio' => true,
                'configured' => $external && $key !== '' && !str_starts_with($key, 'CHANGE_ME'),
            ];
        }
        if ($name === 'local_whisper') {
            $model = trim((string)($_ENV['LOCAL_WHISPER_MODEL'] ?? 'small'));
            $worker = trim((string)($_ENV['LOCAL_WHISPER_WORKER'] ?? ''));
            return [
                'name' => 'local_whisper',
                'model' => $model,
                'external_audio' => false,
                'configured' => $worker !== '',
            ];
        }
        return ['name' => 'unconfigured', 'model' => 'unconfigured', 'external_audio' => false, 'configured' => false];
    }

    /** @return array<string,mixed> */
    public function sessionByIdForWorker(int $id): array
    {
        $q = $this->db->prepare('SELECT * FROM recording_sessions WHERE id=? LIMIT 1');
        $q->execute([$id]);
        $row = $q->fetch();
        if (!$row) throw new RuntimeException('SESSION_NOT_FOUND');
        return $row;
    }

    /** @return list<array<string,mixed>> */
    public function chunksForWorker(int $sessionId): array
    {
        $q = $this->db->prepare(
            'SELECT * FROM recording_chunks WHERE session_id=? ORDER BY sequence_no ASC'
        );
        $q->execute([$sessionId]);
        return $q->fetchAll();
    }

    public function appendMachineTranscript(
        int $sessionId,
        string $provider,
        string $model,
        array $document
    ): int {
        return $this->insertTranscriptVersion($sessionId, 'machine', $provider, $model, $document, null, null);
    }

    public function appendDerivedTranscript(
        int $sessionId,
        string $kind,
        string $provider,
        string $model,
        array $document,
        ?int $sourceVersion
    ): int {
        if (!in_array($kind, ['normalised','agent_candidate','consensus'], true)) {
            throw new RuntimeException('INVALID_TRANSCRIPT_KIND');
        }
        return $this->insertTranscriptVersion($sessionId, $kind, $provider, $model, $document, null, $sourceVersion);
    }

    public function markReviewRequired(int $sessionId): void
    {
        $this->db->prepare(
            "UPDATE recording_sessions SET status='review_required',processing_error_code=NULL WHERE id=? AND consent_withdrawn_at IS NULL"
        )->execute([$sessionId]);
    }

    public function markProcessingFailure(int $sessionId, string $code): void
    {
        $code = preg_replace('/[^A-Z0-9_.:-]/', '_', strtoupper($code)) ?: 'TRANSCRIPTION_FAILED';
        $this->db->prepare(
            "UPDATE recording_sessions SET status='failed',processing_error_code=? WHERE id=? AND consent_withdrawn_at IS NULL"
        )->execute([substr($code, 0, 96), $sessionId]);
    }

    private function assertScope(int $clinicId, int $patientId): void
    {
        if ($clinicId < 1 || $patientId < 1) throw new RuntimeException('ACCESS_DENIED');
        $q = $this->db->prepare(
            'SELECT id FROM patients WHERE id=? AND clinic_id=? AND deleted_at IS NULL LIMIT 1'
        );
        $q->execute([$patientId, $clinicId]);
        if (!$q->fetchColumn()) throw new RuntimeException('PATIENT_NOT_FOUND');
    }

    /** @return array<string,mixed> */
    private function sessionForScope(int $clinicId, int $patientId, string $uuid, bool $forUpdate): array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $uuid)) throw new RuntimeException('SESSION_NOT_FOUND');
        $sql = 'SELECT * FROM recording_sessions WHERE uuid=? AND clinic_id=? AND patient_id=? LIMIT 1';
        if ($forUpdate && $this->db->inTransaction()) $sql .= ' FOR UPDATE';
        $q = $this->db->prepare($sql);
        $q->execute([$uuid, $clinicId, $patientId]);
        $row = $q->fetch();
        if (!$row) throw new RuntimeException('SESSION_NOT_FOUND');
        return $row;
    }

    private function tablesReady(): bool
    {
        try {
            foreach (['recording_sessions','recording_chunks','recording_jobs','recording_transcript_versions'] as $table) {
                $q = $this->db->prepare(
                    'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?'
                );
                $q->execute([$table]);
                if ((int)$q->fetchColumn() !== 1) return false;
            }
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function storageRoot(): ?string
    {
        $root = trim((string)($_ENV['RECORDING_STORAGE_PATH'] ?? ''));
        if ($root === '' || !str_starts_with($root, '/')) return null;
        return rtrim($root, '/');
    }

    private function storageRootSafe(string $root): bool
    {
        $realHome = realpath('/home/drbastaninejad');
        if ($realHome === false) return false;
        $candidate = $root;
        $docRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? '')) ?: '';
        if (!str_starts_with($candidate . '/', rtrim($realHome, '/') . '/')) return false;
        if ($docRoot !== '' && str_starts_with($candidate . '/', rtrim($docRoot, '/') . '/')) return false;
        return !str_contains($candidate, '/../') && !str_ends_with($candidate, '/..');
    }

    private function ensureStorage(string $path): bool
    {
        if (!is_dir($path) && !@mkdir($path, 0750, true) && !is_dir($path)) return false;
        return is_writable($path);
    }

    private function isPathInsideStorage(string $path): bool
    {
        $root = $this->storageRoot();
        if ($root === null) return false;
        $realRoot = realpath($root);
        $realPath = realpath($path);
        return $realRoot !== false && $realPath !== false &&
            str_starts_with($realPath, rtrim($realRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR);
    }

    private function detectMime(string $path): ?string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string)$finfo->file($path));
        $allowed = ['audio/webm','video/webm','audio/ogg','application/ogg','audio/mp4','video/mp4'];
        return in_array($mime, $allowed, true) ? $mime : null;
    }

    private function positiveOrZeroInt(mixed $value): int
    {
        if (is_int($value)) return $value >= 0 ? $value : -1;
        if (!is_string($value) || !preg_match('/^[0-9]{1,12}$/', $value)) return -1;
        $int = (int)$value;
        return $int >= 0 ? $int : -1;
    }

    /** @param array<string,mixed> $row */
    private function receipt(string $uuid, int $patientId, int $sequence, array $row): array
    {
        return [
            'stored' => true,
            'session_id' => $uuid,
            'patient_id' => $patientId,
            'sequence' => $sequence,
            'start_ms' => (int)$row['start_ms'],
            'end_ms' => (int)$row['end_ms'],
            'mime_type' => (string)$row['mime_type'],
            'byte_length' => (int)$row['byte_length'],
            'sha256' => (string)$row['sha256'],
        ];
    }

    private function duplicateReceiptOrConflict(
        int $sessionId,
        string $uuid,
        int $patientId,
        int $sequence,
        string $sha,
        int $size
    ): array {
        $q = $this->db->prepare(
            'SELECT sequence_no,byte_length,sha256,start_ms,end_ms,mime_type
             FROM recording_chunks WHERE session_id=? AND sequence_no=? LIMIT 1'
        );
        $q->execute([$sessionId, $sequence]);
        $row = $q->fetch();
        if ($row && (int)$row['byte_length'] === $size && hash_equals((string)$row['sha256'], $sha)) {
            return $this->receipt($uuid, $patientId, $sequence, $row);
        }
        throw new RuntimeException('CHUNK_CONFLICT');
    }

    private function isDuplicate(\Throwable $e): bool
    {
        return str_contains($e->getMessage(), 'Duplicate entry') || (string)$e->getCode() === '23000';
    }

    /** @return array<string,mixed>|null */
    private function latestVersion(int $sessionId, bool $forUpdate): ?array
    {
        $sql = 'SELECT * FROM recording_transcript_versions WHERE session_id=? ORDER BY version_no DESC LIMIT 1';
        if ($forUpdate && $this->db->inTransaction()) $sql .= ' FOR UPDATE';
        $q = $this->db->prepare($sql);
        $q->execute([$sessionId]);
        $row = $q->fetch();
        return $row ?: null;
    }

    private function insertTranscriptVersion(
        int $sessionId,
        string $kind,
        string $provider,
        string $model,
        array $document,
        ?int $createdBy,
        ?int $sourceVersion
    ): int {
        $json = json_encode($document, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare(
                'SELECT COALESCE(MAX(version_no),0) FROM recording_transcript_versions WHERE session_id=? FOR UPDATE'
            );
            $q->execute([$sessionId]);
            $version = (int)$q->fetchColumn() + 1;
            $stmt = $this->db->prepare(
                'INSERT INTO recording_transcript_versions
                 (session_id,version_no,kind,provider,model,transcript_json,transcript_sha256,created_by,source_version_no)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            );
            $stmt->execute([
                $sessionId, $version, $kind, substr($provider,0,64), substr($model,0,128),
                $json, hash('sha256', $json), $createdBy, $sourceVersion,
            ]);
            $this->db->commit();
            return $version;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function iso(string $value): string
    {
        $ts = strtotime($value . ' UTC');
        return $ts ? gmdate('c', $ts) : $value;
    }
}
