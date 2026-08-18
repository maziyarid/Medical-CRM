(function () {
  'use strict';
  function translated(message) { return (window.__DRB_LOCALIZED_FORMS__ || {})[message] || message; }
  document.addEventListener('submit', async function (event) {
    var form = event.target.closest('.drb-localized-form');
    if (!form) return;
    event.preventDefault();
    var status = form.querySelector('.drb-localized-form__status');
    var button = form.querySelector('button[type="submit"]');
    var api = window.__DRB_FORMS_API__ || {};
    var endpoint = api[form.dataset.endpoint];
    if (!endpoint) { status.textContent = translated('سامانه نوبت‌دهی موقتاً در دسترس نیست.'); return; }
    var payload = Object.fromEntries(new FormData(form).entries());
    var procedureSelect = form.querySelector('select[name="procedure"]');
    if (procedureSelect && procedureSelect.selectedOptions && procedureSelect.selectedOptions[0]) {
      var stableKey = procedureSelect.selectedOptions[0].getAttribute('data-procedure-key') || '';
      if (stableKey) payload.procedureKey = stableKey;
    }
    var proc = String(payload.procedure || payload.service || '').toLowerCase();
    if (!payload.procedureKey && /ترمیم|ترميم|revision|revizyon|ревиз|повторн|révision|revisión/.test(proc)) payload.procedureKey = 'revision-rhinoplasty';
    var locale = window.__DRB_I18N__ && window.__DRB_I18N__.lang;
    if (!payload.language && locale) payload.language = locale;
    button.disabled = true; status.textContent = '';
    try {
      var response = await fetch(endpoint, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-DRB-Form-Nonce': api.nonce || '' }, body: JSON.stringify(payload) });
      var body = await response.json().catch(function () { return {}; });
      var message = body.message || (body.data && body.data.message) || (response.ok ? 'درخواست نوبت شما با موفقیت ثبت شد.' : 'ثبت درخواست نوبت انجام نشد. لطفاً دوباره تلاش کنید.');
      status.textContent = translated(message);
      status.dataset.state = response.ok ? 'success' : 'error';
      if (response.ok) form.reset();
    } catch (error) { status.textContent = translated('ثبت درخواست نوبت انجام نشد. لطفاً دوباره تلاش کنید.'); status.dataset.state = 'error'; }
    finally { button.disabled = false; }
  });
}());
