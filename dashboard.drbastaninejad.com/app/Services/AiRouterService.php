<?php
declare(strict_types=1);

namespace App\Services;

/**
 * AI router: You.com (web-grounded) + optional OpenRouter (LLM draft).
 *
 * Env:
 *   AI_ENABLED=1
 *   AI_ALLOW_CLINICAL_TEXT=0|1   (must be 1 before any clinical text leaves server)
 *   AI_PROVIDER=auto|you|openrouter
 *   YOU_API_KEY=...             (or YDC_API_KEY)
 *   OPENROUTER_API_KEY=...
 *   OPENROUTER_MODEL=openai/gpt-4o-mini
 *   AI_MAX_TOKENS=600
 *
 * You.com docs: https://docs.you.com/  (X-API-Key)
 * Search:  GET/POST https://ydc-index.io/v1/search
 * Answer:  POST https://api.you.com/v1/answer
 */
final class AiRouterService
{
    public function isEnabled(): bool
    {
        if (($_ENV['AI_ENABLED'] ?? '0') !== '1') {
            return false;
        }
        return $this->hasYouKey() || $this->hasOpenRouterKey();
    }

    public function hasYouKey(): bool
    {
        $k = $this->youKey();
        return $k !== '' && !str_starts_with($k, 'CHANGE_ME');
    }

    public function hasOpenRouterKey(): bool
    {
        $k = trim((string)($_ENV['OPENROUTER_API_KEY'] ?? ''));
        return $k !== '' && !str_starts_with($k, 'CHANGE_ME');
    }

    private function youKey(): string
    {
        $k = trim((string)($_ENV['YOU_API_KEY'] ?? ''));
        if ($k === '') {
            $k = trim((string)($_ENV['YDC_API_KEY'] ?? ''));
        }
        return $k;
    }

    private function provider(): string
    {
        $p = strtolower(trim((string)($_ENV['AI_PROVIDER'] ?? 'auto')));
        return in_array($p, ['auto', 'you', 'openrouter'], true) ? $p : 'auto';
    }

    private function clinicalAllowed(): bool
    {
        return (($_ENV['AI_ALLOW_CLINICAL_TEXT'] ?? '0') === '1');
    }

    /**
     * Web-grounded answer via You.com (preferred for FAQs / public knowledge).
     * Does not send patient chart text unless AI_ALLOW_CLINICAL_TEXT=1.
     *
     * @return array{answer:string,sources:array<int,array{title?:string,url?:string}>}
     */
    public function answerWithYou(string $query, int $count = 5): array
    {
        if (!$this->hasYouKey()) {
            throw new \RuntimeException('YOU_API_KEY is not configured');
        }
        $query = trim($query);
        if ($query === '' || mb_strlen($query) > 2000) {
            throw new \InvalidArgumentException('Query length invalid');
        }

        // Prefer unified answer endpoint (search + synthesis).
        $payload = json_encode(['query' => $query], JSON_UNESCAPED_UNICODE);
        $raw = $this->httpJson(
            'POST',
            'https://api.you.com/v1/answer',
            [
                'X-API-Key: ' . $this->youKey(),
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            $payload
        );

        $answer = '';
        $sources = [];
        if (is_array($raw)) {
            $answer = (string)($raw['answer'] ?? $raw['output'] ?? $raw['text'] ?? '');
            $cites = $raw['citations'] ?? $raw['sources'] ?? $raw['results']['web'] ?? [];
            if (is_array($cites)) {
                foreach ($cites as $c) {
                    if (!is_array($c)) {
                        continue;
                    }
                    $sources[] = [
                        'title' => (string)($c['title'] ?? ''),
                        'url' => (string)($c['url'] ?? $c['link'] ?? ''),
                    ];
                }
            }
        }

        // Fallback: plain search snippets if answer empty.
        if ($answer === '') {
            $search = $this->searchYou($query, $count);
            $bits = [];
            foreach ($search as $row) {
                $title = $row['title'] ?? '';
                $snip = $row['snippet'] ?? '';
                if ($title !== '' || $snip !== '') {
                    $bits[] = trim($title . ': ' . $snip);
                }
                $sources[] = ['title' => $title, 'url' => $row['url'] ?? ''];
            }
            $answer = implode("\n", array_slice($bits, 0, 8));
        }

        return ['answer' => $answer, 'sources' => $sources];
    }

    /**
     * @return array<int,array{title:string,url:string,snippet:string}>
     */
    public function searchYou(string $query, int $count = 5): array
    {
        if (!$this->hasYouKey()) {
            throw new \RuntimeException('YOU_API_KEY is not configured');
        }
        $count = max(1, min(20, $count));
        $url = 'https://ydc-index.io/v1/search?query=' . rawurlencode($query) . '&count=' . $count;
        $raw = $this->httpJson('GET', $url, [
            'X-API-Key: ' . $this->youKey(),
            'Accept: application/json',
        ], null);

        $out = [];
        $web = $raw['results']['web'] ?? $raw['web'] ?? [];
        if (!is_array($web)) {
            return $out;
        }
        foreach ($web as $row) {
            if (!is_array($row)) {
                continue;
            }
            $snippets = $row['snippets'] ?? [];
            $snippet = is_array($snippets) && isset($snippets[0]) ? (string)$snippets[0] : (string)($row['description'] ?? '');
            $out[] = [
                'title' => (string)($row['title'] ?? ''),
                'url' => (string)($row['url'] ?? ''),
                'snippet' => $snippet,
            ];
        }
        return $out;
    }

    /**
     * Clinical note draft. Requires AI_ALLOW_CLINICAL_TEXT=1.
     * Provider preference: AI_PROVIDER, else OpenRouter if key present, else You.com answer.
     *
     * @param array<string,mixed> $context
     */
    public function draftClinicalNote(string $prompt, array $context = []): string
    {
        if (!$this->isEnabled()) {
            throw new \RuntimeException('AI disabled');
        }
        if (!$this->clinicalAllowed()) {
            throw new \RuntimeException('AI_ALLOW_CLINICAL_TEXT is off — clinical text will not leave the server');
        }

        $prompt = trim($prompt);
        if ($prompt === '' || mb_strlen($prompt) > 2000) {
            throw new \InvalidArgumentException('Invalid prompt length');
        }

        // Never send full national ID / raw mobile in upstream prompts.
        $safeContext = [];
        foreach (['chief_complaint', 'visit_reason', 'service_type', 'age_band', 'gender'] as $k) {
            if (!empty($context[$k]) && is_scalar($context[$k])) {
                $safeContext[$k] = (string)$context[$k];
            }
        }

        $system = 'You are a clinical drafting assistant for an ENT/rhinoplasty clinic in Tehran. '
            . 'Produce a structured draft note for physician review only. '
            . 'Do not invent diagnoses, guarantees, or drug doses. Mark uncertainty. Use Persian if the prompt is Persian.';

        $user = $prompt;
        if ($safeContext !== []) {
            $user .= "\n\nContext JSON:\n" . json_encode($safeContext, JSON_UNESCAPED_UNICODE);
        }

        $provider = $this->provider();
        if ($provider === 'auto') {
            $provider = $this->hasOpenRouterKey() ? 'openrouter' : 'you';
        }

        if ($provider === 'openrouter') {
            return $this->openRouterChat($system, $user);
        }

        // You.com grounded answer as draft source (not a medical authority).
        $res = $this->answerWithYou($user);
        $draft = trim($res['answer']);
        if ($draft === '') {
            throw new \RuntimeException('Empty AI draft from You.com');
        }
        return $draft;
    }

    private function openRouterChat(string $system, string $user): string
    {
        if (!$this->hasOpenRouterKey()) {
            throw new \RuntimeException('OPENROUTER_API_KEY is not configured');
        }
        $model = trim((string)($_ENV['OPENROUTER_MODEL'] ?? 'openai/gpt-4o-mini'));
        $max = (int)($_ENV['AI_MAX_TOKENS'] ?? 600);
        if ($max < 100) {
            $max = 600;
        }
        $body = [
            'model' => $model,
            'max_tokens' => $max,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ];
        $headers = [
            'Authorization: Bearer ' . trim((string)$_ENV['OPENROUTER_API_KEY']),
            'Content-Type: application/json',
            'Accept: application/json',
        ];
        $site = trim((string)($_ENV['OPENROUTER_SITE_URL'] ?? ''));
        if ($site !== '') {
            $headers[] = 'HTTP-Referer: ' . $site;
            $headers[] = 'X-Title: DrBastaninejad-CRM';
        }
        $raw = $this->httpJson(
            'POST',
            'https://openrouter.ai/api/v1/chat/completions',
            $headers,
            json_encode($body, JSON_UNESCAPED_UNICODE)
        );
        $text = $raw['choices'][0]['message']['content'] ?? '';
        $text = is_string($text) ? trim($text) : '';
        if ($text === '') {
            throw new \RuntimeException('Empty OpenRouter response');
        }
        return $text;
    }

    /**
     * @param list<string> $headers
     * @return array<string,mixed>
     */
    private function httpJson(string $method, string $url, array $headers, ?string $body): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('curl extension required');
        }
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_CONNECTTIMEOUT => 10,
        ];
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);
        $resp = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if ($resp === false) {
            throw new \RuntimeException('HTTP error: ' . $err);
        }
        $data = json_decode($resp, true);
        if ($code >= 400) {
            $msg = is_array($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : substr((string)$resp, 0, 300);
            throw new \RuntimeException('Upstream HTTP ' . $code . ': ' . $msg);
        }
        return is_array($data) ? $data : [];
    }
}
