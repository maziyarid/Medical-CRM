<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Optional external ASR adapter.
 *
 * It is unusable unless the operator explicitly enables external clinical
 * audio transmission and configures a server-side Groq key. The browser never
 * receives the key and audio is never sent from client JavaScript to Groq.
 */
final class GroqTranscriptionService
{
    /**
     * @return array{language:string,duration:float,text:string,segments:list<array<string,mixed>>}
     */
    public function transcribeFile(string $path, string $mime): array
    {
        if (($_ENV['TRANSCRIPTION_EXTERNAL_AUDIO_ALLOWED'] ?? '0') !== '1') {
            throw new RuntimeException('EXTERNAL_AUDIO_DISABLED');
        }
        $key = trim((string)($_ENV['GROQ_API_KEY'] ?? ''));
        if ($key === '' || str_starts_with($key, 'CHANGE_ME')) {
            throw new RuntimeException('GROQ_NOT_CONFIGURED');
        }
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('AUDIO_NOT_READABLE');
        }

        $model = trim((string)($_ENV['GROQ_TRANSCRIPTION_MODEL'] ?? 'whisper-large-v3'));
        if (!in_array($model, ['whisper-large-v3','whisper-large-v3-turbo'], true)) {
            throw new RuntimeException('INVALID_GROQ_MODEL');
        }

        $post = [
            'file' => new \CURLFile($path, $mime, basename($path)),
            'model' => $model,
            'language' => 'fa',
            'response_format' => 'verbose_json',
            'temperature' => '0',
            'timestamp_granularities[]' => 'segment',
        ];
        $prompt = trim((string)($_ENV['GROQ_TRANSCRIPTION_PROMPT_FA'] ?? ''));
        if ($prompt !== '') {
            $post['prompt'] = mb_substr($prompt, 0, 1200);
        }

        $ch = curl_init('https://api.groq.com/openai/v1/audio/transcriptions');
        if ($ch === false) throw new RuntimeException('GROQ_HTTP_INIT_FAILED');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $post,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $key,
                'Accept: application/json',
            ],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => max(60, min(600, (int)($_ENV['GROQ_TRANSCRIPTION_TIMEOUT_SECONDS'] ?? 180))),
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new RuntimeException('GROQ_NETWORK_ERROR:' . substr($error, 0, 120));
        }
        $json = json_decode((string)$body, true);
        if ($status === 429) throw new RuntimeException('GROQ_RATE_LIMITED');
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            throw new RuntimeException('GROQ_UPSTREAM_' . $status);
        }

        $segments = [];
        foreach (($json['segments'] ?? []) as $index => $segment) {
            if (!is_array($segment)) continue;
            $text = trim((string)($segment['text'] ?? ''));
            $start = isset($segment['start']) && is_numeric($segment['start']) ? (float)$segment['start'] : null;
            $end = isset($segment['end']) && is_numeric($segment['end']) ? (float)$segment['end'] : null;
            if ($text === '' || $start === null || $end === null || $end <= $start) continue;
            $segments[] = [
                'index' => (int)$index,
                'start_seconds' => max(0.0, $start),
                'end_seconds' => max($start, $end),
                'text' => $text,
                'avg_logprob' => isset($segment['avg_logprob']) && is_numeric($segment['avg_logprob']) ? (float)$segment['avg_logprob'] : null,
                'no_speech_prob' => isset($segment['no_speech_prob']) && is_numeric($segment['no_speech_prob']) ? (float)$segment['no_speech_prob'] : null,
            ];
        }

        $text = trim((string)($json['text'] ?? ''));
        if ($text === '' && $segments === []) {
            throw new RuntimeException('GROQ_EMPTY_TRANSCRIPT');
        }

        return [
            'language' => (string)($json['language'] ?? 'fa'),
            'duration' => isset($json['duration']) && is_numeric($json['duration']) ? (float)$json['duration'] : 0.0,
            'text' => $text,
            'segments' => $segments,
        ];
    }
}
