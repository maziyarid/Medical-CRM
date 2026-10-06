/* MAZ//ID · © 2026 Maziyar / Dr. Shahin Bastaninejad
 * Presentation only. Production remains unavailable until authenticated storage,
 * capture and a real provider are implemented and independently reviewed.
 */
import { createSession, elapsedMs, assignSpeaker, editSegment } from './session.js';
const statusText = {
  unconfigured: 'ضبط جلسه حضوری هنوز فعال نشده', idle: 'آماده‌سازی جلسه', ready: 'آماده برای شروع با رضایت ثبت‌شده',
  recording: 'در حال ضبط', paused: 'ضبط متوقف موقت', interrupted: 'ضبط قطع شده؛ ادامه فقط با تأیید شما',
  stopped: 'ضبط پایان یافته', discarded: 'رضایت پس گرفته شد؛ ضبط لغو شده', denied: 'دسترسی ضبط مجاز نیست',
};
export function formatDuration(milliseconds) {
  const seconds = Math.floor(Math.max(0, milliseconds) / 1000);
  return [Math.floor(seconds / 3600), Math.floor(seconds / 60) % 60, seconds % 60].map(value => String(value).padStart(2, '0')).join(':');
}
export function recorderView(state, at, available = false) {
  const code = available ? state.status : 'unconfigured';
  return { code, status: statusText[code] || statusText.unconfigured, duration: formatDuration(elapsedMs(state, at)),
    controls: { start: available && state.status === 'ready', pause: available && state.status === 'recording',
      resume: available && ['paused', 'interrupted'].includes(state.status), stop: available && ['recording', 'paused', 'interrupted'].includes(state.status),
      withdraw: available && ['ready', 'recording', 'paused', 'interrupted', 'stopped'].includes(state.status) } };
}
function node(document, tag, text, className) {
  const element = document.createElement(tag); if (text !== undefined) element.textContent = text;
  if (className) element.className = className; return element;
}
export function mountRecorderPanel(host, { patientId }) {
  const document = host.ownerDocument;
  const view = recorderView(createSession({ patientId, sessionId: 'pending-session' }), 0);
  host.replaceChildren(); host.classList.add('recording-panel');
  const header = node(document, 'div', undefined, 'recording-header');
  header.append(node(document, 'h3', 'ضبط جلسه حضوری'), node(document, 'span', 'پیش‌نمایش قابلیت', 'pill muted'));
  const status = node(document, 'p', view.status, 'recording-status'); status.setAttribute('role', 'status');
  const explanation = node(document, 'p', 'ذخیره‌سازی امن، ثبت رضایت و سرویس تبدیل گفتار هنوز متصل نشده‌اند. در این نسخه هیچ صدایی ضبط یا ارسال نمی‌شود.', 'recording-muted');
  explanation.id = 'recording-availability';
  const timer = node(document, 'output', view.duration, 'recording-timer'); timer.dir = 'ltr'; timer.setAttribute('aria-label', 'مدت ضبط');
  const limit = node(document, 'p', 'حداکثر مدت جلسه: ۳ ساعت از شروع، شامل زمان توقف موقت', 'recording-muted');
  const consent = node(document, 'label', undefined, 'recording-consent');
  const checkbox = node(document, 'input'); checkbox.type = 'checkbox'; checkbox.disabled = true;
  checkbox.setAttribute('aria-describedby', explanation.id);
  consent.append(checkbox, node(document, 'span', 'رضایت صریح افراد حاضر برای ضبط و تبدیل گفتار به متن، طبق سیاست مرکز، ثبت شده است.'));
  const actions = node(document, 'div', undefined, 'recording-actions');
  for (const [action, label] of [['start', 'شروع ضبط'], ['pause', 'توقف موقت'], ['resume', 'ادامه ضبط'], ['stop', 'پایان ضبط'], ['withdraw', 'پس گرفتن رضایت']]) {
    const button = node(document, 'button', label, action === 'start' ? 'btn btn-primary' : 'btn btn-secondary');
    button.type = 'button'; button.disabled = !view.controls[action]; button.setAttribute('aria-describedby', explanation.id); actions.append(button);
  }
  const cautions = node(document, 'ul', undefined, 'recording-cautions');
  for (const text of ['در موبایل صفحه را باز و روشن نگه دارید. قفل صفحه، تماس ورودی یا تعویض برنامه می‌تواند ضبط را قطع کند.',
    'قطع شبکه یا کمبود فضای بافر باید ضبط را متوقف کند؛ ادامه ضبط خودکار نیست.',
    'ذخیره محلی صدا یا متن روی این دستگاه فعال نیست. بازیابی پس از بستن یا خرابی مرورگر هنوز پیاده‌سازی نشده است.',
    'نام گوینده باید دستی تأیید شود. متن و دسته‌بندی احتمالی پرونده تا بازبینی پزشک، پیش‌نویس هستند.']) cautions.append(node(document, 'li', text));
  const transcript = node(document, 'section', undefined, 'recording-transcript');
  transcript.append(node(document, 'h4', 'گفت‌وگوی جلسه'), node(document, 'p', 'هنوز متنی تولید نشده است. تبدیل گفتار و دسته‌بندی پرونده پیکربندی نشده‌اند.', 'recording-muted'));
  host.append(header, status, explanation, timer, limit, consent, actions, cautions, transcript);
}

// Reusable draft view for a future authenticated adapter. No save/finalize side
// effects; onChange receives an in-memory draft. Never mounts example turns.
export function renderDraftTurns(host, initialDraft, { actorId, onChange } = {}) {
  const document = host.ownerDocument; let draft = initialDraft;
  host.replaceChildren();
  host.append(node(document, 'p', 'پیش‌نویس بازبینی‌نشده؛ تغییرات این نما در سرور ذخیره نمی‌شوند.', 'recording-status'));
  const list = node(document, 'ol', undefined, 'recording-turns');
  for (const segment of draft.segments) {
    const item = node(document, 'li', undefined, 'recording-turn');
    const time = node(document, 'time', `${formatDuration(segment.startMs)} – ${formatDuration(segment.endMs)}`); time.dir = 'ltr';
    const speaker = node(document, 'strong', segment.speaker.name || 'گوینده نامشخص');
    const text = node(document, 'p', segment.text); text.style.whiteSpace = 'pre-wrap';
    item.append(time, speaker, text);
    if (actorId && typeof onChange === 'function') {
      const roleLabel = node(document, 'label', 'نقش گوینده'); const role = node(document, 'select');
      for (const [value, label] of [['unknown', 'نامشخص'], ['doctor', 'پزشک'], ['patient', 'بیمار'], ['other', 'فرد دیگر']]) {
        const option = node(document, 'option', label); option.value = value; role.append(option);
      }
      role.value = segment.speaker.role; roleLabel.append(role);
      const nameLabel = node(document, 'label', 'نام تأییدشده گوینده'); const name = node(document, 'input'); name.maxLength = 200; name.value = segment.speaker.name; nameLabel.append(name);
      const error = node(document, 'p', '', 'recording-error'); error.setAttribute('role', 'alert');
      const assign = node(document, 'button', 'ثبت دستی گوینده', 'btn btn-secondary btn-sm'); assign.type = 'button';
      assign.addEventListener('click', () => {
        try {
          const next = assignSpeaker(draft, segment.id, { role: role.value, name: name.value, actorId });
          onChange(next); draft = next; speaker.textContent = role.value === 'unknown' ? 'گوینده نامشخص' : name.value.trim(); error.textContent = '';
        } catch { error.textContent = 'نقش و نام گوینده را بررسی کنید؛ تغییر ثبت نشد.'; }
      });
      const editLabel = node(document, 'label', 'اصلاح متن این بخش'); const edit = node(document, 'textarea'); edit.maxLength = 20_000; edit.value = segment.text; editLabel.append(edit);
      const save = node(document, 'button', 'ثبت اصلاح متن', 'btn btn-secondary btn-sm'); save.type = 'button';
      save.addEventListener('click', () => {
        try {
          const next = editSegment(draft, segment.id, edit.value, actorId); onChange(next); draft = next; text.textContent = edit.value; error.textContent = '';
        } catch { error.textContent = 'اصلاح متن ثبت نشد.'; }
      });
      item.append(roleLabel, nameLabel, assign, editLabel, save, error);
    }
    list.append(item);
  }
  host.append(list);
}
