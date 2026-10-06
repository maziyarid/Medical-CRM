/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad — synthetic bytes only */
import test from 'node:test';
import assert from 'node:assert/strict';
import { ChunkOutbox, buildManifest, createTranscriptionJob, MAX_CHUNK_BYTES, MAX_PENDING_BYTES, validateChunkReceipt } from '../assets/js/recording/chunks.js';
import { createSession, transition } from '../assets/js/recording/session.js';
const bytes = new TextEncoder().encode('synthetic audio bytes, not a playable recording');
const input = (sequence = 0, extra = {}) => ({ sequence, startMs: sequence * 30_000, endMs: (sequence + 1) * 30_000, mimeType: 'audio/webm;codecs=opus', streamId: 'stream-1', initializationSequence: 0, ...extra });
const receiptFor = metadata => ({ ...metadata, stored: true });
const outbox = () => new ChunkOutbox({ patientId: '42', sessionId: 'session-synthetic' });
const code = expected => error => error.code === expected;
async function receipts(count = 2) {
  const box = outbox(); for (let i = 0; i < count; i++) await box.enqueue(bytes, input(i));
  await box.flush(async (metadata) => receiptFor(metadata)); return box.receipts;
}
test('digest binds the exact bytes and sequence to the patient/session', async () => {
  const box = outbox(); const metadata = await box.enqueue(new TextEncoder().encode('abc'), input());
  assert.equal(metadata.sha256, 'ba7816bf8f01cfea414140de5dae2223b00361a396177a9cb410ff61f20015ad');
  assert.equal(metadata.byteLength, 3); assert.equal(metadata.patientId, '42');
  assert.equal(metadata.idempotencyKey, `session-synthetic:0:${metadata.sha256}`);
});
test('retry keeps identical idempotency identity and bytes until verified durable acknowledgement', async () => {
  const box = outbox(); await box.enqueue(bytes, input()); const seen = [];
  await assert.rejects(box.flush(async (metadata, data) => { seen.push({ metadata, data: [...data] }); throw new Error('offline'); }), /offline/);
  assert.equal(box.pendingBytes, bytes.byteLength); assert.equal(box.receipts.length, 0);
  await box.flush(async (metadata, data) => { seen.push({ metadata, data: [...data] }); return receiptFor(metadata); });
  assert.deepEqual(seen[0], seen[1]); assert.equal(box.pendingBytes, 0); assert.equal(box.receipts.length, 1);
});
test('acknowledgements must match scope, exact digest, size, timing and persisted state', async () => {
  const box = outbox(); const metadata = await box.enqueue(bytes, input());
  for (const patch of [{ sha256: '0'.repeat(64) }, { sessionId: 'other' }, { patientId: '99' }, { sequence: 4 }, { stored: false }, { byteLength: 9 }, { endMs: 8 }, { streamId: 'other' }]) {
    assert.throws(() => validateChunkReceipt(metadata, { ...receiptFor(metadata), ...patch }), code('INVALID_ACK'));
  }
  await assert.rejects(box.flush(async metadata => ({ ...metadata, stored: false })), code('INVALID_ACK'));
  assert.equal(box.pendingBytes, bytes.byteLength);
});
test('mutating source or transport bytes cannot alter bytes kept for retry', async () => {
  const source = new Uint8Array([1, 2, 3]); const box = outbox(); await box.enqueue(source, input()); source[0] = 9;
  await assert.rejects(box.flush(async (metadata, data) => { assert.equal(data[0], 1); data[0] = 8; throw new Error('offline'); }));
  await box.flush(async (metadata, data) => { assert.equal(data[0], 1); return receiptFor(metadata); });
});
test('concurrent flush calls use a single in-flight upload', async () => {
  const box = outbox(); await box.enqueue(bytes, input()); let calls = 0; let release;
  const send = async metadata => { calls++; await new Promise(resolve => { release = resolve; }); return receiptFor(metadata); };
  const first = box.flush(send); const second = box.flush(send); assert.equal(calls, 1); release(); await Promise.all([first, second]); assert.equal(calls, 1);
});
test('bounded outbox rejects overlarge, zero-sized and overcapacity chunks', async () => {
  const box = outbox();
  await assert.rejects(box.enqueue(new Uint8Array(MAX_CHUNK_BYTES + 1), input()), code('INVALID_CHUNK'));
  await assert.rejects(box.enqueue(new Uint8Array(), input()), code('INVALID_CHUNK'));
  for (let i = 0; i < MAX_PENDING_BYTES / MAX_CHUNK_BYTES; i++) await box.enqueue(new Uint8Array(MAX_CHUNK_BYTES), input(i));
  await assert.rejects(box.enqueue(bytes, input(4)), code('BACKPRESSURE'));
  assert.equal(box.pendingBytes, MAX_PENDING_BYTES);
});
test('concurrent enqueue cannot exceed the pending-byte bound', async () => {
  const box = outbox(); const tasks = Array.from({ length: 5 }, (_, i) => box.enqueue(new Uint8Array(MAX_CHUNK_BYTES), input(i)));
  const results = await Promise.allSettled(tasks); assert.equal(results.filter(x => x.status === 'fulfilled').length, 4); assert.equal(box.pendingBytes, MAX_PENDING_BYTES);
});
test('duplicate sequence cannot replace an existing chunk and discard clears pending bytes', async () => {
  const box = outbox(); await box.enqueue(bytes, input());
  await assert.rejects(box.enqueue(new Uint8Array([9]), input()), code('DUPLICATE_SEQUENCE'));
  box.discard(); assert.equal(box.pendingBytes, 0); assert.deepEqual(box.receipts, []);
  await assert.rejects(box.enqueue(bytes, input(1)), code('SESSION_DISCARDED'));
  await assert.rejects(box.flush(async () => {}), code('SESSION_DISCARDED'));
});
test('discard during upload ignores late acknowledgement and never queues the next chunk', async () => {
  const box = outbox(); await box.enqueue(bytes, input()); await box.enqueue(bytes, input(1)); let release; let count = 0;
  const task = box.flush(async metadata => { count++; await new Promise(resolve => { release = resolve; }); return receiptFor(metadata); });
  box.discard(); release(); await assert.rejects(task, code('SESSION_DISCARDED'));
  assert.equal(count, 1); assert.equal(box.pendingBytes, 0); assert.deepEqual(box.receipts, []);
});
test('reordered durable chunks build an ordered manifest preserving container headers', async () => {
  const chunks = await receipts(); const manifest = buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks: chunks.reverse(), expectedChunks: 2, audioDurationMs: 60_000 });
  assert.deepEqual(manifest.chunks.map(chunk => chunk.sequence), [0, 1]); assert.equal(manifest.chunks[1].initializationSequence, 0);
  assert.equal(manifest.containerHandling, 'ordered-remux-required');
});
test('missing, duplicate, altered, wrong-scope or discontinuous chunks prevent finalization', async () => {
  const chunks = await receipts(); const args = { patientId: '42', sessionId: 'session-synthetic', chunks, expectedChunks: 2, audioDurationMs: 60_000 };
  for (const replacement of [chunks.slice(1), [chunks[0], chunks[0]], [chunks[0], { ...chunks[1], patientId: '99' }], [chunks[0], { ...chunks[1], startMs: 31_000 }], [chunks[0], { ...chunks[1], initializationSequence: 8 }], [chunks[0], { ...chunks[1], sha256: 'bad' }]]) {
    assert.throws(() => buildManifest({ ...args, chunks: replacement }), code('INVALID_MANIFEST'));
  }
});
test('new container after interruption requires a matching initialization chunk', async () => {
  const chunks = await receipts();
  const valid = { ...chunks[1], streamId: 'stream-2', initializationSequence: 1 };
  assert.equal(buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks: [chunks[0], valid], expectedChunks: 2, audioDurationMs: 60_000 }).chunks.length, 2);
  assert.throws(() => buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks: [chunks[0], { ...valid, initializationSequence: 0 }], expectedChunks: 2, audioDurationMs: 60_000 }), code('INVALID_MANIFEST'));
});
test('simulated three-hour manifest contains exactly 360 acknowledged 30-second chunks', async () => {
  const chunks = await receipts(360); const manifest = buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks, expectedChunks: 360, audioDurationMs: 10_800_000 });
  assert.equal(manifest.chunks.length, 360); assert.equal(manifest.audioDurationMs, 10_800_000);
});
test('queue contract rejects unconfigured provider, missing authorization, active or withdrawn session', async () => {
  const manifest = buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks: await receipts(), expectedChunks: 2, audioDurationMs: 60_000 });
  const initial = createSession({ patientId: '42', sessionId: 'session-synthetic' });
  const grant = { patientId: '42', sessionId: 'session-synthetic', clinicId: '3', actorId: '9', canRecord: true, privateStorageReady: true, queueReady: true, expiresAt: 20_000_000 };
  const consent = { id: 'consent-synthetic', patientId: '42', actorId: '9', scope: 'recording_transcription', status: 'active', policyVersion: 'v1', recordedAt: 0 };
  const recording = transition(transition(initial, { type: 'PREPARE', at: 0, grant, consent }), { type: 'START', at: 0 });
  const session = transition(recording, { type: 'STOP', at: 60_000 });
  const args = { session, manifest, at: 61_000, provider: { name: 'synthetic-test', version: '1', configured: true } };
  assert.throws(() => createTranscriptionJob({ ...args, provider: null }), code('TRANSCRIPTION_UNCONFIGURED'));
  assert.throws(() => createTranscriptionJob({ ...args, session: recording }), code('SESSION_NOT_FINAL'));
  assert.throws(() => createTranscriptionJob({ ...args, session: transition(session, { type: 'WITHDRAW_CONSENT', at: 61_000 }) }), code('SESSION_NOT_FINAL'));
  assert.throws(() => createTranscriptionJob({ ...args, session: { ...session, grant: null } }), code('ACCESS_DENIED'));
  const job = createTranscriptionJob(args); assert.equal(job.kind, 'transcribe'); assert.equal(job.outputStatus, 'draft'); assert.equal(job.language, 'fa'); assert.equal(job.clinicId, '3'); assert.equal(job.autoWriteEmr, false);
});

test('authorization requires an explicit valid evaluation time', async () => {
  const { assertGrant } = await import('../assets/js/recording/session.js');
  const session = { patientId: '42', sessionId: 'session-synthetic' };
  const grant = { ...session, clinicId: '3', actorId: '9', canRecord: true, privateStorageReady: true, queueReady: true, expiresAt: 1000 };
  for (const at of [undefined, NaN, -1, Infinity, '0']) assert.throws(() => assertGrant(grant, session, at), code('INVALID_TIME'));
});
test('missing chunk descriptor fails with a typed contract error', async () => {
  await assert.rejects(outbox().enqueue(bytes, undefined), code('INVALID_CHUNK'));
});

test('queue revalidates complete persisted consent and rejects time reversal', async () => {
  const session = { patientId: '42', sessionId: 'session-synthetic', status: 'stopped', recordedMs: 30_000, lastEventAt: 30_000,
    grant: { patientId: '42', sessionId: 'session-synthetic', clinicId: '3', actorId: '9', canRecord: true, privateStorageReady: true, queueReady: true, expiresAt: 100_000 },
    consent: { id: 'receipt-1', patientId: '42', actorId: '9', scope: 'recording_transcription', status: 'active', policyVersion: 'v1', recordedAt: 0 } };
  const manifest = buildManifest({ patientId: '42', sessionId: 'session-synthetic', chunks: await receipts(1), expectedChunks: 1, audioDurationMs: 30_000 });
  const args = { session, manifest, provider: { name: 'synthetic', version: '1.2.0', configured: true }, at: 31_000 };
  for (const patch of [{ id: undefined }, { policyVersion: undefined }, { recordedAt: 32_000 }]) assert.throws(() => createTranscriptionJob({ ...args, session: { ...session, consent: { ...session.consent, ...patch } } }), code('CONSENT_REQUIRED'));
  assert.throws(() => createTranscriptionJob({ ...args, at: 0 }), code('INVALID_TIME'));
  assert.equal(createTranscriptionJob(args).consentReceiptId, 'receipt-1');
});
test('Node Buffer subclasses cannot alias bytes after hashing', async () => {
  const source = Buffer.from([1, 2, 3]); const box = outbox(); await box.enqueue(source, input()); source[0] = 9;
  await box.flush(async (metadata, data) => { assert.deepEqual([...data], [1, 2, 3]); return receiptFor(metadata); });
});
