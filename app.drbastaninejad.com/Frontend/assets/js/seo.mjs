/* MAZ//ID · SEO screen. Numeric reports remain gated on the reviewed integration. */
import { getToken, requireAuth, clearToken } from '../../shared/api.js';
import { buildSeoQuery, createSeoClient } from './seo-client.mjs';
import { renderReport } from './seo-render.mjs';

const messages = {
  loading: ['در حال بررسی اتصال…', 'وضعیت دسترسی و اتصال گزارش بررسی می‌شود.'],
  unconfigured: ['اتصال گزارش‌ها هنوز آماده نیست', 'این بخش آماده اتصال به MS Robot است. تا تأیید دسترسی و ارتباط پروژه با وب‌سایت، هیچ آمار یا نموداری نمایش داده نمی‌شود.'],
  forbidden: ['اجازه مشاهده این بخش را ندارید', 'این گزارش برای مدیران مجاز کلینیک در دسترس است.'],
  unavailable: ['گزارش فعلاً در دسترس نیست', 'داده‌ای قابل نمایش نیست. می‌توانید کمی بعد وضعیت اتصال را دوباره بررسی کنید.'],
  invalid_request: ['بازه گزارش معتبر نیست', 'یکی از بازه‌های زمانی موجود را انتخاب کنید.'],
  ready: ['گزارش دریافت شد', 'منبع، تاریخ و وضعیت پوشش داده را کنار هر شاخص بررسی کنید.'],
};
const stateBox = document.querySelector('[data-testid="seo-state"]');
const content = document.getElementById('seo-authorized-content');
const button = document.getElementById('seo-recheck');
const form = document.getElementById('seo-controls');
const retry = document.getElementById('seo-status-retry');
let controller;
let generation = 0;
let loggingOut = false;
const reportContainer = document.getElementById('seo-rendered-report');
document.getElementById('seo-end-date').max = new Date().toISOString().slice(0, 10);

function show(state) {
  const copy = messages[state] || messages.unavailable;
  stateBox.dataset.state = state;
  document.getElementById('seo-state-title').textContent = copy[0];
  document.getElementById('seo-state-detail').textContent = copy[1];
  button.disabled = state === 'loading';
  stateBox.setAttribute('aria-busy', state === 'loading' ? 'true' : 'false');
  // Only a known authenticated, role-checked unconfigured response reveals the
  // section. Unknown errors never become evidence of access or provider state.
  content.hidden = !['unconfigured', 'ready', 'invalid_request'].includes(state);
  reportContainer.hidden = state !== 'ready';
  if (state !== 'ready') reportContainer.replaceChildren();
  document.getElementById('seo-connection-details').hidden = state === 'ready';
  document.getElementById('seo-window-detail').textContent = state === 'ready' ? 'بازه‌های داده و مقایسه در گزارش مشخص شده‌اند.' : 'تا آماده شدن اتصال، داده‌ای برای این بازه نمایش داده نمی‌شود.';
  retry.hidden = state !== 'unavailable' && state !== 'invalid_request';
}

if (requireAuth('../auth/login.html', 'staff')) {
  const base = window.__DRB_STAFF_API_BASE__ || window.__DRB_API_BASE__ || 'https://dashboard.drbastaninejad.com/api/v1';
  let client;
  try { client = createSeoClient({ base, token: () => getToken('staff') }); } catch { show('unavailable'); }
  document.addEventListener('click', async event => {
    const logoutButton = event.target.closest?.('.logout-btn[data-logout-href]');
    if (!logoutButton) return;
    event.preventDefault(); event.stopImmediatePropagation();
    if (loggingOut) return;
    loggingOut = true; generation++; controller?.abort();
    content.hidden = true; reportContainer.replaceChildren(); logoutButton.disabled = true;
    const revoke = new AbortController();
    const timeout = setTimeout(() => revoke.abort(), 8000);
    const logoutClient = client || createSeoClient({ base:'https://dashboard.drbastaninejad.com/api/v1',token:()=>getToken('staff') });
    try { await logoutClient.logout(() => { clearToken('staff'); window.MAZCRM?.session?.clear('staff'); }, revoke.signal); }
    finally { clearTimeout(timeout); window.location.replace('../auth/login.html'); }
  }, true);
  async function load() {
    if (!client || loggingOut) return;
    const query = { period: document.getElementById('seo-period').value, comparison: document.getElementById('seo-comparison').value, endDate: document.getElementById('seo-end-date').value };
    try { buildSeoQuery(query); } catch { generation++; controller?.abort(); show('invalid_request'); return; }
    const current = ++generation;
    controller?.abort();
    controller = new AbortController();
    const active = controller;
    const timeout = setTimeout(() => active.abort(), 15000);
    show('loading');
    try {
      const result = await client.read(query, active.signal);
      if (current !== generation) return;
      if (result.state === 'unauthorized') { clearToken('staff'); window.location.replace('../auth/login.html'); return; }
      show(result.state);
      if (result.state === 'ready') renderReport(reportContainer, result.report);
    } catch {
      if (current === generation) show('unavailable');
    } finally { clearTimeout(timeout); }
  }
  form.addEventListener('submit', event => { event.preventDefault(); load(); });
  retry.addEventListener('click', load);
  window.addEventListener('pagehide', () => { generation++; controller?.abort(); });
  window.addEventListener('pageshow', event => { if (event.persisted) load(); });
  load();
}
