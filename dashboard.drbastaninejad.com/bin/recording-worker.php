<?php
declare(strict_types=1);

// CLI-only worker for RPH-137. Intended for cron every minute.
// Processes at most one durable transcription job per invocation.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

define('BASE_PATH', dirname(__DIR__));

$envFile = is_file('/home/drbastaninejad/.dashboard.env')
    ? '/home/drbastaninejad/.dashboard.env'
    : BASE_PATH . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        if (strlen($value) >= 2 && (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === "'" && str_ends_with($value, "'")))) {
            $value = substr($value, 1, -1);
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}

spl_autoload_register(static function (string $class): void {
    if (!str_starts_with($class, 'App\')) return;
    $relative = str_replace('\', DIRECTORY_SEPARATOR, substr($class, 4));
    $file = BASE_PATH . '/app/' . $relative . '.php';
    if (is_file($file)) require_once $file;
});

date_default_timezone_set('UTC');

function claimJob(PDO $db): ?array
{
    $db->beginTransaction();
    try {
        $q = $db->query(
            "SELECT * FROM recording_jobs
             WHERE kind='transcribe'
               AND status IN ('queued','retry')
               AND available_at <= UTC_TIMESTAMP()
               AND (lease_expires_at IS NULL OR lease_expires_at < UTC_TIMESTAMP())
             ORDER BY id ASC
             LIMIT 1
             FOR UPDATE"
        );
        $job = $q->fetch();
        if (!$job) {
            $db->commit();
            return null;
        }
        $lease = bin2hex(random_bytes(32));
        $db->prepare(
            "UPDATE recording_jobs
             SET status='running',attempts=attempts+1,lease_token=?,
                 lease_expires_at=DATE_ADD(UTC_TIMESTAMP(), INTERVAL 20 MINUTE),error_code=NULL
             WHERE id=?"
        )->execute([$lease, (int)$job['id']]);
        $db->commit();
        $job['lease_token'] = $lease;
        $job['attempts'] = (int)$job['attempts'] + 1;
        return $job;
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}

function finishJob(PDO $db, int $jobId, string $lease): void
{
    $db->prepare(
        "UPDATE recording_jobs SET status='done',lease_token=NULL,lease_expires_at=NULL,error_code=NULL
         WHERE id=? AND lease_token=?"
    )->execute([$jobId, $lease]);
}

function failOrRetry(PDO $db, array $job, Throwable $e): bool
{
    $attempts = (int)$job['attempts'];
    $code = strtoupper((string)$e->getMessage());
    $code = preg_replace('/[^A-Z0-9_.:-]/', '_', $code) ?: 'TRANSCRIPTION_FAILED';
    $code = substr($code, 0, 96);
    $retryable = str_contains($code, 'RATE_LIMIT') || str_contains($code, 'NETWORK') || str_contains($code, 'UPSTREAM_5');
    $retry = $retryable && $attempts < 3;
    $minutes = min(60, 5 * max(1, $attempts));
    $status = $retry ? 'retry' : 'failed';
    $available = $retry ? "DATE_ADD(UTC_TIMESTAMP(), INTERVAL {$minutes} MINUTE)" : 'UTC_TIMESTAMP()';
    $sql = "UPDATE recording_jobs SET status=?,available_at={$available},lease_token=NULL,lease_expires_at=NULL,error_code=?
            WHERE id=? AND lease_token=?";
    $db->prepare($sql)->execute([$status, $code, (int)$job['id'], (string)$job['lease_token']]);
    return $retry;
}

try {
    $db = AppCoreDatabase::conn();
    $job = claimJob($db);
    if ($job === null) {
        fwrite(STDOUT, "recording_jobs=0
");
        exit(0);
    }

    $sessions = new AppServicesRecordingSessionService();
    $session = $sessions->sessionByIdForWorker((int)$job['session_id']);

    if ($session['consent_withdrawn_at'] !== null || in_array($session['status'], ['withdrawn','cancelled'], true)) {
        $db->prepare(
            "UPDATE recording_jobs SET status='cancelled',lease_token=NULL,lease_expires_at=NULL,error_code='CONSENT_WITHDRAWN'
             WHERE id=? AND lease_token=?"
        )->execute([(int)$job['id'], (string)$job['lease_token']]);
        fwrite(STDOUT, "recording_job=cancelled
");
        exit(0);
    }

    if (!in_array($session['status'], ['queued','processing'], true)) {
        throw new RuntimeException('SESSION_NOT_QUEUED');
    }
    if (!empty($session['retention_until']) && strtotime((string)$session['retention_until'] . ' UTC') < time()) {
        throw new RuntimeException('RETENTION_EXPIRED');
    }

    $db->prepare(
        "UPDATE recording_sessions SET status='processing',processing_error_code=NULL WHERE id=? AND consent_withdrawn_at IS NULL"
    )->execute([(int)$session['id']]);

    $chunks = $sessions->chunksForWorker((int)$session['id']);
    if (count($chunks) !== (int)$session['expected_chunks'] || $chunks === []) {
        throw new RuntimeException('MISSING_CHUNKS');
    }

    $provider = (string)$session['provider'];
    $model = (string)$session['provider_model'];
    if ($provider !== 'groq') {
        throw new RuntimeException('LOCAL_WORKER_HANDOFF_REQUIRED');
    }

    $asr = new AppServicesGroqTranscriptionService();
    $segments = [];
    $rawParts = [];

    foreach ($chunks as $chunk) {
        if (!is_file((string)$chunk['storage_path'])) throw new RuntimeException('AUDIO_NOT_READABLE');
        $result = $asr->transcribeFile((string)$chunk['storage_path'], (string)$chunk['mime_type']);
        $chunkStart = (int)$chunk['start_ms'];
        $chunkEnd = (int)$chunk['end_ms'];
        $chunkSegments = $result['segments'];

        if ($chunkSegments === [] && trim((string)$result['text']) !== '') {
            $chunkSegments = [[
                'index' => 0,
                'start_seconds' => 0.0,
                'end_seconds' => max(0.001, ($chunkEnd - $chunkStart) / 1000),
                'text' => trim((string)$result['text']),
                'avg_logprob' => null,
                'no_speech_prob' => null,
            ]];
        }

        foreach ($chunkSegments as $local) {
            $localIndex = (int)($local['index'] ?? 0);
            $startMs = $chunkStart + (int)round(((float)$local['start_seconds']) * 1000);
            $endMs = $chunkStart + (int)round(((float)$local['end_seconds']) * 1000);
            $startMs = max($chunkStart, min($chunkEnd - 1, $startMs));
            $endMs = max($startMs + 1, min($chunkEnd, $endMs));
            $text = trim((string)($local['text'] ?? ''));
            if ($text === '') continue;

            $flags = [];
            $noSpeech = $local['no_speech_prob'] ?? null;
            $avgLogprob = $local['avg_logprob'] ?? null;
            if (is_float($noSpeech) && $noSpeech >= 0.55) $flags[] = 'uncertain_audio';
            if (is_float($avgLogprob) && $avgLogprob <= -1.0) $flags[] = 'low_asr_signal';

            $segments[] = [
                'id' => 'c' . (int)$chunk['sequence_no'] . '-s' . $localIndex,
                'start_ms' => $startMs,
                'end_ms' => $endMs,
                'raw_text' => $text,
                'text' => $text,
                'speaker' => ['role' => 'unknown', 'name' => '', 'source' => 'unassigned'],
                'review_flags' => $flags,
                'source' => [
                    'chunk_sequence' => (int)$chunk['sequence_no'],
                    'sha256' => (string)$chunk['sha256'],
                ],
            ];
            $rawParts[] = $text;
        }
    }

    if ($segments === []) throw new RuntimeException('EMPTY_TRANSCRIPT');

    $machine = [
        'schema_version' => 1,
        'session_id' => (string)$session['uuid'],
        'language' => 'fa',
        'audio_duration_ms' => (int)$session['audio_duration_ms'],
        'provider' => ['name' => $provider, 'model' => $model],
        'raw_text' => implode("
", $rawParts),
        'segments' => $segments,
        'speaker_assignment' => 'manual_required',
        'review_status' => 'unreviewed',
        'auto_write_emr' => false,
    ];

    $machineVersion = $sessions->appendMachineTranscript((int)$session['id'], $provider, $model, $machine);

    $polisher = new AppServicesTranscriptPolisherService();
    $normalised = $polisher->normalise($machine);
    $normalisedVersion = $sessions->appendDerivedTranscript(
        (int)$session['id'],
        'normalised',
        'local',
        'persian-normaliser-v1',
        $normalised,
        $machineVersion
    );

    $finalVersion = $normalisedVersion;
    try {
        $review = $polisher->reviewEnsemble($normalised);
        if ($review['status'] === 'reviewed') {
            $reviewed = $review['document'];
            $reviewed['ensemble']['reviewers'] = $review['reviewers'];
            $reviewed['review_status'] = 'unreviewed';
            $finalVersion = $sessions->appendDerivedTranscript(
                (int)$session['id'],
                'consensus',
                'openrouter',
                'free-ensemble',
                $reviewed,
                $normalisedVersion
            );
        }
    } catch (Throwable $reviewError) {
        // A reviewer outage must never erase or fail the machine transcript.
        error_log('[recording-worker] reviewer unavailable: ' . preg_replace('/[^A-Za-z0-9_.:-]/', '_', $reviewError->getMessage()));
    }

    $sessions->markReviewRequired((int)$session['id']);
    finishJob($db, (int)$job['id'], (string)$job['lease_token']);

    fwrite(STDOUT, json_encode([
        'recording_job' => 'done',
        'session_id' => (string)$session['uuid'],
        'machine_version' => $machineVersion,
        'final_version' => $finalVersion,
        'segments' => count($segments),
        'clinical_review_required' => true,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit(0);
} catch (Throwable $e) {
    error_log('[recording-worker] ' . preg_replace('/[^A-Za-z0-9_.:-]/', '_', $e->getMessage()));
    if (isset($db, $job) && is_array($job)) {
        try {
            $retry = failOrRetry($db, $job, $e);
            if (!$retry && isset($sessions, $session) && is_array($session)) {
                $sessions->markProcessingFailure((int)$session['id'], $e->getMessage());
            } elseif ($retry && isset($session) && is_array($session)) {
                $db->prepare(
                    "UPDATE recording_sessions SET status='queued',processing_error_code=NULL WHERE id=? AND consent_withdrawn_at IS NULL"
                )->execute([(int)$session['id']]);
            }
        } catch (Throwable $secondary) {
            error_log('[recording-worker] failure-state update failed');
        }
    }
    fwrite(STDERR, "recording worker failed
");
    exit(1);
}
