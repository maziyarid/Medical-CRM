/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad
 * Provider-neutral, memory-only upload contract. No fetch/storage side effects.
 * A server must independently enforce every bound, digest, scope and consent check.
 */
import { assertGrant, assertConsent, fail, MAX_SESSION_MS } from './session.js';
export const MAX_CHUNK_BYTES = 2 * 1024 * 1024;
export const MAX_PENDING_BYTES = 8 * 1024 * 1024;
export const MAX_SESSION_BYTES = 128 * 1024 * 1024;
export const MAX_CHUNKS = 720;
export const TARGET_CHUNK_MS = 30_000;
const mimeTypes = new Set(['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/mp4']);
const opaque = value => typeof value === 'string' && /^[A-Za-z0-9_-]{1,128}$/.test(value);
const providerId = value => typeof value === 'string' && /^[A-Za-z0-9_.-]{1,128}$/.test(value);
const patient = value => typeof value === 'string' && /^[1-9]\d{0,15}$/.test(value);
const integer = value => Number.isSafeInteger(value) && value >= 0;
const key = chunk => `${chunk.sessionId}:${chunk.sequence}:${chunk.sha256}`;
function validShape(chunk) {
  return chunk && patient(chunk.patientId) && opaque(chunk.sessionId) && opaque(chunk.streamId) &&
    integer(chunk.sequence) && chunk.sequence < MAX_CHUNKS &&
    integer(chunk.initializationSequence) && chunk.initializationSequence <= chunk.sequence &&
    integer(chunk.startMs) && integer(chunk.endMs) && chunk.endMs > chunk.startMs &&
    chunk.endMs <= MAX_SESSION_MS && chunk.endMs - chunk.startMs <= 60_000 &&
    mimeTypes.has(chunk.mimeType) && integer(chunk.byteLength) && chunk.byteLength > 0 && chunk.byteLength <= MAX_CHUNK_BYTES;
}
function validDigest(chunk) { return typeof chunk.sha256 === 'string' && /^[0-9a-f]{64}$/.test(chunk.sha256) && chunk.idempotencyKey === key(chunk); }
export function validateChunkReceipt(metadata, receipt) {
  const fields = ['patientId', 'sessionId', 'sequence', 'startMs', 'endMs', 'mimeType', 'streamId', 'initializationSequence', 'byteLength', 'sha256', 'idempotencyKey'];
  if (!receipt || receipt.stored !== true || !validShape(metadata) || !validDigest(metadata) ||
      fields.some(field => receipt[field] !== metadata[field])) fail('INVALID_ACK');
  return Object.freeze({ ...metadata, stored: true });
}

export class ChunkOutbox {
  #scope; #pending = new Map(); #receipts = new Map(); #pendingBytes = 0; #totalBytes = 0; #flight = null; #discarded = false;
  constructor({ patientId, sessionId }) {
    if (!patient(patientId) || !opaque(sessionId)) fail('INVALID_SESSION');
    this.#scope = { patientId, sessionId };
  }
  get pendingBytes() { return this.#pendingBytes; }
  get receipts() { return [...this.#receipts.values()].map(receipt => ({ ...receipt })); }
  async enqueue(data, descriptor) {
    if (this.#discarded) fail('SESSION_DISCARDED');
    if (!(data instanceof Uint8Array) || !descriptor || typeof descriptor !== 'object') fail('INVALID_CHUNK');
    // Select fields explicitly: callers cannot override patient/session scope.
    const metadata = { ...this.#scope, sequence: descriptor.sequence, startMs: descriptor.startMs, endMs: descriptor.endMs,
      mimeType: descriptor.mimeType, streamId: descriptor.streamId, initializationSequence: descriptor.initializationSequence,
      byteLength: data.byteLength };
    if (!validShape(metadata)) fail('INVALID_CHUNK');
    if (this.#pending.has(metadata.sequence) || this.#receipts.has(metadata.sequence)) fail('DUPLICATE_SEQUENCE');
    if (this.#totalBytes + data.byteLength > MAX_SESSION_BYTES) fail('SESSION_SIZE_LIMIT');
    if (this.#pendingBytes + data.byteLength > MAX_PENDING_BYTES) fail('BACKPRESSURE');
    // Reserve synchronously before hashing so concurrent callbacks remain bounded.
    const entry = { data: new Uint8Array(data), metadata: null };
    this.#pending.set(metadata.sequence, entry); this.#pendingBytes += data.byteLength; this.#totalBytes += data.byteLength;
    try {
      const digest = await globalThis.crypto.subtle.digest('SHA-256', entry.data);
      if (this.#discarded) fail('SESSION_DISCARDED');
      metadata.sha256 = [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, '0')).join('');
      metadata.idempotencyKey = key(metadata); entry.metadata = Object.freeze(metadata);
      return { ...metadata };
    } catch (error) {
      if (this.#pending.delete(metadata.sequence)) { this.#pendingBytes -= data.byteLength; this.#totalBytes -= data.byteLength; }
      throw error;
    }
  }
  flush(upload) {
    if (this.#discarded) return Promise.reject(Object.assign(new Error('SESSION_DISCARDED'), { code: 'SESSION_DISCARDED' }));
    if (!this.#flight) this.#flight = this.#drain(upload).finally(() => { this.#flight = null; });
    return this.#flight;
  }
  async #drain(upload) {
    while (this.#pending.size) {
      const sequence = Math.min(...this.#pending.keys());
      const entry = this.#pending.get(sequence);
      if (!entry.metadata) return; // Hashing is still pending; caller flushes after enqueue resolves.
      const response = await upload({ ...entry.metadata }, new Uint8Array(entry.data));
      if (this.#discarded) fail('SESSION_DISCARDED');
      const receipt = validateChunkReceipt(entry.metadata, response);
      this.#receipts.set(sequence, receipt); this.#pending.delete(sequence); this.#pendingBytes -= entry.data.byteLength;
    }
  }
  discard() {
    this.#discarded = true;
    this.#pending.clear(); this.#receipts.clear(); this.#pendingBytes = 0; this.#totalBytes = 0;
    // The caller must abort any active request and cancel/delete server-side data.
    // Clearing RAM cannot revoke bytes already accepted by a server.
  }
}

export function buildManifest({ patientId, sessionId, chunks, expectedChunks, audioDurationMs }) {
  if (!patient(patientId) || !opaque(sessionId) || !Array.isArray(chunks) || !integer(expectedChunks) ||
      expectedChunks < 1 || expectedChunks > MAX_CHUNKS || chunks.length !== expectedChunks ||
      !integer(audioDurationMs) || audioDurationMs < 1 || audioDurationMs > MAX_SESSION_MS) fail('INVALID_MANIFEST');
  const ordered = chunks.map(chunk => ({ ...chunk })).sort((a, b) => a.sequence - b.sequence);
  let endMs = 0; let totalBytes = 0; const streams = new Map(); let currentStream = null;
  for (let sequence = 0; sequence < ordered.length; sequence++) {
    const chunk = ordered[sequence];
    if (!validShape(chunk) || !validDigest(chunk) || chunk.stored !== true || chunk.patientId !== patientId ||
        chunk.sessionId !== sessionId || chunk.sequence !== sequence || chunk.startMs !== endMs) fail('INVALID_MANIFEST');
    if (chunk.streamId !== currentStream) {
      if (streams.has(chunk.streamId) || chunk.initializationSequence !== sequence) fail('INVALID_MANIFEST');
      streams.set(chunk.streamId, { initializationSequence: sequence, mimeType: chunk.mimeType }); currentStream = chunk.streamId;
    }
    const stream = streams.get(chunk.streamId);
    if (stream.initializationSequence !== chunk.initializationSequence || stream.mimeType !== chunk.mimeType) fail('INVALID_MANIFEST');
    endMs = chunk.endMs; totalBytes += chunk.byteLength;
  }
  if (endMs !== audioDurationMs || totalBytes > MAX_SESSION_BYTES) fail('INVALID_MANIFEST');
  return { schemaVersion: 1, patientId, sessionId, chunks: ordered, totalBytes, audioDurationMs,
    containerHandling: 'ordered-remux-required' };
}

// Produces a descriptor, never an actual queue submission or clinical note.
export function createTranscriptionJob({ session, manifest, provider, at }) {
  if (!session || session.status !== 'stopped') fail('SESSION_NOT_FINAL');
  if (!integer(at) || !integer(session.lastEventAt) || at < session.lastEventAt) fail('INVALID_TIME');
  assertGrant(session.grant, session, at);
  assertConsent(session.consent, session, session.grant, at);
  if (!provider || provider.configured !== true || !providerId(provider.name) || !providerId(provider.version)) fail('TRANSCRIPTION_UNCONFIGURED');
  if (!manifest || manifest.sessionId !== session.sessionId || manifest.patientId !== session.patientId ||
      manifest.audioDurationMs !== session.recordedMs) fail('INVALID_MANIFEST');
  const validated = buildManifest({ ...manifest, expectedChunks: manifest.chunks?.length });
  return { schemaVersion: 1, kind: 'transcribe', sessionId: session.sessionId, patientId: session.patientId,
    clinicId: session.grant.clinicId, actorId: session.grant.actorId, consentReceiptId: session.consent.id,
    idempotencyKey: `${session.sessionId}:${provider.name}:${provider.version}:v1`, provider: { name: provider.name, version: provider.version },
    manifest: validated, language: 'fa', outputStatus: 'draft', autoWriteEmr: false };
}
