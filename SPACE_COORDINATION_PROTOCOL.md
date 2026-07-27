# Space Coordination Instructions — Multi-Agent, Multi-Chat Protocol

**Purpose:** Prevent conflicting work when multiple chats/accounts operate in the same Space on this project.
**Applies to:** This chat (Frontend/Dashboard track) + the parallel "Medical-CRM" chat (Backend track), potentially running under a different account.

---

## 1. Why this exists

You are now running two active development tracks simultaneously, in the same Space, sometimes from different accounts, specifically to spread usage load. Without a shared checkpoint protocol, both tracks can silently duplicate or contradict each other's schema, naming, or file decisions — as already happened once with the `patients` table (see `UNIFIED_MASTER_PLAN.md` §1).

## 2. The single rule

**`UNIFIED_MASTER_PLAN.md` is the only authoritative architecture document.** Any chat, in any account, must treat it as read-only ground truth for schema, naming, and phase sequencing. If a chat needs to deviate from it, that chat must first output a proposed amendment to `UNIFIED_MASTER_PLAN.md` — never silently create a parallel table, endpoint, or naming convention.

## 3. Before starting ANY work session, each agent must:

1. Re-read `UNIFIED_MASTER_PLAN.md` in full (not summarized) — treat it as the current state of truth, not memory from a prior turn.
2. Re-read `PROGRESS_LOG.md` (new file, see §4) to see what the *other* track has completed since this agent's last session.
3. State explicitly, at the start of its response, which Phase (per `UNIFIED_MASTER_PLAN.md` §3) it is working on and confirm no other open task in `PROGRESS_LOG.md` overlaps with it.

## 4. New file: `PROGRESS_LOG.md` (shared append-only ledger)

Every agent, after completing meaningful work, must append an entry in this format:

```
## [YYYY-MM-DD HH:MM] — [Track: Frontend|Backend] — [Agent/Chat identifier]
Phase: <phase number from UNIFIED_MASTER_PLAN.md>
Completed: <one-line summary>
Files touched: <list>
Schema/API changes: <none | describe exactly>
Blocking questions raised: <none | list>
```

Never delete or rewrite prior entries — this is append-only, like a commit log. If a conflict is discovered (e.g., two tracks touched the same table), the discovering agent must add a `## CONFLICT FLAGGED` entry immediately and pause that specific piece of work until the user resolves it.

## 5. Division of responsibility (current split)

| Track | Scope | Must NOT touch |
|---|---|---|
| **This chat (Frontend/Dashboard)** | Dashboard UI completion, patient portal pages, static site/landing pages, admin UI screens, email templates (visual), PWA/APK wrapper concerns | Database schema, PHP backend logic, API endpoint contracts |
| **Other chat (Backend)** | MySQL schema deployment, PHP MVC backend, API endpoints, migration jobs, AI Copilot/OpenRouter service, SMS/OTP server logic | Visual design, CSS/brand tokens, page copy, UI component structure |

Both tracks share: `Medical CRM.md` (design system tokens — frontend reads it, backend must not alter it), `UNIFIED_MASTER_PLAN.md` (both read, neither edits without explicit user instruction), `PROGRESS_LOG.md` (both write, append-only).

## 6. Naming conflict prevention

- Table names, column names, and API route names are **backend track's exclusive naming authority** — frontend must request an endpoint/field by describing the need, not by inventing a name and hoping backend matches it.
- Component names, CSS class conventions, and brand token names are **frontend track's exclusive naming authority**.
- Any cross-track naming decision (e.g., what a JSON payload field is literally called) must be written into `UNIFIED_MASTER_PLAN.md` as an amendment before either side codes against it.

## 7. Realtime sync note (per user's urgency: SQL submission, no double-submissions)

Because both tracks now touch the intake submission path from different angles (frontend: form UX; backend: DB write), the double-submission prevention must be implemented **once, in the backend**, using a single idempotency mechanism (e.g., a client-generated UUID sent with the form, checked against a unique DB constraint before insert — see `UNIFIED_MASTER_PLAN.md` Phase 1/2). The frontend track must not attempt to prevent double-submission independently (e.g., via JS-only disable-on-click) as the sole safeguard — that is a UX nicety, not the actual safety mechanism, and must not be confused as sufficient.

---

*Add this file to the repository root. Both accounts/chats should be pointed to it explicitly at the start of every new session.*
