/* MAZ//ID · Consent-gated browser recorder for RPH-137.
 * Capture can begin only after the visible UI confirms participant consent and
 * calls startByUserGesture() from a staff click/tap. There is no hidden or
 * automatic microphone activation and no local device persistence.
 */
import { RecordingApi, sha256Hex } from './api.js';

export const CAPTURE_SEGMENT_MS = 30000;
export const MAX_SESSION_MS = 10800000;
export const MAX_PENDING_BYTES = 8 * 1024 * 1024;
export const MAX_SESSION_BYTES = 128 * 1024 * 1024;

const wait = ms => new Promise(resolve => setTimeout(resolve, ms));
const clock = () => (globalThis.performance?.now ? performance.now() : Date.now());

export function preferredRecordingMime() {
  if (typeof MediaRecorder === 'undefined') return null;
  return [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/ogg;codecs=opus',
    'audio/mp4',
  ].find(type => MediaRecorder.isTypeSupported(type)) || '';
}

export class ConsentGatedRecorder {
  constructor({ patientId, sessionId, consentConfirmed, onState = () => {}, onProgress = () => {} }) {
    if (consentConfirmed !== true) throw new Error('CONSENT_REQUIRED');
    this.patientId = String(patientId);
    this.sessionId = String(sessionId);
    this.onState = onState;
    this.onProgress = onProgress;
    this.state = 'ready';
    this.stream = null;
    this.recorder = null;
    this.mime = null;
    this.sequence = 0;
    this.recordedMs = 0;
    this.totalBytes = 0;
    this.pendingBytes = 0;
    this.pending = [];
    this.uploading = false;
    this.failedUpload = null;
    this.startedAt = null;
    this.segmentStartedAt = null;
    this.segmentTimer = null;
    this.durationTimer = null;
    this.pieces = [];
    this.actionAfterStop = null;
    this.stopPromiseResolve = null;
  }

  emit(extra = {}) {
    this.onState({
      state: this.state,
      recordedMs: this.recordedMs,
      pendingBytes: this.pendingBytes,
      chunks: this.sequence,
      ...extra,
    });
  }

  async startByUserGesture() {
    if (this.state !== 'ready') throw new Error('INVALID_CAPTURE_STATE');
    if (!navigator.mediaDevices?.getUserMedia || typeof MediaRecorder === 'undefined') {
      throw new Error('MICROPHONE_UNSUPPORTED');
    }
    this.mime = preferredRecordingMime();
    if (this.mime === null) throw new Error('MICROPHONE_UNSUPPORTED');

    this.stream = await navigator.mediaDevices.getUserMedia({
      audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: true },
      video: false,
    });
    for (const track of this.stream.getAudioTracks()) {
      track.addEventListener('ended', () => {
        if (this.state === 'recording') this.interrupt('device_lost').catch(() => {});
      }, { once: true });
    }

    this.startedAt = clock();
    this.state = 'recording';
    this.emit();
    this.durationTimer = setInterval(() => {
      const wallMs = Math.max(0, clock() - this.startedAt);
      this.onProgress({ wallMs, recordedMs: this.recordedMs });
      if (wallMs >= MAX_SESSION_MS && ['recording', 'paused', 'interrupted'].includes(this.state)) {
        this.stop('duration_limit').catch(() => {});
      }
    }, 1000);
    await this.openIndependentSegment();
  }

  async openIndependentSegment() {
    if (this.state !== 'recording') return;
    this.pieces = [];
    this.actionAfterStop = null;
    this.segmentStartedAt = clock();
    const recorder = new MediaRecorder(this.stream, this.mime ? { mimeType: this.mime } : undefined);
    this.recorder = recorder;

    recorder.addEventListener('dataavailable', event => {
      if (event.data?.size) this.pieces.push(event.data);
    });
    recorder.addEventListener('stop', () => {
      this.finishIndependentSegment().catch(error => {
        this.failedUpload = error;
        this.state = 'interrupted';
        this.emit({ reason: 'capture_or_upload_error', error: error.message });
        if (this.stopPromiseResolve) {
          const done = this.stopPromiseResolve;
          this.stopPromiseResolve = null;
          done();
        }
      });
    }, { once: true });

    recorder.start();
    this.segmentTimer = setTimeout(() => {
      if (this.state === 'recording' && recorder.state === 'recording') this.requestSegmentStop('rotate');
    }, CAPTURE_SEGMENT_MS);
  }

  requestSegmentStop(nextAction) {
    if (this.segmentTimer) clearTimeout(this.segmentTimer);
    this.segmentTimer = null;
    this.actionAfterStop = nextAction;
    if (!this.recorder || this.recorder.state === 'inactive') return Promise.resolve();
    return new Promise(resolve => {
      this.stopPromiseResolve = resolve;
      this.recorder.stop();
    });
  }

  async finishIndependentSegment() {
    const action = this.actionAfterStop || 'interrupt';
    const elapsed = Math.max(1, Math.min(60000, Math.round(clock() - this.segmentStartedAt)));
    const blob = new Blob(this.pieces, { type: this.recorder?.mimeType || this.mime || 'audio/webm' });
    this.pieces = [];
    this.recorder = null;

    if (blob.size) {
      if (blob.size > 2 * 1024 * 1024) throw new Error('CHUNK_TOO_LARGE');
      if (this.totalBytes + blob.size > MAX_SESSION_BYTES) throw new Error('SESSION_SIZE_LIMIT');
      const startMs = this.recordedMs;
      const endMs = Math.min(MAX_SESSION_MS, startMs + elapsed);
      const chunk = {
        sequence: this.sequence++,
        blob,
        startMs,
        endMs,
        sha256: await sha256Hex(blob),
      };
      this.recordedMs = endMs;
      this.totalBytes += blob.size;
      this.pendingBytes += blob.size;
      this.pending.push(chunk);
      if (this.pendingBytes > MAX_PENDING_BYTES) throw new Error('BACKPRESSURE');
      this.pumpUploads().catch(error => {
        this.failedUpload = error;
        if (this.state === 'recording') this.interrupt('network').catch(() => {});
      });
    }

    if (action === 'rotate' && this.state === 'recording') {
      await this.openIndependentSegment();
    } else if (action === 'pause') {
      this.state = 'paused';
      this.emit();
    } else if (action === 'interrupt') {
      this.state = 'interrupted';
      this.emit();
    }

    if (this.stopPromiseResolve) {
      const done = this.stopPromiseResolve;
      this.stopPromiseResolve = null;
      done();
    }
  }

  async uploadOne(chunk) {
    let lastError;
    for (const delay of [0, 1000, 2500]) {
      if (delay) await wait(delay);
      try {
        const receipt = await RecordingApi.uploadChunk(
          this.patientId,
          this.sessionId,
          chunk.sequence,
          chunk.blob,
          { start_ms: chunk.startMs, end_ms: chunk.endMs, sha256: chunk.sha256 }
        );
        if (!receipt?.stored || receipt.sha256 !== chunk.sha256) throw new Error('INVALID_CHUNK_RECEIPT');
        return receipt;
      } catch (error) {
        lastError = error;
      }
    }
    throw lastError || new Error('CHUNK_UPLOAD_FAILED');
  }

  async pumpUploads() {
    if (this.uploading) return;
    this.uploading = true;
    try {
      while (this.pending.length) {
        const chunk = this.pending[0];
        await this.uploadOne(chunk);
        this.pending.shift();
        this.pendingBytes = Math.max(0, this.pendingBytes - chunk.blob.size);
        this.emit();
      }
      this.failedUpload = null;
    } finally {
      this.uploading = false;
    }
  }

  async ensureUploaded() {
    if (this.failedUpload) {
      this.failedUpload = null;
      await this.pumpUploads();
    }
    while (this.uploading) await wait(100);
    if (this.pending.length) await this.pumpUploads();
    if (this.pending.length || this.failedUpload) throw this.failedUpload || new Error('PENDING_UPLOADS');
  }

  async pauseByUserGesture() {
    if (this.state !== 'recording') throw new Error('INVALID_CAPTURE_STATE');
    this.state = 'pausing';
    this.emit();
    await this.requestSegmentStop('pause');
  }

  async resumeByUserGesture() {
    if (!['paused', 'interrupted'].includes(this.state)) throw new Error('INVALID_CAPTURE_STATE');
    await this.ensureUploaded();
    this.state = 'recording';
    this.emit();
    await this.openIndependentSegment();
  }

  async interrupt(reason) {
    if (this.state !== 'recording') return;
    this.state = 'interrupting';
    this.emit({ reason });
    await this.requestSegmentStop('interrupt');
  }

  async stop(reason = 'user') {
    if (!['recording', 'paused', 'interrupted', 'pausing', 'interrupting'].includes(this.state)) {
      throw new Error('INVALID_CAPTURE_STATE');
    }
    this.state = 'stopping';
    this.emit({ reason });
    if (this.recorder && this.recorder.state !== 'inactive') await this.requestSegmentStop('stop');
    await this.ensureUploaded();
    if (!this.sequence || !this.recordedMs) throw new Error('EMPTY_RECORDING');
    this.releaseMicrophone();
    const result = await RecordingApi.finalize(
      this.patientId, this.sessionId, this.sequence, this.recordedMs
    );
    this.state = 'queued';
    this.emit({ reason, result });
    return result;
  }

  async withdrawByUserGesture() {
    if (this.segmentTimer) clearTimeout(this.segmentTimer);
    if (this.recorder && this.recorder.state !== 'inactive') {
      this.actionAfterStop = 'withdraw';
      try { this.recorder.stop(); } catch (_) {}
    }
    this.pending = [];
    this.pendingBytes = 0;
    this.releaseMicrophone();
    await RecordingApi.withdraw(this.patientId, this.sessionId);
    this.state = 'withdrawn';
    this.emit();
  }

  releaseMicrophone() {
    if (this.durationTimer) clearInterval(this.durationTimer);
    this.durationTimer = null;
    if (this.stream) {
      for (const track of this.stream.getTracks()) {
        try { track.stop(); } catch (_) {}
      }
    }
    this.stream = null;
  }
}
