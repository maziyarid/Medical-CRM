# Recording service contract verification

Source-only RPH-137 slice, October 5, 2026. No production system or patient data
was used. Baseline Medical-CRM `6e5e6fa9daf2faa3571043c7df237fdb3b8a4a3c`;
frontend comparison PR35 `bce113ce0f607f320d7f4ef65944ee2364331b96`.

## Results

- 55 standalone PHP behavioral tests pass on PHP WASM 8.1.34 and 8.3.33.
- Five synthetic PHP-to-JavaScript interchange comparisons pass against the exact
  reviewed PR35 session/chunk helper blobs. They also expose and document the
  consent, readiness, job identity and persisted-draft adapter gaps.
- Existing frontend recorder suite passes all 48 tests unchanged.
- Independent review reran the PHP suite on both versions and nine extra synthetic
  invalid-state/expiry cases. It found no remaining material blocker in this slice.
- The full existing backend PHPUnit suite was attempted but could not start:
  native `php` is absent (exit 127). The selective workspace also lacks its
  original backend test tree/bootstrap and installed PHPUnit dependencies.

## Independent review changes verified

The review resulted in regression coverage and fixes for PHP 8.1 compatibility,
unsafe persisted job/draft fields, stored manifest/result integrity, malformed
job states, immutable transcript source/history fields and authorization/consent
expiry at creation/retry and worker lease boundaries. Stage gating and expanded
draft size limits were also exercised. All data and fault cases were synthetic.

## What these tests do not establish

No SQL/storage implementation, actual locking or concurrent database race,
production queue delivery, external worker cancellation, audio capture,
container/codec/remux behavior, ASR accuracy/speed, clinical review process,
retention/deletion execution, browser device support or three-hour real-device
endurance is verified. The three-hour case simulates 360 small metadata chunks
and a clock; it does not record audio. There is no deployed feature and no claim
that the source snapshot matches current live hotfixes.

See `RECORDING_SERVICE_CONTRACTS.md` for exact dependency, policy, transport,
private-storage and integration acceptance requirements before any pilot.
