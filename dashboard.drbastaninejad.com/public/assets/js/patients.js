// Patients module — list, search, pagination, detail timeline
const PatientsModule = (() => {
  let state = { q: '', page: 1, perPage: 20 };

  const TYPE_LABEL = {
    intake: 'پذیرش',
    appointment: 'نوبت',
    emr_note: 'یادداشت بالینی',
    invoice: 'فاکتور',
  };
  const TYPE_BADGE = {
    intake: 'info',
    appointment: 'success',
    emr_note: 'warning',
    invoice: 'muted',
  };

  function renderRows(rows) {
    const tbody = document.querySelector('#patients-table tbody');
    if (!rows.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--muted);padding:2rem">بیماری یافت نشد</td></tr>';
      return;
    }
    tbody.innerHTML = rows.map(p => `
      <tr data-id="${p.id}" style="cursor:pointer">
        <td>${p.name || '—'}</td>
        <td dir="ltr" style="text-align:right">${p.mobile || '—'}</td>
        <td dir="ltr" style="text-align:right">${p.national_id || '—'}</td>
        <td><span class="badge badge-${p.insurance_status === 'active' ? 'success' : 'muted'}">${p.insurance_status || 'نامشخص'}</span></td>
        <td>${p.last_visit ? new Date(p.last_visit).toLocaleDateString('fa-IR') : '—'}</td>
        <td>${p.upcoming_count > 0 ? `<span class="badge badge-info">${p.upcoming_count}</span>` : '—'}</td>
        <td><button class="btn btn-ghost view-patient-btn" data-id="${p.id}" style="padding:.3rem .8rem;font-size:.75rem">مشاهده</button></td>
      </tr>`).join('');

    tbody.querySelectorAll('tr[data-id]').forEach(tr => {
      tr.addEventListener('click', (e) => {
        if (e.target.closest('.view-patient-btn')) return;
        openDetail(tr.dataset.id);
      });
    });
    tbody.querySelectorAll('.view-patient-btn').forEach(btn => {
      btn.addEventListener('click', () => openDetail(btn.dataset.id));
    });
  }

  function renderPagination(total, page, perPage) {
    const pages = Math.max(1, Math.ceil(total / perPage));
    const el = document.getElementById('patients-pagination');
    if (pages <= 1) { el.innerHTML = ''; return; }
    let html = '';
    for (let i = 1; i <= pages; i++) {
      html += `<button class="btn ${i === page ? 'btn-primary' : 'btn-ghost'}" data-page="${i}" style="padding:.4rem .8rem;font-size:.8rem">${i}</button>`;
    }
    el.innerHTML = html;
    el.querySelectorAll('button').forEach(b => {
      b.addEventListener('click', () => { state.page = parseInt(b.dataset.page, 10); load(); });
    });
  }

  async function load() {
    const tbody = document.querySelector('#patients-table tbody');
    tbody.innerHTML = '<tr><td colspan="7"><div class="skeleton" style="height:20px"></div></td></tr>';
    try {
      const qs = new URLSearchParams({ q: state.q, page: state.page, per_page: state.perPage });
      const res = await api(`/patients?${qs.toString()}`);
      renderRows(res.data.rows);
      renderPagination(res.data.total, res.data.page, res.data.per_page);
    } catch (e) {
      tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;color:var(--error);padding:2rem">خطا در بارگذاری فهرست بیماران</td></tr>';
      console.warn('Patients API error:', e.message);
    }
  }

  function renderTimeline(items) {
    const el = document.getElementById('patient-timeline');
    if (!items.length) {
      el.innerHTML = '<div style="text-align:center;color:var(--muted);padding:2rem">هنوز رویدادی ثبت نشده است</div>';
      return;
    }
    el.innerHTML = items.map(i => `
      <div style="display:flex;gap:1rem;padding:.85rem 0;border-bottom:1px solid var(--border)">
        <span class="badge badge-${TYPE_BADGE[i.type] || 'muted'}" style="flex-shrink:0;align-self:flex-start;margin-top:2px">${TYPE_LABEL[i.type] || i.type}</span>
        <div style="flex:1">
          <div style="font-size:.88rem;color:var(--graphite)">${i.summary || '—'}</div>
          <div style="font-size:.72rem;color:var(--muted);margin-top:2px">${i.ts ? new Date(i.ts).toLocaleString('fa-IR') : ''} · ${i.status || ''}</div>
        </div>
      </div>`).join('');
  }

  async function openDetail(id) {
    document.getElementById('view-patients').style.display = 'none';
    document.getElementById('view-patient-detail').style.display = 'block';
    document.getElementById('page-title').textContent = 'پرونده بیمار';
    document.getElementById('page-sub').textContent = '';

    const headerEl = document.getElementById('patient-header');
    headerEl.innerHTML = '<div class="skeleton" style="height:60px"></div>';
    document.getElementById('patient-timeline').innerHTML = '<div class="skeleton" style="height:80px"></div>';

    try {
      const res = await api(`/patients/${id}`);
      const p = res.data.patient;
      headerEl.innerHTML = `
        <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap">
          <div class="avatar" style="width:56px;height:56px;font-size:1.1rem">${(p.name || '؟').slice(0,2)}</div>
          <div style="flex:1;min-width:200px">
            <div style="font-size:1.1rem;font-weight:700">${p.name}</div>
            <div style="color:var(--muted);font-size:.85rem;margin-top:2px" dir="ltr">${p.mobile || ''} ${p.national_id ? '· ' + p.national_id : ''}</div>
          </div>
          <span class="badge badge-${p.insurance_status === 'active' ? 'success' : 'muted'}">بیمه: ${p.insurance_status || 'نامشخص'}</span>
          <button class="btn btn-secondary" id="patient-write-note-btn" style="font-size:.82rem">+ یادداشت بالینی</button>
          <button class="btn btn-ghost" style="font-size:.82rem">ویرایش</button>
        </div>`;
      document.getElementById('patient-write-note-btn').addEventListener('click', () => EmrModule.open(id));
      renderTimeline(res.data.timeline);
    } catch (e) {
      headerEl.innerHTML = '<div style="color:var(--error)">خطا در بارگذاری پرونده بیمار</div>';
      console.warn('Patient detail error:', e.message);
    }
  }

  function init() {
    document.getElementById('patient-search')?.addEventListener('input', (e) => {
      state.q = e.target.value;
      state.page = 1;
      clearTimeout(window.__patientSearchDebounce);
      window.__patientSearchDebounce = setTimeout(load, 350);
    });
    document.getElementById('back-to-patients')?.addEventListener('click', () => {
      document.getElementById('view-patient-detail').style.display = 'none';
      document.getElementById('view-patients').style.display = 'block';
      document.getElementById('page-title').textContent = 'پرونده بیماران';
      document.getElementById('page-sub').textContent = 'فهرست و جستجوی بیماران';
    });
  }

  return { load, init };
})();
