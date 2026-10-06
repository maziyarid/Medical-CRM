/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad */
import test from 'node:test';
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import { recorderView, formatDuration } from '../assets/js/recording/panel.js';
import { createSession } from '../assets/js/recording/session.js';
const state = createSession({ patientId: '42', sessionId: 'synthetic-session' });
test('unconfigured recorder disables every capture control even if UI consent is checked', () => {
  const view = recorderView({ ...state, status: 'ready' }, 0, false);
  assert.equal(view.code, 'unconfigured'); assert.equal(Object.values(view.controls).every(value => value === false), true);
});
test('recording and interruption views only enable valid explicit actions', () => {
  const recording = recorderView({ ...state, status: 'recording' }, 0, true);
  assert.equal(recording.controls.pause, true); assert.equal(recording.controls.start, false); assert.equal(recording.controls.stop, true);
  const interrupted = recorderView({ ...state, status: 'interrupted' }, 0, true);
  assert.equal(interrupted.controls.resume, true); assert.equal(interrupted.controls.pause, false);
});
test('timer has an unambiguous hour field through the three-hour cap', () => {
  assert.equal(formatDuration(0), '00:00:00'); assert.equal(formatDuration(3_600_000), '01:00:00'); assert.equal(formatDuration(10_800_000), '03:00:00');
});
test('patient detail includes accessible in-person recorder tab and fail-closed fallback', async () => {
  const source = await readFile(new URL('../pages/staff/patient-detail.html', import.meta.url), 'utf8');
  assert.match(source, /id="tab-6"[^>]*aria-selected="false"[\s\S]*?aria-controls="panel-6"/);
  assert.match(source, /id="panel-6"[^>]*role="tabpanel"[^>]*aria-labelledby="tab-6"/);
  assert.match(source, /id="session-recorder"/); assert.match(source, /mountRecorderPanel/);
  assert.match(source, /هنوز فعال نشده/);
});
