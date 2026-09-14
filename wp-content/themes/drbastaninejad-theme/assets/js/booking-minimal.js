(function () {
  'use strict';

  var api = window.__DRB_BOOKING_API__ || {};
  if (!api.appointment || !api.otpSend || !api.otpVerify || !api.nonce) return;
  var v2 = '/wp-json/drb/v1/booking-v2';

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
    }).then(parseResponse);
  }
  function v2Post(path, body) {
    return fetch(v2 + path, {
      method: 'POST', credentials: 'same-origin',
      headers: {'Content-Type': 'application/json', 'X-DRB-Form-Nonce': api.nonce},
      body: JSON.stringify(body)
    }).then(parseResponse);
  }
  function parseResponse(response) {
    return response.json().catch(function () { return {}; }).then(function (data) {
      if (!response.ok || data.success === false || data.ok === false) {
        var message = data.message || (data.errors && data.errors[0] && data.errors[0].message) || 'انجام درخواست ممکن نشد.';
        var err = new Error(message); err.status = response.status; throw err;
      }
      return data;
    });
  }
  function field(label, name, type, attrs, help) {
    attrs = attrs || '';
    return '<label class="drb-min-field"><span>' + label + '</span><input name="' + name + '" type="' + type + '" ' + attrs + '>' +
      (help ? '<small>' + help + '</small>' : '') + '</label>';
  }
  function faDate(iso) {
    try { return new Intl.DateTimeFormat('fa-IR-u-ca-persian',{weekday:'long',day:'numeric',month:'long'}).format(new Date(iso+'T12:00:00')); }
    catch (_) { return iso; }
  }
  function toman(rials) {
    return new Intl.NumberFormat('fa-IR').format(Math.round(Number(rials || 0) / 10));
  }
  function findLegacyForm() {
    var forms = document.querySelectorAll('form');
    for (var i = 0; i < forms.length; i++) {
      if (!forms[i].hasAttribute('data-drb-minimal-booking') && forms[i].querySelector('input[type="tel"]') && forms[i].querySelector('input[type="number"]')) return forms[i];
    }
    return null;
  }

  function mount() {
    var legacy = findLegacyForm();
    var existing = document.querySelector('[data-drb-minimal-booking]');
    if (existing) { if (legacy) legacy.hidden = true; return; }
    if (!legacy || !legacy.parentNode) return;

    var form = document.createElement('form');
    form.className = 'drb-min-booking'; form.setAttribute('data-drb-minimal-booking','1'); form.noValidate = true;
    form.innerHTML =
      '<div class="drb-min-grid">' +
      field('نام *','firstName','text','required maxlength="100" autocomplete="given-name"') +
      field('نام خانوادگی *','lastName','text','required maxlength="100" autocomplete="family-name"') +
      field('تاریخ تولد شمسی *','birthDateJalali','text','required inputmode="numeric" placeholder="۱۳۷۰/۰۵/۱۲"','نمونه: ۱۳۷۰/۰۵/۱۲') +
      field('کد ملی *','nationalId','text','required inputmode="numeric" maxlength="10" autocomplete="off"') + '</div>' +
      '<div class="drb-min-otp"><label class="drb-min-field"><span>تلفن همراه * <b data-otp-badge>تأیید نشده</b></span><span class="drb-min-phone"><input name="mobile" type="tel" required inputmode="tel" maxlength="11" placeholder="09123456789" autocomplete="tel"><button type="button" data-otp-send>ارسال کد</button></span></label>' +
      '<div class="drb-min-code" hidden><label class="drb-min-field"><span>کد تأیید پیامکی *</span><span class="drb-min-phone"><input name="otp" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code"><button type="button" data-otp-verify>تأیید شماره</button></span></label></div></div>' +
      field('ایمیل','email','email','autocomplete="email" dir="ltr"','اختیاری') +
      '<label class="drb-min-field"><span>تاریخچه پزشکی *</span><textarea name="medicalHistory" required maxlength="2000" placeholder="اگر موردی ندارید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>داروهای مصرفی *</span><textarea name="medications" required maxlength="2000" placeholder="اگر دارویی مصرف نمی‌کنید بنویسید ندارم"></textarea></label>' +
      '<label class="drb-min-field"><span>درخواست شما از دکتر *</span><textarea name="doctorRequest" required maxlength="2000"></textarea></label>' +
      '<section class="drb-min-availability"><h3>انتخاب تاریخ و ساعت نوبت</h3><p class="drb-min-fee" data-fee>در حال دریافت زمان‌های آزاد…</p><div data-availability class="drb-min-slots"></div></section>' +
      '<p class="drb-min-notice">نوبت آنلاین فقط پس از انتخاب زمان آزاد و پرداخت هزینه ویزیت قطعی می‌شود. زمان‌های نمایش‌داده‌شده با تقویم کلینیک همگام هستند.</p>' +
      '<div class="drb-min-status" role="status" aria-live="polite"></div>' +
      '<button class="drb-min-submit" type="submit">ثبت اطلاعات و پرداخت</button>';

    legacy.hidden = true; legacy.parentNode.insertBefore(form, legacy);
    var status = form.querySelector('.drb-min-status'), mobileInput = form.elements.mobile, codeBox = form.querySelector('.drb-min-code'), badge = form.querySelector('[data-otp-badge]');
    var verificationToken = '', verifiedMobile = '', selectedSlot = null, paymentConfig = null, patientSessionToken = '', heldBookingId = 0;
    var submit = form.querySelector('.drb-min-submit');

    function message(text,error){ status.textContent=text||''; status.className='drb-min-status'+(error?' is-error':' is-ok'); }
    function resetVerification(){ verificationToken='';verifiedMobile='';badge.textContent='تأیید نشده';badge.className=''; }
    function loadAvailability(){
      var host=form.querySelector('[data-availability]'); host.innerHTML='<p>در حال دریافت زمان‌های آزاد…</p>';
      return fetch(v2+'/availability',{credentials:'same-origin',cache:'no-store'}).then(parseResponse).then(function(res){
        var d=res.data||{}; paymentConfig=d.payment||{};
        form.querySelector('[data-fee]').textContent=paymentConfig.ready ? 'هزینه ویزیت آنلاین: '+toman(paymentConfig.deposit_rials)+' تومان — پرداخت با زرین‌پال' : 'پرداخت آنلاین موقتاً آماده نیست.';
        var days=(d.days||[]).filter(function(day){return day.status==='open' && Array.isArray(day.slots) && day.slots.length;});
        if(!days.length){host.innerHTML='<div class="drb-min-no-slots">در حال حاضر زمان آزاد برای رزرو آنلاین تعریف نشده است.</div>';selectedSlot=null;return;}
        host.innerHTML=days.map(function(day){return '<div class="drb-min-day"><strong>'+faDate(day.date)+'</strong><div class="drb-min-slot-buttons">'+day.slots.map(function(slot){return '<button type="button" class="drb-min-slot" data-day="'+day.id+'" data-start="'+slot.start_at+'">'+slot.local_time+'</button>';}).join('')+'</div></div>';}).join('');
        host.querySelectorAll('.drb-min-slot').forEach(function(btn){btn.addEventListener('click',function(){host.querySelectorAll('.drb-min-slot').forEach(function(x){x.classList.remove('is-selected');});btn.classList.add('is-selected');selectedSlot={openDayId:Number(btn.dataset.day),startAt:btn.dataset.start};message('زمان انتخاب شد. پس از تأیید اطلاعات به زرین‌پال منتقل می‌شوید.',false);});});
      }).catch(function(err){host.innerHTML='<div class="drb-min-no-slots">دریافت زمان‌های آزاد ممکن نشد.</div>';message(err.message,true);});
    }

    var params=new URLSearchParams(location.search); var pay=params.get('payment');
    if(pay==='success') message('پرداخت با موفقیت تأیید شد و نوبت شما ثبت گردید.',false);
    else if(pay==='failed') message('پرداخت تأیید نشد. در صورت کسر وجه، وضعیت توسط کلینیک قابل بررسی است.',true);

    mobileInput.addEventListener('input',resetVerification);
    form.querySelector('[data-otp-send]').addEventListener('click',function(){var mobile=normaliseMobile(mobileInput.value);if(!/^09\d{9}$/.test(mobile))return message('شماره همراه معتبر وارد کنید.',true);this.disabled=true;message('در حال ارسال کد…',false);var b=this;request(api.otpSend,{mobile:mobile}).then(function(data){codeBox.hidden=false;form.elements.otp.focus();message(data.message||'کد تأیید ارسال شد.',false);}).catch(function(e){message(e.message,true);}).finally(function(){b.disabled=false;});});
    form.querySelector('[data-otp-verify]').addEventListener('click',function(){var mobile=normaliseMobile(mobileInput.value),otp=digits(form.elements.otp.value).replace(/\D/g,'');if(!/^\d{5}$/.test(otp))return message('کد پنج‌رقمی را وارد کنید.',true);this.disabled=true;var b=this;message('در حال تأیید شماره…',false);request(api.otpVerify,{mobile:mobile,otp:otp}).then(function(data){verificationToken=data.verificationToken||'';verifiedMobile=mobile;if(!verificationToken)throw new Error('توکن تأیید دریافت نشد.');badge.textContent='تأیید شد';badge.className='is-verified';message('شماره همراه تأیید شد.',false);}).catch(function(e){resetVerification();message(e.message,true);}).finally(function(){b.disabled=false;});});

    function startPayment(){
      submit.disabled=true; message('در حال اتصال به زرین‌پال…',false);
      return v2Post('/payment',{sessionToken:patientSessionToken,bookingId:heldBookingId}).then(function(res){var url=res.data&&res.data.redirect_url;if(!url)throw new Error('آدرس درگاه پرداخت دریافت نشد.');location.assign(url);}).catch(function(e){message(e.message+' برای تلاش دوباره همین دکمه را بزنید.',true);submit.disabled=false;throw e;});
    }

    form.addEventListener('submit',function(event){
      event.preventDefault();
      if(patientSessionToken&&heldBookingId){startPayment().catch(function(){});return;}
      var mobile=normaliseMobile(mobileInput.value),national=digits(form.elements.nationalId.value).replace(/\D/g,''),birth=digits(form.elements.birthDateJalali.value).replace(/-/g,'/');
      if(!selectedSlot)return message('ابتدا یکی از زمان‌های آزاد را انتخاب کنید.',true);
      if(!paymentConfig||!paymentConfig.ready)return message('پرداخت آنلاین هنوز برای رزرو فعال نیست.',true);
      if(!verificationToken||verifiedMobile!==mobile)return message('ابتدا شماره همراه را با کد پیامکی تأیید کنید.',true);
      if(!validNationalId(national))return message('کد ملی معتبر نیست.',true);
      if(!/^(?:12|13|14|15)\d{2}\/(?:0?[1-9]|1[0-2])\/(?:0?[1-9]|[12]\d|3[01])$/.test(birth))return message('تاریخ تولد شمسی را مانند ۱۳۷۰/۰۵/۱۲ وارد کنید.',true);
      if(!form.checkValidity()){form.reportValidity();return;}
      submit.disabled=true;message('در حال ثبت اطلاعات و رزرو موقت زمان…',false);
      request(api.appointment,{firstName:form.elements.firstName.value.trim(),lastName:form.elements.lastName.value.trim(),birthDateJalali:birth,nationalId:national,mobile:mobile,email:form.elements.email.value.trim(),medicalHistory:form.elements.medicalHistory.value.trim(),medications:form.elements.medications.value.trim(),doctorRequest:form.elements.doctorRequest.value.trim(),otpToken:verificationToken,language:'fa'})
        .then(function(data){if(!data.continuationToken)throw new Error('نشست ادامه رزرو دریافت نشد.');return v2Post('/session',{continuationToken:data.continuationToken});})
        .then(function(res){patientSessionToken=(res.data&&res.data.patient_session_token)||'';if(!patientSessionToken)throw new Error('نشست بیمار ایجاد نشد.');return v2Post('/hold',{sessionToken:patientSessionToken,openDayId:selectedSlot.openDayId,startAt:selectedSlot.startAt});})
        .then(function(res){heldBookingId=Number(res.data&&res.data.id)||0;if(!heldBookingId)throw new Error('رزرو موقت زمان ایجاد نشد.');return startPayment();})
        .catch(function(e){if(e.status===409){selectedSlot=null;loadAvailability();}message(e.message,true);submit.disabled=false;});
    });
    loadAvailability();
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount);else mount();
  window.addEventListener('popstate',function(){setTimeout(mount,50);});
  if('MutationObserver'in window)new MutationObserver(function(){mount();}).observe(document.documentElement,{childList:true,subtree:true});
})();
