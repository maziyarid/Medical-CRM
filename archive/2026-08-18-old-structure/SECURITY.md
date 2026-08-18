# Security Policy — MΛZ Medical CRM

_Last reviewed: 2026-07-25 · Owner: MAZ//ID (Maziyar)_

The system handles protected health information (PHI). Security is not an add-on.

---

## 1. Reporting a vulnerability

- **Do NOT open a public GitHub issue** for a suspected vulnerability.
- Email **maziyarid@gmail.com** with:
  - A short title and severity estimate.
  - A minimal, reproducible description (URL, request/response, expected vs actual).
  - Any suggested remediation.
- You will receive an acknowledgement within **48 hours** and a first triage within **5 business days**.

We do not currently run a paid bug-bounty programme, but responsible disclosure is credited in [`CHANGELOG.md`](CHANGELOG.md) with your permission.

---

## 2. Secrets & credentials — hard rules

- **Never** commit secrets. `.env`, `.env.local`, `*.pem`, `*.key`, `credentials.json`, API tokens are ignored by `.gitignore`.
- Any secret accidentally committed is treated as **compromised** — rotate immediately, then rewrite history if the leak was pushed.
- OpenRouter, Kavenegar, Zarinpal, ArvanCloud, and database credentials live only in the server's `.env` file, outside the web root, chmod `600`.
- The client-side JS bundle **must never contain** any secret. If it looks like one is required, treat it as an architecture bug — proxy through the PHP backend.

---

## 3. Authentication & session

- Passwords hashed with **Argon2id** (fallback: bcrypt cost ≥ 12).
- Session cookies: `HttpOnly`, `Secure`, `SameSite=Lax`; separate cookie names for staff (`/admin`) vs patient portal sessions.
- OTP codes are **hashed** in `otp_codes.code_hash` — never store the plaintext.
- OTP: 5-minute expiry, max 5 attempts, per-mobile rate-limit (max 3 sends / 10 minutes).

---

## 4. PHI (patient health information) handling

- All timestamps stored **UTC**; presentation-only conversion to Jalali.
- All clinical tables use **soft delete** (`deleted_at`), never hard-delete.
- Every clinical write goes through `audit_logs` (`created_by`, `updated_by`, before/after diff).
- Media is stored on **ArvanCloud / Liara S3** with **short-lived signed URLs** — no public buckets, no direct `object_key` exposure in HTML.
- Data residency is Iran-only for MVP; no cross-border replication without explicit written consent and a signed data-processing agreement.

---

## 5. AI / LLM safety (Dr. Copilot)

- **No autonomous agents** in MVP — every AI output is presented as a reviewable card that requires explicit human "Accept" before it touches a patient record.
- Prompts sent to OpenRouter must **strip PHI** to the minimum necessary: use pseudonymised identifiers (`patient_uuid`) rather than name + national ID in the prompt body.
- Each LLM call is logged to `ai_interactions` (model, tier, tokens, cost, latency, whether it was a fallback, and who approved it).
- Configure OpenRouter's data-retention setting to **"no data retention"** for models that support it; document per-model retention in `docs/AI_STRATEGY.md`.

---

## 6. Frontend hardening (baseline)

- `Content-Security-Policy` header defined server-side; the frontend deliberately keeps inline scripts minimal so CSP can stay tight (`script-src 'self' cdn.jsdelivr.net` in production).
- All forms use POST-with-CSRF-token in production (frontend already validates client-side and sends JSON — server must add CSRF middleware).
- No third-party analytics or trackers on public-facing intake pages — patient trust > funnel metrics.

---

## 7. Supported browsers & dependency policy

- Modern Chrome/Edge/Safari/Firefox (last 2 majors) + iOS Safari 15+, Android WebView 100+.
- **No `?.` / `??` / async-await** in intake page JS — targets old browsers used by patients in Iran (rewritten to XMLHttpRequest-style + ES5).
- Dependencies (fonts via CDN, future NPM packages) pinned to specific versions.

---

## 8. Backup & disaster recovery (planned)

- Daily encrypted MySQL dumps to a separate storage account (v1.5 milestone).
- Weekly restore drills.
- Signed-URL audit logs retained for 12 months.

---

_M•Z — MAZ//ID — © 2026 Dr. Shahin Bastaninejad_
