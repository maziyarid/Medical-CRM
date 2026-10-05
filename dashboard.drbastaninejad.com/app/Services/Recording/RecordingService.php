<?php
declare(strict_types=1);
namespace App\Services\Recording;

/** Source-only service boundary. NO routes, binary handler, real repository,
 * provisioning, microphone use, provider invocation, or EMR publication.
 * Every injected adapter is trusted server code; arrays from HTTP are NOT adapters.
 */
final class RecordingService
{
    public const MAX_SESSION_MS = 10_800_000;
    public const MAX_CHUNK_BYTES = 2_097_152;
    public const MAX_SESSION_BYTES = 134_217_728;
    public const MAX_CHUNKS = 720;
    public const LEASE_MS = 60_000;
    public const MAX_ATTEMPTS = 3;
    private const MAX_TRANSCRIPT_BYTES = 1_048_576;
    private const CHUNK_FIELDS = ['patientId', 'sessionId', 'sequence', 'startMs', 'endMs', 'mimeType', 'streamId',
        'initializationSequence', 'byteLength', 'sha256', 'idempotencyKey'];
    private const MIMES = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4'];

    public function __construct(
        private ?RecordingRepository $repository = null,
        private ?RecordingAuthority $authority = null,
        private ?RecordingStorage $storage = null,
        private ?RecordingRuntime $runtime = null
    ) {}

    public function create(array $scope, string $consentId, string $requestKey): array
    {
        $this->dependencies(); $this->scope($scope);
        if (!$this->opaque($consentId) || !$this->opaque($requestKey)) { $this->fail('INVALID_SESSION'); }
        return $this->repository->transaction(function () use ($scope, $consentId, $requestKey): array {
            $now = $this->now(); $this->grant($scope, 'recording.record', $now); $provider = $this->configuration($scope);
            $this->consent($scope, $consentId, $now);
            $existing = $this->repository->findByRequest($scope, $requestKey);
            if ($existing !== null) {
                $this->bound($scope, $existing);
                $this->storedState($existing, $existing['sessionId'] ?? '', $requestKey);
                if ($existing['consentReceiptId'] !== $consentId) { $this->fail('IDEMPOTENCY_CONFLICT'); }
                if ($existing['status'] === 'cancelled') { $this->fail('SESSION_CANCELLED'); }
                $this->recheck($scope, 'recording.record', $consentId, $now, $provider);
                return $this->sessionReceipt($existing);
            }
            $id = $this->id();
            $s = $scope + ['sessionId' => $id, 'consentReceiptId' => $consentId, 'requestKey' => $requestKey,
                'status' => 'ready', 'version' => 1, 'createdAt' => $now, 'updatedAt' => $now,
                'startedAt' => null, 'stoppedAt' => null, 'expectedChunks' => null, 'audioDurationMs' => null,
                'chunks' => [], 'manifest' => null, 'job' => null, 'drafts' => []];
            $this->recheck($scope, 'recording.record', $consentId, $now, $provider);
            $this->repository->save($scope, $s, null);
            return $this->sessionReceipt($s);
        });
    }

    public function start(array $scope, string $sessionId): array
    {
        return $this->withSession($scope, $sessionId, 'recording.record', function (array &$s, int $now): array {
            if ($s['status'] !== 'ready') { $this->fail('INVALID_STATE'); }
            $s['status'] = 'recording'; $s['startedAt'] = $now;
            return $this->sessionReceipt($s);
        });
    }

    /** A trusted storage adapter must already have verified durable bytes. */
    public function acceptChunk(array $scope, string $sessionId, array $metadata): array
    {
        return $this->withSession($scope, $sessionId, 'recording.record', function (array &$s, int $now) use ($scope, $metadata): array {
            if (!in_array($s['status'], ['recording', 'stopped'], true)) { $this->fail('INVALID_STATE'); }
            if ($s['status'] === 'recording' && $now >= $s['startedAt'] + self::MAX_SESSION_MS) { $this->fail('DURATION_LIMIT'); }
            $c = $this->chunk($s, $metadata);
            $availableDuration = $s['status'] === 'stopped' ? $s['audioDurationMs'] : $now - $s['startedAt'];
            if ($c['endMs'] > $availableDuration || ($s['status'] === 'stopped' && $c['sequence'] >= $s['expectedChunks'])) { $this->fail('INVALID_CHUNK'); }
            $old = $s['chunks'][$c['sequence']] ?? null;
            if ($old !== null) {
                if ($old['receipt'] !== $c + ['stored' => true]) { $this->fail('CHUNK_CONFLICT'); }
                return $old['receipt'];
            }
            $size = array_sum(array_map(static fn(array $row): int => $row['receipt']['byteLength'], $s['chunks']));
            if ($size + $c['byteLength'] > self::MAX_SESSION_BYTES) { $this->fail('SESSION_SIZE_LIMIT'); }
            $stored = $this->storage->verifiedChunk($scope, $s['sessionId'], $c['sequence']);
            if ($stored === null || ($stored['clinicId'] ?? null) !== $scope['clinicId'] || ($stored['durable'] ?? null) !== true ||
                ($stored['checksumVerified'] ?? null) !== true || !$this->opaque($stored['privateObjectId'] ?? null)) { $this->fail('CHUNK_NOT_VERIFIED'); }
            foreach (self::CHUNK_FIELDS as $field) { if (($stored[$field] ?? null) !== $c[$field]) { $this->fail('CHUNK_NOT_VERIFIED'); } }
            $receipt = $c + ['stored' => true];
            $s['chunks'][$c['sequence']] = ['receipt' => $receipt, 'privateObjectId' => $stored['privateObjectId']];
            return $receipt;
        });
    }

    public function stop(array $scope, string $sessionId, int $expectedChunks, int $audioDurationMs): array
    {
        return $this->withSession($scope, $sessionId, 'recording.record', function (array &$s, int $now) use ($expectedChunks, $audioDurationMs): array {
            if ($s['status'] === 'stopped') {
                if ($s['expectedChunks'] !== $expectedChunks || $s['audioDurationMs'] !== $audioDurationMs) { $this->fail('IDEMPOTENCY_CONFLICT'); }
                return $this->sessionReceipt($s);
            }
            if ($s['status'] !== 'recording') { $this->fail('INVALID_STATE'); }
            $until = min($now, $s['startedAt'] + self::MAX_SESSION_MS);
            if ($expectedChunks < 1 || $expectedChunks > self::MAX_CHUNKS || $audioDurationMs < 1 ||
                $audioDurationMs > self::MAX_SESSION_MS || $audioDurationMs > $until - $s['startedAt']) { $this->fail('INVALID_MANIFEST'); }
            $s['status'] = 'stopped'; $s['stoppedAt'] = $until;
            $s['expectedChunks'] = $expectedChunks; $s['audioDurationMs'] = $audioDurationMs;
            return $this->sessionReceipt($s);
        });
    }

    public function finalize(array $scope, string $sessionId): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider): array {
            if ($s['job'] !== null) { return $s['job']['descriptor']; }
            if ($s['status'] !== 'stopped') { $this->fail('INVALID_STATE'); }
            $manifest = $this->manifest($s);
            $hash = $this->digest($manifest);
            $descriptor = ['schemaVersion' => 1, 'kind' => 'transcribe', 'sessionId' => $s['sessionId'],
                'manifestHash' => $hash, 'provider' => $provider, 'language' => 'fa',
                'outputStatus' => 'draft', 'autoWriteEmr' => false,
                'idempotencyKey' => $s['sessionId'].':'.$hash.':'.$provider['name'].':'.$provider['version']];
            // The durable job row and immutable manifest MUST commit atomically.
            $s['manifest'] = $manifest; $s['status'] = 'finalized';
            $s['job'] = ['descriptor' => $descriptor, 'status' => 'queued', 'attempts' => 0,
                'leaseToken' => null, 'leaseExpiresAt' => null, 'retryAfter' => null, 'errorCode' => null, 'resultHash' => null];
            return $descriptor;
        });
    }

    public function claim(array $scope, string $sessionId): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider): array {
            $this->jobConfiguration($s, $provider); $j = &$s['job'];
            if ($j['status'] === 'running' && $j['leaseExpiresAt'] > $now) { $this->fail('JOB_BUSY'); }
            if ($j['status'] === 'draft' || $j['status'] === 'cancelled') { $this->fail('INVALID_STATE'); }
            if ($j['attempts'] >= self::MAX_ATTEMPTS) { $this->fail('JOB_EXHAUSTED'); }
            if ($j['retryAfter'] !== null && $j['retryAfter'] > $now) { $this->fail('RETRY_NOT_DUE'); }
            $j['status'] = 'running'; $j['attempts']++; $j['leaseToken'] = $this->id();
            $j['leaseExpiresAt'] = $now + self::LEASE_MS; $j['retryAfter'] = null;
            return $j['descriptor'] + ['leaseToken' => $j['leaseToken'], 'leaseExpiresAt' => $j['leaseExpiresAt'], 'attempt' => $j['attempts']];
        });
    }

    /** Only the validation worker may receive these private references. No HTTP route exists. */
    public function authorizeMediaValidation(array $scope, string $sessionId, string $leaseToken): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider) use ($leaseToken): array {
            $this->lease($s, $provider, $leaseToken, $now);
            return ['manifest' => $s['manifest'], 'stage' => 'media_validation', 'leaseExpiresAt' => $s['job']['leaseExpiresAt']];
        }, $leaseToken);
    }

    public function renewLease(array $scope, string $sessionId, string $leaseToken): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider) use ($leaseToken): array {
            $this->lease($s, $provider, $leaseToken, $now);
            $s['job']['leaseExpiresAt'] = $now + self::LEASE_MS;
            return ['leaseToken' => $leaseToken, 'leaseExpiresAt' => $s['job']['leaseExpiresAt'], 'attempt' => $s['job']['attempts']];
        }, $leaseToken);
    }

    /** Call immediately before EACH audio/ASR stage. A previous grant is not reusable. */
    public function authorizeProcessing(array $scope, string $sessionId, string $leaseToken): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider) use ($scope, $leaseToken): array {
            $this->lease($s, $provider, $leaseToken, $now);
            $this->media($scope, $s);
            return ['manifest' => $s['manifest'], 'provider' => $provider, 'leaseExpiresAt' => $s['job']['leaseExpiresAt']];
        }, $leaseToken);
    }

    public function failJob(array $scope, string $sessionId, string $leaseToken, string $code): void
    {
        if (!in_array($code, ['transient', 'invalid_media', 'provider_failed'], true)) { $this->fail('INVALID_ERROR_CODE'); }
        $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider) use ($leaseToken, $code): mixed {
            $this->lease($s, $provider, $leaseToken, $now);
            $s['job']['status'] = 'failed'; $s['job']['errorCode'] = $code;
            $s['job']['leaseToken'] = null; $s['job']['leaseExpiresAt'] = null;
            $s['job']['retryAfter'] = $now + 30_000;
            if ($code !== 'transient') { $s['job']['attempts'] = self::MAX_ATTEMPTS; }
            return null;
        }, $leaseToken);
    }

    public function withdraw(array $scope, string $sessionId): void
    {
        $this->dependencies(); $this->scope($scope);
        $this->repository->transaction(function () use ($scope, $sessionId): void {
            $now = $this->now(); $this->grant($scope, 'recording.withdraw', $now);
            $s = $this->repository->load($scope, $sessionId);
            if ($s === null) { $this->fail('ACCESS_DENIED'); }
            $this->bound($scope, $s);
            $this->storedState($s, $sessionId);
            if ($s['status'] === 'cancelled') { return; }
            $this->repository->revokeConsent($scope, $s['consentReceiptId'], $now);
            $s['status'] = 'cancelled'; $s['cancelledAt'] = $now;
            if ($s['job'] !== null) { $s['job']['status'] = 'cancelled'; $s['job']['leaseToken'] = null; $s['job']['leaseExpiresAt'] = null; }
            // A policy-controlled cleanup outbox must act on this marker; NOT proof of erasure.
            $s['retentionActionRequired'] = true;
            $this->save($scope, $s, $now);
        });
    }

    public function completeDraft(array $scope, string $sessionId, string $leaseToken, array $result): array
    {
        return $this->withSession($scope, $sessionId, 'recording.transcribe', function (array &$s, int $now, array $provider) use ($scope, $leaseToken, $result): array {
            $this->jobConfiguration($s, $provider);
            $draft = $this->draft($s, $provider, $result);
            $hash = $this->digest($draft);
            if ($s['job']['status'] === 'draft') {
                if ($s['job']['leaseToken'] !== $leaseToken || $s['job']['resultHash'] !== $hash) { $this->fail('DRAFT_CONFLICT'); }
                return $s['drafts'][0];
            }
            $this->lease($s, $provider, $leaseToken, $now); $this->media($scope, $s);
            $draft['createdAt'] = $now; $draft['createdBy'] = $scope['actorId'];
            if (strlen($this->json($draft)) > self::MAX_TRANSCRIPT_BYTES) { $this->fail('INVALID_TRANSCRIPT'); }
            $s['drafts'][] = $draft; $s['job']['status'] = 'draft'; $s['job']['resultHash'] = $hash;
            return $draft;
        }, $leaseToken);
    }

    public function editDraft(array $scope, string $sessionId, int $expectedVersion, string $segmentId, array $change): array
    {
        return $this->withSession($scope, $sessionId, 'recording.edit', function (array &$s, int $now) use ($scope, $expectedVersion, $segmentId, $change): array {
            if ($s['drafts'] === []) { $this->fail('INVALID_STATE'); }
            $draft = $s['drafts'][count($s['drafts']) - 1];
            if ($draft['version'] !== $expectedVersion) { $this->fail('VERSION_CONFLICT'); }
            if (count($change) !== 1 || (!array_key_exists('text', $change) && !array_key_exists('speaker', $change))) { $this->fail('INVALID_EDIT'); }
            $update = [];
            if (array_key_exists('text', $change)) {
                if (!$this->text($change['text'], 20_000)) { $this->fail('INVALID_EDIT'); }
                $update['text'] = $change['text']; $kind = 'text_correction';
            } else {
                $speaker = $change['speaker'];
                if (!is_array($speaker) || !in_array($speaker['role'] ?? null, ['doctor', 'patient', 'other', 'unknown'], true) ||
                    !$this->text($speaker['name'] ?? null, 200) || ($speaker['role'] !== 'unknown' && trim($speaker['name']) === '')) { $this->fail('INVALID_EDIT'); }
                $update['speaker'] = ['role' => $speaker['role'], 'name' => trim($speaker['name']), 'source' => 'manual']; $kind = 'speaker_assignment';
            }
            $found = false;
            foreach ($draft['segments'] as &$segment) { if ($segment['id'] === $segmentId) { $segment = array_replace($segment, $update); $found = true; } }
            unset($segment);
            if (!$found) { $this->fail('INVALID_EDIT'); }
            $draft['version']++; $draft['reviewStatus'] = 'unreviewed';
            $draft['history'][] = ['version' => $draft['version'], 'previousVersion' => $expectedVersion,
                'segmentId' => $segmentId, 'actorId' => $scope['actorId'], 'at' => $now, 'kind' => $kind];
            $draft['createdAt'] = $now; $draft['createdBy'] = $scope['actorId'];
            if (strlen($this->json($draft)) > self::MAX_TRANSCRIPT_BYTES) { $this->fail('INVALID_TRANSCRIPT'); }
            $s['drafts'][] = $draft;
            return $draft;
        });
    }

    private function withSession(array $scope, string $sessionId, string $permission, callable $operation, ?string $leaseToken = null): mixed
    {
        $this->dependencies(); $this->scope($scope);
        if (!$this->opaque($sessionId)) { $this->fail('ACCESS_DENIED'); }
        return $this->repository->transaction(function () use ($scope, $sessionId, $permission, $operation, $leaseToken): mixed {
            $now = $this->now(); $this->grant($scope, $permission, $now);
            $s = $this->repository->load($scope, $sessionId);
            if ($s === null) { $this->fail('ACCESS_DENIED'); }
            $this->bound($scope, $s);
            $this->storedState($s, $sessionId);
            if ($now < $s['updatedAt']) { $this->fail('INVALID_TIME'); }
            if ($s['status'] === 'cancelled') { $this->fail('SESSION_CANCELLED'); }
            $provider = $this->configuration($scope); $this->consent($scope, $s['consentReceiptId'], $now);
            $before = $s; $result = $operation($s, $now, $provider);
            // Recheck after trusted storage/worker callbacks and before commit.
            $at = $this->recheck($scope, $permission, $s['consentReceiptId'], $now, $provider);
            if ($leaseToken !== null && ($before['job']['status'] ?? null) !== 'draft') { $this->lease($before, $provider, $leaseToken, $at); }
            if ($s !== $before) { $this->save($scope, $s, $at); }
            return $result;
        });
    }

    private function save(array $scope, array $s, int $now): void
    {
        $version = $s['version']; $s['version']++; $s['updatedAt'] = $now;
        $this->repository->save($scope, $s, $version);
    }
    private function dependencies(): void
    {
        if ($this->repository === null || $this->authority === null || $this->runtime === null) { $this->fail('RECORDING_UNCONFIGURED'); }
    }
    private function recheck(array $scope, string $permission, string $consentId, int $before, array $provider): int
    {
        $at = $this->now();
        if ($at < $before) { $this->fail('INVALID_TIME'); }
        $this->grant($scope, $permission, $at); $this->consent($scope, $consentId, $at);
        if ($this->configuration($scope) !== $provider) { $this->fail('TRANSCRIPTION_UNCONFIGURED'); }
        return $at;
    }
    private function keys(array $value, array $expected): bool
    {
        $actual = array_keys($value); sort($actual); sort($expected); return $actual === $expected;
    }
    private function scope(array $scope): void
    {
        foreach (['clinicId', 'patientId', 'actorId'] as $key) { if (!is_string($scope[$key] ?? null) || !preg_match('/^[1-9][0-9]{0,15}$/D', $scope[$key])) { $this->fail('ACCESS_DENIED'); } }
        if (count($scope) !== 3) { $this->fail('ACCESS_DENIED'); }
    }
    private function bound(array $scope, array $s): void
    {
        // The pilot is creator-only. Cross-staff sharing requires a separately reviewed contract.
        foreach (['clinicId', 'patientId', 'actorId'] as $key) { if (($s[$key] ?? null) !== $scope[$key]) { $this->fail('ACCESS_DENIED'); } }
    }
    private function grant(array $scope, string $permission, int $now): void
    {
        $grant = $this->authority->grant($scope, $permission, $now);
        if ($grant === null) { $this->fail('ACCESS_DENIED'); }
        $this->bound($scope, $grant);
        if (!$this->integer($grant['expiresAt'] ?? null) || $grant['expiresAt'] <= $now) { $this->fail('ACCESS_DENIED'); }
    }
    private function configuration(array $scope): array
    {
        if ($this->storage === null || !$this->storage->ready($scope)) { $this->fail('STORAGE_UNCONFIGURED'); }
        $p = $this->runtime->provider();
        if ($p === null || !$this->providerId($p['name'] ?? null) || !$this->providerId($p['version'] ?? null) ||
            !$this->providerId($p['model'] ?? null) || !$this->providerId($p['revision'] ?? null)) { $this->fail('TRANSCRIPTION_UNCONFIGURED'); }
        return ['name' => $p['name'], 'version' => $p['version'], 'model' => $p['model'], 'revision' => $p['revision']];
    }
    private function consent(array $scope, string $id, int $now): void
    {
        $c = $this->repository->consent($scope, $id); $policy = $this->runtime->consentPolicyVersion();
        if ($c === null || ($c['id'] ?? null) !== $id || !$this->opaque($policy) || ($c['policyVersion'] ?? null) !== $policy ||
            ($c['status'] ?? null) !== 'active' || ($c['scope'] ?? null) !== 'recording_transcription' ||
            !array_key_exists('revokedAt', $c) || $c['revokedAt'] !== null ||
            !$this->integer($c['recordedAt'] ?? null) || $c['recordedAt'] > $now || !$this->integer($c['expiresAt'] ?? null) || $c['expiresAt'] <= $now ||
            !is_array($c['participants'] ?? null) || count($c['participants']) < 1 || count($c['participants']) > 20) { $this->fail('CONSENT_REQUIRED'); }
        foreach (['clinicId', 'patientId', 'actorId'] as $key) { if (($c[$key] ?? null) !== $scope[$key]) { $this->fail('CONSENT_REQUIRED'); } }
        foreach ($c['participants'] as $p) { if (!$this->opaque($p)) { $this->fail('CONSENT_REQUIRED'); } }
    }
    private function chunk(array $s, array $metadata): array
    {
        $c = [];
        foreach (self::CHUNK_FIELDS as $field) { $c[$field] = $metadata[$field] ?? null; }
        if ($c['patientId'] !== $s['patientId'] || $c['sessionId'] !== $s['sessionId'] ||
            !$this->integer($c['sequence']) || $c['sequence'] >= self::MAX_CHUNKS || !$this->integer($c['initializationSequence']) || $c['initializationSequence'] > $c['sequence'] ||
            !$this->integer($c['startMs']) || !$this->integer($c['endMs']) || $c['endMs'] <= $c['startMs'] || $c['endMs'] > self::MAX_SESSION_MS || $c['endMs'] - $c['startMs'] > 60_000 ||
            !$this->integer($c['byteLength']) || $c['byteLength'] < 1 || $c['byteLength'] > self::MAX_CHUNK_BYTES ||
            !in_array($c['mimeType'], self::MIMES, true) || !$this->opaque($c['streamId']) ||
            !is_string($c['sha256']) || !preg_match('/^[a-f0-9]{64}$/D', $c['sha256']) ||
            $c['idempotencyKey'] !== $s['sessionId'].':'.$c['sequence'].':'.$c['sha256']) { $this->fail('INVALID_CHUNK'); }
        return $c;
    }
    private function manifest(array $s): array
    {
        if (count($s['chunks']) !== $s['expectedChunks']) { $this->fail('INVALID_MANIFEST'); }
        $rows = $s['chunks']; ksort($rows, SORT_NUMERIC); $ordered = []; $streams = []; $current = null; $end = 0; $total = 0; $sequence = 0;
        foreach ($rows as $row) {
            $c = $this->chunk($s, $row['receipt']);
            if ($c['sequence'] !== $sequence || $c['startMs'] !== $end || ($row['receipt']['stored'] ?? null) !== true) { $this->fail('INVALID_MANIFEST'); }
            if ($c['streamId'] !== $current) {
                if (isset($streams[$c['streamId']]) || $c['initializationSequence'] !== $sequence) { $this->fail('INVALID_MANIFEST'); }
                $current = $c['streamId']; $streams[$current] = ['init' => $sequence, 'mime' => $c['mimeType']];
            }
            if ($streams[$current]['init'] !== $c['initializationSequence'] || $streams[$current]['mime'] !== $c['mimeType']) { $this->fail('INVALID_MANIFEST'); }
            $ordered[] = $c + ['stored' => true, 'privateObjectId' => $row['privateObjectId']];
            $end = $c['endMs']; $total += $c['byteLength']; $sequence++;
        }
        if ($end !== $s['audioDurationMs'] || $total > self::MAX_SESSION_BYTES) { $this->fail('INVALID_MANIFEST'); }
        return ['schemaVersion' => 1, 'patientId' => $s['patientId'], 'sessionId' => $s['sessionId'],
            'chunks' => $ordered, 'totalBytes' => $total, 'audioDurationMs' => $end, 'containerHandling' => 'ordered-remux-required'];
    }
    /** Reject corrupt/mismatched persisted envelopes before returning or changing them. */
    private function storedState(array $s, string $sessionId, ?string $requestKey = null): void
    {
        try {
            if (($s['sessionId'] ?? null) !== $sessionId || !$this->opaque($sessionId) ||
                !$this->opaque($s['requestKey'] ?? null) || ($requestKey !== null && $s['requestKey'] !== $requestKey) ||
                !$this->opaque($s['consentReceiptId'] ?? null) || !$this->integer($s['version'] ?? null) || $s['version'] < 1 ||
                !in_array($s['status'] ?? null, ['ready', 'recording', 'stopped', 'finalized', 'cancelled'], true) ||
                !$this->integer($s['createdAt'] ?? null) || !$this->integer($s['updatedAt'] ?? null) || $s['updatedAt'] < $s['createdAt'] ||
                !array_key_exists('startedAt', $s) || !array_key_exists('stoppedAt', $s) || !array_key_exists('manifest', $s) ||
                !array_key_exists('job', $s) || !is_array($s['chunks'] ?? null) || count($s['chunks']) > self::MAX_CHUNKS || !is_array($s['drafts'] ?? null)) { $this->fail('INVALID_STORED_STATE'); }
            if ($s['status'] === 'ready' && ($s['startedAt'] !== null || $s['chunks'] !== [])) { $this->fail('INVALID_STORED_STATE'); }
            if ($s['startedAt'] !== null && (!$this->integer($s['startedAt']) || $s['startedAt'] < $s['createdAt'])) { $this->fail('INVALID_STORED_STATE'); }
            if (in_array($s['status'], ['recording', 'stopped', 'finalized'], true) && $s['startedAt'] === null) { $this->fail('INVALID_STORED_STATE'); }
            if ($s['stoppedAt'] !== null && (!$this->integer($s['stoppedAt']) || $s['startedAt'] === null || $s['stoppedAt'] < $s['startedAt'] ||
                $s['stoppedAt'] > $s['startedAt'] + self::MAX_SESSION_MS || !$this->integer($s['expectedChunks'] ?? null) || $s['expectedChunks'] < 1 || $s['expectedChunks'] > self::MAX_CHUNKS ||
                !$this->integer($s['audioDurationMs'] ?? null) || $s['audioDurationMs'] < 1 || $s['audioDurationMs'] > $s['stoppedAt'] - $s['startedAt'])) { $this->fail('INVALID_STORED_STATE'); }
            if (in_array($s['status'], ['stopped', 'finalized'], true) && $s['stoppedAt'] === null) { $this->fail('INVALID_STORED_STATE'); }
            $total = 0;
            foreach ($s['chunks'] as $sequence => $row) {
                if (!is_array($row) || !is_array($row['receipt'] ?? null) || !$this->opaque($row['privateObjectId'] ?? null)) { $this->fail('INVALID_STORED_STATE'); }
                $c = $this->chunk($s, $row['receipt']);
                if ($sequence !== $c['sequence'] || ($row['receipt']['stored'] ?? null) !== true) { $this->fail('INVALID_STORED_STATE'); }
                $total += $c['byteLength'];
            }
            if ($total > self::MAX_SESSION_BYTES) { $this->fail('INVALID_STORED_STATE'); }
            if ($s['job'] === null) {
                if ($s['manifest'] !== null || $s['drafts'] !== [] || $s['status'] === 'finalized') { $this->fail('INVALID_STORED_STATE'); }
                return;
            }
            if (!in_array($s['status'], ['finalized', 'cancelled'], true) || !is_array($s['job']) || !is_array($s['manifest']) || $s['stoppedAt'] === null) { $this->fail('INVALID_STORED_STATE'); }
            $manifest = $this->manifest($s); $hash = $this->digest($manifest); $j = $s['job']; $d = $j['descriptor'] ?? null;
            if ($this->digest($s['manifest']) !== $hash || !is_array($d) || !is_array($d['provider'] ?? null) ||
                !$this->providerId($d['provider']['name'] ?? null) || !$this->providerId($d['provider']['version'] ?? null) ||
                !$this->providerId($d['provider']['model'] ?? null) || !$this->providerId($d['provider']['revision'] ?? null)) { $this->fail('INVALID_STORED_STATE'); }
            $provider = ['name' => $d['provider']['name'], 'version' => $d['provider']['version'], 'model' => $d['provider']['model'], 'revision' => $d['provider']['revision']];
            $expected = ['schemaVersion' => 1, 'kind' => 'transcribe', 'sessionId' => $sessionId, 'manifestHash' => $hash,
                'provider' => $provider, 'language' => 'fa', 'outputStatus' => 'draft', 'autoWriteEmr' => false,
                'idempotencyKey' => $sessionId.':'.$hash.':'.$provider['name'].':'.$provider['version']];
            if ($d !== $expected || !in_array($j['status'] ?? null, ['queued', 'running', 'failed', 'cancelled', 'draft'], true) ||
                !$this->integer($j['attempts'] ?? null) || $j['attempts'] > self::MAX_ATTEMPTS ||
                !array_key_exists('leaseToken', $j) || !array_key_exists('leaseExpiresAt', $j) || !array_key_exists('retryAfter', $j) ||
                !array_key_exists('resultHash', $j) || !array_key_exists('errorCode', $j) ||
                !in_array($j['errorCode'], [null, 'transient', 'invalid_media', 'provider_failed'], true) ||
                !($j['leaseToken'] === null || $this->opaque($j['leaseToken'])) || !($j['leaseExpiresAt'] === null || $this->integer($j['leaseExpiresAt'])) ||
                !($j['retryAfter'] === null || $this->integer($j['retryAfter']))) { $this->fail('INVALID_STORED_STATE'); }
            if (($j['status'] === 'running' && ($j['leaseToken'] === null || $j['leaseExpiresAt'] === null || $j['attempts'] < 1)) ||
                ($j['status'] === 'queued' && ($j['attempts'] !== 0 || $j['leaseToken'] !== null)) ||
                ($s['status'] === 'cancelled' && $j['status'] !== 'cancelled') ||
                ($j['status'] === 'draft' && ($s['drafts'] === [] || !is_string($j['resultHash']) || !preg_match('/^[a-f0-9]{64}$/D', $j['resultHash'])))) { $this->fail('INVALID_STORED_STATE'); }
            foreach ($s['drafts'] as $index => $draft) { $this->storedDraft($s, $draft, $index + 1, $provider); }
        } catch (RecordingError $e) { $this->fail('INVALID_STORED_STATE'); }
    }

    private function storedDraft(array $s, mixed $draft, int $version, array $provider): void
    {
        if (!is_array($draft) || !$this->keys($draft, ['sessionId','audioDurationMs','provider','language','version','status','reviewStatus','classification','segments','history','createdAt','createdBy']) || ($draft['sessionId'] ?? null) !== $s['sessionId'] || ($draft['audioDurationMs'] ?? null) !== $s['audioDurationMs'] ||
            ($draft['provider'] ?? null) !== $provider || ($draft['language'] ?? null) !== 'fa' || ($draft['version'] ?? null) !== $version ||
            ($draft['status'] ?? null) !== 'draft' || ($draft['reviewStatus'] ?? null) !== 'unreviewed' ||
            !array_key_exists('classification', $draft) || $draft['classification'] !== null || !is_array($draft['segments'] ?? null) ||
            !is_array($draft['history'] ?? null) || count($draft['history']) !== $version - 1 || !$this->integer($draft['createdAt'] ?? null) ||
            ($draft['createdBy'] ?? null) !== $s['actorId'] || strlen($this->json($draft)) > self::MAX_TRANSCRIPT_BYTES) { $this->fail('INVALID_STORED_STATE'); }
        $raw = [];
        foreach ($draft['segments'] as $r) {
            if (!is_array($r) || !$this->keys($r, ['id','startMs','endMs','speakerId','rawText','text','uncertainty','sourceIntervals','speaker']) || !$this->text($r['text'] ?? null, 20_000) || !$this->text($r['rawText'] ?? null, 20_000) || !is_array($r['speaker'] ?? null)) { $this->fail('INVALID_STORED_STATE'); }
            $speaker = $r['speaker'];
            if (!$this->keys($speaker, ['role','name','source']) || !in_array($speaker['role'] ?? null, ['unknown','doctor','patient','other'], true) || !$this->text($speaker['name'] ?? null, 200) ||
                !in_array($speaker['source'] ?? null, ['unassigned','manual'], true) ||
                ($speaker['source'] === 'unassigned' && ($speaker['role'] !== 'unknown' || $speaker['name'] !== '')) ||
                ($speaker['role'] !== 'unknown' && trim($speaker['name']) === '')) { $this->fail('INVALID_STORED_STATE'); }
            $raw[] = array_replace($r, ['text' => $r['rawText']]);
        }
        $canonical = $this->draft($s, $provider, ['language' => 'fa', 'segments' => $raw]);
        if ($version === 1) {
            $initial = $draft; unset($initial['createdAt'], $initial['createdBy']);
            if ($initial !== $canonical || $this->digest($initial) !== $s['job']['resultHash']) { $this->fail('INVALID_STORED_STATE'); }
        } else {
            $original = $s['drafts'][0]['segments']; $prior = $s['drafts'][$version - 2];
            if (count($original) !== count($draft['segments']) || array_slice($draft['history'], 0, -1) !== $prior['history']) { $this->fail('INVALID_STORED_STATE'); }
            foreach ($draft['segments'] as $i => $r) {
                foreach (['id','startMs','endMs','speakerId','rawText','uncertainty','sourceIntervals'] as $key) {
                    if (!isset($original[$i]) || $original[$i][$key] !== $r[$key]) { $this->fail('INVALID_STORED_STATE'); }
                }
            }
        }
        foreach ($draft['history'] as $i => $h) {
            if (!is_array($h) || !$this->keys($h, ['version','previousVersion','segmentId','actorId','at','kind']) || ($h['version'] ?? null) !== $i + 2 || ($h['previousVersion'] ?? null) !== $i + 1 ||
                ($h['actorId'] ?? null) !== $s['actorId'] || !$this->integer($h['at'] ?? null) || !$this->opaque($h['segmentId'] ?? null) ||
                !in_array($h['kind'] ?? null, ['text_correction','speaker_assignment'], true)) { $this->fail('INVALID_STORED_STATE'); }
        }
    }

    private function jobConfiguration(array $s, array $provider): void
    {
        if ($s['job'] === null) { $this->fail('INVALID_STATE'); }
        if ($s['job']['descriptor']['provider'] !== $provider) { $this->fail('TRANSCRIPTION_UNCONFIGURED'); }
    }
    private function lease(array $s, array $provider, string $token, int $now): void
    {
        $this->jobConfiguration($s, $provider);
        if ($s['job']['status'] !== 'running' || $s['job']['leaseToken'] !== $token || $s['job']['leaseExpiresAt'] <= $now) { $this->fail('JOB_LEASE_INVALID'); }
    }
    private function media(array $scope, array $s): void
    {
        $hash = $s['job']['descriptor']['manifestHash']; $m = $this->storage->verifiedMedia($scope, $s['sessionId'], $hash);
        if ($m === null || ($m['manifestHash'] ?? null) !== $hash || ($m['audioDurationMs'] ?? null) !== $s['audioDurationMs'] ||
            ($m['codecValidated'] ?? null) !== true || ($m['containerValidated'] ?? null) !== true) { $this->fail('MEDIA_NOT_VERIFIED'); }
    }
    private function draft(array $s, array $provider, array $result): array
    {
        if (($result['language'] ?? null) !== 'fa' || !is_array($result['segments'] ?? null) || count($result['segments']) < 1 || count($result['segments']) > 20_000 ||
            strlen($this->json($result)) > self::MAX_TRANSCRIPT_BYTES) { $this->fail('INVALID_TRANSCRIPT'); }
        $segments = []; $ids = []; $previous = 0;
        foreach ($result['segments'] as $r) {
            if (!is_array($r) || !$this->opaque($r['id'] ?? null) || isset($ids[$r['id']]) || !$this->interval($r, $s['audioDurationMs']) || $r['startMs'] < $previous ||
                !$this->text($r['text'] ?? null, 20_000) || !array_key_exists('speakerId', $r) || !($r['speakerId'] === null || $this->opaque($r['speakerId'])) ||
                !array_key_exists('uncertainty', $r) || !($r['uncertainty'] === null || (is_int($r['uncertainty']) || is_float($r['uncertainty'])) && is_finite((float)$r['uncertainty']) && $r['uncertainty'] >= 0 && $r['uncertainty'] <= 1) ||
                !is_array($r['sourceIntervals'] ?? null) || count($r['sourceIntervals']) < 1 || count($r['sourceIntervals']) > 720) { $this->fail('INVALID_TRANSCRIPT'); }
            $sources = [];
            foreach ($r['sourceIntervals'] as $interval) { if (!is_array($interval) || !$this->interval($interval, $s['audioDurationMs'])) { $this->fail('INVALID_TRANSCRIPT'); } $sources[] = ['startMs' => $interval['startMs'], 'endMs' => $interval['endMs']]; }
            $ids[$r['id']] = true; $previous = $r['startMs'];
            $segments[] = ['id' => $r['id'], 'startMs' => $r['startMs'], 'endMs' => $r['endMs'], 'speakerId' => $r['speakerId'],
                'rawText' => $r['text'], 'text' => $r['text'], 'uncertainty' => $r['uncertainty'], 'sourceIntervals' => $sources,
                'speaker' => ['role' => 'unknown', 'name' => '', 'source' => 'unassigned']];
        }
        return ['sessionId' => $s['sessionId'], 'audioDurationMs' => $s['audioDurationMs'], 'provider' => $provider,
            'language' => 'fa', 'version' => 1, 'status' => 'draft', 'reviewStatus' => 'unreviewed', 'classification' => null,
            'segments' => $segments, 'history' => []];
    }
    private function interval(array $value, int $duration): bool { return $this->integer($value['startMs'] ?? null) && $this->integer($value['endMs'] ?? null) && $value['endMs'] > $value['startMs'] && $value['endMs'] <= $duration; }
    private function sessionReceipt(array $s): array { return array_intersect_key($s, array_flip(['sessionId','clinicId','patientId','actorId','consentReceiptId','status','startedAt','stoppedAt','expectedChunks','audioDurationMs'])); }
    private function now(): int { $now = $this->runtime->nowMs(); if (!$this->integer($now)) { $this->fail('INVALID_TIME'); } return $now; }
    private function id(): string { $id = $this->runtime->newId(); if (!$this->opaque($id)) { $this->fail('RECORDING_UNCONFIGURED'); } return $id; }
    private function integer(mixed $value): bool { return is_int($value) && $value >= 0 && $value <= 9_007_199_254_740_991; }
    private function opaque(mixed $value): bool { return is_string($value) && (bool)preg_match('/^[A-Za-z0-9_-]{1,128}$/D', $value); }
    private function providerId(mixed $value): bool { return is_string($value) && (bool)preg_match('/^[A-Za-z0-9_.-]{1,128}$/D', $value); }
    private function text(mixed $value, int $maxBytes): bool { return is_string($value) && strlen($value) <= $maxBytes && preg_match('//u', $value) === 1; }
    private function json(array $value): string { try { return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); } catch (\JsonException) { $this->fail('INVALID_TRANSCRIPT'); } }
    private function digest(array $value): string { return hash('sha256', $this->json($value)); }
    private function fail(string $code): never { throw new RecordingError($code); }
}
