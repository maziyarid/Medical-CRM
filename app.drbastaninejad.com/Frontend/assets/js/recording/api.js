/* MAZ//ID · DrB private recording API adapter. */
import { getToken } from '../../shared/api.js';

const BASE = (typeof window !== 'undefined' && window.__DRB_STAFF_API_BASE__)
  ? String(window.__DRB_STAFF_API_BASE__).replace(/\/+$/, '')
  : 'https://dashboard.drbastaninejad.com/api/v1';

function authHeaders(extra = {}) {
  const token = getToken('staff');
  return {
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...extra,
  };
}

async function parse(response) {
  let data = null;
  try { data = await response.json(); } catch (_) {}
  if (!response.ok || !data || data.ok === false) {
    const message = data?.errors?.[0]?.message || 'خطا در سرویس ضبط جلسه';
    throw Object.assign(new Error(message), {
      code: response.status === 401 ? 'UNAUTHORIZED' : response.status === 403 ? 'FORBIDDEN' : 'API_ERROR',
      status: response.status,
      response: data,
    });
  }
  return data.data;
}

async function json(method, path, body = null) {
  const response = await fetch(`${BASE}${path}`, {
    method,
    headers: authHeaders({ 'Content-Type': 'application/json' }),
    body: body === null ? undefined : JSON.stringify(body),
  });
  return parse(response);
}

export const RecordingApi = {
  readiness(patientId) {
    return json('GET', `/patients/${encodeURIComponent(patientId)}/recording-sessions/readiness`);
  },
  create(patientId, body) {
    return json('POST', `/patients/${encodeURIComponent(patientId)}/recording-sessions`, body);
  },
  async uploadChunk(patientId, sessionId, sequence, blob, meta) {
    const form = new FormData();
    form.append('audio', blob, `segment-${String(sequence).padStart(4, '0')}.webm`);
    for (const [key, value] of Object.entries(meta)) form.append(key, String(value));
    const response = await fetch(
      `${BASE}/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}/chunks/${sequence}`,
      { method: 'POST', headers: authHeaders(), body: form }
    );
    return parse(response);
  },
  finalize(patientId, sessionId, expectedChunks, audioDurationMs) {
    return json('POST',
      `/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}/finalize`,
      { expected_chunks: expectedChunks, audio_duration_ms: audioDurationMs }
    );
  },
  show(patientId, sessionId) {
    return json('GET',
      `/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}`
    );
  },
  withdraw(patientId, sessionId) {
    return json('POST',
      `/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}/withdraw`,
      {}
    );
  },
  patchSegment(patientId, sessionId, segmentId, patch) {
    return json('PATCH',
      `/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}/segments/${encodeURIComponent(segmentId)}`,
      patch
    );
  },
  approve(patientId, sessionId, expectedVersion) {
    return json('POST',
      `/patients/${encodeURIComponent(patientId)}/recording-sessions/${encodeURIComponent(sessionId)}/approve`,
      { expected_version: expectedVersion }
    );
  },
};

export async function sha256Hex(blob) {
  const digest = await crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
  return [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, '0')).join('');
}
