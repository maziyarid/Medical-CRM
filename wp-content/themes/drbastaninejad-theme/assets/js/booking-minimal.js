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
      '<label class="drb-min-field drb-min-birth-field"><span>تاریخ تولد شمسی *</span><span class="drb-min-birth-selects"><select name="birthDay" required aria-label="روز تولد"><option value="">روز</option></select><select name="birthMonth" required aria-label="ماه تولد"><option value="">ماه</option></select><select name="birthYear" required aria-label="سال تولد"><option value="">سال</option></select></span><input name="birthDateJalali" type="hidden" value=""><small data-birth-status class="drb-inline-status">روز، ماه و سال تولد را جداگانه انتخاب کنید.</small></label>' +
      '<label class="drb-min-field"><span>کد ملی *</span><input name="nationalId" type="text" required inputmode="numeric" maxlength="10" autocomplete="off"><small data-national-status class="drb-inline-status">کد ملی ۱۰ رقمی را وارد کنید.</small></label></div>' +
      '<div class="drb-min-otp"><label class="drb-min-field"><span>تلفن همراه * <b data-otp-badge>تأیید نشده</b></span><span class="drb-min-phone"><input name="mobile" type="tel" required inputmode="tel" maxlength="11" placeholder="09123456789" autocomplete="tel"><button type="button" data-otp-send>ارسال کد</button></span></label>' +
      '<div class="drb-min-code" hidden><label class="drb-min-field"><span>کد تأیید پیامکی *</span><span class="drb-min-phone"><input name="otp" type="text" inputmode="numeric" maxlength="5" autocomplete="one-time-code"><button type="button" data-otp-verify>تأیید شماره</button></span></label></div></div>' +
      '<section class="drb-min-availability"><h3>انتخاب تاریخ و ساعت نوبت</h3><p class="drb-min-fee" data-fee>در حال دریافت زمان‌های آزاد…</p><div data-availability class="drb-min-slots"></div></section>' +
      '<p class="drb-min-notice">نوبت حضوری شما پس از انتخاب زمان آزاد و پرداخت هزینه ویزیت قطعی می‌شود. زمان‌های نمایش‌داده‌شده با تقویم کلینیک همگام هستند.</p>' +
      '<div class="drb-min-status" role="status" aria-live="polite"></div>' +
      '<button class="drb-min-submit" type="submit">ثبت اطلاعات و پرداخت</button>';

    legacy.hidden = true; legacy.parentNode.insertBefore(form, legacy);
    var status = form.querySelector('.drb-min-status'), mobileInput = form.elements.mobile, codeBox = form.querySelector('.drb-min-code'), badge = form.querySelector('[data-otp-badge]');
    var birthDay = form.elements.birthDay, birthMonth = form.elements.birthMonth, birthYear = form.elements.birthYear, birthHidden = form.elements.birthDateJalali;
    var birthStatus = form.querySelector('[data-birth-status]'), nationalInput = form.elements.nationalId, nationalStatus = form.querySelector('[data-national-status]');
    var verificationToken = '', verifiedMobile = '', selectedSlot = null, selectedDateIso = '', paymentConfig = null, patientSessionToken = '', heldBookingId = 0, intakeId = 0, resumeMode = false, bookingDays = [], holidayYears = {}, calendarMonth = tehranJalaliToday(), availabilityTimer = null, availabilityBusy = false, nationalEligible = null, nationalEligibilityTimer = null;
    var submit = form.querySelector('.drb-min-submit');

    function message(text,error){ status.textContent=text||''; status.className='drb-min-status'+(error?' is-error':' is-ok'); }
    function resetVerification(){ verificationToken='';verifiedMobile='';badge.textContent='تأیید نشده';badge.className=''; }
    function pad2(n){return String(n).padStart(2,'0');}
    function isoLocalDate(d){return d.getFullYear()+'-'+pad2(d.getMonth()+1)+'-'+pad2(d.getDate());}
    function faNum(v){return Jalali.toPersianDigits(String(v));}
    function tehranJalaliToday(){
      try{
        var parts=new Intl.DateTimeFormat('fa-IR-u-ca-persian',{timeZone:'Asia/Tehran',year:'numeric',month:'numeric',day:'numeric'}).formatToParts(new Date()),out={};
        parts.forEach(function(x){if(x.type==='year'||x.type==='month'||x.type==='day')out[x.type]=Number(digits(x.value));});
        if(out.year&&out.month&&out.day)return {jy:out.year,jm:out.month,jd:out.day};
      }catch(_){}
      return Jalali.toJalali(new Date());
    }
    function tehranIsoToday(){
      try{
        var parts=new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Tehran',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date()),out={};
        parts.forEach(function(x){if(x.type==='year'||x.type==='month'||x.type==='day')out[x.type]=x.value;});
        if(out.year&&out.month&&out.day)return out.year+'-'+out.month+'-'+out.day;
      }catch(_){}
      var j=tehranJalaliToday(),d=Jalali.fromJalali(j.jy,j.jm,j.jd);
      return isoLocalDate(d);
    }
    function birthValue(){
      var jy=Number(birthYear.value),jm=Number(birthMonth.value),jd=Number(birthDay.value);
      if(!jy||!jm||!jd||jd>Jalali.monthLength(jy,jm)){birthHidden.value='';return '';}
      birthHidden.value=jy+'/'+pad2(jm)+'/'+pad2(jd);return birthHidden.value;
    }
    function updateBirthDays(){
      var old=Number(birthDay.value),jy=Number(birthYear.value),jm=Number(birthMonth.value),max=(jy&&jm)?Jalali.monthLength(jy,jm):0,html='<option value="">روز</option>';
      for(var d=1;d<=max;d++)html+='<option value="'+d+'">'+faNum(d)+'</option>';
      birthDay.innerHTML=html;if(old&&old<=max)birthDay.value=String(old);birthValue();updateBirthEligibility();
    }
    function initBirthSelectors(){
      var nowJ=tehranJalaliToday(),months=Jalali.MONTHS_FA||['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'],years='<option value="">سال</option>',monthsHtml='<option value="">ماه</option>';
      for(var y=nowJ.jy;y>=nowJ.jy-100;y--)years+='<option value="'+y+'">'+faNum(y)+'</option>';
      for(var m=1;m<=12;m++)monthsHtml+='<option value="'+m+'">'+months[m-1]+'</option>';
      birthYear.innerHTML=years;birthMonth.innerHTML=monthsHtml;
      birthYear.addEventListener('change',updateBirthDays);birthMonth.addEventListener('change',updateBirthDays);birthDay.addEventListener('change',function(){birthValue();updateBirthEligibility();});
    }
    function birthAge(value){
      var p=value.split('/'),jy=Number(p[0]),jm=Number(p[1]),jd=Number(p[2]),todayJ=tehranJalaliToday(),age=todayJ.jy-jy;
      if(todayJ.jm<jm||(todayJ.jm===jm&&todayJ.jd<jd))age--;
      return age;
    }
    function validBirthAge(value){
      var age=birthAge(value);
      return age>=18&&age<=45;
    }
    function setInlineStatus(el,text,state){
      if(!el)return;
      el.textContent=text||'';
      el.className='drb-inline-status'+(state?' is-'+state:'');
    }
    function updateBirthEligibility(){
      var value=birthValue();
      if(!value){setInlineStatus(birthStatus,'روز، ماه و سال تولد را کامل انتخاب کنید.','');return;}
      var age=birthAge(value);
      if(age<18||age>45){
        setInlineStatus(birthStatus,'سن شما '+faNum(age)+' سال است؛ پذیرش فقط برای سنین ۱۸ تا ۴۵ سال انجام می‌شود.','error');
        return;
      }
      setInlineStatus(birthStatus,'سن شما '+faNum(age)+' سال است و از نظر سنی واجد شرایط هستید.','ok');
    }
    function checkNationalEligibility(){
      var national=digits(nationalInput.value).replace(/\D/g,'');
      nationalEligible=null;
      if(nationalEligibilityTimer)clearTimeout(nationalEligibilityTimer);
      if(!national){setInlineStatus(nationalStatus,'کد ملی ۱۰ رقمی را وارد کنید.','');return;}
      if(national.length<10){setInlineStatus(nationalStatus,'کد ملی باید ۱۰ رقم باشد.','');return;}
      if(!validNationalId(national)){setInlineStatus(nationalStatus,'کد ملی معتبر نیست.','error');return;}
      setInlineStatus(nationalStatus,'در حال بررسی شرایط رزرو…','');
      nationalEligibilityTimer=setTimeout(function(){
        v2Post('/eligibility',{nationalId:national}).then(function(res){
          var d=(res&&res.data)||{};
          if(d.eligible===false){
            nationalEligible=false;
            setInlineStatus(nationalStatus,d.message||'شما واجد شرایط نیستید.','error');
            message(d.message||'شما واجد شرایط نیستید.',true);
          }else{
            nationalEligible=true;
            setInlineStatus(nationalStatus,'کد ملی بررسی شد.','ok');
          }
        }).catch(function(){
          nationalEligible=null;
          setInlineStatus(nationalStatus,'بررسی خودکار انجام نشد؛ در مرحله ثبت نهایی دوباره بررسی می‌شود.','');
        });
      },250);
    }
    function escHtml(v){return String(v==null?'':v).replace(/[&<>\"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#039;'}[c];});}
    function jKey(jy,jm,jd){return jy+'/'+pad2(jm)+'/'+pad2(jd);}
    function monthRange(){var len=Jalali.monthLength(calendarMonth.jy,calendarMonth.jm),a=Jalali.fromJalali(calendarMonth.jy,calendarMonth.jm,1),b=Jalali.fromJalali(calendarMonth.jy,calendarMonth.jm,len);return {from:isoLocalDate(a),to:isoLocalDate(b)};}
    function loadHolidayYear(year){
      if(holidayYears[year])return Promise.resolve();
      var base=api.holidayBase||'';
      if(!base){holidayYears[year]={};return Promise.resolve();}
      return fetch(base+year+'.json',{cache:'force-cache'}).then(function(r){if(!r.ok)throw new Error('holiday');return r.json();}).then(function(rows){var map={};(rows||[]).forEach(function(x){(map[x.jDate]||(map[x.jDate]=[])).push(x);});holidayYears[year]=map;}).catch(function(){holidayYears[year]={};});
    }
    function availableMap(){var map={};bookingDays.forEach(function(d){map[d.date]=d;});return map;}
    function selectCalendarDay(iso,preserveStart){
      var day=availableMap()[iso],host=form.querySelector('[data-availability]'),panel=host.querySelector('[data-patient-time-panel]');
      selectedDateIso=iso;
      var previousStart=preserveStart || (selectedSlot&&selectedSlot.startAt) || '';
      selectedSlot=null;
      host.querySelectorAll('.drb-patient-cal-day').forEach(function(x){x.classList.toggle('is-selected',x.dataset.date===iso);});
      if(!day||day.status!=='open'||!Array.isArray(day.slots)||!day.slots.length){panel.innerHTML='<div class="drb-min-no-slots">برای این روز زمان آزادی وجود ندارد.</div>';return;}
      panel.innerHTML='<div class="drb-patient-time-head"><strong>'+faDate(iso)+'</strong><small>'+faNum(day.slots.length)+' زمان آزاد</small></div><div class="drb-min-slot-buttons">'+day.slots.map(function(slot){return '<button type="button" class="drb-min-slot" data-day="'+day.id+'" data-start="'+slot.start_at+'">'+faNum(slot.local_time)+'</button>';}).join('')+'</div>';
      panel.querySelectorAll('.drb-min-slot').forEach(function(btn){
        if(previousStart && btn.dataset.start===previousStart){btn.classList.add('is-selected');selectedSlot={openDayId:Number(btn.dataset.day),startAt:btn.dataset.start};}
        btn.addEventListener('click',function(){panel.querySelectorAll('.drb-min-slot').forEach(function(x){x.classList.remove('is-selected');});btn.classList.add('is-selected');selectedSlot={openDayId:Number(btn.dataset.day),startAt:btn.dataset.start};selectedDateIso=iso;message('تاریخ و ساعت انتخاب شد.',false);});
      });
    }
    function renderPatientCalendar(preserveStart){
      var host=form.querySelector('[data-availability]'),names=['شنبه','یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه'],map=availableMap(),hm=holidayYears[calendarMonth.jy]||{},len=Jalali.monthLength(calendarMonth.jy,calendarMonth.jm),first=Jalali.fromJalali(calendarMonth.jy,calendarMonth.jm,1),offset=(first.getDay()+1)%7,today=tehranIsoToday(),html='',monthName=Jalali.MONTHS_FA[calendarMonth.jm-1],hasOpen=bookingDays.some(function(row){return row&&row.date>=today&&row.status==='open'&&Array.isArray(row.slots)&&row.slots.length;});
      html+='<div class="drb-patient-cal-toolbar"><button type="button" data-cal-prev aria-label="ماه قبل">→</button><div><strong>'+monthName+' '+faNum(calendarMonth.jy)+'</strong><small>تقویم شمسی</small></div><button type="button" data-cal-next aria-label="ماه بعد">←</button></div>';
      if(!hasOpen)html+='<div class="drb-month-full" role="status"><div><strong>ظرفیت '+monthName+' تکمیل است.</strong><span>در این ماه زمان آزادی برای رزرو باقی نمانده است. ماه بعد را بررسی کنید.</span></div><button type="button" data-cal-next-full>رفتن به ماه بعد</button></div>';
      html+='<div class="drb-patient-cal-legend"><span><i class="is-open"></i>روز قابل رزرو</span><span><i class="is-holiday"></i>تعطیل رسمی</span></div>';
      html+='<div class="drb-patient-cal-grid">'+names.map(function(n){return '<div class="drb-patient-weekday">'+n+'</div>';}).join('');
      for(var i=0;i<offset;i++)html+='<div class="drb-patient-cal-day is-empty"></div>';
      for(var d=1;d<=len;d++){
        var g=Jalali.fromJalali(calendarMonth.jy,calendarMonth.jm,d),iso=isoLocalDate(g),row=map[iso],events=hm[jKey(calendarMonth.jy,calendarMonth.jm,d)]||[],isFriday=g.getDay()===5,holiday=events.some(function(x){return x.isHoliday;})||isFriday,holidayText=events.filter(function(x){return x.isHoliday;}).map(function(x){return x.text.replace(/^\d+\s+\S+\s*/, '');}).join('، '),past=iso<today,available=!!(row&&row.status==='open'&&Array.isArray(row.slots)&&row.slots.length&&!past),classes=['drb-patient-cal-day'];
        if(available)classes.push('is-open'); else classes.push('is-disabled');
        if(holiday)classes.push('is-holiday');
        if(iso===today)classes.push('is-today');
        if(!holidayText&&isFriday)holidayText='جمعه';
        html+='<button type="button" class="'+classes.join(' ')+'" data-date="'+iso+'" '+(available?'':'disabled aria-disabled="true"')+'><span class="day-number">'+faNum(d)+'</span>'+(available?'<span class="day-open">'+faNum(row.slots.length)+' زمان آزاد</span>':'<span class="day-closed">—</span>')+(holidayText?'<span class="day-holiday" title="'+escHtml(holidayText)+'">'+escHtml(holidayText)+'</span>':'')+'</button>';
      }
      for(var t=offset+len;t%7;t++)html+='<div class="drb-patient-cal-day is-empty"></div>';
      html+='</div><div class="drb-patient-time-panel" data-patient-time-panel><div class="drb-min-no-slots">'+(hasOpen?'یک روز سبز را انتخاب کنید تا ساعت‌های آزاد نمایش داده شوند.':'این ماه زمان آزادی باقی نمانده است؛ برای مشاهده نوبت‌ها به ماه بعد بروید.')+'</div></div>';
      host.innerHTML=html;
      host.querySelectorAll('.drb-patient-cal-day[data-date]:not([disabled])').forEach(function(btn){btn.addEventListener('click',function(){selectCalendarDay(btn.dataset.date);});});
      var nowJ=tehranJalaliToday(),prev=host.querySelector('[data-cal-prev]');
      prev.disabled=(calendarMonth.jy<nowJ.jy)||(calendarMonth.jy===nowJ.jy&&calendarMonth.jm<=nowJ.jm);
      prev.addEventListener('click',function(){if(prev.disabled)return;calendarMonth.jm--;if(calendarMonth.jm<1){calendarMonth.jm=12;calendarMonth.jy--;}selectedSlot=null;selectedDateIso='';loadAvailability();});
      host.querySelectorAll('[data-cal-next],[data-cal-next-full]').forEach(function(next){next.addEventListener('click',function(){calendarMonth.jm++;if(calendarMonth.jm>12){calendarMonth.jm=1;calendarMonth.jy++;}selectedSlot=null;selectedDateIso='';loadAvailability();});});
      if(selectedDateIso && availableMap()[selectedDateIso]) selectCalendarDay(selectedDateIso,preserveStart);
    }
    function loadAvailability(silent){
      if(availabilityBusy)return Promise.resolve();
      availabilityBusy=true;
      var host=form.querySelector('[data-availability]'),range=monthRange(),keepStart=selectedSlot&&selectedSlot.startAt;
      if(!silent)host.innerHTML='<p>در حال دریافت تقویم نوبت‌ها…</p>';
      return Promise.all([
        fetch(v2+'/availability?from='+encodeURIComponent(range.from)+'&to='+encodeURIComponent(range.to),{credentials:'same-origin',cache:'no-store'}).then(parseResponse),
        loadHolidayYear(calendarMonth.jy)
      ]).then(function(all){
        var d=(all[0]&&all[0].data)||{}; paymentConfig=d.payment||{}; bookingDays=d.days||[];
        form.querySelector('[data-fee]').textContent=paymentConfig.ready ? 'هزینه ویزیت حضوری: '+toman(paymentConfig.deposit_rials)+' تومان' : 'رزرو آنلاین موقتاً آماده نیست.';
        renderPatientCalendar(keepStart);
        if(keepStart && !selectedSlot)message('زمان انتخاب‌شده دیگر آزاد نیست؛ لطفاً زمان دیگری انتخاب کنید.',true);
      }).catch(function(err){if(!silent)host.innerHTML='<div class="drb-min-no-slots">دریافت تقویم نوبت‌ها ممکن نشد.</div>';message(err.message,true);})
        .finally(function(){availabilityBusy=false;});
    }
    function startAvailabilityRealtime(){
      if(availabilityTimer)clearInterval(availabilityTimer);
      availabilityTimer=setInterval(function(){if(!document.hidden&&!heldBookingId)loadAvailability(true);},15000);
      document.addEventListener('visibilitychange',function(){if(!document.hidden&&!heldBookingId)loadAvailability(true);});
      window.addEventListener('focus',function(){if(!heldBookingId)loadAvailability(true);});
    }
    function selectedSlotStillAvailable(){
      if(!selectedSlot||!selectedDateIso)return Promise.resolve(false);
      var start=selectedSlot.startAt,dayId=selectedSlot.openDayId,range=monthRange();
      return fetch(v2+'/availability?from='+encodeURIComponent(range.from)+'&to='+encodeURIComponent(range.to),{credentials:'same-origin',cache:'no-store'}).then(parseResponse).then(function(res){
        var d=(res&&res.data)||{},days=d.days||[],day=days.find(function(x){return Number(x.id)===Number(dayId);});
        return !!(day&&Array.isArray(day.slots)&&day.slots.some(function(x){return x.start_at===start;}));
      }).catch(function(){return null;});
    }

    function enterResumeMode(data){
      patientSessionToken=(data&&data.patient_session_token)||''; intakeId=Number((data&&data.intake_id)||0); resumeMode=!!patientSessionToken&&intakeId>0;
      if(!resumeMode)throw new Error('نشست تکمیل نوبت ایجاد نشد.');
      try{sessionStorage.setItem('drb_booking_resume',JSON.stringify({token:patientSessionToken,intakeId:intakeId,name:data.name||'',mobile:data.mobile||''}));}catch(_){ }
      form.querySelector('.drb-min-grid').hidden=true; form.querySelector('.drb-min-otp').hidden=true;
      submit.textContent='رزرو زمان و پرداخت';
      var n=document.createElement('div');n.className='drb-min-resume-banner';n.innerHTML='<strong>درخواست قبلی شما پیدا شد.</strong><span>'+(data.name||'')+' '+(data.mobile?'· '+data.mobile:'')+'</span><small>فقط تاریخ و ساعت آزاد را انتخاب کنید و سپس پرداخت را انجام دهید.</small>';
      form.querySelector('.drb-min-availability').before(n); message('درخواست قبلی بازیابی شد؛ زمان مناسب را انتخاب کنید.',false);
    }
    function loadResume(){
      var params=new URLSearchParams(location.search),resume=params.get('resume'); if(!resume)return Promise.resolve(false);
      try{var cached=JSON.parse(sessionStorage.getItem('drb_booking_resume')||'null');if(cached&&cached.token&&cached.intakeId){enterResumeMode({patient_session_token:cached.token,intake_id:cached.intakeId,name:cached.name||'',mobile:cached.mobile||''});return Promise.resolve(true);}}catch(_){ }
      message('در حال بازیابی درخواست قبلی…',false);
      return v2Post('/resume',{resumeToken:resume}).then(function(res){enterResumeMode(res.data||{});return true;}).catch(function(e){message(e.message,true);return false;});
    }

    var params=new URLSearchParams(location.search); var pay=params.get('payment');
    if(pay==='success'){var ref=params.get('ref');message('پرداخت با موفقیت تأیید شد و نوبت شما ثبت گردید.'+(ref?' کد پیگیری: '+ref:''),false);}
    else if(pay==='failed') message('پرداخت تأیید نشد. در صورت کسر وجه، وضعیت توسط کلینیک قابل بررسی است.',true);

    initBirthSelectors();
    nationalInput.addEventListener('input',checkNationalEligibility);
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
      var mobile=normaliseMobile(mobileInput.value),national=digits(form.elements.nationalId.value).replace(/\D/g,''),birth=birthValue();
      if(!resumeMode){
        if(!birth)return message('روز، ماه و سال تولد را کامل انتخاب کنید.',true);
        if(!validBirthAge(birth))return message('پذیرش فقط برای سنین ۱۸ تا ۴۵ سال امکان‌پذیر است.',true);
        if(!validNationalId(national))return message('کد ملی معتبر نیست.',true);
        if(nationalEligible===false)return message('شما واجد شرایط نیستید.',true);
        if(!form.checkValidity()){form.reportValidity();return;}
        if(!verificationToken||verifiedMobile!==mobile)return message('ابتدا شماره همراه را با کد پیامکی تأیید کنید.',true);
      }
      if(!selectedSlot)return message('ابتدا یکی از زمان‌های آزاد را انتخاب کنید.',true);
      if(!paymentConfig||!paymentConfig.ready)return message('رزرو آنلاین هنوز فعال نیست.',true);
      submit.disabled=true;message('در حال بررسی لحظه‌ای زمان انتخاب‌شده…',false);
      selectedSlotStillAvailable().then(function(fresh){
        if(fresh===null){submit.disabled=false;message('امکان بررسی لحظه‌ای زمان فراهم نشد؛ دوباره تلاش کنید.',true);return;}
        if(!fresh){selectedSlot=null;submit.disabled=false;loadAvailability(true);message('این زمان دیگر آزاد نیست؛ لطفاً زمان دیگری انتخاب کنید.',true);return;}
        if(resumeMode){
          message('در حال رزرو موقت زمان…',false);
          v2Post('/hold',{sessionToken:patientSessionToken,openDayId:selectedSlot.openDayId,startAt:selectedSlot.startAt,intakeId:intakeId})
            .then(function(res){heldBookingId=Number(res.data&&res.data.id||0);if(!heldBookingId)throw new Error('رزرو موقت ایجاد نشد.');return startPayment();})
            .catch(function(e){if(e.status===409||/slot|زمان|ظرفیت/i.test(String(e.message||''))){selectedSlot=null;loadAvailability(true);}message(e.message,true);submit.disabled=false;});
          return;
        }
        message('در حال ثبت اطلاعات و رزرو موقت زمان…',false);
        request(api.appointment,{firstName:form.elements.firstName.value.trim(),lastName:form.elements.lastName.value.trim(),birthDateJalali:birth,nationalId:national,mobile:mobile,otpToken:verificationToken,language:'fa'})
          .then(function(data){if(!data.continuationToken)throw new Error('نشست ادامه رزرو دریافت نشد.');return v2Post('/session',{continuationToken:data.continuationToken});})
          .then(function(res){patientSessionToken=(res.data&&res.data.patient_session_token)||'';intakeId=Number((res.data&&res.data.intake_id)||0);if(!patientSessionToken)throw new Error('نشست بیمار ایجاد نشد.');return v2Post('/hold',{sessionToken:patientSessionToken,openDayId:selectedSlot.openDayId,startAt:selectedSlot.startAt,intakeId:intakeId});})
          .then(function(res){heldBookingId=Number(res.data&&res.data.id)||0;if(!heldBookingId)throw new Error('رزرو موقت زمان ایجاد نشد.');return startPayment();})
          .catch(function(e){if(e.status===409||/slot|زمان|ظرفیت/i.test(String(e.message||''))){selectedSlot=null;loadAvailability(true);}message(e.message,true);submit.disabled=false;});
      });
    });
    loadResume().finally(function(){loadAvailability().finally(startAvailabilityRealtime);});
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount);else mount();
  window.addEventListener('popstate',function(){setTimeout(mount,50);});
  if('MutationObserver'in window)new MutationObserver(function(){mount();}).observe(document.documentElement,{childList:true,subtree:true});
})();
