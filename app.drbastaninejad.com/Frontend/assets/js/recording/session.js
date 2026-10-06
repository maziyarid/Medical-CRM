/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad
 * Pure state/contract helpers. Browser gates are NOT server authorization.
 * No microphone, network, storage or clinical-record side effects.
 */
export const MAX_SESSION_MS = 10_800_000;
const active = ['recording', 'paused', 'interrupted'];
const identity = value => typeof value === 'string' && /^[1-9]\d{0,15}$/.test(value);
const opaqueId = value => typeof value === 'string' && /^[A-Za-z0-9_-]{1,128}$/.test(value);
const providerId = value => typeof value === 'string' && /^[A-Za-z0-9_.-]{1,128}$/.test(value);
const number = value => Number.isSafeInteger(value) && value >= 0;
export function fail(code) { throw Object.assign(new Error(code), { code }); }

export function assertGrant(grant, session, at) {
  if (!number(at)) fail('INVALID_TIME');
  if (!grant || grant.patientId !== session.patientId || grant.sessionId !== session.sessionId ||
      !identity(grant.clinicId) || !identity(grant.actorId) || grant.canRecord !== true ||
      grant.privateStorageReady !== true || grant.queueReady !== true ||
      !number(grant.expiresAt) || grant.expiresAt <= at) fail('ACCESS_DENIED');
}
export function assertConsent(consent, session, grant, at) {
  if (!consent || !opaqueId(consent.id) || consent.patientId !== session.patientId ||
      consent.actorId !== grant.actorId || consent.scope !== 'recording_transcription' ||
      consent.status !== 'active' || !opaqueId(consent.policyVersion) ||
      !number(consent.recordedAt) || consent.recordedAt > at) fail('CONSENT_REQUIRED');
}
export function createSession({ patientId, sessionId }) {
  if (!identity(patientId) || !opaqueId(sessionId)) fail('INVALID_SESSION');
  return { patientId, sessionId, status: 'idle', grant: null, consent: null, startedAt: null,
    activeSince: null, recordedMs: 0, lastEventAt: 0, reason: null };
}
export function elapsedMs(state, at) {
  if (!number(at) || at < state.lastEventAt) fail('INVALID_TIME');
  if (state.activeSince === null) return state.recordedMs;
  const until = Math.min(at, state.startedAt + MAX_SESSION_MS, state.grant.expiresAt);
  return state.recordedMs + Math.max(0, until - state.activeSince);
}
export function transition(state, event) {
  const { type, at } = event;
  if (!number(at) || at < state.lastEventAt) fail('INVALID_TIME');
  const allowed = {
    PREPARE: ['idle'], START: ['ready'], PAUSE: ['recording'], RESUME: ['paused', 'interrupted'],
    INTERRUPT: ['recording'], STOP: active, TICK: ['ready', ...active, 'stopped', 'discarded', 'denied'],
    WITHDRAW_CONSENT: ['ready', ...active, 'stopped'], REVOKE_ACCESS: ['ready', ...active, 'stopped'],
  };
  if (!allowed[type]?.includes(state.status)) fail('INVALID_TRANSITION');
  const next = { ...state, lastEventAt: at };
  const settle = () => { next.recordedMs = elapsedMs(state, at); next.activeSince = null; };
  if (type === 'WITHDRAW_CONSENT' || type === 'REVOKE_ACCESS') {
    settle();
    return { ...next, status: type === 'WITHDRAW_CONSENT' ? 'discarded' : 'denied',
      consent: null, grant: null, reason: type === 'WITHDRAW_CONSENT' ? 'consent_withdrawn' : 'access_denied' };
  }
  if (type === 'PREPARE') {
    assertGrant(event.grant, state, at);
    assertConsent(event.consent, state, event.grant, at);
    return { ...next, status: 'ready', grant: Object.freeze({ ...event.grant }), consent: Object.freeze({ ...event.consent }) };
  }
  if (state.status === 'ready' || active.includes(state.status)) {
    const deadline = state.startedAt === null ? Infinity : state.startedAt + MAX_SESSION_MS;
    if (at >= state.grant.expiresAt && state.grant.expiresAt <= deadline) {
      settle();
      return { ...next, status: 'denied', grant: null, consent: null, reason: 'access_expired' };
    }
    if (at >= deadline) {
      settle();
      return { ...next, status: 'stopped', reason: 'duration_limit' };
    }
    assertGrant(state.grant, state, at);
    assertConsent(state.consent, state, state.grant, at);
  }
  if (type === 'START') return { ...next, status: 'recording', startedAt: at, activeSince: at };
  if (type === 'RESUME') return { ...next, status: 'recording', activeSince: at, reason: null };
  if (type === 'PAUSE' || type === 'STOP' || type === 'INTERRUPT') {
    if (type === 'INTERRUPT' && !['network', 'backpressure', 'device_lost', 'hidden', 'clock_gap'].includes(event.reason)) fail('INVALID_INTERRUPTION');
    settle();
    return { ...next, status: { PAUSE: 'paused', STOP: 'stopped', INTERRUPT: 'interrupted' }[type], reason: event.reason || null };
  }
  return next;
}

export const unconfiguredProvider = Object.freeze({
  async transcribe() { return { ok: false, code: 'TRANSCRIPTION_UNCONFIGURED' }; },
});

// Drafts intentionally have no conversion to EMR or finalization method.
export function createDraft(result) {
  if (!result || !opaqueId(result.sessionId) || !number(result.audioDurationMs) ||
      result.audioDurationMs > MAX_SESSION_MS || !result.provider ||
      !providerId(result.provider.name) || !providerId(result.provider.version) ||
      !Array.isArray(result.segments) || result.segments.length > 20_000) fail('INVALID_TRANSCRIPT');
  const ids = new Set();
  let previousStart = 0;
  const segments = result.segments.map(segment => {
    if (!opaqueId(segment.id) || ids.has(segment.id) || !number(segment.startMs) ||
        !number(segment.endMs) || segment.startMs < previousStart || segment.endMs <= segment.startMs ||
        segment.endMs > result.audioDurationMs || typeof segment.text !== 'string' || segment.text.length > 20_000 ||
        !(segment.speakerId === null || opaqueId(segment.speakerId))) fail('INVALID_TRANSCRIPT');
    ids.add(segment.id); previousStart = segment.startMs;
    return { id: segment.id, startMs: segment.startMs, endMs: segment.endMs, speakerId: segment.speakerId,
      rawText: segment.text, text: segment.text, speaker: { role: 'unknown', name: '', source: 'unassigned' } };
  });
  return { sessionId: result.sessionId, audioDurationMs: result.audioDurationMs,
    provider: { ...result.provider }, version: 1, status: 'draft', reviewStatus: 'unreviewed',
    classification: null, segments, history: [] };
}
function changeSegment(draft, segmentId, actorId, update, kind) {
  if (!identity(actorId) || draft.status !== 'draft' || !draft.segments.some(segment => segment.id === segmentId)) fail('INVALID_ASSIGNMENT');
  return { ...draft, version: draft.version + 1, reviewStatus: 'unreviewed',
    segments: draft.segments.map(segment => segment.id === segmentId ? { ...segment, ...update } : segment),
    history: [...draft.history, { version: draft.version + 1, segmentId, actorId, kind, previousVersion: draft.version }] };
}
export function editSegment(draft, segmentId, text, actorId) {
  if (typeof text !== 'string' || text.length > 20_000) fail('INVALID_TRANSCRIPT');
  return changeSegment(draft, segmentId, actorId, { text }, 'text_correction');
}
export function assignSpeaker(draft, segmentId, { role, name, actorId }) {
  if (!['doctor', 'patient', 'other', 'unknown'].includes(role) || typeof name !== 'string' ||
      name.length > 200 || (role !== 'unknown' && !name.trim())) fail('INVALID_ASSIGNMENT');
  return changeSegment(draft, segmentId, actorId, { speaker: { role, name: name.trim(), source: 'manual' } }, 'speaker_assignment');
}
