<!-- MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad -->

# AI Strategy — Dr. Copilot

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 25 July 2026

> **Guiding principle — "Routing is Revenue."**
> A Request Router can slash API costs by **60–80 %** by offloading simple, high-volume tasks to small/cheap models and reserving premium clinical models for the tasks that actually need them.

---

## 1. Architecture — Router + RAG

```
Staff or patient prompt
        │
        ▼
┌───────────────────────┐
│    RouterService      │  ← rule-based routing first (fast, deterministic)
│  (config/ai.php map)  │     semantic routing added in v1.5
└───────────┬───────────┘
            │  selects cheapest capable model in the tier
            ▼
┌───────────────────────┐    ┌──────────────────────────┐
│   OpenRouter API      │◄──►│  RAG Vector Store        │
│  (single entrypoint)  │    │  clinic protocols, drug  │
└───────────┬───────────┘    │  list, post-op scripts…  │
            │                └──────────────────────────┘
            │  fallback chain if primary model errors
            ▼
   Draft returned as a reviewable card
            │
            ▼
   Human "Accept" → write to `emr_records`
                    log to `ai_interactions`
```

### Two design rules that drive this

1. **RAG over fine-tuning.** For an MVP, retrieval-augmented generation is ~10× cheaper and more reliable than fine-tuning. Ground the Copilot on **the clinic's own documents** (post-op instructions, drug list, protocols) rather than training a custom model.
2. **Rule-based routing first, semantic later.** Start with a deterministic map from `context_type` → model tier. Once we have production logs from `ai_interactions`, introduce a small semantic classifier for edge cases.

---

## 2. Model tier map (MVP)

Configured in `config/ai.php`. Every entry has: **primary model**, **fallback**, **max tokens**, **temperature**, **PII policy**.

| CRM context | Feature | Tier | Primary model | Fallback | Why this tier |
|---|---|---|---|---|---|
| **Intake** | parse messy input, normalise Persian digits, flag missing fields | `small` | `openai/gpt-4o-mini` | `google/gemini-flash-1.5` | High-volume, simple. Cheap = margin. |
| **Patient Master View** | 3-line summary of patient history timeline | `mid` | `anthropic/claude-3-haiku` | `openai/gpt-4o-mini` | Needs comprehension, not deep reasoning. |
| **EMR / Clinical notes** | Draft SOAP notes, suggest template, contraindication check | **`premium`** | `anthropic/claude-3-sonnet` | `openai/gpt-4o` | Clinical accuracy is critical — worth the cost. |
| **Scheduling** | Natural-language booking ("book Sara Tuesday PM") | `small` + tool-use | `openai/gpt-4o-mini` | `mistralai/mistral-large` | Structured output, low reasoning. |
| **Billing** | Explain invoice, detect anomalies | `mid` | `anthropic/claude-3-haiku` | `openai/gpt-4o-mini` | Moderate reasoning. |
| **Marketing / Analytics** | Insight summaries, SMS campaign copy | `mid` | `openai/gpt-4o-mini` | `google/gemini-flash-1.5` | Copy generation. |
| **Patient portal Q&A** | post-op care questions, RAG-grounded | `small`+`mid` (strict RAG) | `openai/gpt-4o-mini` | `anthropic/claude-3-haiku` | Must be grounded in approved docs only. |
| **Dev tooling** | Auto-document PHP APIs, generate migrations | `premium` (coder) | `anthropic/claude-3-sonnet` | `openai/gpt-4o` | Internal-only; quality matters more than cost. |

### Tier definitions
- **`small`** — < $0.20 / 1M input tokens. Aggressive caching. 3 s timeout.
- **`mid`**   — $0.30–$3 / 1M input. Cached where safe. 8 s timeout.
- **`premium`** — > $3 / 1M input. Never used without a `context_type` marked `clinical`. 15 s timeout.

---

## 3. Copilot behaviour modes (pluggable strategies)

Encapsulated as pluggable strategy classes under `app/Modules/AI/Modes/`, not conditional branches — the orchestrator stays lean.

| Mode | Behaviour |
|---|---|
| **Assistant** | General staff help; no PHI in prompt. |
| **Clinical** | Strict, RAG-grounded, cautious wording, always human-approved. Adds `"clinical caution"` system instruction. |
| **Reception** | Scheduling + patient comms; tool-use enabled (`create_appointment`, `send_reminder`). |
| **Strict**    | For anything with medical/legal liability. Refuses to output unless RAG matches ≥ 1 approved doc. |

---

## 4. Prompt policy — what NEVER goes to the model

- **National ID** — never sent.
- **Full name** — never sent verbatim in a prompt body. Use `patient_uuid` and let the model refer to "the patient".
- **Insurance card number** — never sent.
- **Signature images** — never sent.
- Anything that would break Iranian data-protection expectations even in a private inference call.

The `PromptRegistry` service enforces this by scrubbing keys before shipping to `OpenRouterClient`.

---

## 5. RAG store — MVP shape

- Chunk size 400 tokens, 50-token overlap.
- Sources: `post_op_instructions.md`, `drug_interactions.csv`, `contraindications.md`, `clinic_protocols/*.md` — all owned by the doctor and version-controlled.
- Backend: SQLite + `sqlite-vss` (v1.0) → pgvector (v1.5) → dedicated service (v2). The interface is `RagStore::search(query, top_k)` so backends swap freely.
- Every retrieval is logged so we can prove which docs grounded a given answer.

---

## 6. Human-in-the-loop — the non-negotiable

- **No autonomous AI agents in MVP.** No auto-scheduling, no auto-sending, no silent EMR writes.
- Every AI output is rendered in the UI as a card explicitly labelled `Dr. Copilot — پیش‌نویس · نیازمند بازبینی` with **Accept / Edit / Discard** actions.
- The "Accept" action is the ONLY code path that writes to a clinical table. It records `approved_by` on the `ai_interactions` row and the `ai_summary` / `ai_model_used` on `emr_records`.

---

## 7. Cost controls

| Control | Implementation |
|---|---|
| Tier routing | 70 %+ of traffic to `small` models (see §2) |
| Prompt caching | OpenRouter caching enabled on identical system prompts |
| Response caching | RAG retrievals + template drafts cached ≤ 24 h |
| Daily cost cap | Cron job checks sum of `ai_interactions.cost_usd` per clinic per day; disables Copilot beyond threshold with a UI banner |
| Fallback ladder | OpenRouter fallback chain configured per tier so a single provider outage doesn’t block the doctor |
| Observability | Every call logs `model_used`, `tier`, `prompt_tokens`, `completion_tokens`, `cost_usd`, `latency_ms`, `was_fallback` |

Frontend already surfaces the model + tier + cost line in the Copilot cards so the doctor can build intuition ("this draft was `claude-3-haiku`, tier `mid`, cost $0.0021").

---

## 8. OpenRouter integration — code contract

`app/Modules/AI/OpenRouterClient.php`
```php
public function chat(
    string $model,          // e.g. 'anthropic/claude-3-haiku'
    array $messages,        // [['role'=>'system'|'user','content'=>'...']]
    array $opts = []        // max_tokens, temperature, tools, timeout
): OpenRouterResponse
```

`config/ai.php`
```php
return [
    'openrouter' => [
        'base_uri' => env('OPENROUTER_BASE', 'https://openrouter.ai/api/v1'),
        'api_key'  => env('OPENROUTER_API_KEY'),
        'app_name' => 'MΛZ Medical CRM',
    ],
    'tiers' => [
        'small'   => ['openai/gpt-4o-mini', 'google/gemini-flash-1.5'],
        'mid'     => ['anthropic/claude-3-haiku', 'openai/gpt-4o-mini'],
        'premium' => ['anthropic/claude-3-sonnet', 'openai/gpt-4o'],
    ],
    'contexts' => [
        'intake.parse'        => 'small',
        'patient.summary'     => 'mid',
        'emr.draft'           => 'premium',
        'scheduling.parse'    => 'small',
        'billing.explain'     => 'mid',
        'patient.qa'          => 'mid',
        'marketing.copy'      => 'mid',
        'dev.docs'            => 'premium',
    ],
    'timeouts_ms' => ['small' => 3000, 'mid' => 8000, 'premium' => 15000],
    'daily_cap_usd' => 5.00,
];
```

---

## 9. Growth path

- **v1.0 (MVP)** — rule-based routing, RAG on clinic docs, EMR drafting + patient timeline summary.
- **v1.5** — semantic routing on ambiguous contexts, prompt-cache metrics dashboard in the CRM.
- **v2.0** — model-graded evals in CI (a small held-out set of Persian intake blurbs and EMR notes tested against every tier candidate weekly), fine-tuned small model for intake parsing.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_
