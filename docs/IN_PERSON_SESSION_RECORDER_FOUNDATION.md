# In-person session recorder: source foundation

MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad

Task: RPH-137. Source baseline: `maziyarid/Medical-CRM` main at
`6e5e6fa9daf2faa3571043c7df237fdb3b8a4a3c`, checked October 5, 2026.

## What this milestone actually does

- Adds an RTL **in-person session** tab to the existing patient detail page. The patient index already links to that page.
- Shows consent, elapsed-time, start/pause/resume/stop/withdrawal controls and mobile interruption cautions. Controls are deliberately disabled: no capture or secure session API is connected.
- Implements a pure lifecycle with explicit, persisted-consent receipt and scoped-readiness requirements, interruption/resume, consent withdrawal, access denial/expiry, and a three-hour wall-clock maximum. Pauses count toward the maximum but not recorded-audio duration.
- Implements an in-memory bounded chunk outbox with SHA-256, stable retry identity, durable-acknowledgement validation, sequence checks and discard handling. It invokes only a caller-supplied transport; none is supplied by the application.
- Validates ordered chunk manifests, preserving container initialization boundaries across interrupted streams.
- Defines a provider-neutral, draft-only job descriptor. The real provider is unconfigured and returns `TRANSCRIPTION_UNCONFIGURED`.
- Validates timestamped transcript turns. Every speaker starts unassigned. Manual role/name assignments and text corrections make new in-memory draft versions and preserve raw ASR text.
- Supplies a reusable, safely escaped Persian transcript review view; the application does not populate it with sample turns or pretend a transcription occurred.

**This is not a working recording service.** It does not call `getUserMedia`, instantiate `MediaRecorder`, upload audio, persist clinical information, run ASR, infer a diagnosis, create EMR notes, or expose a backend route. Telephone recording and real-time translation belong to separate Smart Teb work and are outside this feature.

## Why capture is still disabled

The inspected source has several real prerequisites:

1. `app.drbastaninejad.com/.htaccess` explicitly sets `Permissions-Policy: microphone=()`. Changing that policy is a separate reviewed security-sensitive step.
2. Staff patient routes use general `patients.view` / `patients.manage`; that does not define permission to record, listen to, transcribe or export a clinical conversation. Existing patient handlers also default a missing clinic to `1`; the new feature must never copy that fallback.
3. The current shared API helper serializes JSON. `Request::fromGlobals()` has a default 65,536-byte body cap and JSON-only parsing; it is not a bounded binary upload service. Do not send a Blob through the existing helper, inflate the global cap, or add public uploads.
4. There is no private session-audio storage and durable transcription queue in the inspected source.
5. Patient portal EMR reads do not provide the review gate this feature needs. Never write an unreviewed transcript or classification into existing EMR routes.
6. Main may lag production hotfixes. Reconcile a safe, exact live-source baseline before deployment; this source snapshot is not proof of production parity.

The scope checks in JavaScript are **contracts and UX guards, not a security boundary**. The PHP service, storage layer and worker must independently enforce them using authenticated identities and current database state. A browser-supplied grant, receipt, digest, `stored` flag or clinic ID is never authoritative.

## Files and seams

- `Frontend/assets/js/recording/session.js`: pure lifecycle, explicit access/consent checks, unconfigured provider, draft transcript versions.
- `Frontend/assets/js/recording/chunks.js`: bounded outbox, receipts, ordered manifest and draft-job contract.
- `Frontend/assets/js/recording/panel.js`: disabled production panel and reusable manual transcript review view.
- `Frontend/assets/css/recording.css`: responsive RTL component styling.
- `Frontend/pages/staff/patient-detail.html`: seventh tab; panel mounted only after a successful existing patient fetch.
- `Frontend/tests/recording-*.test.mjs`: dependency-free Node unit/contract tests.
- `Frontend/tests/recording-dom.mjs`: optional jsdom interaction checks.
- `Frontend/tests/recording-browser.mjs`: optional local Chromium interaction/layout check; no microphone or external page access.

No PHP route, request parser, permission policy, database schema, deployment setting or existing API helper is changed in this milestone.

## Capture integration contract for the next slice

Use an independently written, replaceable capture driver. Starting capture requires all of:

1. An authenticated staff session; positive server-derived clinic, actor and patient IDs; patient-clinic ownership; explicit session-recording permission.
2. A persisted, current consent receipt covering recording and transcription, with policy version, actor, participants and timestamp. Policy/guardian handling must be resolved by the clinic; a UI checkbox alone is insufficient.
3. A server-created session bound to the exact patient/clinic, a valid authorization lease, approved private storage with capacity, and the durable worker queue ready.
4. An explicit user start action after a capabilities check. A page render, login, permission grant or tab switch must never start capture.

The current `PREPARE` event models server readiness and consent receipt. The real adapter must persist and retrieve these first, never manufacture them from browser state. The session state machine does not replace that adapter.

Future adapter obligations:

- Keep microphone acquisition asynchronous and cancellation-safe. Permission rejection, device loss, auth loss, navigation and repeated clicks must release tracks, cancel pending work and show the real outcome.
- Use elapsed time from a monotonic clock, with an explicit server-time mapping for lease checks. Current pure events accept nonnegative millisecond timestamps in a single clock domain; do not mix epoch time with `performance.now()`.
- Do not infer recorded duration merely from timer callbacks. Reconcile actual sample/container duration after remuxing. Detect long callback gaps and require explicit recovery rather than claiming uninterrupted capture.
- On page hidden, device loss, upload backpressure or unreliable capture: pause/stop the driver, emit `INTERRUPT`, identify possible gaps and require explicit `RESUME`. A lock screen, incoming call, OS suspension or browser crash may interrupt mobile capture; background recording is not promised.
- Enforce the three-hour wall-clock cap in the driver and server, not just a visible counter. A paused session does not gain extra time.
- `WITHDRAW_CONSENT` and `REVOKE_ACCESS` stop capture and processing immediately. Clear pending client bytes, abort in-flight requests and invoke an authenticated server cancellation/withdrawal operation. An outbox `discard()` cannot retract bytes already sent.
- No IndexedDB, localStorage, sessionStorage, service-worker audio cache, downloads or other local patient-data persistence by default. Crash/refresh recovery of unacknowledged chunks is not implemented. Explain that limitation and any potential lost interval; never claim the whole session was saved.

## Bounded audio transport contract

Constants in the source are pilot limits, not codec-quality guarantees:

- Target callback: 30 seconds
- Maximum accepted chunk: 2 MiB and 60 seconds of audio
- Maximum outstanding payload bytes: 8 MiB
- Maximum session payload: 128 MiB
- Maximum chunk count: 720
- Maximum session wall-clock and audio duration: 10,800,000 ms

The pending-byte bound is not a promise about total browser heap. Hashing and an in-flight upload may temporarily hold additional copies. Monitor real-device memory before acceptance.

Each chunk carries patient/session IDs, sequence, audio-relative start/end, MIME type, stream ID, initialization-sequence reference, byte length and SHA-256. The idempotency key is session + sequence + SHA-256. Client input cannot override the outbox patient/session binding.

Suggested future route family (not implemented):

- `POST /api/v1/patients/{patientId}/recording-sessions`: authenticated consent/session creation, with idempotency.
- `PUT .../{sessionId}/chunks/{sequence}`: bounded authenticated binary stream or narrowly scoped private-store upload; server-derived object names, strict MIME/size validation, streaming checksum and storage limits.
- `POST .../{sessionId}/finalize`: validates exact expected chunk count, duration, no gaps/duplicates, immutable ordered manifest and transactional job creation.
- `POST .../{sessionId}/withdraw`: atomically prevents new writes/jobs, cancels work and applies the approved retention/deletion policy.
- `GET .../{sessionId}` and a patient-scoped draft endpoint: never public, never patient-portal publication by default.

Require exact patient/session/clinic ownership on **every** request and worker transition. Missing tenant/actor, access denial or unreadable authorization state fail closed. Decide whether the existing super-admin bypass is applicable; do not inherit it implicitly.

Server acknowledgement is issued only after durable storage and checksum validation. Its identity, timing, size and digest must match the request. Retrying the same session/sequence/digest returns the same successful receipt; a different digest for an existing sequence returns conflict. Use a unique database constraint and transactional metadata, not an in-memory duplicate cache.

Receiving chunks out of order is acceptable; finalization must sort and require every sequence exactly once. Audio-relative time is contiguous across normal pause/resume; track wall-clock pause/interruption gaps separately. After a new capture container starts, its first chunk is its initialization sequence. Preserve each stream's order and container header. **MediaRecorder fragments are not assumed to be standalone playable ASR files.** Remux/validate each ordered stream before bounded ASR segmentation. Validate actual media duration and codec; caller-supplied metadata is not evidence.

## Durable job and draft contract

The JavaScript job descriptor is not a queue. Implement PHP persistence and a separate bounded worker that:

- Loads the immutable finalized manifest and current consent/authorization from trusted storage.
- Rejects incomplete, withdrawn, cross-clinic, expired/denied or unconfigured work.
- Uses a unique session/manifest/provider-version key; retries are idempotent and do not duplicate draft versions.
- Stores only private object references, not patient names, transcript text or credentials in queue/log payloads.
- Tracks queued/running/failed/cancelled/draft states, claim leases, attempts, retry-after and sanitized error codes.
- Rechecks consent before reading audio, before each processing stage, and before publishing any draft. Withdrawal wins over a late worker response.
- Limits CPU, memory and scratch disk; no heavy inference on the strained production VPS. Benchmark a separately approved local CPU worker first.

ASR output must preserve model/revision, language, raw text, timestamps, uncertainty and source intervals. Unreliable or missing diarization yields an unknown speaker. Never guess doctor/patient from silence gaps, turn order or perceived voice. Reconcile anonymous speaker IDs across chunks; manual per-turn mapping remains available. Overlap and additional speakers must be representable.

The current draft helpers increment versions in memory, retain raw text, and append edit provenance. They are **not an immutable database/audit history**. Persist complete versions with server-derived actor/time and optimistic concurrency in the next backend slice. Clinical review must be a separate explicit authorization, not the manual-speaker button or a text correction.

Case classification remains unconfigured. Any later suggestion must cite transcript evidence, stay an unreviewed draft, and require a clinician's approval before becoming part of the medical record. No autonomous diagnosis, treatment or patient-portal publication.

## Required decisions and independent work

Before enabling production capture, obtain decisions on:

1. Who may record, listen, edit, review, finalize and export; cross-clinic/super-admin behavior.
2. Consent wording/policy, participants/guardian handling, and withdrawal consequences.
3. Private audio/transcript storage location, encryption/key ownership, capacity and backup handling; retention periods and deletion authority.
4. Which separately provisioned machine runs ASR, desired turnaround, allowed model downloads/licensing and dependency installation.
5. The exact app-origin microphone policy and browser support after explicit security review.

Work that can proceed in parallel without patient data or deployment: PHP interface/negative-test design for authorization and storage, synthetic worker/job tests, scripted Persian evaluation-set preparation, and source-baseline reconciliation. Provisioning, security-setting changes, models/credentials, real recordings and external patient-data transmission remain separate authorized steps.

## Verification and honest limits

Run from repository root with Node 22+:

```sh
node --test app.drbastaninejad.com/Frontend/tests/*.test.mjs
```

Optional DOM checks require an existing jsdom installation; optional browser checks require Playwright and an available Chromium. Point `JSDOM_MODULE` or `PLAYWRIGHT_MODULE` to the module when they are outside this source slice. `CHROMIUM_EXECUTABLE` overrides the browser path. Tests never install dependencies automatically.

```sh
node app.drbastaninejad.com/Frontend/tests/recording-dom.mjs
node app.drbastaninejad.com/Frontend/tests/recording-browser.mjs
```

Test fixtures are synthetic strings/bytes and injected timestamps, not patient audio. The three-hour tests simulate 360 30-second chunks and time advancement; they do not run a microphone for three hours. Browser QA initially could not launch because this cloud sandbox denied Chromium's socket creation. DOM interaction checks do not establish layout quality, Safari/Android microphone support, sleep/lockscreen behavior, real codec handling or endurance.

There is no frontend package/test runner in the inspected static-app source. All new dependency-free unit tests are run together. The unchanged backend PHPUnit suite is not run in this selective safe-source workspace; no PHP binary/backend dependency environment was provisioned.

Before a real pilot, verify full integrated app behavior, repeated clicks/navigation, scoped authorization, revocation races, quotas, authenticated streaming, actual remuxing, gaps/recovery and an uninterrupted three-hour device test. Measure Persian medical WER/CER plus critical medication, dose, number, negation, laterality and speaker-role errors against clinician-reviewed reference transcripts. No accuracy, real-time speed or clinical safety guarantee is established here.

No VoiceStudio code or models are copied or installed. There is no new runtime dependency in the foundation.
