(function () {
  'use strict';

  var api = window.__DRB_BOOKING_API__ || {};
  if (!api.appointment || !api.otpSend || !api.otpVerify || !api.nonce) return;

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
  function request(url, body) {
    return fetch(url, {
      method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type': 'application/json', 'X-DRB-Form-Nonce': api.nonce},
      body: JSON.stringify(body)
    }).then(function (response) {
      return response.json().catch(function () { return {}; }).then(function (data) {
        if (!response.ok || data.success === false || data.ok === false) {
          throw new Error(data.message || 'انجام درخواست ممکن نشد.');
        }
        return data;
      });
    });
  }
  function field(label, name, type, attrs, help) {
    attrs = attrs || '';
    return '<label class="drb-min-field"><span>' + label + '</span><input name="' + name + '" type="' + type + '" ' + attrs + '>' +
      (help ? '<small>' + help + '</small>' : '') + '</label>';
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

  function mount() {
    var legacy = findLegacyForm();
    var existing = document.querySelector('[data-drb-minimal-booking]');
    if (existing) {
      if (legacy) legacy.hidden = true;
      return;
    }
    if (!legacy || !legacy.parentNode) return;

    var form = document.createElement('form');
    form.className = 'drb-min-booking';
    form.setAttribute('data-drb-minimal-booking', '1');
    form.noValidate = true;
    form.innerHTML =
      '<div class="drb-min-grid">' +
      field('نام *', 'firstName', 'text', 'required maxlength="100" autocomplete="given-name"') +
      field('نام خانوادگی *', 'lastName', 'text', 'required maxlength="100" autocomplete="family-name"') +
      field('تاریخ تولد شمسی *', 'birthDateJalali', 'text', 'required inputmode="numeric" placeholder="۱۳۷۰/۰۵/۱۲"', 'نمونه: ۱۳۷۰/۰۵/۱۲') +
      field('کد ملی *', 'nationalId', 'text', 'required inputmode="numeric" maxlength="10" autocomplete="off"') +
      '</div>' +
      '<div class="drb-min-otp"><label class="drb-min-field"><span>تلفن همراه * <b data-otp-badge>تأیید نشده</b></span>' +
      '<span class="drb-min-phone"><input name="mobile" type="tel" required inputmode="tel" maxlength="11" placeholder="09123456789" autocomplete="tel"><button type="button" data-otp-send>ارسال کد</button></span></label>' +
      '<div class="drb-min-code" hidden><label class="drb-min-field"><span>کد تأیید پیامکی *</span><span class="drb-min-phone"><input name="otp" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code"><button type="button" data-otp-verify>تأیید شماره</button></span></label></div></div>' +
      field('ایمیل', 'email', 'email', 'autocomplete="email" dir="ltr"', 'اختیاری؛ در صورت تکمیل، ایمیل تأیید نوبت و راه‌اندازی حساب کاربری برای شما ارسال می‌شود.') +
      '<label class="drb-min-field"><span>تاریخچه پزشکی *</span><textarea name="medicalHistory" required maxlength="2000" placeholder="بیماری‌ها، حساسیت‌ها و سوابق مهم؛ اگر موردی ندارید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>داروهای مصرفی *</span><textarea name="medications" required maxlength="2000" placeholder="نام داروها؛ اگر دارویی مصرف نمی‌کنید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>درخواست شما از دکتر *</span><textarea name="doctorRequest" required maxlength="2000" placeholder="موضوع و درخواست خود را کوتاه و روشن بنویسید"></textarea></label>' +
      '<p class="drb-min-notice">این فرم فقط درخواست نوبت است و زمان نوبت را قطعی نمی‌کند. همکاران کلینیک پس از بررسی برای اعلام و تأیید زمان با شما تماس می‌گیرند.</p>' +
      '<div class="drb-min-status" role="status" aria-live="polite"></div>' +
      '<button class="drb-min-submit" type="submit">ثبت درخواست نوبت</button>';

    legacy.hidden = true;
    legacy.parentNode.insertBefore(form, legacy);
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
        var success = document.createElement('div');
        success.className = 'drb-min-success';
        var title = document.createElement('strong');
        title.textContent = 'درخواست شما دریافت شد';
        var detail = document.createElement('p');
        detail.textContent = data.message || 'این ثبت به معنی نوبت قطعی نیست. همکاران کلینیک برای اعلام و تأیید زمان با شما تماس می‌گیرند.';
        success.appendChild(title); success.appendChild(detail);
        form.replaceChildren(success);
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
