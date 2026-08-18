// MZ Medical CRM Dashboard — client shell
const state = { view: 'overview' };

const VIEWS = {
  overview: { title: 'داشبورد کلی', sub: 'خلاصه وضعیت کلینیک امروز' },
  patients: { title: 'پرونده بیماران', sub: 'فهرست و جستجوی بیماران' },
  calendar: { title: 'تقویم نوبت‌دهی', sub: 'مشاهده و مدیریت نوبت‌ها' },
  intake: { title: 'پذیرش', sub: 'فرم ورود بیمار جدید' },
  emr: { title: 'پرونده الکترونیک', sub: 'ثبت یادداشت بالینی' },
  media: { title: 'تصاویر و اسناد', sub: 'آرشیو تصاویر بالینی' },
  'ai-copilot': { title: 'دستیار هوش مصنوعی', sub: 'پیشنهادات نیازمند تایید انسانی' },
  billing: { title: 'مالی و صورتحساب', sub: 'فاکتورها و پرداخت‌ها' },
  tasks: { title: 'وظایف تیم', sub: 'کانبان وظایف کلینیک' },
  analytics: { title: 'آنالیتیکس و بازاریابی', sub: 'منابع ارجاع و نرخ تبدیل' },
  staff: { title: 'کاربران و نقش‌ها', sub: 'مدیریت دسترسی RBAC' },
  settings: { title: 'تنظیمات و قالب‌ها', sub: 'پیکربندی کلینیک' }
};

async function api(path, opts = {}) {
  const res = await fetch(`/api/v1${path}`, {
    headers: { 'Content-Type': 'application/json', ...(opts.headers || {}) },
    credentials: 'include',
    ...opts
  });
  if (!res.ok) throw new Error(`API ${path} -> ${res.status}`);
  return res.json();
}

function setActiveNav(view) {
  document.querySelectorAll('.nav-item').forEach(el => {
    el.classList.toggle('active', el.dataset.view === view);
  });
  document.getElementById('page-title').textContent = VIEWS[view]?.title || view;
  document.getElementById('page-sub').textContent = VIEWS[view]?.sub || '';
}

function renderMetrics(metrics) {
  const grid = document.getElementById('metric-grid');
  grid.innerHTML = metrics.map(m => `
    <div class="metric-card" data-href="${m.href || '#'}">
      <div class="label">${m.label}</div>
      <div class="value">${m.value}</div>
      <div class="delta ${m.deltaDir || ''}">${m.delta || ''}</div>
    </div>`).join('');
}

function renderAttention(rows) {
  const tbody = document.querySelector('#attention-table tbody');
  tbody.innerHTML = rows.map(r => `
    <tr>
      <td>${r.patient}</td>
      <td>${r.item}</td>
      <td><span class="badge badge-${r.badge}">${r.status}</span></td>
      <td><button class="btn btn-ghost" style="padding:.3rem .8rem;font-size:.75rem">مشاهده</button></td>
    </tr>`).join('');
}

function renderTodayList(list) {
  const el = document.getElementById('today-list');
  el.innerHTML = list.map(a => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-weight:600;font-size:.88rem">${a.patient}</div>
        <div style="font-size:.75rem;color:var(--muted)">${a.time} · ${a.reason}</div>
      </div>
      <span class="badge badge-${a.badge}">${a.status}</span>
    </div>`).join('') || '<div style="color:var(--muted);font-size:.85rem;text-align:center;padding:1rem">نوبتی برای امروز ثبت نشده است</div>';
}

async function loadOverview() {
  try {
    const data = await api('/dashboard/overview');
    renderMetrics(data.metrics);
    renderAttention(data.attention);
    renderTodayList(data.today);
  } catch (e) {
    // Fallback demo data while backend endpoints are wired up
    renderMetrics([
      { label: 'نوبت‌های امروز', value: '—', href: '#calendar' },
      { label: 'پذیرش‌های در انتظار', value: '—', href: '#patients' },
      { label: 'درآمد امروز', value: '—', href: '#billing' },
      { label: 'وظایف باز', value: '—', href: '#tasks' }
    ]);
    renderAttention([]);
    renderTodayList([]);
    console.warn('Overview API not reachable yet:', e.message);
  }
}

function showSection(view) {
  document.querySelectorAll('#view-root > section').forEach(sec => {
    sec.style.display = 'none';
  });
  const map = { overview: 'view-overview', patients: 'view-patients', calendar: 'view-calendar' };
  const sectionId = map[view];
  if (sectionId) {
    const el = document.getElementById(sectionId);
    if (el) el.style.display = 'block';
  }
}

function navigate(view) {
  state.view = view;
  setActiveNav(view);
  if (view === 'overview') {
    showSection('overview');
    loadOverview();
  } else if (view === 'patients') {
    showSection('patients');
    document.getElementById('view-patient-detail').style.display = 'none';
    PatientsModule.load();
  } else if (view === 'calendar') {
    showSection('calendar');
    CalendarModule.load();
  } else {
    // Modules not yet scaffolded — keep shell usable without a hard error
    document.querySelectorAll('#view-root > section').forEach(sec => sec.style.display = 'none');
  }
}

document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => navigate(item.dataset.view));
});

document.getElementById('menu-toggle')?.addEventListener('click', () => {
  document.getElementById('sidebar').classList.toggle('open');
});

document.addEventListener('keydown', (e) => {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
    e.preventDefault();
    document.getElementById('search-btn').click();
  }
});

EmrModule.init();
PatientsModule.init();
CalendarModule.init();
navigate('overview');

if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js').catch(() => {});
  });
}
