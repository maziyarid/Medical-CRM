// MZ Medical CRM Dashboard — client shell (not the canonical staff UI)
const state = { view: 'overview' };

const VIEWS = {
  overview: { title: 'داشبورد کلی', sub: 'خلاصه وضعیت کلینیک امروز' },
  patients: { title: 'پرونده بیماران', sub: 'فهرست و جستجوی بیماران' },
  calendar: { title: 'تقویم نوبت‌دهی', sub: 'مشاهده و مدیریت نوبت‌ها' }
};

function escapeHtml(value) {
  return String(value == null ? '' : value)
    .replace(/&/g, '&')
    .replace(/</g, '<')
    .replace(/>/g, '>')
    .replace(/"/g, '"')
    .replace(/'/g, '&#39;');
}

function safeHref(value) {
  const href = String(value || '');
  if (href.charAt(0) === '/' || href.charAt(0) === '#') return href;
  return '#';
}

function unwrap(payload) {
  if (payload && payload.data && typeof payload.data === 'object') return payload.data;
  return payload || {};
}

function getToken() {
  try {
    return (window.MAZCRM && window.MAZCRM.session && window.MAZCRM.session.token('staff')) || '';
  } catch (e) {
    return '';
  }
}

async function api(path, opts) {
  opts = opts || {};
  const headers = { 'Content-Type': 'application/json', ...(opts.headers || {}) };
  const token = getToken();
  if (token) headers.Authorization = 'Bearer ' + token;
  const res = await fetch('/api/v1' + path, {
    credentials: 'include',
    ...opts,
    headers
  });
  if (res.status === 401) {
    window.location.href = 'https://app.drbastaninejad.com/Frontend/pages/auth/login.html';
    throw new Error('unauthorized');
  }
  if (!res.ok) throw new Error('API ' + path + ' -> ' + res.status);
  return unwrap(await res.json());
}

function setActiveNav(view) {
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.view === view);
  });
  const title = document.getElementById('page-title');
  const sub = document.getElementById('page-sub');
  if (title) title.textContent = (VIEWS[view] && VIEWS[view].title) || view;
  if (sub) sub.textContent = (VIEWS[view] && VIEWS[view].sub) || '';
}

function renderMetrics(metrics) {
  const grid = document.getElementById('metric-grid');
  if (!grid) return;
  grid.textContent = '';
  (metrics || []).forEach(m => {
    const card = document.createElement('div');
    card.className = 'metric-card';
    card.dataset.href = safeHref(m.href);
    const label = document.createElement('div');
    label.className = 'label';
    label.textContent = m.label || '';
    const value = document.createElement('div');
    value.className = 'value';
    value.textContent = m.value || '—';
    const delta = document.createElement('div');
    delta.className = 'delta ' + (m.deltaDir || '');
    delta.textContent = m.delta || '';
    card.appendChild(label);
    card.appendChild(value);
    card.appendChild(delta);
    grid.appendChild(card);
  });
}

function renderAttention(rows) {
  const tbody = document.querySelector('#attention-table tbody');
  if (!tbody) return;
  tbody.textContent = '';
  (rows || []).forEach(r => {
    const tr = document.createElement('tr');
    const patient = document.createElement('td');
    patient.textContent = r.patient || r.title || '';
    const item = document.createElement('td');
    item.textContent = r.item || '';
    const status = document.createElement('td');
    const badge = document.createElement('span');
    badge.className = 'badge badge-' + (r.badge || 'muted');
    badge.textContent = r.status || '';
    status.appendChild(badge);
    const action = document.createElement('td');
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'btn btn-ghost';
    btn.textContent = 'مشاهده';
    action.appendChild(btn);
    tr.appendChild(patient);
    tr.appendChild(item);
    tr.appendChild(status);
    tr.appendChild(action);
    tbody.appendChild(tr);
  });
}

function renderTodayList(list) {
  const el = document.getElementById('today-list');
  if (!el) return;
  el.textContent = '';
  if (!list || !list.length) {
    const empty = document.createElement('div');
    empty.style.cssText = 'color:var(--muted);font-size:.85rem;text-align:center;padding:1rem';
    empty.textContent = 'نوبتی برای امروز ثبت نشده است';
    el.appendChild(empty);
    return;
  }
  list.forEach(a => {
    const row = document.createElement('div');
    row.style.cssText = 'display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border)';
    const left = document.createElement('div');
    const name = document.createElement('div');
    name.style.cssText = 'font-weight:600;font-size:.88rem';
    name.textContent = a.patient || '';
    const meta = document.createElement('div');
    meta.style.cssText = 'font-size:.75rem;color:var(--muted)';
    meta.textContent = (a.time || '') + ' · ' + (a.reason || '');
    left.appendChild(name);
    left.appendChild(meta);
    const badge = document.createElement('span');
    badge.className = 'badge badge-' + (a.badge || 'muted');
    badge.textContent = a.status || '';
    row.appendChild(left);
    row.appendChild(badge);
    el.appendChild(row);
  });
}

async function loadOverview() {
  try {
    const data = await api('/dashboard/overview');
    renderMetrics(data.metrics);
    renderAttention(data.attention);
    renderTodayList(data.today);
  } catch (e) {
    renderMetrics([
      { label: 'نوبت‌های امروز', value: '—', href: '#calendar' },
      { label: 'پذیرش‌های در انتظار', value: '—', href: '#patients' },
      { label: 'درآمد امروز', value: '—', href: '#billing' },
      { label: 'وظایف باز', value: '—', href: '#tasks' }
    ]);
    renderAttention([]);
    renderTodayList([]);
  }
}

function showUnavailable(view) {
  const root = document.getElementById('view-root');
  if (!root) return;
  document.querySelectorAll('#view-root > section').forEach(sec => { sec.style.display = 'none'; });
  let box = document.getElementById('view-unavailable');
  if (!box) {
    box = document.createElement('section');
    box.id = 'view-unavailable';
    box.className = 'card';
    box.style.padding = '2rem';
    root.appendChild(box);
  }
  box.style.display = 'block';
  box.textContent = '';
  const h = document.createElement('h2');
  h.textContent = (VIEWS[view] && VIEWS[view].title) || 'این بخش آماده نیست';
  const p = document.createElement('p');
  p.textContent = 'این ماژول در پوسته آزمایشی در دسترس نیست. از پنل اصلی کارکنان استفاده کنید.';
  const a = document.createElement('a');
  a.className = 'btn btn-primary';
  a.href = 'https://app.drbastaninejad.com/Frontend/pages/auth/login.html';
  a.textContent = 'ورود به پنل کارکنان';
  box.appendChild(h);
  box.appendChild(p);
  box.appendChild(a);
}

function showSection(view) {
  const unavailable = document.getElementById('view-unavailable');
  if (unavailable) unavailable.style.display = 'none';
  document.querySelectorAll('#view-root > section').forEach(sec => {
    if (sec.id !== 'view-unavailable') sec.style.display = 'none';
  });
  const map = { overview: 'view-overview', patients: 'view-patients', calendar: 'view-calendar' };
  const el = document.getElementById(map[view]);
  if (el) el.style.display = 'block';
}

function navigate(view) {
  state.view = view;
  setActiveNav(view);
  if (view === 'overview') {
    showSection('overview');
    loadOverview();
  } else if (view === 'patients') {
    showSection('patients');
    const detail = document.getElementById('view-patient-detail');
    if (detail) detail.style.display = 'none';
    if (window.PatientsModule) PatientsModule.load();
  } else if (view === 'calendar') {
    showSection('calendar');
    if (window.CalendarModule) CalendarModule.load();
  } else {
    showUnavailable(view);
  }
}

document.querySelectorAll('.nav-item').forEach(item => {
  if (!item.getAttribute('href')) item.setAttribute('href', '#' + (item.dataset.view || ''));
  item.addEventListener('click', (e) => {
    e.preventDefault();
    navigate(item.dataset.view);
  });
});

if (window.EmrModule) EmrModule.init();
if (window.PatientsModule) PatientsModule.init();
if (window.CalendarModule) CalendarModule.init();
if (document.getElementById('view-overview')) navigate('overview');

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.getRegistrations().then(function (regs) {
    regs.forEach(function (reg) { reg.unregister(); });
  });
  if (window.caches) {
    caches.keys().then(function (keys) {
      keys.forEach(function (key) { caches.delete(key); });
    });
  }
}

void escapeHtml;
