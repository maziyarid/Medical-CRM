// Calendar / Scheduling module — day/week/month/agenda views
const CalendarModule = (() => {
  let state = { view: 'day', anchor: new Date(), events: [] };

  const WEEKDAYS_FA = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
  const MONTHS_FA = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const WORK_START = 8, WORK_END = 20;


  function fmtTime(dateStr) {
    const d = new Date(dateStr);
    const pad = (n) => String(n).padStart(2, '0');
    return Jalali.toPersianDigits(`${pad(d.getHours())}:${pad(d.getMinutes())}`);
  }

  function fmtDate(d) { return d.toISOString().slice(0, 10); }
  function startOfDay(d) { const x = new Date(d); x.setHours(0,0,0,0); return x; }
  function addDays(d, n) { const x = new Date(d); x.setDate(x.getDate() + n); return x; }
  function startOfWeek(d) { const x = startOfDay(d); const day = x.getDay(); return addDays(x, -day); }
  function startOfMonth(d) { return new Date(d.getFullYear(), d.getMonth(), 1); }

  function rangeForView() {
    if (state.view === 'day') {
      const from = startOfDay(state.anchor);
      return { from, to: addDays(from, 1) };
    }
    if (state.view === 'week') {
      const from = startOfWeek(state.anchor);
      return { from, to: addDays(from, 7) };
    }
    if (state.view === 'month') {
      const from = startOfMonth(state.anchor);
      const to = new Date(from.getFullYear(), from.getMonth() + 1, 1);
      return { from, to };
    }
    // agenda: next 14 days
    const from = startOfDay(state.anchor);
    return { from, to: addDays(from, 14) };
  }

  function updateRangeLabel() {
    const { from, to } = rangeForView();
    const label = document.getElementById('cal-range-label');
    if (state.view === 'month') {
      const { jy, jm } = Jalali.toJalali(from);
      label.textContent = `${Jalali.MONTHS_FA[jm - 1]} ${Jalali.toPersianDigits(jy)}`;
    } else if (state.view === 'day') {
      label.textContent = Jalali.formatFull(from);
    } else {
      const toLabel = addDays(to, -1);
      label.textContent = `${Jalali.formatShort(from)} — ${Jalali.formatShort(toLabel)}`;
    }
  }

  async function fetchEvents() {
    const { from, to } = rangeForView();
    const qs = new URLSearchParams({ from: fmtDate(from) + ' 00:00:00', to: fmtDate(to) + ' 00:00:00' });
    try {
      const res = await api(`/appointments?${qs.toString()}`);
      state.events = res.data.events;
    } catch (e) {
      state.events = [];
      console.warn('Appointments API error:', e.message);
    }
  }

  function eventsForDay(day) {
    const key = fmtDate(day);
    return state.events.filter(e => e.start.slice(0, 10) === key);
  }

  function renderDayColumn(day, compact = false) {
    const hours = [];
    for (let h = WORK_START; h < WORK_END; h++) hours.push(h);
    const dayEvents = eventsForDay(day);

    const slotsHtml = hours.map(h => {
      const hourEvents = dayEvents.filter(e => new Date(e.start).getHours() === h);
      return `
        <div class="cal-slot" data-hour="${h}" data-date="${fmtDate(day)}"
             style="border-bottom:1px solid var(--border);min-height:${compact ? 34 : 56}px;position:relative;padding:2px 4px">
          ${!compact ? `<span style="font-size:.68rem;color:var(--muted);position:absolute;left:4px;top:2px">${h}:00</span>` : ''}
          ${hourEvents.map(ev => `
            <div class="cal-event" draggable="true" data-id="${ev.id}"
                 style="background:var(--evergreen-soft);border-right:3px solid var(--evergreen);border-radius:.4rem;
                        padding:.25rem .5rem;margin:${compact ? '1px 0' : '2px 20px 2px 2px'};font-size:.72rem;cursor:grab">
              <div style="font-weight:700;color:var(--graphite)">${ev.patient_name}</div>
              <div style="color:var(--muted)">${fmtTime(ev.start)} · ${ev.reason || ''}</div>
              <span class="badge badge-${ev.badge}" style="margin-top:2px">${ev.status}</span>
            </div>`).join('')}
        </div>`;
    }).join('');

    return `<div class="cal-day-col" style="flex:1;min-width:0">${slotsHtml}</div>`;
  }

  function renderDayView() {
    const el = document.getElementById('calendar-body');
    el.innerHTML = `<div style="display:flex">${renderDayColumn(state.anchor)}</div>`;
    attachDnD();
  }

  function renderWeekView() {
    const from = startOfWeek(state.anchor);
    const days = Array.from({ length: 7 }, (_, i) => addDays(from, i));
    const header = days.map(d => {
      const { jd } = Jalali.toJalali(d);
      return `
      <div style="flex:1;text-align:center;font-size:.75rem;font-weight:700;padding:.5rem 0;border-bottom:2px solid var(--border)">
        ${WEEKDAYS_FA[d.getDay()]}<br><span style="color:var(--muted);font-weight:400">${Jalali.toPersianDigits(jd)}</span>
      </div>`;
    }).join('');
    const cols = days.map(d => renderDayColumn(d, true)).join('');
    document.getElementById('calendar-body').innerHTML =
      `<div style="display:flex">${header}</div><div style="display:flex">${cols}</div>`;
    attachDnD();
  }

  function renderMonthView() {
    const from = startOfMonth(state.anchor);
    const gridStart = startOfWeek(from);
    const cells = Array.from({ length: 42 }, (_, i) => addDays(gridStart, i));
    const header = WEEKDAYS_FA.map(w => `<div style="text-align:center;font-size:.72rem;font-weight:700;color:var(--muted);padding:.4rem 0">${w}</div>`).join('');
    const body = cells.map(d => {
      const inMonth = d.getMonth() === from.getMonth();
      const dayEvents = eventsForDay(d);
      const { jd } = Jalali.toJalali(d);
      return `
        <div class="cal-month-cell" data-date="${fmtDate(d)}"
             style="border:1px solid var(--border);min-height:88px;padding:.35rem;background:${inMonth ? '#fff' : 'var(--porcelain)'};opacity:${inMonth ? 1 : .5}">
          <div style="font-size:.75rem;font-weight:600;color:var(--graphite)">${Jalali.toPersianDigits(jd)}</div>
          ${dayEvents.slice(0,3).map(ev => `
            <div class="badge badge-${ev.badge}" style="display:block;margin-top:2px;font-size:.65rem;text-align:right;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
              ${fmtTime(ev.start)} ${ev.patient_name}
            </div>`).join('')}
          ${dayEvents.length > 3 ? `<div style="font-size:.65rem;color:var(--muted);margin-top:2px">+${dayEvents.length - 3} بیشتر</div>` : ''}
        </div>`;
    }).join('');
    document.getElementById('calendar-body').innerHTML =
      `<div style="display:grid;grid-template-columns:repeat(7,1fr)">${header}</div>
       <div style="display:grid;grid-template-columns:repeat(7,1fr)">${body}</div>`;
  }

  function renderAgendaView() {
    const grouped = {};
    state.events.forEach(ev => {
      const key = ev.start.slice(0, 10);
      (grouped[key] = grouped[key] || []).push(ev);
    });
    const keys = Object.keys(grouped).sort();
    const el = document.getElementById('calendar-body');
    if (!keys.length) {
      el.innerHTML = '<div style="text-align:center;color:var(--muted);padding:3rem">نوبتی در این بازه ثبت نشده است</div>';
      return;
    }
    el.innerHTML = keys.map(k => {
      const d = new Date(k);
      return `
        <div style="margin-bottom:1rem">
          <div style="font-weight:700;font-size:.85rem;margin-bottom:.5rem;padding-bottom:.3rem;border-bottom:1px solid var(--border)">
            ${Jalali.formatFull(d)}
          </div>
          ${grouped[k].map(ev => `
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.5rem 0;border-bottom:1px solid var(--border)">
              <div>
                <div style="font-weight:600;font-size:.85rem">${ev.patient_name}</div>
                <div style="font-size:.72rem;color:var(--muted)">${fmtTime(ev.start)} · ${ev.reason || ''}</div>
              </div>
              <span class="badge badge-${ev.badge}">${ev.status}</span>
            </div>`).join('')}
        </div>`;
    }).join('');
  }

  function render() {
    updateRangeLabel();
    if (state.view === 'day') renderDayView();
    else if (state.view === 'week') renderWeekView();
    else if (state.view === 'month') renderMonthView();
    else renderAgendaView();
  }

  async function load() {
    document.getElementById('calendar-body').innerHTML = '<div class="skeleton" style="height:400px"></div>';
    await fetchEvents();
    render();
  }

  function attachDnD() {
    document.querySelectorAll('.cal-event').forEach(evEl => {
      evEl.addEventListener('dragstart', (e) => {
        e.dataTransfer.setData('text/plain', evEl.dataset.id);
      });
      evEl.addEventListener('dblclick', () => {
        const ev = state.events.find(x => String(x.id) === evEl.dataset.id);
        if (ev) EmrModule.open(ev.patient_id, ev.id);
      });
    });
    document.querySelectorAll('.cal-slot').forEach(slot => {
      slot.addEventListener('dragover', (e) => e.preventDefault());
      slot.addEventListener('drop', async (e) => {
        e.preventDefault();
        const id = e.dataTransfer.getData('text/plain');
        const date = slot.dataset.date;
        const hour = slot.dataset.hour;
        const newStart = `${date} ${String(hour).padStart(2,'0')}:00:00`;
        try {
          await api(`/appointments/${id}/reschedule`, { method: 'PATCH', body: JSON.stringify({ scheduled_at: newStart }) });
          await load();
        } catch (err) {
          alert('تداخل زمانی یا خطا در جابجایی نوبت');
          console.warn(err.message);
        }
      });
    });
  }

  function setView(view) {
    state.view = view;
    document.querySelectorAll('#cal-view-switch button').forEach(b => {
      b.classList.toggle('btn-secondary', b.dataset.calView === view);
      b.classList.toggle('btn-ghost', b.dataset.calView !== view);
    });
    load();
  }

  function shift(direction) {
    const stepDays = state.view === 'month' ? null : (state.view === 'week' ? 7 : (state.view === 'agenda' ? 14 : 1));
    if (state.view === 'month') {
      state.anchor = new Date(state.anchor.getFullYear(), state.anchor.getMonth() + direction, 1);
    } else {
      state.anchor = addDays(state.anchor, direction * stepDays);
    }
    load();
  }

  function init() {
    document.querySelectorAll('#cal-view-switch button').forEach(b => {
      b.addEventListener('click', () => setView(b.dataset.calView));
    });
    document.getElementById('cal-prev').addEventListener('click', () => shift(-1));
    document.getElementById('cal-next').addEventListener('click', () => shift(1));
    document.getElementById('cal-today').addEventListener('click', () => { state.anchor = new Date(); load(); });
  }

  return { init, load };
})();
