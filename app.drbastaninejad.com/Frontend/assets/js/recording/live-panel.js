/* MAZ//ID · Live super-admin in-person recorder panel for RPH-137.
 * Microphone access is requested only from the visible Start button after the
 * server has persisted consent and confirmed private storage/provider readiness.
 */
import { RecordingApi } from './api.js';
import { ConsentGatedRecorder } from './capture.js';
import { formatDuration } from './panel.js';

const TERMINAL = new Set(['review_required', 'approved', 'failed', 'withdrawn', 'cancelled']);
const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

function el(document, tag, text, className) {
  const node = document.createElement(tag);
  if (text !== undefined) node.textContent = text;
  if (className) node.className = className;
  return node;
}

function friendlyError(error) {
  const raw = String(error?.message || error || '');
  const map = {
    CONSENT_REQUIRED: 'ثبت رضایت برای شروع ضبط الزامی است.',
    MICROPHONE_UNSUPPORTED: 'مرورگر این دستگاه امکان ضبط مطمئن صدا را ندارد.',
    INVALID_CAPTURE_STATE: 'این عملیات در وضعیت فعلی ضبط قابل انجام نیست.',
    CHUNK_TOO_LARGE: 'حجم یک بخش صوتی از حد مجاز بیشتر شده است.',
    SESSION_SIZE_LIMIT: 'حجم کل جلسه از حد مجاز بیشتر شده است.',
    BACKPRESSURE: 'ارسال صدا از ضبط عقب افتاده است؛ ضبط متوقف شد تا داده از بین نرود.',
    EMPTY_RECORDING: 'صدای قابل استفاده‌ای برای این جلسه ثبت نشد.',
    PENDING_UPLOADS: 'هنوز بخشی از صدا به سرور نرسیده است.',
  };
  return map[raw] || raw || 'عملیات ضبط جلسه ناموفق بود.';
}

function blockerText(blockers = []) {
  const labels = {
    feature_disabled: 'قابلیت ضبط هنوز توسط مدیر سیستم فعال نشده است.',
    schema_not_ready: 'ساختار امن ذخیره‌سازی پایگاه داده آماده نیست.',
    private_storage_not_ready: 'فضای خصوصی ذخیره صدا آماده نیست.',
    retention_not_configured: 'سیاست نگهداری و حذف صدا تعیین نشده است.',
    consent_policy_not_configured: 'نسخه سیاست رضایت ضبط تعیین نشده است.',
    transcription_provider_not_ready: 'سرویس تبدیل گفتار هنوز آماده نیست.',
  };
  return blockers.map(code => labels[code] || code);
}

function statusLabel(status) {
  return ({
    ready: 'آماده شروع',
    recording: 'در حال ضبط',
    pausing: 'در حال توقف موقت',
    paused: 'متوقف موقت',
    interrupting: 'در حال توقف امن',
    interrupted: 'ضبط قطع شده؛ ادامه فقط با تأیید شما',
    stopping: 'در حال پایان و ارسال',
    queued: 'در صف تبدیل گفتار',
    processing: 'در حال تبدیل گفتار',
    review_required: 'متن آماده بازبینی پزشک',
    approved: 'بازبینی و تأیید شده',
    failed: 'پردازش ناموفق',
    withdrawn: 'رضایت پس گرفته شد',
    cancelled: 'لغو شده',
  }[status] || status || 'نامشخص');
}

function safeParticipants(raw) {
  return String(raw || '')
    .split(/[,،\n]+/)
    .map(value => value.trim())
    .filter(Boolean)
    .slice(0, 8);
}

export async function mountLiveRecorderPanel(host, { patientId }) {
  const document = host.ownerDocument;
  host.replaceChildren();
  host.classList.add('recording-panel');

  const header = el(document, 'div', undefined, 'recording-header');
  header.append(
    el(document, 'h3', 'ضبط و متن جلسه حضوری'),
    el(document, 'span', 'فقط مدیر کل', 'pill muted')
  );

  const status = el(document, 'p', 'در حال بررسی آمادگی سرویس…', 'recording-status');
  status.setAttribute('role', 'status');
  const error = el(document, 'p', '', 'recording-error');
  error.setAttribute('role', 'alert');
  const timer = el(document, 'output', '00:00:00', 'recording-timer');
  timer.dir = 'ltr';
  timer.setAttribute('aria-label', 'مدت صدای ثبت‌شده');

  const readinessBox = el(document, 'div', undefined, 'recording-readiness');
  const consentBox = el(document, 'div', undefined, 'recording-consent-box');
  const actions = el(document, 'div', undefined, 'recording-actions');
  const transcript = el(document, 'section', undefined, 'recording-transcript');
  const cautions = el(document, 'ul', undefined, 'recording-cautions');
  [
    'در موبایل صفحه را باز و روشن نگه دارید. قفل صفحه، تماس ورودی یا تعویض برنامه ممکن است ضبط را قطع کند.',
    'متن ماشینی و نسخه اصلاح‌شده جدا نگهداری می‌شوند؛ هیچ اصلاحی متن اصلی را پاک نمی‌کند.',
    'نام گوینده به‌صورت خودکار حدس زده نمی‌شود و باید در بازبینی مشخص شود.',
    'تأیید متن، آن را خودکار وارد پرونده پزشکی نمی‌کند.',
  ].forEach(text => cautions.append(el(document, 'li', text)));

  host.append(header, status, error, timer, readinessBox, consentBox, actions, cautions, transcript);

  let readiness;
  try {
    readiness = await RecordingApi.readiness(patientId);
  } catch (err) {
    status.textContent = 'سرویس ضبط در دسترس نیست';
    error.textContent = friendlyError(err);
    return;
  }

  if (!readiness.available) {
    status.textContent = 'ضبط هنوز برای استفاده واقعی فعال نشده است';
    readinessBox.replaceChildren();
    const list = el(document, 'ul', undefined, 'recording-cautions');
    blockerText(readiness.blockers).forEach(text => list.append(el(document, 'li', text)));
    readinessBox.append(list);
    return;
  }

  const consentText = String(readiness.consent_text || '').trim();
  if (!consentText) {
    status.textContent = 'متن رضایت ضبط پیکربندی نشده است';
    return;
  }

  status.textContent = 'آماده ثبت رضایت و شروع جلسه';

  const consentStatement = el(document, 'p', consentText, 'recording-consent-text');
  const participantsLabel = el(document, 'label', 'افراد حاضر در جلسه');
  const participants = el(document, 'input');
  participants.type = 'text';
  participants.placeholder = 'مثلاً: پزشک، بیمار، همراه بیمار';
  participants.maxLength = 500;
  participants.autocomplete = 'off';
  participantsLabel.append(participants);

  const consentLabel = el(document, 'label', undefined, 'recording-consent');
  const consent = el(document, 'input');
  consent.type = 'checkbox';
  consentLabel.append(
    consent,
    el(document, 'span', 'تأیید می‌کنم رضایت صریح افراد حاضر برای ضبط و تبدیل گفتار طبق متن بالا دریافت شده است.')
  );
  consentBox.append(consentStatement, participantsLabel, consentLabel);

  const start = el(document, 'button', 'شروع ضبط', 'btn btn-primary');
  const pause = el(document, 'button', 'توقف موقت', 'btn btn-secondary');
  const resume = el(document, 'button', 'ادامه ضبط', 'btn btn-secondary');
  const stop = el(document, 'button', 'پایان ضبط', 'btn btn-secondary');
  const withdraw = el(document, 'button', 'پس گرفتن رضایت و توقف', 'btn btn-danger');
  [start, pause, resume, stop, withdraw].forEach(button => { button.type = 'button'; });
  pause.disabled = resume.disabled = stop.disabled = withdraw.disabled = true;
  actions.append(start, pause, resume, stop, withdraw);

  let driver = null;
  let sessionId = null;
  let pollAbort = false;

  function setButtons(state) {
    start.disabled = state !== 'ready';
    pause.disabled = state !== 'recording';
    resume.disabled = !['paused', 'interrupted'].includes(state);
    stop.disabled = !['recording', 'paused', 'interrupted'].includes(state);
    withdraw.disabled = !['recording', 'paused', 'interrupted', 'queued', 'processing', 'review_required'].includes(state);
  }

  function setBusy(message) {
    status.textContent = message;
    error.textContent = '';
  }

  async function refreshTranscript(session) {
    status.textContent = statusLabel(session.status);
    if (session.transcript?.data) {
      renderConversation(transcript, session, { patientId });
    } else {
      transcript.replaceChildren(el(document, 'p', 'هنوز متنی برای بازبینی تولید نشده است.', 'recording-muted'));
    }
    setButtons(session.status);
  }

  async function pollUntilReview() {
    if (!sessionId) return;
    pollAbort = false;
    for (let attempt = 0; attempt < 360 && !pollAbort; attempt++) {
      try {
        const session = await RecordingApi.show(patientId, sessionId);
        await refreshTranscript(session);
        if (TERMINAL.has(session.status)) return;
      } catch (err) {
        error.textContent = friendlyError(err);
      }
      await sleep(5000);
    }
  }

  start.addEventListener('click', async () => {
    if (!consent.checked) {
      error.textContent = 'برای شروع، ثبت رضایت صریح الزامی است.';
      return;
    }
    const people = safeParticipants(participants.value);
    if (!people.length) {
      error.textContent = 'افراد حاضر در جلسه را وارد کنید.';
      participants.focus();
      return;
    }

    start.disabled = true;
    setBusy('در حال ثبت رضایت…');
    try {
      const session = await RecordingApi.create(patientId, {
        consent_acknowledged: true,
        policy_version: readiness.policy_version,
        participants: people,
      });
      sessionId = session.session_id;
      driver = new ConsentGatedRecorder({
        patientId,
        sessionId,
        consentConfirmed: true,
        onState: event => {
          status.textContent = statusLabel(event.state);
          timer.textContent = formatDuration(event.recordedMs || 0);
          setButtons(event.state);
        },
        onProgress: progress => {
          timer.textContent = formatDuration(progress.recordedMs || 0);
        },
      });
      // This explicit button handler is the only path that requests the mic.
      await driver.startByUserGesture();
      participants.disabled = consent.disabled = true;
      error.textContent = '';
    } catch (err) {
      status.textContent = 'شروع ضبط انجام نشد';
      error.textContent = friendlyError(err);
      if (sessionId) {
        try { await RecordingApi.withdraw(patientId, sessionId); } catch (_) {}
      }
      sessionId = null;
      driver = null;
      start.disabled = false;
    }
  });

  pause.addEventListener('click', async () => {
    try { await driver?.pauseByUserGesture(); } catch (err) { error.textContent = friendlyError(err); }
  });

  resume.addEventListener('click', async () => {
    try { await driver?.resumeByUserGesture(); } catch (err) { error.textContent = friendlyError(err); }
  });

  stop.addEventListener('click', async () => {
    stop.disabled = true;
    setBusy('در حال پایان ضبط و اطمینان از ذخیره همه بخش‌ها…');
    try {
      await driver?.stop('user');
      status.textContent = 'ضبط ذخیره شد؛ در صف تبدیل گفتار';
      pollUntilReview();
    } catch (err) {
      error.textContent = friendlyError(err);
      status.textContent = 'پایان امن جلسه کامل نشد';
      setButtons(driver?.state || 'interrupted');
    }
  });

  withdraw.addEventListener('click', async () => {
    withdraw.disabled = true;
    try {
      pollAbort = true;
      if (driver && !['queued', 'withdrawn'].includes(driver.state)) {
        await driver.withdrawByUserGesture();
      } else if (sessionId) {
        await RecordingApi.withdraw(patientId, sessionId);
      }
      status.textContent = 'رضایت پس گرفته شد؛ پردازش متوقف شد';
      transcript.replaceChildren();
      [start, pause, resume, stop, withdraw].forEach(button => { button.disabled = true; });
    } catch (err) {
      error.textContent = friendlyError(err);
      withdraw.disabled = false;
    }
  });

  const visibilityHandler = () => {
    if (document.visibilityState === 'hidden' && driver?.state === 'recording') {
      driver.interrupt('hidden').catch(err => { error.textContent = friendlyError(err); });
    }
  };
  document.addEventListener('visibilitychange', visibilityHandler);
  globalThis.addEventListener?.('pagehide', () => {
    pollAbort = true;
    // Never continue capture silently after navigation. Track closure is local;
    // server session remains recoverable/reviewable.
    if (driver?.state === 'recording') driver.releaseMicrophone();
  }, { once: true });
}

function renderConversation(host, session, { patientId }) {
  const document = host.ownerDocument;
  const data = session.transcript?.data || {};
  const segments = Array.isArray(data.segments) ? data.segments : [];
  host.replaceChildren();

  const heading = el(document, 'div', undefined, 'recording-review-header');
  const title = el(document, 'div');
  title.append(
    el(document, 'h4', 'گفت‌وگوی جلسه'),
    el(document, 'p', `نسخه ${session.transcript.version} · ${segments.length} بخش · نیازمند بازبینی انسانی`, 'recording-muted')
  );
  heading.append(title);

  const list = el(document, 'ol', undefined, 'recording-chat');
  for (const segment of segments) {
    if (!segment || typeof segment !== 'object') continue;
    list.append(renderTurn(document, session, segment, { patientId }));
  }

  const approveBox = el(document, 'div', undefined, 'recording-approval');
  const confirmLabel = el(document, 'label', undefined, 'recording-consent');
  const confirm = el(document, 'input');
  confirm.type = 'checkbox';
  confirmLabel.append(confirm, el(document, 'span', 'متن و نقش گویندگان را بررسی کرده‌ام و این نسخه را تأیید می‌کنم.'));
  const approve = el(document, 'button', 'تأیید نسخه بازبینی‌شده', 'btn btn-primary');
  approve.type = 'button';
  approve.disabled = session.status !== 'review_required';
  const approveStatus = el(document, 'p', '', 'recording-muted');
  approve.addEventListener('click', async () => {
    if (!confirm.checked) {
      approveStatus.textContent = 'برای تأیید، بازبینی انسانی را تأیید کنید.';
      return;
    }
    approve.disabled = true;
    try {
      const result = await RecordingApi.approve(patientId, session.session_id, session.transcript.version);
      approveStatus.textContent = `نسخه ${result.version} تأیید شد. این کار هیچ یادداشت پزشکی را خودکار ایجاد نکرد.`;
    } catch (err) {
      approveStatus.textContent = friendlyError(err);
      approve.disabled = false;
    }
  });
  approveBox.append(confirmLabel, approve, approveStatus);

  host.append(heading, list, approveBox);
}

function renderTurn(document, session, segment, { patientId }) {
  const role = segment.speaker?.role || 'unknown';
  const item = el(document, 'li', undefined, `recording-chat-turn role-${role}`);
  const time = el(document, 'time', `${formatDuration(segment.start_ms || 0)} – ${formatDuration(segment.end_ms || 0)}`);
  time.dir = 'ltr';

  const roleSelect = el(document, 'select');
  [
    ['unknown', 'گوینده نامشخص'],
    ['doctor', 'پزشک'],
    ['patient', 'بیمار'],
    ['other', 'فرد دیگر'],
  ].forEach(([value, label]) => {
    const option = el(document, 'option', label);
    option.value = value;
    roleSelect.append(option);
  });
  roleSelect.value = role;

  const name = el(document, 'input');
  name.type = 'text';
  name.maxLength = 200;
  name.placeholder = 'نام تأییدشده';
  name.value = segment.speaker?.name || '';

  const text = el(document, 'textarea');
  text.maxLength = 20000;
  text.value = segment.text || '';

  const raw = String(segment.raw_text || '');
  const rawBox = el(document, 'details', undefined, 'recording-raw');
  rawBox.append(
    el(document, 'summary', raw && raw !== text.value ? 'مشاهده متن خام ماشین' : 'متن خام ماشین'),
    el(document, 'p', raw || '—')
  );

  const flags = Array.isArray(segment.review_flags) ? segment.review_flags : [];
  const flagText = flags.length
    ? `نیازمند توجه: ${flags.join('، ')}`
    : 'بدون علامت خودکار؛ همچنان نیازمند بازبینی انسانی';
  const note = el(document, 'p', flagText, 'recording-muted');

  const save = el(document, 'button', 'ثبت اصلاح این بخش', 'btn btn-secondary btn-sm');
  save.type = 'button';
  const message = el(document, 'p', '', 'recording-muted');
  save.addEventListener('click', async () => {
    save.disabled = true;
    try {
      const result = await RecordingApi.patchSegment(
        patientId,
        session.session_id,
        segment.id,
        {
          text: text.value,
          speaker_role: roleSelect.value,
          speaker_name: name.value,
          expected_version: session.transcript.version,
        }
      );
      message.textContent = `اصلاح در نسخه ${result.version} ثبت شد. برای ادامه بازبینی، صفحه را تازه‌سازی کنید.`;
    } catch (err) {
      message.textContent = friendlyError(err);
      save.disabled = false;
    }
  });

  item.append(time, roleSelect, name, text, rawBox, note, save, message);
  return item;
}
