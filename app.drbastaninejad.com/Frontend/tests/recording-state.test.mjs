/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad — synthetic fixtures only */
import test from 'node:test';
import assert from 'node:assert/strict';
import { createSession, transition, elapsedMs, MAX_SESSION_MS, unconfiguredProvider, createDraft, editSegment, assignSpeaker } from '../assets/js/recording/session.js';

const receipt = { id: 'consent-synthetic', patientId: '42', actorId: '9', scope: 'recording_transcription', status: 'active', policyVersion: 'pilot-v1', recordedAt: 0 };
const grant = { patientId: '42', sessionId: 'session-synthetic', clinicId: '3', actorId: '9', canRecord: true, privateStorageReady: true, queueReady: true, expiresAt: 20_000_000 };
function initial() { return createSession({ patientId: '42', sessionId: 'session-synthetic' }); }
function ready() { return transition(initial(), { type: 'PREPARE', at: 0, grant, consent: receipt }); }
function recording() { return transition(ready(), { type: 'START', at: 1000 }); }
const code = value => error => error.code === value;

test('cannot start without a persisted consent receipt and scoped readiness', () => {
  assert.throws(() => transition(initial(), { type: 'START', at: 0 }), code('INVALID_TRANSITION'));
  assert.throws(() => transition(initial(), { type: 'PREPARE', at: 0, grant }), code('CONSENT_REQUIRED'));
});
for (const [label, patch] of Object.entries({ patient: { patientId: '43' }, session: { sessionId: 'other' }, clinic: { clinicId: null }, actor: { actorId: '' }, role: { canRecord: false }, storage: { privateStorageReady: false }, queue: { queueReady: false }, expiry: { expiresAt: 0 } })) {
  test(`rejects invalid authorization: ${label}`, () => assert.throws(() => transition(initial(), { type: 'PREPARE', at: 0, grant: { ...grant, ...patch }, consent: receipt }), code('ACCESS_DENIED')));
}
test('receipt must be active, scoped, identifiable and for the same patient and actor', () => {
  for (const patch of [{ status: 'withdrawn' }, { patientId: '43' }, { actorId: '10' }, { scope: 'generic' }, { id: '' }, { policyVersion: '' }]) {
    assert.throws(() => transition(initial(), { type: 'PREPARE', at: 0, grant, consent: { ...receipt, ...patch } }), code('CONSENT_REQUIRED'));
  }
});
test('start, pause and explicit resume exclude paused time and do not mutate prior state', () => {
  const first = recording();
  const paused = transition(first, { type: 'PAUSE', at: 31_000 });
  assert.equal(elapsedMs(paused, 61_000), 30_000);
  const resumed = transition(paused, { type: 'RESUME', at: 61_000 });
  const stopped = transition(resumed, { type: 'STOP', at: 91_000 });
  assert.equal(stopped.status, 'stopped');
  assert.equal(elapsedMs(stopped, 999_000), 60_000);
  assert.equal(first.status, 'recording');
  assert.equal(first.recordedMs, 0);
});
test('repeated start/resume and stale timestamps are rejected', () => {
  assert.throws(() => transition(recording(), { type: 'START', at: 1001 }), code('INVALID_TRANSITION'));
  assert.throws(() => transition(recording(), { type: 'RESUME', at: 1001 }), code('INVALID_TRANSITION'));
  assert.throws(() => transition(recording(), { type: 'TICK', at: 999 }), code('INVALID_TIME'));
});
for (const reason of ['network', 'backpressure', 'device_lost', 'hidden', 'clock_gap']) {
  test(`interruption ${reason} requires explicit resume`, () => {
    const state = transition(recording(), { type: 'INTERRUPT', reason, at: 11_000 });
    assert.equal(state.status, 'interrupted');
    assert.equal(transition(state, { type: 'TICK', at: 21_000 }).status, 'interrupted');
    assert.equal(transition(state, { type: 'RESUME', at: 31_000 }).status, 'recording');
    assert.equal(elapsedMs(state, 999_000), 10_000);
  });
}
test('three hours of simulated recording stops at the exact wall-clock cap', () => {
  let state = recording();
  for (let at = 31_000; at < 1000 + MAX_SESSION_MS; at += 30_000) state = transition(state, { type: 'TICK', at });
  state = transition(state, { type: 'TICK', at: 1000 + MAX_SESSION_MS + 5000 });
  assert.equal(state.status, 'stopped');
  assert.equal(state.reason, 'duration_limit');
  assert.equal(elapsedMs(state, 99_000_000), 10_800_000);
  assert.throws(() => transition(state, { type: 'RESUME', at: 20_000_000 }), code('INVALID_TRANSITION'));
});
test('pause does not extend the three-hour wall-clock limit', () => {
  const paused = transition(recording(), { type: 'PAUSE', at: 31_000 });
  const stopped = transition(paused, { type: 'TICK', at: MAX_SESSION_MS + 1000 });
  assert.equal(stopped.status, 'stopped');
  assert.equal(stopped.recordedMs, 30_000);
});
test('consent withdrawal discards the session and prevents resuming', () => {
  const state = transition(recording(), { type: 'WITHDRAW_CONSENT', at: 10_000 });
  assert.equal(state.status, 'discarded');
  assert.equal(state.consent, null);
  assert.equal(state.grant, null);
  assert.throws(() => transition(state, { type: 'RESUME', at: 11_000 }), code('INVALID_TRANSITION'));
});
test('access revocation and expiration stop the session fail closed', () => {
  assert.equal(transition(recording(), { type: 'REVOKE_ACCESS', at: 10_000 }).status, 'denied');
  const short = transition(initial(), { type: 'PREPARE', at: 0, grant: { ...grant, expiresAt: 2000 }, consent: receipt });
  const state = transition(transition(short, { type: 'START', at: 1000 }), { type: 'TICK', at: 3000 });
  assert.equal(state.status, 'denied');
  assert.equal(state.recordedMs, 1000);
});
test('unconfigured provider never fabricates a transcript', async () => {
  assert.deepEqual(await unconfiguredProvider.transcribe(), { ok: false, code: 'TRANSCRIPTION_UNCONFIGURED' });
});
const result = { sessionId: 'session-synthetic', audioDurationMs: 5000, provider: { name: 'synthetic-test', version: '1' }, segments: [{ id: 'seg-1', startMs: 100, endMs: 1100, speakerId: null, text: 'متن آزمایشی' }, { id: 'seg-2', startMs: 1200, endMs: 4000, speakerId: 'speaker-1', text: 'فقط داده ساختگی' }] };
test('timestamped transcript starts unreviewed and speaker identities are never inferred', () => {
  const draft = createDraft(result);
  assert.equal(draft.status, 'draft');
  assert.equal(draft.reviewStatus, 'unreviewed');
  assert.equal(draft.segments[0].speaker.role, 'unknown');
  assert.equal(draft.segments[1].speaker.role, 'unknown');
  assert.equal(draft.classification, null);
});
test('manual speaker assignment and text edits preserve original evidence and create versions', () => {
  const before = createDraft(result);
  const assigned = assignSpeaker(before, 'seg-1', { role: 'doctor', name: 'پزشک آزمایشی', actorId: '9' });
  const edited = editSegment(assigned, 'seg-1', 'اصلاح آزمایشی', '9');
  assert.equal(edited.version, 3);
  assert.equal(edited.segments[0].rawText, 'متن آزمایشی');
  assert.equal(edited.segments[0].text, 'اصلاح آزمایشی');
  assert.equal(edited.segments[0].speaker.source, 'manual');
  assert.equal(before.segments[0].speaker.role, 'unknown');
  assert.equal(edited.reviewStatus, 'unreviewed');
  assert.equal(edited.history.length, 2);
});
test('malformed and out-of-range transcript turns are rejected', () => {
  for (const patch of [{ startMs: -1 }, { endMs: 9000 }, { endMs: 99 }, { text: 42 }, { id: '' }]) assert.throws(() => createDraft({ ...result, segments: [{ ...result.segments[0], ...patch }] }), code('INVALID_TRANSCRIPT'));
  assert.throws(() => createDraft({ ...result, segments: [result.segments[0], result.segments[0]] }), code('INVALID_TRANSCRIPT'));
  assert.throws(() => assignSpeaker(createDraft(result), 'seg-1', { role: 'doctor', name: '', actorId: '9' }), code('INVALID_ASSIGNMENT'));
});

test('provider version provenance accepts semantic versions', () => {
  assert.equal(createDraft({ ...result, provider: { name: 'faster-whisper', version: '1.2.0' } }).provider.version, '1.2.0');
});
