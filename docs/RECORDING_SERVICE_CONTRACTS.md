# In-person recorder: PHP service contracts

MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad

RPH-137, October 5, 2026. Source baseline: Medical-CRM
`6e5e6fa9daf2faa3571043c7df237fdb3b8a4a3c`. This is an additive companion to
`IN_PERSON_SESSION_RECORDER_FOUNDATION.md`. Live-source parity remains unresolved.

## Scope and status

This slice implements PHP 8.1+ service logic against mandatory injected contracts,
with synthetic in-memory collaborators only in tests. **It is not a working
recording backend or production-ready queue.** There is no concrete database,
private-store adapter, job runner, codec/remux integration, ASR provider, HTTP
controller, route, schema migration, deployment, permission grant, or policy
change. All files are new; existing request parsing, API helpers, clinical tables,
and the microphone-denying Permissions-Policy are unchanged.

The existing API is JSON-only and capped at 64 KiB. It must not receive audio via
this slice. The existing patient-portal EMR queries expose non-deleted records;
therefore drafts must have separate persistence and access controls. This service
has no clinical approval, diagnosis, treatment, export, portal publication or
EMR-save method. Manual correction and speaker assignment always remain
unreviewed. Telephone recording and translation are outside this task.

## Files

- `dashboard.drbastaninejad.com/app/Services/Recording/RecordingService.php`
- `RecordingRepository.php`, `RecordingAuthority.php`, `RecordingStorage.php`,
  `RecordingRuntime.php`, and `RecordingError.php` in the same directory
- `dashboard.drbastaninejad.com/tests/recording-contracts.php`
- `dashboard.drbastaninejad.com/tests/run-recording-contracts.mjs` (optional,
  offline runner for an already installed PHP WASM runtime)

No dependency is added to the application. PSR-4 namespace is
`App\Services\Recording`.

## Authority and consent

Every service call uses `{clinicId, patientId, actorId}` from trusted authenticated
server context. All are positive decimal strings; no missing-clinic fallback.
The authority adapter must resolve current patient-clinic ownership, active staff
membership, explicit operation permission and a finite unexpired lease from
trusted data. A browser grant, `stored` flag, actor or clinic ID is never proof.

The conservative pilot is creator-only: another staff member is denied even if
otherwise authorized. This is conservative foundation behavior, not the final clinical-role policy.
Cross-staff delegation, super-admin behavior and final clinical review need a
separate approved policy. The service asks for distinct
`recording.record`, `recording.transcribe`, `recording.edit`, and
`recording.withdraw` permissions; this slice does not create or grant them.

Consent is loaded from persisted state, never created from a checkbox. It needs:

- Matching receipt ID, clinic, patient and recording actor
- Scope `recording_transcription`, active status, explicit `revokedAt: null`
- Current approved policy version, recorded timestamp no later than server time,
  and explicit expiry later than server time
- One to twenty opaque participant-attestation references

The adapter must establish what those participant references attest to, including
patient/guardian capacity, consent wording and required participants. The service
cannot determine consent validity from voice or infer consent from attendance.
Consent capture and its audit workflow are not implemented. The policy version is
mandatory and unknown policy, consent, permissions, storage, repository or pinned
provider configuration fail closed.

## Lifecycle and limits

`create` persists a ready session with a server ID and scope-bound request
idempotency key. An explicit `start` sets the server-authoritative start time.
`stop` fixes the expected chunk count and declared audio duration. Pauses do not
reset start time; all pause/interruption time counts against the three-hour wall
clock. The server has no pause/resume events because this slice limits elapsed
wall time independently of client recording status.

- Wall-clock and audio-duration maximum: 10,800,000 ms
- Each chunk: at most 2 MiB, 60,000 ms, known audio MIME type
- Session: at most 128 MiB and 720 chunks
- Client pending payload: 8 MiB, enforced by the existing frontend outbox
- Server pending bytes: no payload buffer exists in this service. The future
  ingress must enforce its own bounded stream/concurrency/quota limits.

A live chunk may not claim more audio than elapsed server time. At or after the
wall-clock deadline live chunk admission stops. `stop` may then clamp the stop
instant to the deadline; already captured chunks may drain afterward only within
that declared duration/count and while current consent/access remain valid. A
server timer is not proof that capture stopped: the real driver and worker must
also enforce the deadline. Zero-audio sessions should be withdrawn/cancelled;
empty sessions are not queued.

All times here are server epoch milliseconds. The frontend foundation uses a
single injected clock domain. A real adapter must establish the mapping; it must
not compare `performance.now()` directly with these epoch values.

## Chunk and manifest contract

`acceptChunk` accepts metadata only. It selects the same eleven identity fields
as the frontend outbox: patient/session, sequence, audio start/end, MIME, stream,
initialization sequence, byte length, SHA-256 and idempotency key. It ignores an
untrusted `stored` field. The storage adapter must independently look up the
immutable durable object by authorized scope/session/sequence, confirm a server-
computed checksum and byte length, and return matching metadata plus a private
opaque object ID. Object IDs are stripped from client acknowledgements.

An exact retry returns the same receipt. Changed digest **or metadata** on the
same sequence conflicts. Arrival order is unrestricted. Finalization requires
exactly every sequence from zero, contiguous audio-relative time, a complete
matching duration, session quota and valid stream boundaries. Each new stream
starts at its own initialization sequence and cannot be re-entered later. MIME
and initialization remain consistent within a stream.

Finalization atomically persists the canonical manifest and one draft-only job.
The canonical SHA-256 digest binds ordered chunk metadata and private object IDs.
Every later load revalidates the stored envelope, digest and safe job descriptor.
MediaRecorder fragments are not assumed independently playable. Metadata and raw
byte verification do not establish real audio duration or a valid codec.

## Queue and worker contract

The job is a persisted state contract inside the repository transaction, not a
side-effecting queue client. Its descriptor contains only session ID, manifest
hash, pinned provider/model identity, language, idempotency key and draft-only
flags. It contains no names, audio bytes, transcript or credentials. The queue
scheduler must bind the session ID to trusted scope; it cannot trust scope from a
message payload.

`claim` creates a 60-second lease and increments the attempt count. `renewLease`
checks current access, consent and configuration before extending an unexpired
lease. Expired leases may be reclaimed; old tokens cannot complete the job.
There are at most three attempts. `transient` failures retry after 30 seconds;
`invalid_media` and `provider_failed` exhaust attempts. Only those sanitized
codes are stored. No raw exception or patient text belongs in queue/log payloads.

Worker sequence:

1. Claim the scoped job and renew the lease during bounded work.
2. Call `authorizeMediaValidation` immediately before reading private objects for
   remux/decoder validation. This returns the private ordered manifest only to
   the authorized internal worker.
3. Remux streams and independently validate codec, actual duration, gaps and
   decoding. Persist a trusted attestation bound to the exact manifest hash.
4. Call `authorizeProcessing` immediately before each ASR stage. It requires that
   attestation and current consent/access/configuration/lease.
5. Run bounded, separately approved inference, then call `completeDraft`.
   The service rechecks consent/access, media proof and lease before admission.

There is no actual worker or attestation implementation here. A real worker must
stop work on revocation and monitor it during long operations. A returned stage
response cannot authorize an unbounded future read. Processes, remote providers,
object URLs, credentials, and revocation signalling remain separate adapter
obligations. No inference should run on the strained production VPS by default.
Provider configuration requires approved `name`, `version`, `model`, `revision`;
no model is installed, downloaded or invoked. Those values are preserved in every
draft. Changing a queued job's configured identity is rejected.

`withdraw` does not require live ASR/storage configuration or unexpired recording
consent. It does require current authorized withdrawal access, revokes the receipt,
cancels the session/job and sets a retention-action marker. Late worker results
are denied. The marker is **not proof of deletion**: actual object/process/backup
cleanup must follow approved retention policy. Other sessions sharing a revoked
receipt also fail future checks; the production cancellation coordinator must
propagate revocation to all affected jobs.

## Draft and edit contract

Drafts preserve pinned provider/model provenance, Persian language, raw ASR text,
audio timestamps, uncertainty (0..1 or null), source intervals, and anonymous
speaker ID or null. Overlap is allowed. All role/name assignments start unknown,
regardless of ASR hints. Raw text is separate from corrected text.

`completeDraft` is idempotent for the exact completed result and lease token;
a conflicting replay fails. Inputs and expanded serialized drafts are bounded
at 1 MiB; each text field is at most 20,000 UTF-8 bytes, names 200 bytes and there
are at most 20,000 segments. These byte limits are intentionally conservative
compared with the frontend JavaScript string-length limit. The codec/ASR adapter
must reconcile anonymous speakers across segmentation and must not infer doctor/
patient role from perceived voice, order or silence.

`editDraft` uses optimistic draft versioning and appends a complete new version.
Only one text correction or explicit manual speaker assignment is accepted per
edit. Server actor/time, previous version, target turn and edit kind are retained.
Original raw fields, previous versions and audit-history prefixes remain immutable.
Loaded draft/segment/history envelopes are allowlisted; persisted publication
flags or a tampered original result are rejected. Drafts remain `unreviewed` with
null classification. No edit is clinical approval.

## Frontend interchange: compatible pieces and explicit gaps

The optional synthetic comparison is pinned to frontend PR35 at
`bce113ce0f607f320d7f4ef65944ee2364331b96`:

- `session.js` Git blob `f811fbd92c1be802b5d0a943922ecb7320c4e811`
- `chunks.js` Git blob `0a7718a2de0297e04b11e7d4896837ffd2ae9ee5`

It runs real PHP service operations against synthetic repositories, emits their
receipts/draft, and checks them with the unchanged JavaScript helpers. It verifies
five outcomes, including deliberately incompatible pieces. Passing it **does not
mean the two slices plug together unchanged**.

1. **Compatible chunk acknowledgement:** The eleven camelCase identity fields and
   `stored: true` are directly accepted by `validateChunkReceipt`. Private object
   IDs are never returned in that receipt. Ordered public manifest fields agree
   after the server-only private object IDs are stripped by the comparison.
2. **Scope and naming:** Both contracts use camelCase string identifiers such as
   `clinicId`, `actorId`, `patientId`, `sessionId`, `policyVersion`, and epoch-like
   millisecond fields. Any database `clinic_id`/`actor_id`/`patient_id` or SQL time
   conversion belongs to the future repository. No PHP/database mapping exists.
   Service scope must be derived from the authenticated server identity, never
   copied from JavaScript. Creator-only access is provisional conservative policy.
3. **Stricter server consent and readiness:** JavaScript's `assertConsent` checks
   patient/actor, active status, policy token and recorded time. It does **not**
   enforce this PHP contract's clinic binding, expiry, explicit revocation field,
   approved policy registry or participant references. The interchange test
   demonstrates that difference. Server `create` returns a session receipt, not a
   frontend `PREPARE` grant. A future authorized response adapter must issue fresh
   scope/lease/readiness facts; the sample grant in the interchange test is only a
   fixture. It cannot be derived by setting browser booleans.
4. **Different jobs:** JavaScript produces a proposal with inline manifest and
   scope plus session/provider/version idempotency. PHP ignores that proposal,
   reconstructs a manifest from verified stored receipts and persists a hash-bound
   private queue descriptor. Its provider includes pinned model/revision. Only
   the server may create job identity; never submit the browser descriptor directly
   as a trusted durable job or substitute its idempotency key.
5. **Different draft lifecycle:** JavaScript `createDraft` is for raw ASR input,
   not a persisted server-draft loader. Applying it to this service's complete
   draft drops uncertainty, source intervals, server actor/time and persisted
   version history, and resets version/assignments. A new frontend read/edit
   adapter must preserve all server provenance and send optimistic versioned
   edits. The existing in-memory edit helpers are not persistence or audit history.

The transport, error-envelope/HTTP status mapping, consent capture, session
readiness response, clock mapping, server draft loader and edit synchronization
remain unimplemented. Never use this comparison as a production adapter.

## Required production adapter guarantees

These requirements are NOT established by the fake tests:

- Transaction isolation that serializes consent/policy/access revocation against
  session/chunk/job/draft writes, with fresh locked reads and rollback on failure.
  Authority and policy reads must join that boundary or use an equivalent
  revocation-safe design. Rechecking arrays alone does not prevent database races.
- Database uniqueness for scoped request IDs, globally unguessable session IDs,
  session+sequence, session+manifest+provider job identity, and draft version.
  `save` must use expected-version compare-and-swap and acknowledge only after a
  durable commit. An adapter failure must propagate; no successful fake fallback.
- Normalize the aggregate contract into bounded tables as appropriate. Do not
  deserialize unlimited untrusted objects or load unbounded draft history into a
  request. Choose history pagination/retention limits before integration.
- Private non-public immutable objects, encryption/key ownership, streaming
  checksum and quotas, capacity reservation, safe temporary files and cleanup of
  orphaned uploads. A mutable object cannot be covered by an old attestation.
- A truly durable transactional job/outbox plus supervised bounded workers,
  claim/reclaim/renewal and safe retry behavior. No in-request heavy inference.
- Preserve canonical field order for stored job/manifest/draft arrays, or normalize
  on reads to this contract. Strict equality is deliberate and fails closed.
- Authenticated, patient-scoped draft read/edit endpoints with no patient-portal
  exposure, separate approval policy, and safe rendering. Internal media-stage
  methods must never become general-purpose public read endpoints.
- Sanitized handling of adapter exceptions and correlation IDs. Never log audio,
  transcript, credentials, participant identities or raw upstream exceptions.
- Approved consent, permission, retention and deletion policy; production/live
  source reconciliation; security-reviewed origin microphone policy and binary
  ingress; real device recording, interruptions, crash recovery, endurance,
  throughput and clinician-reviewed Persian accuracy tests.

## Verification

From `dashboard.drbastaninejad.com` with PHP 8.1+:

```sh
php tests/recording-contracts.php
```

Optional existing-runtime runner (does not install anything):

```sh
PHP_WASM_RUNTIME_ROOT=/path/to/existing/php-runtime PHP_VERSION=8.1 node tests/run-recording-contracts.mjs
PHP_WASM_RUNTIME_ROOT=/path/to/existing/php-runtime PHP_VERSION=8.3 node tests/run-recording-contracts.mjs
PHP_WASM_RUNTIME_ROOT=/path/to/existing/php-runtime DRB_RECORDING_FRONTEND_ROOT=/path/to/PR35/app.drbastaninejad.com/Frontend node tests/recording-interchange.mjs
```

The deterministic suite includes scope/consent/config denial, expiry and revocation
after callbacks, full receipt identity, digest conflicts, out-of-order and missing
chunks, stream continuity, 128 MiB quota, a simulated 360-chunk three-hour session,
transaction failure rollback, lease/retry/cancellation, media gating, uncertain
speakers/overlap, append-only edits and malformed stored-state rejection. The
three-hour test advances a synthetic clock and uses metadata, not audio or a
three-hour microphone run. In-memory rollback tests do not prove real database
isolation, storage durability or production queue delivery.

A full backend PHPUnit run is unavailable in the selective safe-source workspace:
its application test tree, bootstrap, native PHP/Composer and backend dependency
installation are absent. This standalone suite does not substitute for it.
