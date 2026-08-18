(() => {
  'use strict';

  const CONFIG = Object.assign({
    OTP_SEND_URL: '/api/otp/send.php',
    OTP_VERIFY_URL: '/api/otp/verify.php',
    SUBMIT_URL: '/api/intake/submit.php',
    HEALTH_URL: '/api/health.php',
    API_TIMEOUT_MS: 300000,
    WEB_OTP_ENABLED: true,
    OTP_CODE_LENGTH: 5,
    VERIFIED_SESSION_TTL_SECONDS: 1800,
    SMS_PROVIDER_LABEL: 'سامانه پیامک'
  }, window.TAJ_CONFIG || {});

  const $ = (id) => document.getElementById(id);
  const state = { step: 1, otpToken: '', otpVerified: false, otpAbort: null, verifiedUntil: 0, verifiedTimer: null, submitting: false };

  function toEnglish(value) {
    const map = {'۰':'0','۱':'1','۲':'2','۳':'3','۴':'4','۵':'5','۶':'6','۷':'7','۸':'8','۹':'9','٠':'0','١':'1','٢':'2','٣':'3','٤':'4','٥':'5','٦':'6','٧':'7','٨':'8','٩':'9'};
    return String(value === undefined || value === null ? '' : value).replace(/[۰-۹٠-٩]/g, function (c) { return map[c] || c; });
  }
  function digits(value) { return toEnglish(value).replace(/\D/g, ''); }
  function normalizeNumericInput(input) {
    const original = String(input.value || '');
    const normalized = digits(original);
    if (original === normalized) return;
    const selectionStart = typeof input.selectionStart === 'number' ? input.selectionStart : null;
    const caret = selectionStart === null ? null : digits(original.slice(0, selectionStart)).length;
    input.value = normalized;
    if (caret !== null && typeof input.setSelectionRange === 'function') {
      try { input.setSelectionRange(caret, caret); } catch (error) { /* unsupported input type */ }
    }
  }
  document.querySelectorAll('input[inputmode="numeric"]').forEach((input) => {
    input.addEventListener('input', (event) => {
      if (!event.isComposing) normalizeNumericInput(input);
    });
    input.addEventListener('compositionend', () => normalizeNumericInput(input));
    input.addEventListener('change', () => normalizeNumericInput(input));
  });
  function mobile(value) { return /^09\d{9}$/.test(digits(value)); }
  function isValidNationalId(code) {
    code = digits(code);
    if (code.length < 8 || code.length > 10) return false;
    while (code.length < 10) code = '0' + code;
    if (/^(\d)\1{9}$/.test(code)) return false;
    let sum = 0;
    for (let i = 0; i < 9; i++) sum += parseInt(code.charAt(i), 10) * (10 - i);
    const remainder = sum % 11;
    const checkDigit = parseInt(code.charAt(9), 10);
    return (remainder < 2 && checkDigit === remainder) || (remainder >= 2 && checkDigit === 11 - remainder);
  }
  function toast(message, type = '', duration = 4200) {
    const el = $('toast-element');
    el.textContent = message;
    el.className = `toast show${type ? ` ${type}` : ''}`;
    clearTimeout(el._timer);
    el._timer = setTimeout(() => el.classList.remove('show'), duration);
  }
  function fieldGroup(el) { return el && el.closest ? el.closest('.input-group') : null; }
  function invalid(el, message) {
    const group = (el && el.classList && el.classList.contains('input-group')) ? el : fieldGroup(el);
    if (!group) return;
    group.classList.add('invalid');
    const error = group.querySelector('.error-message');
    if (error) error.textContent = message;
  }
  function valid(el) {
    const group = (el && el.classList && el.classList.contains('input-group')) ? el : fieldGroup(el);
    if (!group) return;
    group.classList.remove('invalid');
    const error = group.querySelector('.error-message');
    if (error) error.textContent = '';
  }
  function setBusy(button, busy, busyText, normalText) {
    button.disabled = busy;
    button.textContent = busy ? busyText : normalText;
  }
  function toPersianDigits(value) {
    return String(value).replace(/\d/g, (digit) => '۰۱۲۳۴۵۶۷۸۹'[Number(digit)]);
  }
  function verifiedSecondsRemaining() {
    return state.verifiedUntil > 0 ? Math.max(0, Math.ceil((state.verifiedUntil - Date.now()) / 1000)) : 0;
  }
  function formatRemainingTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;
    return toPersianDigits(`${String(minutes).padStart(2, '0')}:${String(remainder).padStart(2, '0')}`);
  }
  function stopVerificationTimer(hide) {
    if (state.verifiedTimer !== null) {
      clearInterval(state.verifiedTimer);
      state.verifiedTimer = null;
    }
    if (hide) {
      $('verification-timer').classList.add('hidden');
      $('verification-timer').classList.remove('warning', 'expired');
    }
  }
  function expireVerifiedSession(showToast) {
    stopVerificationTimer(false);
    state.otpVerified = false;
    state.otpToken = '';
    state.verifiedUntil = 0;
    if (state.otpAbort) state.otpAbort.abort();
    $('verification-code').value = '';
    $('code-input-wrapper').classList.add('hidden');
    $('verify-code-btn').classList.add('hidden');
    $('verify-code-btn').disabled = false;
    $('verify-code-btn').textContent = 'تأیید کد';
    $('send-code-btn').disabled = false;
    $('send-code-btn').textContent = 'ارسال کد جدید';
    $('otp-status').className = 'otp-status';
    $('otp-status').textContent = 'مهلت ۳۰ دقیقه‌ای تکمیل فرم به پایان رسید. لطفاً کد جدید دریافت کنید.';
    $('verification-timer-value').textContent = formatRemainingTime(0);
    $('verification-timer').classList.remove('hidden', 'warning');
    $('verification-timer').classList.add('expired');
    if (!state.submitting && !$('patient-form').classList.contains('hidden')) {
      goToStep(1);
      if (showToast !== false) toast('مهلت تکمیل فرم به پایان رسید. لطفاً کد جدید دریافت کنید.', 'error', 7000);
    }
  }
  function updateVerificationTimer() {
    const remaining = verifiedSecondsRemaining();
    $('verification-timer-value').textContent = formatRemainingTime(remaining);
    $('verification-timer').classList.toggle('warning', remaining > 0 && remaining <= 300);
    if (remaining <= 0) expireVerifiedSession(true);
  }
  function startVerificationTimer(seconds) {
    const configured = Number(CONFIG.VERIFIED_SESSION_TTL_SECONDS) || 1800;
    const ttl = Math.max(1, Math.floor(Number(seconds) || configured));
    stopVerificationTimer(false);
    state.otpVerified = true;
    state.verifiedUntil = Date.now() + (ttl * 1000);
    $('verification-timer').classList.remove('hidden', 'warning', 'expired');
    updateVerificationTimer();
    state.verifiedTimer = setInterval(updateVerificationTimer, 1000);
  }

  function api(url, payload) {
    return new Promise((resolve, reject) => {
      let request;
      try { request = new XMLHttpRequest(); }
      catch (e) { reject(new Error('مرورگر شما از ارسال فرم پشتیبانی نمی\u200cکند. لطفاً مرورگر خود را به\u200cروزرسانی کنید.')); return; }

      const timeoutMs = Number(CONFIG.API_TIMEOUT_MS) || 300000;
      let settled = false;
      const finish = (fn, arg) => { if (settled) return; settled = true; fn(arg); };

      try { request.open('POST', url, true); } catch (e) { finish(reject, new Error('امکان اتصال به سرور وجود ندارد.')); return; }
      try {
        request.setRequestHeader('Content-Type', 'application/json');
        request.setRequestHeader('Accept', 'application/json');
      } catch (e) { /* older WebViews may restrict headers; continue anyway */ }
      request.timeout = timeoutMs;
      request.withCredentials = true;

      request.onreadystatechange = function () {
        if (request.readyState !== 4) return;
        const status = request.status;
        const text = request.responseText || '';
        let data;
        try { data = text ? JSON.parse(text) : {}; }
        catch (e) {
          console.error('Non-JSON API response', status, text.slice(0, 1000));
          finish(reject, new Error(`پاسخ نامعتبر از سرور دریافت شد (HTTP ${status || 0}).`));
          return;
        }
        if (status === 0) { finish(reject, new Error('ارتباط با سرور برقرار نشد. اتصال اینترنت یا آدرس /api را بررسی کنید.')); return; }
        if (status < 200 || status >= 300 || data.success === false) {
          finish(reject, new Error(data.message || `خطای سرور (HTTP ${status})`));
          return;
        }
        finish(resolve, data);
      };
      request.onerror = function () { finish(reject, new Error('ارتباط با بخش PHP برقرار نشد. مسیر /api، SSL و محل app_private را بررسی کنید.')); };
      request.ontimeout = function () { finish(reject, new Error('پاسخ سرور بیش از حد طول کشید. دوباره تلاش کنید.')); };
      request.onabort = function () { finish(reject, new Error('درخواست لغو شد.')); };

      try { request.send(JSON.stringify(payload)); }
      catch (e) { finish(reject, new Error('ارسال اطلاعات با خطا مواجه شد.')); }
    });
  }

  function updateSelect(select) {
    select.classList.toggle('has-value', String(select.value) !== '');
  }
  document.querySelectorAll('.form-select').forEach((select) => {
    updateSelect(select);
    select.addEventListener('change', () => updateSelect(select));
  });

  function fillBirthDates() {
    const day = $('birth-day'), month = $('birth-month'), year = $('birth-year');
    for (let i = 1; i <= 31; i++) day.add(new Option(String(i).padStart(2,'0'), String(i)));
    for (let i = 1; i <= 12; i++) month.add(new Option(String(i).padStart(2,'0'), String(i)));
    for (let i = 1405; i >= 1300; i--) year.add(new Option(String(i), String(i)));
  }
  fillBirthDates();

  function goToStep(number) {
    document.querySelectorAll('.form-step').forEach((step) => step.classList.remove('active'));
    $(`step-${number}`).classList.add('active');
    document.querySelectorAll('.progress-step').forEach((item, index) => {
      item.classList.toggle('done', index + 1 < number);
      item.classList.toggle('active', index + 1 === number);
    });
    state.step = number;
    if (number === 3) requestAnimationFrame(() => signature.resize(true));
    window.scrollTo({top: 0, behavior: 'smooth'});
  }

  function resetOtp() {
    state.otpVerified = false;
    state.otpToken = '';
    state.verifiedUntil = 0;
    stopVerificationTimer(true);
    if (state.otpAbort) state.otpAbort.abort();
    $('verification-code').value = '';
    $('code-input-wrapper').classList.add('hidden');
    $('verify-code-btn').classList.add('hidden');
    $('verify-code-btn').disabled = false;
    $('verify-code-btn').textContent = 'تأیید کد';
    $('send-code-btn').textContent = 'ارسال کد';
    $('otp-status').className = 'otp-status';
    $('otp-status').textContent = '';
  }
  $('verification-phone').addEventListener('input', resetOtp);

  function validateIdentity() {
    let ok = true;
    for (const [id, label] of [['first-name','نام'],['last-name','نام خانوادگی']]) {
      const el = $(id), value = el.value.trim(), length = Array.from(value).length;
      if (length < 2 || length > 30) { invalid(el, `${label} باید بین ۲ تا ۳۰ حرف باشد.`); ok = false; }
      else valid(el);
    }
    const phone = $('verification-phone');
    if (!mobile(phone.value)) { invalid(phone, 'شماره همراه معتبر با 09 وارد کنید.'); ok = false; }
    else valid(phone);
    return ok;
  }

  function verifyOtp(automatic) {
    automatic = automatic || false;
    const button = $('verify-code-btn');
    if (button.disabled) return;
    const code = digits($('verification-code').value);
    if (!/^\d{4,6}$/.test(code)) { invalid($('verification-code'), 'کد ۴ تا ۶ رقمی را وارد کنید.'); return; }
    valid($('verification-code'));
    setBusy(button, true, 'در حال بررسی...', 'تأیید کد');
    api(CONFIG.OTP_VERIFY_URL, {
      phone: digits($('verification-phone').value),
      token: state.otpToken,
      code: code
    }).then(function (result) {
      if (state.otpAbort) state.otpAbort.abort();
      startVerificationTimer(result.expiresIn);
      $('otp-status').className = 'otp-status listening';
      $('otp-status').textContent = result.message || 'شماره با موفقیت تأیید شد.';
      button.textContent = 'تأیید شد ✓';
      toast('شماره تأیید شد؛ انتقال به مرحله بعد...', 'success');
      setTimeout(function () { goToStep(2); }, automatic ? 250 : 450);
    }).catch(function (error) {
      toast(error.message, 'error', 6500);
      setBusy(button, false, '', 'تأیید کد');
    });
  }

  function listenForWebOtp() {
    if (!CONFIG.WEB_OTP_ENABLED || !window.isSecureContext || !('OTPCredential' in window) || !navigator.credentials) return;
    if (state.otpAbort) state.otpAbort.abort();
    state.otpAbort = new AbortController();
    $('otp-status').className = 'otp-status listening';
    $('otp-status').textContent = 'در انتظار تشخیص خودکار کد پیامک…';
    navigator.credentials.get({otp:{transport:['sms']}, signal:state.otpAbort.signal}).then(function (credential) {
      if (credential && credential.code) {
        $('verification-code').value = digits(credential.code);
        verifyOtp(true);
      }
    }).catch(function (error) {
      if (!error || error.name !== 'AbortError') console.info('WebOTP unavailable:', error);
    });
  }

  $('send-code-btn').addEventListener('click', function () {
    if (!validateIdentity()) return;
    const button = $('send-code-btn');
    setBusy(button, true, 'در حال ارسال...', 'ارسال کد');
    api(CONFIG.OTP_SEND_URL, {phone: digits($('verification-phone').value)}).then(function (result) {
      state.otpVerified = false;
      state.verifiedUntil = 0;
      stopVerificationTimer(true);
      state.otpToken = result.token || '';
      $('verification-code').value = '';
      $('code-input-wrapper').classList.remove('hidden');
      $('verify-code-btn').classList.remove('hidden');
      $('verify-code-btn').disabled = false;
      $('verify-code-btn').textContent = 'تأیید کد';
      $('otp-status').className = 'otp-status';
      $('otp-status').textContent = result.message || 'کد تأیید ارسال شد.';
      try { $('verification-code').focus({preventScroll:true}); } catch (e) { $('verification-code').focus(); }
      button.textContent = 'ارسال مجدد';
      toast(result.message || 'کد تأیید ارسال شد.', 'success');
      listenForWebOtp();
      button.disabled = false;
    }).catch(function (error) {
      toast(error.message, 'error', 7000);
      setBusy(button, false, '', 'ارسال کد');
      button.disabled = false;
    });
  });
  $('verify-code-btn').addEventListener('click', function () { verifyOtp(false); });
  $('verification-code').addEventListener('input', function () {
    const length = digits($('verification-code').value).length;
    if (length === Number(CONFIG.OTP_CODE_LENGTH || 5) && state.otpToken) verifyOtp(true);
  });

  function updateInsurance() {
    const other = $('insurance-type').value === 'سایر موارد';
    $('insurance-other-wrap').classList.toggle('hidden', !other);
    $('insurance-other').required = other;
    if (!other) { $('insurance-other').value = ''; valid($('insurance-other')); }
  }
  function updateTehran() {
    const isTehran = $('province').value === 'تهران';
    $('tehran-district-wrap').classList.toggle('hidden', !isTehran);
    if (!isTehran) { $('tehran-district').value = ''; updateSelect($('tehran-district')); valid($('tehran-district-wrap')); }
  }
  $('insurance-type').addEventListener('change', updateInsurance);
  $('province').addEventListener('change', updateTehran);
  updateInsurance(); updateTehran();

  document.querySelectorAll('input[name="specific-condition"]').forEach((radio) => radio.addEventListener('change', () => {
    const checkedRadio = document.querySelector('input[name="specific-condition"]:checked');
    const show = checkedRadio ? checkedRadio.value === 'دارم' : false;
    $('specific-explanation-wrap').style.display = show ? 'block' : 'none';
    $('specific-condition-explanation').required = show;
    if (!show) { $('specific-condition-explanation').value = ''; valid($('specific-condition-explanation')); }
  }));

  function validateStep2() {
    let ok = true;
    const required = [
      ['father-name','نام پدر را وارد کنید.'],['occupation','شغل را انتخاب کنید.'],['national-id','کد ملی را وارد کنید.'],
      ['birth-day','روز تولد را انتخاب کنید.'],['birth-month','ماه تولد را انتخاب کنید.'],['birth-year','سال تولد را انتخاب کنید.'],
      ['province','استان را انتخاب کنید.'],['city','شهر را وارد کنید.'],['address-details','آدرس را وارد کنید.'],
      ['visit-reason','علت مراجعه را انتخاب کنید.'],['referral','نحوه آشنایی را انتخاب کنید.']
    ];
    required.forEach(([id,message]) => { const el=$(id); if (!String(el.value).trim()) { invalid(el,message); ok=false; } else valid(el); });
    const father = $('father-name'); if (Array.from(father.value.trim()).length < 2 || Array.from(father.value.trim()).length > 30) { invalid(father,'نام پدر باید بین ۲ تا ۳۰ حرف باشد.'); ok=false; }
    const city = $('city'); if (Array.from(city.value.trim()).length < 2 || Array.from(city.value.trim()).length > 40) { invalid(city,'نام شهر باید بین ۲ تا ۴۰ حرف باشد.'); ok=false; }
    const national = digits($('national-id').value);
    if (!isValidNationalId(national)) { invalid($('national-id'),'کد ملی وارد شده معتبر نیست.'); ok=false; }
    else valid($('national-id'));
    const emergencyRaw = $('mobile-phone-2').value.trim();
    if (emergencyRaw !== '') {
      const emergency = digits(emergencyRaw), patient = digits($('verification-phone').value);
      if (!mobile(emergency)) { invalid($('mobile-phone-2'),'شماره همراه اضطراری معتبر نیست.'); ok=false; }
      else if (emergency === patient) { invalid($('mobile-phone-2'),'شماره همراه اضطراری نباید شماره خود بیمار باشد.'); ok=false; }
      else valid($('mobile-phone-2'));
    } else { valid($('mobile-phone-2')); }
    if ($('province').value === 'تهران' && !$('tehran-district').value) { valid($('tehran-district-wrap')); }
    if ($('insurance-type').value === 'سایر موارد' && Array.from($('insurance-other').value.trim()).length < 2) { invalid($('insurance-other'),'نام بیمه یا سازمان را وارد کنید.'); ok=false; }
    return ok;
  }

  const signature = (() => {
    const canvas = $('signature-pad');
    const context = canvas.getContext('2d', {alpha:false});
    let drawing = false, empty = true, last = null;

    function position(event) {
      const rect = canvas.getBoundingClientRect();
      const clientX = (event.clientX !== undefined && event.clientX !== null) ? event.clientX : (event.touches && event.touches[0] ? event.touches[0].clientX : 0);
      const clientY = (event.clientY !== undefined && event.clientY !== null) ? event.clientY : (event.touches && event.touches[0] ? event.touches[0].clientY : 0);
      return {x: clientX - rect.left, y: clientY - rect.top};
    }
    function configure() {
      context.lineCap='round'; context.lineJoin='round'; context.strokeStyle='#17211a'; context.lineWidth=2.6;
    }
    function resize(preserve = true) {
      const rect = canvas.getBoundingClientRect();
      if (rect.width < 20 || rect.height < 20) return;
      const saved = preserve && !empty ? canvas.toDataURL('image/png') : '';
      const ratio = Math.max(1, Math.min(3, window.devicePixelRatio || 1));
      canvas.width = Math.round(rect.width * ratio); canvas.height = Math.round(rect.height * ratio);
      context.setTransform(ratio,0,0,ratio,0,0); context.fillStyle='#ffffff'; context.fillRect(0,0,rect.width,rect.height); configure();
      if (saved) { const image=new Image(); image.onload=()=>context.drawImage(image,0,0,rect.width,rect.height); image.src=saved; }
    }

    function start(event) {
      event.preventDefault();
      drawing = true; empty = false; last = position(event);
      if (event.pointerId !== undefined && canvas.setPointerCapture) canvas.setPointerCapture(event.pointerId);
    }
    function move(event) {
      if (!drawing) return;
      event.preventDefault();
      const next = position(event);
      context.beginPath(); context.moveTo(last.x,last.y); context.lineTo(next.x,next.y); context.stroke();
      last = next;
    }
    function stop(event) {
      if (drawing) event.preventDefault();
      drawing = false; last = null;
    }

    if (window.PointerEvent) {
      canvas.addEventListener('pointerdown', start, {passive:false});
      canvas.addEventListener('pointermove', move, {passive:false});
      canvas.addEventListener('pointerup', stop, {passive:false});
      canvas.addEventListener('pointercancel', stop, {passive:false});
      canvas.addEventListener('pointerleave', stop, {passive:false});
    } else {
      canvas.addEventListener('touchstart', start, {passive:false});
      canvas.addEventListener('touchmove', move, {passive:false});
      canvas.addEventListener('touchend', stop, {passive:false});
      canvas.addEventListener('touchcancel', stop, {passive:false});
      canvas.addEventListener('mousedown', start, {passive:false});
      canvas.addEventListener('mousemove', move, {passive:false});
      canvas.addEventListener('mouseup', stop, {passive:false});
      canvas.addEventListener('mouseleave', stop, {passive:false});
    }

    canvas.addEventListener('touchmove', (e) => e.preventDefault(), {passive:false});

    $('clear-signature-btn').addEventListener('click',()=>{ empty=true; resize(false); });
    let timer; window.addEventListener('resize',()=>{ clearTimeout(timer); timer=setTimeout(()=>resize(true),180); });
    let orientTimer; window.addEventListener('orientationchange', () => { clearTimeout(orientTimer); orientTimer = setTimeout(() => resize(true), 300); });
    return {resize, isEmpty:()=>empty, data:()=>empty?'':canvas.toDataURL('image/png')};
  })();

  function validateStep3() {
    let ok = true;
    const consent = $('confirmation-check'), error = document.querySelector('.checkbox-error-message');
    if (!consent.checked) { error.style.display='block'; error.textContent='تأیید رضایت الزامی است.'; ok=false; } else error.style.display='none';
    if (signature.isEmpty()) { toast('لطفاً امضای خود را ثبت کنید.', 'error'); ok=false; }
    const conditionRadio = document.querySelector('input[name="specific-condition"]:checked');
    const condition = conditionRadio ? conditionRadio.value : undefined;
    if (condition === 'دارم' && !$('specific-condition-explanation').value.trim()) { invalid($('specific-condition-explanation'),'توضیح ناراحتی را وارد کنید.'); ok=false; }
    const doctorRequest = $('doctor-request');
if (Array.from(doctorRequest.value.trim()).length < 2) {
  invalid(doctorRequest, 'لطفاً درخواست خود از دکتر را وارد کنید.');
  ok = false;
} else {
  valid(doctorRequest);
}
    return ok;
  }

  $('prev-step-btn-2').addEventListener('click',()=>goToStep(1));
  $('next-step-btn-2').addEventListener('click',()=>{ if(validateStep2()) goToStep(3); });
  $('prev-step-btn-3').addEventListener('click',()=>goToStep(2));

  function insuranceName() {
    return $('insurance-type').value === 'سایر موارد' ? $('insurance-other').value.trim() : $('insurance-type').value;
  }
  function buildPayload() {
    const insurance = insuranceName();
    const conditionRadio2 = document.querySelector('input[name="specific-condition"]:checked');
    const condition = conditionRadio2 ? conditionRadio2.value : '';
    const explanation = $('specific-condition-explanation').value.trim();
    const visitReason = $('visit-reason').value;
    const doctorRequest = $('doctor-request').value.trim();
    const description = [visitReason ? `علت مراجعه: ${visitReason}` : '', doctorRequest ? `شرح درخواست: ${doctorRequest}` : '', condition ? `ناراحتی خاص: ${condition}` : '', explanation ? `توضیح: ${explanation}` : '', insurance ? `نوع بیمه: ${insurance}` : ''].filter(Boolean).join(' | ');
    const address = [$('province').value, $('city').value.trim(), $('tehran-district').value ? `منطقه ${$('tehran-district').value}` : '', $('address-details').value.trim()].filter(Boolean).join('، ');
    return {
      FirstName:$('first-name').value.trim(), LastName:$('last-name').value.trim(), FatherName:$('father-name').value.trim(),
      TavalodDay:$('birth-day').value, TavalodMonth:$('birth-month').value, TavalodYear:$('birth-year').value,
      HomeTel:digits($('home-phone').value), Mobile:digits($('verification-phone').value), Mobile2:digits($('mobile-phone-2').value),
      CodeAshnaei:$('referral').value, CodeBimeh:'1', CodeMeli:digits($('national-id').value), CodeJob:$('occupation').value,
      HomeAd:address, Description:description, IsTransfer:'0', VisitReason:$('visit-reason').value, Email:$('patient-email').value.trim(),
      drugs:Array.from(document.querySelectorAll('.drug-chk:checked')).map((x)=>x.value).join('/'),
      difficult:Array.from(document.querySelectorAll('.med-hist:checked')).map((x)=>x.value).join('/'),
      morefmob:digits($('work-phone').value),
      _website:$('website-field').value,
      _source:location.href,
      _meta:{otpToken:state.otpToken, signature:signature.data(), insuranceType:insurance, ua:navigator.userAgent}
    };
  }

  $('patient-form').addEventListener('submit', function (event) {
    event.preventDefault();
    if (!state.otpVerified) { toast('شماره همراه دوباره باید تأیید شود.', 'error'); goToStep(1); return; }
    if (verifiedSecondsRemaining() <= 0) { expireVerifiedSession(true); return; }
    if (!validateStep3()) return;
    const button = $('submit-btn'); setBusy(button,true,'در حال ثبت...','ثبت نهایی پرونده');
    state.submitting = true;
    api(CONFIG.SUBMIT_URL, buildPayload()).then(function (result) {
      state.submitting = false; state.otpVerified = false; state.verifiedUntil = 0; stopVerificationTimer(true);
      $('patient-form').classList.add('hidden'); document.querySelector('.progress-bar').classList.add('hidden'); $('success-panel').classList.remove('hidden');
      $('success-msg').textContent = result.reference ? ('پرونده با کد پیگیری ' + result.reference + ' ثبت شد.') : 'پرونده با موفقیت ثبت شد.';
      toast('تشکیل پرونده اولیه شما با موفقیت انجام شد', 'success');
    }).catch(function (error) {
      state.submitting = false; setBusy(button,false,'','ثبت نهایی پرونده');
      if (verifiedSecondsRemaining() <= 0 || /مهلت تکمیل فرم/.test(error.message)) expireVerifiedSession(false);
      toast(error.message, 'error', 8500);
    });
  });

  (function () {
    let request;
    try { request = new XMLHttpRequest(); } catch (e) { return; }
    try { request.open('GET', CONFIG.HEALTH_URL, true); } catch (e) { return; }
    request.timeout = 15000;
    request.onreadystatechange = function () {
      if (request.readyState !== 4) return;
      let data = {};
      try { data = request.responseText ? JSON.parse(request.responseText) : {}; } catch (e) { data = {}; }
      if (request.status === 0 || request.status < 200 || request.status >= 300 || data.success === false) {
        $('api-warning').textContent = 'بخش PHP در دسترس نیست؛ فایل\u200cهای api و app_private را بررسی کنید.';
        $('api-warning').classList.remove('hidden');
        return;
      }
      if (Array.isArray(data.warnings) && data.warnings.length) {
        $('api-warning').textContent = data.warnings.join(' — ');
        $('api-warning').classList.remove('hidden');
      }
    };
    request.onerror = function () {
      $('api-warning').textContent = 'بخش PHP در دسترس نیست؛ فایل\u200cهای api و app_private را بررسی کنید.';
      $('api-warning').classList.remove('hidden');
    };
    request.ontimeout = request.onerror;
    try { request.send(); } catch (e) { /* ignore */ }
  })();
})();
