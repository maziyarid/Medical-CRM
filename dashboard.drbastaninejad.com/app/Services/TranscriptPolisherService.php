<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/**
 * Conservative post-ASR reviewer.
 *
 * Local normalisation is always available. External multi-agent review is
 * separately gated because transcript text is clinical data. Reviewer output
 * is advisory: raw ASR is preserved, speaker identities are never inferred,
 * and only small unanimous edits that preserve numbers/negation are accepted.
 */
final class TranscriptPolisherService
{
    /** @param array<string,mixed> $document */
    public function normalise(array $document): array
    {
        $segments = $document['segments'] ?? [];
        if (!is_array($segments)) throw new RuntimeException('INVALID_TRANSCRIPT');

        foreach ($segments as &$segment) {
            if (!is_array($segment)) continue;
            $raw = (string)($segment['raw_text'] ?? $segment['text'] ?? '');
            $text = $this->normaliseText((string)($segment['text'] ?? $raw));
            $segment['raw_text'] = $raw;
            $segment['text'] = $text;
            $segment['speaker'] = $segment['speaker'] ?? [
                'role' => 'unknown',
                'name' => '',
                'source' => 'unassigned',
            ];
            $segment['review_flags'] = array_values(array_unique(array_filter(
                is_array($segment['review_flags'] ?? null) ? $segment['review_flags'] : []
            )));
        }
        unset($segment);

        $document['segments'] = $segments;
        $document['normalisation'] = [
            'version' => 1,
            'rules' => ['arabic_yeh_kaf', 'unicode_spaces', 'persian_punctuation_spacing'],
        ];
        return $document;
    }

    /**
     * @param array<string,mixed> $document
     * @return array{document:array<string,mixed>,reviewers:list<array<string,mixed>>,status:string}
     */
    public function reviewEnsemble(array $document): array
    {
        if (!$this->externalReviewEnabled()) {
            return ['document' => $document, 'reviewers' => [], 'status' => 'disabled'];
        }

        $segments = $document['segments'] ?? [];
        if (!is_array($segments) || $segments === []) {
            return ['document' => $document, 'reviewers' => [], 'status' => 'empty'];
        }

        $payloadSegments = [];
        foreach ($segments as $segment) {
            if (!is_array($segment)) continue;
            $payloadSegments[] = [
                'id' => (string)($segment['id'] ?? ''),
                'text' => (string)($segment['text'] ?? ''),
            ];
        }

        $reviews = [];
        $seenModels = [];
        $maxCalls = max(2, min(3, (int)($_ENV['TRANSCRIPTION_REVIEW_MAX_CALLS'] ?? 3)));
        for ($i = 0; $i < $maxCalls; $i++) {
            $review = $this->callReviewer($payloadSegments, $i + 1);
            $reviews[] = $review;
            $model = (string)($review['model'] ?? '');
            if ($model !== '') $seenModels[$model] = true;
            if (count($reviews) >= 2 && count($seenModels) >= 2) break;
        }

        if (count($reviews) < 2) {
            return ['document' => $document, 'reviewers' => $this->reviewerMeta($reviews), 'status' => 'insufficient_reviewers'];
        }

        $maps = [];
        foreach ($reviews as $review) {
            $map = [];
            foreach (($review['proposals'] ?? []) as $proposal) {
                if (!is_array($proposal)) continue;
                $id = (string)($proposal['id'] ?? '');
                $text = trim((string)($proposal['proposed_text'] ?? ''));
                if ($id !== '' && $text !== '') $map[$id] = $text;
            }
            $maps[] = $map;
        }

        foreach ($segments as &$segment) {
            if (!is_array($segment)) continue;
            $id = (string)($segment['id'] ?? '');
            $current = (string)($segment['text'] ?? '');
            $candidateValues = [];
            foreach ($maps as $map) {
                if (!isset($map[$id])) {
                    $candidateValues = [];
                    break;
                }
                $candidateValues[] = $this->normaliseText((string)$map[$id]);
            }
            if (count($candidateValues) !== count($maps)) {
                $this->flag($segment, 'agent_incomplete');
                continue;
            }
            $unique = array_values(array_unique($candidateValues));
            if (count($unique) !== 1) {
                $this->flag($segment, 'agent_disagreement');
                continue;
            }

            $candidate = $unique[0];
            if ($candidate === $current) continue;
            if (!$this->safeSmallEdit($current, $candidate)) {
                $this->flag($segment, 'agent_change_requires_human');
                continue;
            }

            $segment['text'] = $candidate;
            $segment['polish'] = [
                'source' => 'unanimous_reviewers',
                'reviewer_count' => count($reviews),
                'requires_human_review' => true,
            ];
        }
        unset($segment);

        $document['segments'] = $segments;
        $document['ensemble'] = [
            'version' => 1,
            'reviewer_count' => count($reviews),
            'distinct_model_count' => count($seenModels),
            'diverse_models' => count($seenModels) >= 2,
            'auto_speaker_assignment' => false,
            'raw_asr_preserved' => true,
        ];

        return [
            'document' => $document,
            'reviewers' => $this->reviewerMeta($reviews),
            'status' => 'reviewed',
        ];
    }

    private function externalReviewEnabled(): bool
    {
        if (($_ENV['TRANSCRIPTION_REVIEW_ENABLED'] ?? '0') !== '1') return false;
        if (($_ENV['TRANSCRIPTION_EXTERNAL_TEXT_ALLOWED'] ?? '0') !== '1') return false;
        if (($_ENV['AI_ALLOW_CLINICAL_TEXT'] ?? '0') !== '1') return false;
        $key = trim((string)($_ENV['OPENROUTER_API_KEY'] ?? ''));
        return $key !== '' && !str_starts_with($key, 'CHANGE_ME');
    }

    /**
     * @param list<array{id:string,text:string}> $segments
     * @return array<string,mixed>
     */
    private function callReviewer(array $segments, int $pass): array
    {
        $key = trim((string)($_ENV['OPENROUTER_API_KEY'] ?? ''));
        $model = trim((string)($_ENV['TRANSCRIPTION_REVIEW_MODEL'] ?? 'openrouter/free'));

        $system = <<<'TXT'
You are one conservative Persian medical transcript proofreader. The text came from speech recognition.
Return JSON only: {"proposals":[{"id":"...","proposed_text":"...","reason":"..."}]}.
Rules:
- Correct only obvious ASR spelling/grammar/punctuation mistakes that can be inferred from the supplied words.
- Never add a diagnosis, symptom, drug, dose, date, number, laterality, name or fact.
- Never change or remove negation.
- Never infer who is doctor/patient and never assign speakers.
- When uncertain, return the original text unchanged.
- Keep every segment id exactly unchanged.
TXT;

        $body = [
            'model' => $model,
            'temperature' => 0,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => json_encode([
                    'language' => 'fa',
                    'pass' => $pass,
                    'segments' => $segments,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
            ],
        ];

        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        if ($ch === false) throw new RuntimeException('OPENROUTER_INIT_FAILED');
        $headers = [
            'Authorization: Bearer ' . $key,
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $site = trim((string)($_ENV['OPENROUTER_SITE_URL'] ?? ''));
        if ($site !== '') $headers[] = 'HTTP-Referer: ' . $site;
        $headers[] = 'X-Title: DrBastaninejad-Transcript-Review';

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => max(30, min(180, (int)($_ENV['TRANSCRIPTION_REVIEW_TIMEOUT_SECONDS'] ?? 90))),
        ]);
        $raw = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($raw === false) throw new RuntimeException('OPENROUTER_NETWORK_ERROR:' . substr($error, 0, 120));
        if ($status === 429) throw new RuntimeException('OPENROUTER_RATE_LIMITED');
        $json = json_decode((string)$raw, true);
        if ($status < 200 || $status >= 300 || !is_array($json)) {
            throw new RuntimeException('OPENROUTER_UPSTREAM_' . $status);
        }

        $responseText = (string)($json['choices'][0]['message']['content'] ?? '');
        $decoded = $this->decodeReviewerJson($responseText);
        $proposals = $decoded['proposals'] ?? [];
        if (!is_array($proposals)) throw new RuntimeException('OPENROUTER_INVALID_REVIEW');

        return [
            'model' => (string)($json['model'] ?? $model),
            'provider' => 'openrouter',
            'proposals' => $proposals,
        ];
    }

    /** @return array<string,mixed> */
    private function decodeReviewerJson(string $content): array
    {
        $content = trim($content);
        $content = preg_replace('/^\x60\x60\x60(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*\x60\x60\x60$/', '', $content) ?? $content;
        try {
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RuntimeException('OPENROUTER_INVALID_REVIEW', 0, $e);
        }
        if (!is_array($decoded)) throw new RuntimeException('OPENROUTER_INVALID_REVIEW');
        return $decoded;
    }

    /** @param list<array<string,mixed>> $reviews */
    private function reviewerMeta(array $reviews): array
    {
        $out = [];
        foreach ($reviews as $review) {
            $out[] = [
                'provider' => (string)($review['provider'] ?? 'openrouter'),
                'model' => (string)($review['model'] ?? 'unknown'),
            ];
        }
        return $out;
    }

    private function normaliseText(string $text): string
    {
        $text = strtr($text, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک']);
        $text = preg_replace('/[\x{200C}\x{200D}]{2,}/u', "\u{200C}", $text) ?? $text;
        $text = preg_replace('/[\x{00A0}\t\r\n ]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+([،؛؟!,.])/u', '$1', $text) ?? $text;
        $text = preg_replace('/([،؛؟!,.])(?=[^\s\d])/u', '$1 ', $text) ?? $text;
        return trim($text);
    }

    private function safeSmallEdit(string $before, string $after): bool
    {
        if ($before === '' || $after === '') return false;
        if ($this->digits($before) !== $this->digits($after)) return false;
        foreach (['نه','نیست','نیستم','نیستند','ندارد','ندارم','نداشت','نکرد','نمی','بدون','فاقد'] as $term) {
            if (substr_count($before, $term) !== substr_count($after, $term)) return false;
        }

        $a = preg_split('/\s+/u', trim($before)) ?: [];
        $b = preg_split('/\s+/u', trim($after)) ?: [];
        if (count($a) !== count($b) || $a === []) return false;
        $changed = 0;
        foreach ($a as $i => $token) {
            if ($token !== ($b[$i] ?? null)) $changed++;
        }
        $allowed = max(1, min(3, (int)floor(count($a) * 0.20)));
        if ($changed > $allowed) return false;

        $la = max(1, mb_strlen($before));
        $lb = mb_strlen($after);
        return abs($la - $lb) / $la <= 0.20;
    }

    /** @return list<string> */
    private function digits(string $text): array
    {
        preg_match_all('/[0-9۰-۹٠-٩]+/u', $text, $m);
        return $m[0] ?? [];
    }

    /** @param array<string,mixed> $segment */
    private function flag(array &$segment, string $flag): void
    {
        $flags = is_array($segment['review_flags'] ?? null) ? $segment['review_flags'] : [];
        $flags[] = $flag;
        $segment['review_flags'] = array_values(array_unique($flags));
    }
}
