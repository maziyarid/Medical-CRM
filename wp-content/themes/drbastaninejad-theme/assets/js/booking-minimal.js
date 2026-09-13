(function () {
  'use strict';

  var api = window.__DRB_BOOKING_API__ || {};
  if (!api.appointment || !api.otpSend || !api.otpVerify || !api.nonce) return;

  var STORAGE_KEY = 'drb_appointment_v2_checkout';

  function digits(value) {
    var fa = '۰۱۲۳۴۵۶۷۸۹', ar = '٠١٢٣٤٥٦٧٨٩';
    return String(value || '').replace(/[۰-۹]/g, function (c) { return fa.indexOf(c); })
      .replace(/[٠-٩]/g, function (c) { return ar.indexOf(c); });
  }
  function normaliseMobile(value) {
    var d = digits(value).replace(/\D/g, '');
    if (/^98\d{10}$/.test(d)) d = '0' + d.slice(2);
    if (/^9\d{9}$/.test(d)) d = '0' + d;
    return d;
  }
  function validNationalId(value) {
    var id = digits(value).replace(/\D/g, '');
    if (!/^\d{10}$/.test(id) || /^(\d)\1{9}$/.test(id)) return false;
    var sum = 0;
    for (var i = 0; i < 9; i++) sum += Number(id[i]) * (10 - i);
    var r = sum % 11;
    return Number(id[9]) === (r < 2 ? r : 11 - r);
  }
  function field(label, name, type, attrs, help) {
    attrs = attrs || '';
    return '<label class="drb-min-field"><span>' + label + '</span><input name="' + name + '" type="' + type + '" ' + attrs + '>' +
      (help ? '<small>' + help + '</small>' : '') + '</label>';
  }
  function parseJson(response) {
    return response.json().catch(function () { return {}; }).then(function (data) {
      if (!response.ok || data.success === false || data.ok === false || data.code) {
        throw new Error(data.message || (data.errors && data.errors[0] && data.errors[0].message) || 'انجام درخواست ممکن نشد.');
      }
      return data;
    });
  }
  function request(url, body) {
    return fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type': 'application/json', 'X-DRB-Form-Nonce': api.nonce},
      body: JSON.stringify(body)
    }).then(parseJson);
  }
  function getJson(url) {
    return fetch(url, {
      method: 'GET', credentials: 'same-origin',
      headers: {'Accept': 'application/json', 'X-DRB-Form-Nonce': api.nonce}
    }).then(parseJson);
  }
  function safeStore(value) {
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(value)); } catch (e) {}
  }
  function safeLoad() {
    try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null'); } catch (e) { return null; }
  }
  function safeClear() {
    try { sessionStorage.removeItem(STORAGE_KEY); } catch (e) {}
  }
  function formatRials(value) {
    var amount = Number(value || 0);
    try { return new Intl.NumberFormat('fa-IR').format(amount) + ' ریال'; }
    catch (e) { return amount + ' ریال'; }
  }
  function formatDateFa(value) {
    try {
      var d = new Date(String(value) + 'T12:00:00');
      return new Intl.DateTimeFormat('fa-IR-u-ca-persian', {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'}).format(d);
    } catch (e) { return String(value || ''); }
  }
  function findLegacyForm() {
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      if (!forms[i].hasAttribute('data-drb-minimal-booking') &&
          forms[i].querySelector('input[type="tel"]') &&
          forms[i].querySelector('input[type="number"]')) {
        return forms[i];
      }
    }
    return null;
  }
  function paymentReturnState() {
    try { return new URLSearchParams(window.location.search).get('booking_payment') || ''; }
    catch (e) { return ''; }
  }
  function cleanPaymentQuery() {
    try {
      var url = new URL(window.location.href);
      url.searchParams.delete('booking_payment');
      history.replaceState({}, document.title, url.pathname + (url.search ? '?' + url.searchParams.toString() : '') + url.hash);
    } catch (e) {}
  }
  function resultBox(kind) {
    var box = document.createElement('div');
    box.className = 'drb-payment-return is-' + kind;
    var title = document.createElement('strong');
    var text = document.createElement('p');
    if (kind === 'success') {
      title.textContent = 'پرداخت با موفقیت تأیید شد';
      text.textContent = 'زمان انتخاب‌شده برای شما نگه داشته شده است. نوبت پس از بررسی پذیرش و تأیید نهایی کلینیک قطعی می‌شود.';
    } else if (kind === 'review') {
      title.textContent = 'پرداخت دریافت شد و نیاز به بررسی دارد';
      text.textContent = 'پرداخت ثبت شده است، اما نگهداری زمان نیاز به تطبیق توسط پذیرش دارد. همکاران کلینیک با شما تماس می‌گیرند.';
    } else {
      title.textContent = 'پرداخت تکمیل نشد';
      text.textContent = 'نوبت قطعی نشده است. اگر زمان هنوز در مهلت نگهداری باشد می‌توانید پرداخت را دوباره انجام دهید.';
    }
    box.appendChild(title); box.appendChild(text);
    return box;
  }

  function renderFallback(host, message) {
    var success = document.createElement('div');
    success.className = 'drb-min-success';
    var title = document.createElement('strong');
    title.textContent = 'درخواست شما دریافت شد';
    var detail = document.createElement('p');
    detail.textContent = message || 'در حال حاضر زمان آنلاین قابل انتخاب نیست. این ثبت به معنی نوبت قطعی نیست و همکاران کلینیک برای اعلام و تأیید زمان با شما تماس می‌گیرند.';
    success.appendChild(title); success.appendChild(detail);
    host.replaceChildren(success);
  }

  function renderSlotStage(host, appointmentToken, introMessage, remembered) {
    if (!api.availability || !api.checkout || !appointmentToken) {
      renderFallback(host, introMessage);
      return;
    }

    host.innerHTML = '<section class="drb-slot-stage" data-drb-slot-stage>' +
      '<div class="drb-slot-heading"><strong>انتخاب زمان نوبت</strong><p>درخواست اولیه شما ثبت شد. یکی از زمان‌های باز را انتخاب کنید و بیعانه را آنلاین بپردازید. پرداخت نیز به تنهایی به معنی تأیید نهایی نوبت نیست.</p></div>' +
      '<div class="drb-slot-loading">در حال دریافت زمان‌های آزاد…</div>' +
      '<div class="drb-slot-content" hidden></div>' +
      '<div class="drb-min-status" role="status" aria-live="polite"></div>' +
      '</section>';

    var stage = host.querySelector('[data-drb-slot-stage]');
    var loading = stage.querySelector('.drb-slot-loading');
    var content = stage.querySelector('.drb-slot-content');
    var status = stage.querySelector('.drb-min-status');
    var selected = {dayId: 0, startAt: '', gateway: ''};

    function message(text, error) {
      status.textContent = text || '';
      status.className = 'drb-min-status' + (error ? ' is-error' : ' is-ok');
    }

    getJson(api.availability).then(function (data) {
      var days = (data.days || []).filter(function (day) { return day && day.status === 'open' && Array.isArray(day.slots) && day.slots.length; });
      var payment = data.payment || {};
      var gateways = Array.isArray(payment.gateways) ? payment.gateways : [];
      loading.hidden = true;

      if (!days.length) {
        renderFallback(host, 'درخواست شما ثبت شد، اما در حال حاضر زمان آنلاین بازی وجود ندارد. پذیرش برای هماهنگی زمان با شما تماس می‌گیرد.');
        return;
      }
      if (!payment.ready || !gateways.length) {
        renderFallback(host, 'درخواست شما ثبت شد و زمان‌های آنلاین موجودند، اما پرداخت آنلاین هنوز فعال نشده است. پذیرش برای هماهنگی و تأیید زمان با شما تماس می‌گیرد.');
        return;
      }

      var html = '<div class="drb-slot-days">';
      days.forEach(function (day) {
        html += '<section class="drb-slot-day" data-day-id="' + Number(day.id) + '">' +
          '<div class="drb-slot-day-head"><strong>' + formatDateFa(day.date) + '</strong><small>' + Number(day.remaining || 0) + ' ظرفیت باقی‌مانده</small></div>' +
          '<div class="drb-slot-times">';
        day.slots.forEach(function (slot) {
          html += '<button type="button" class="drb-slot-time" data-day="' + Number(day.id) + '" data-start="' + String(slot.start_at) + '">' + String(slot.local_time) + '</button>';
        });
        html += '</div></section>';
      });
      html += '</div>';
      html += '<div class="drb-slot-payment"><strong>درگاه پرداخت</strong><p>مبلغ بیعانه: <b>' + formatRials(payment.amountRials) + '</b></p><div class="drb-gateway-options">';
      gateways.forEach(function (gateway, index) {
        var label = gateway === 'zarinpal' ? 'زرین‌پال' : gateway === 'vandar' ? 'وندار' : gateway;
        html += '<label class="drb-gateway"><input type="radio" name="drb_gateway" value="' + gateway + '" ' + (index === 0 ? 'checked' : '') + '><span>' + label + '</span></label>';
      });
      html += '</div><button type="button" class="drb-min-submit drb-pay-submit" disabled>ادامه و پرداخت بیعانه</button><small>زمان برای مدت کوتاهی هنگام انتقال به درگاه نگه داشته می‌شود.</small></div>';
      content.innerHTML = html;
      content.hidden = false;

      selected.gateway = gateways[0] || '';
      if (remembered && gateways.indexOf(remembered.gateway) !== -1) selected.gateway = remembered.gateway;
      var gatewayInput = content.querySelector('input[name="drb_gateway"][value="' + selected.gateway + '"]');
      if (gatewayInput) gatewayInput.checked = true;

      var pay = content.querySelector('.drb-pay-submit');
      content.addEventListener('click', function (event) {
        var button = event.target.closest && event.target.closest('.drb-slot-time');
        if (!button) return;
        content.querySelectorAll('.drb-slot-time.is-selected').forEach(function (el) { el.classList.remove('is-selected'); });
        button.classList.add('is-selected');
        selected.dayId = Number(button.getAttribute('data-day') || 0);
        selected.startAt = button.getAttribute('data-start') || '';
        pay.disabled = !(selected.dayId && selected.startAt && selected.gateway);
        message('', false);
      });
      content.addEventListener('change', function (event) {
        if (event.target && event.target.name === 'drb_gateway') {
          selected.gateway = event.target.value;
          pay.disabled = !(selected.dayId && selected.startAt && selected.gateway);
        }
      });

      if (remembered && remembered.openDayId && remembered.startAt) {
        var prior = content.querySelector('.drb-slot-time[data-day="' + Number(remembered.openDayId) + '"][data-start="' + remembered.startAt + '"]');
        if (prior) prior.click();
      }

      pay.addEventListener('click', function () {
        if (!(selected.dayId && selected.startAt && selected.gateway)) return;
        pay.disabled = true;
        message('در حال نگهداری زمان و اتصال به درگاه پرداخت…', false);
        var state = {token: appointmentToken, openDayId: selected.dayId, startAt: selected.startAt, gateway: selected.gateway};
        safeStore(state);
        request(api.checkout, {
          appointment_token: appointmentToken,
          open_day_id: selected.dayId,
          start_at: selected.startAt,
          gateway: selected.gateway
        }).then(function (data) {
          if (!data.redirectUrl) throw new Error('لینک درگاه پرداخت دریافت نشد.');
          var redirect;
          try { redirect = new URL(data.redirectUrl, window.location.href); } catch (e) { throw new Error('لینک درگاه پرداخت معتبر نیست.'); }
          if (redirect.protocol !== 'https:') throw new Error('لینک درگاه پرداخت امن نیست.');
          message('در حال انتقال به درگاه پرداخت…', false);
          window.location.assign(redirect.href);
        }).catch(function (error) {
          message(error.message, true);
          pay.disabled = false;
        });
      });
    }).catch(function (error) {
      loading.hidden = true;
      message(error.message || 'دریافت زمان‌های آزاد ممکن نشد.', true);
      var fallback = document.createElement('p');
      fallback.className = 'drb-min-notice';
      fallback.textContent = introMessage || 'درخواست شما ثبت شده است. در صورت ادامه مشکل، پذیرش برای هماهنگی زمان با شما تماس می‌گیرد.';
      stage.appendChild(fallback);
    });
  }

  function mount() {
    var legacy = findLegacyForm();
    var existing = document.querySelector('[data-drb-minimal-booking]');
    if (existing) {
      if (legacy) legacy.hidden = true;
      return;
    }
    if (!legacy || !legacy.parentNode) return;

    var paymentState = paymentReturnState();
    var remembered = safeLoad();
    if (paymentState === 'success' || paymentState === 'review') {
      legacy.hidden = true;
      var completed = document.createElement('div');
      completed.className = 'drb-min-booking';
      completed.setAttribute('data-drb-minimal-booking', '1');
      completed.appendChild(resultBox(paymentState));
      legacy.parentNode.insertBefore(completed, legacy);
      safeClear(); cleanPaymentQuery();
      return;
    }

    if (paymentState === 'failed' && remembered && remembered.token && api.availability && api.checkout) {
      legacy.hidden = true;
      var retry = document.createElement('div');
      retry.className = 'drb-min-booking';
      retry.setAttribute('data-drb-minimal-booking', '1');
      retry.appendChild(resultBox('failed'));
      var retryHost = document.createElement('div');
      retry.appendChild(retryHost);
      legacy.parentNode.insertBefore(retry, legacy);
      cleanPaymentQuery();
      renderSlotStage(retryHost, remembered.token, '', remembered);
      return;
    }

    var form = document.createElement('form');
    form.className = 'drb-min-booking';
    form.setAttribute('data-drb-minimal-booking', '1');
    form.noValidate = true;
    form.innerHTML =
      (paymentState === 'failed' ? '<div class="drb-payment-return is-failed"><strong>پرداخت تکمیل نشد</strong><p>برای ادامه، اطلاعات را بررسی و درخواست را دوباره ثبت کنید.</p></div>' : '') +
      '<div class="drb-min-grid">' +
      field('نام *', 'firstName', 'text', 'required maxlength="100" autocomplete="given-name"') +
      field('نام خانوادگی *', 'lastName', 'text', 'required maxlength="100" autocomplete="family-name"') +
      field('تاریخ تولد شمسی *', 'birthDateJalali', 'text', 'required inputmode="numeric" placeholder="۱۳۷۰/۰۵/۱۲"', 'نمونه: ۱۳۷۰/۰۵/۱۲') +
      field('کد ملی *', 'nationalId', 'text', 'required inputmode="numeric" maxlength="10" autocomplete="off"') +
      '</div>' +
      '<div class="drb-min-otp"><label class="drb-min-field"><span>تلفن همراه * <b data-otp-badge>تأیید نشده</b></span>' +
      '<span class="drb-min-phone"><input name="mobile" type="tel" required inputmode="tel" maxlength="11" placeholder="09123456789" autocomplete="tel"><button type="button" data-otp-send>ارسال کد</button></span></label>' +
      '<div class="drb-min-code" hidden><label class="drb-min-field"><span>کد تأیید پیامکی *</span><span class="drb-min-phone"><input name="otp" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code"><button type="button" data-otp-verify>تأیید شماره</button></span></label></div></div>' +
      field('ایمیل', 'email', 'email', 'autocomplete="email" dir="ltr"', 'اختیاری؛ در صورت تکمیل، ایمیل تأیید دریافت درخواست برای شما ارسال می‌شود.') +
      '<label class="drb-min-field"><span>تاریخچه پزشکی *</span><textarea name="medicalHistory" required maxlength="2000" placeholder="بیماری‌ها، حساسیت‌ها و سوابق مهم؛ اگر موردی ندارید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>داروهای مصرفی *</span><textarea name="medications" required maxlength="2000" placeholder="نام داروها؛ اگر دارویی مصرف نمی‌کنید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>درخواست شما از دکتر *</span><textarea name="doctorRequest" required maxlength="2000" placeholder="موضوع و درخواست خود را کوتاه و روشن بنویسید"></textarea></label>' +
      '<p class="drb-min-notice">ثبت فرم یا پرداخت بیعانه به تنهایی به معنی نوبت قطعی نیست. در صورت وجود زمان آنلاین، پس از ثبت فرم می‌توانید زمان را انتخاب کنید؛ تأیید نهایی توسط پذیرش کلینیک انجام می‌شود.</p>' +
      '<div class="drb-min-status" role="status" aria-live="polite"></div>' +
      '<button class="drb-min-submit" type="submit">ثبت اطلاعات و ادامه</button>';

    legacy.hidden = true;
    legacy.parentNode.insertBefore(form, legacy);
    if (paymentState === 'failed') cleanPaymentQuery();
    var status = form.querySelector('.drb-min-status');
    var mobileInput = form.elements.mobile;
    var codeBox = form.querySelector('.drb-min-code');
    var badge = form.querySelector('[data-otp-badge]');
    var verificationToken = '';
    var verifiedMobile = '';

    function message(text, error) {
      status.textContent = text || '';
      status.className = 'drb-min-status' + (error ? ' is-error' : ' is-ok');
    }
    function resetVerification() {
      verificationToken = ''; verifiedMobile = '';
      badge.textContent = 'تأیید نشده'; badge.className = '';
    }

    mobileInput.addEventListener('input', resetVerification);
    form.querySelector('[data-otp-send]').addEventListener('click', function () {
      var mobile = normaliseMobile(mobileInput.value);
      if (!/^09\d{9}$/.test(mobile)) return message('شماره همراه معتبر وارد کنید.', true);
      this.disabled = true; message('در حال ارسال کد…', false);
      var button = this;
      request(api.otpSend, {mobile: mobile}).then(function (data) {
        codeBox.hidden = false; form.elements.otp.focus();
        message(data.message || 'کد تأیید ارسال شد.', false);
      }).catch(function (error) { message(error.message, true); })
        .finally(function () { button.disabled = false; });
    });
    form.querySelector('[data-otp-verify]').addEventListener('click', function () {
      var mobile = normaliseMobile(mobileInput.value), otp = digits(form.elements.otp.value).replace(/\D/g, '');
      if (!/^\d{5}$/.test(otp)) return message('کد پنج‌رقمی را وارد کنید.', true);
      this.disabled = true; message('در حال تأیید شماره…', false);
      var button = this;
      request(api.otpVerify, {mobile: mobile, otp: otp}).then(function (data) {
        verificationToken = data.verificationToken || ''; verifiedMobile = mobile;
        if (!verificationToken) throw new Error('توکن تأیید دریافت نشد.');
        badge.textContent = 'تأیید شد'; badge.className = 'is-verified';
        message('شماره همراه با موفقیت تأیید شد.', false);
      }).catch(function (error) { resetVerification(); message(error.message, true); })
        .finally(function () { button.disabled = false; });
    });
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var mobile = normaliseMobile(mobileInput.value);
      var national = digits(form.elements.nationalId.value).replace(/\D/g, '');
      var birth = digits(form.elements.birthDateJalali.value).replace(/-/g, '/');
      if (!verificationToken || verifiedMobile !== mobile) return message('ابتدا شماره همراه را با کد پیامکی تأیید کنید.', true);
      if (!validNationalId(national)) return message('کد ملی معتبر نیست.', true);
      if (!/^(?:12|13|14|15)\d{2}\/(?:0?[1-9]|1[0-2])\/(?:0?[1-9]|[12]\d|3[01])$/.test(birth)) return message('تاریخ تولد شمسی را مانند ۱۳۷۰/۰۵/۱۲ وارد کنید.', true);
      if (!form.checkValidity()) { form.reportValidity(); return; }
      var submit = form.querySelector('.drb-min-submit');
      submit.disabled = true; message('در حال ثبت درخواست…', false);
      request(api.appointment, {
        firstName: form.elements.firstName.value.trim(), lastName: form.elements.lastName.value.trim(),
        birthDateJalali: birth, nationalId: national, mobile: mobile,
        email: form.elements.email.value.trim(), medicalHistory: form.elements.medicalHistory.value.trim(),
        medications: form.elements.medications.value.trim(), doctorRequest: form.elements.doctorRequest.value.trim(),
        otpToken: verificationToken, language: 'fa'
      }).then(function (data) {
        var intro = data.message || 'درخواست اولیه شما ثبت شد.';
        if (data.appointmentToken && api.availability && api.checkout) {
          safeStore({token: data.appointmentToken});
          renderSlotStage(form, data.appointmentToken, intro, null);
        } else {
          renderFallback(form, intro);
        }
      }).catch(function (error) { message(error.message, true); submit.disabled = false; });
    });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', mount);
  else mount();
  window.addEventListener('popstate', function () { setTimeout(mount, 50); });
  if ('MutationObserver' in window) {
    new MutationObserver(function () { mount(); }).observe(document.documentElement, {childList: true, subtree: true});
  }
})();
