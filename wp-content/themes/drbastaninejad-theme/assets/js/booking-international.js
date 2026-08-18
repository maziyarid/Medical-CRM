/** Add the international-only booking fields missing from the compiled React form. */
(function () {
  'use strict';
  var i18n = window.__DRB_I18N__ || {};
  var lang = String(i18n.lang || 'fa').toLowerCase();
  if (lang === 'fa') return;

  var strings = i18n.strings || {};
  var local = {
    en: { dial: 'Country code', countryHint: 'e.g. Germany', emailHint: 'you@example.com', phoneHint: 'Do not enter the country code here.', required: 'This field is required' },
    ar: { dial: 'رمز الدولة', countryHint: 'مثال: الإمارات العربية المتحدة', emailHint: 'you@example.com', phoneHint: 'لا تُدخل رمز الدولة هنا.', required: 'هذا الحقل مطلوب' },
    tr: { dial: 'Ülke kodu', countryHint: 'örn. Türkiye', emailHint: 'you@example.com', phoneHint: 'Ülke kodunu buraya yazmayın.', required: 'Bu alan zorunludur' },
    ru: { dial: 'Код страны', countryHint: 'например, Россия', emailHint: 'you@example.com', phoneHint: 'Не вводите здесь код страны.', required: 'Это поле обязательно' },
    fr: { dial: 'Indicatif pays', countryHint: 'ex. France', emailHint: 'you@example.com', phoneHint: "N’indiquez pas l’indicatif du pays ici.", required: 'Ce champ est obligatoire' },
    de: { dial: 'Ländervorwahl', countryHint: 'z. B. Deutschland', emailHint: 'you@example.com', phoneHint: 'Geben Sie hier nicht die Ländervorwahl ein.', required: 'Dieses Feld ist erforderlich' },
    es: { dial: 'Código de país', countryHint: 'p. ej., España', emailHint: 'you@example.com', phoneHint: 'No escriba aquí el código de país.', required: 'Este campo es obligatorio' }
  }[lang] || {};

  function field(label, type, attrs) {
    var wrap = document.createElement('div');
    wrap.className = 'drb-intl-booking-field';
    var lab = document.createElement('label');
    lab.className = 'block text-sm font-bold text-[#25272C] mb-1.5';
    lab.textContent = label + ' ';
    var star = document.createElement('span');
    star.className = 'text-red-500';
    star.textContent = '*';
    lab.appendChild(star);
    var input = document.createElement('input');
    input.type = type;
    input.required = true;
    input.className = 'w-full px-4 py-3 rounded-xl border border-[#DDE2DD] text-sm bg-[#F7F8F6] placeholder-[#9CA3AF] focus:outline-none focus:ring-2 focus:ring-[#28722C]/30 transition-all';
    Object.keys(attrs || {}).forEach(function (key) {
      if (key === 'dataset') {
        Object.keys(attrs.dataset).forEach(function (dataKey) { input.dataset[dataKey] = attrs.dataset[dataKey]; });
      } else if (key in input) input[key] = attrs[key];
      else input.setAttribute(key, attrs[key]);
    });
    var err = document.createElement('p');
    err.className = 'text-xs text-red-500 mt-1';
    err.hidden = true;
    err.setAttribute('data-drb-intl-error', '1');
    wrap.appendChild(lab);
    wrap.appendChild(input);
    wrap.appendChild(err);
    return { wrap: wrap, input: input, error: err };
  }

  function showError(item, message) {
    item.input.classList.remove('border-[#DDE2DD]');
    item.input.classList.add('border-red-400');
    item.error.textContent = message || strings.form_required || local.required || 'Required';
    item.error.hidden = false;
  }

  function clearError(item) {
    item.input.classList.remove('border-red-400');
    item.input.classList.add('border-[#DDE2DD]');
    item.error.hidden = true;
  }

  function enhance(form) {
    if (!form) return false;
    if (form.dataset.drbIntlEnhanced === '1') return true;
    var phone = form.querySelector('input[type="tel"]');
    if (!phone) return false;
    var phoneWrap = phone.parentElement;
    if (!phoneWrap) return false;
    form.dataset.drbIntlEnhanced = '1';
    form.classList.add('drb-booking-intl');
    phone.placeholder = '912 123 4567';
    phone.autocomplete = 'tel';
    phone.inputMode = 'tel';
    var phoneHint = document.createElement('p');
    phoneHint.className = 'text-xs text-[#626A62] mt-1';
    phoneHint.textContent = local.phoneHint || 'Do not enter the country code here.';
    phoneHint.setAttribute('data-drb-intl-phone-hint', '1');
    phoneWrap.appendChild(phoneHint);

    var country = field(strings.form_country || 'Country', 'text', {
      autocomplete: 'country-name', placeholder: local.countryHint || '', dataset: { drbIntlCountry: '1' }
    });
    var dial = field(local.dial || 'Country code', 'text', {
      value: '+98', inputMode: 'tel', autocomplete: 'tel-country-code', placeholder: '+98', dataset: { drbIntlDial: '1' }
    });
    dial.input.dir = 'ltr';
    var pair = document.createElement('div');
    pair.className = 'grid sm:grid-cols-2 gap-4 drb-intl-booking-pair';
    pair.appendChild(country.wrap);
    pair.appendChild(dial.wrap);
    phoneWrap.parentNode.insertBefore(pair, phoneWrap);

    var email = field(strings.form_email || 'Email', 'email', {
      autocomplete: 'email', placeholder: local.emailHint || 'you@example.com', dataset: { drbIntlEmail: '1' }
    });
    email.input.dir = 'ltr';
    phoneWrap.parentNode.insertBefore(email.wrap, phoneWrap.nextSibling);

    [country, dial, email].forEach(function (item) {
      item.input.addEventListener('input', function () { clearError(item); });
    });

    // React does not know these injected fields. Validate them in capture phase
    // before its synthetic onSubmit handler is allowed to send the request.
    form.addEventListener('submit', function (event) {
      var valid = true;
      if (!country.input.value.trim()) { showError(country); valid = false; }
      if (!/^\+\d{1,4}$/.test(dial.input.value.trim())) { showError(dial, local.dial || 'Country code'); valid = false; }
      if (!/^\S+@\S+\.\S+$/.test(email.input.value.trim())) { showError(email, strings.form_invalid_email || 'Valid email required'); valid = false; }
      if (!valid) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var first = form.querySelector('.border-red-400');
        if (first && first.focus) first.focus();
      }
    }, true);
    return true;
  }

  function scan(scope) {
    var enhanced = false;
    (scope || document).querySelectorAll('form').forEach(function (form) {
      if (form.querySelector('input[type="tel"]') && form.querySelector('input[type="number"][min="18"]')) {
        enhanced = enhance(form) || enhanced;
      }
    });
    return enhanced;
  }

  function observeRoot(root) {
    if (scan(root) || typeof MutationObserver === 'undefined') return;
    var observer = new MutationObserver(function () {
      if (scan(root)) observer.disconnect();
    });
    observer.observe(root, { childList: true, subtree: true });
  }

  function start() {
    var root = document.getElementById('root');
    if (root) {
      observeRoot(root);
      return;
    }
    if (typeof MutationObserver === 'undefined' || !document.body) return;
    var bootstrapObserver = new MutationObserver(function () {
      var candidate = document.getElementById('root');
      if (!candidate) return;
      bootstrapObserver.disconnect();
      observeRoot(candidate);
    });
    bootstrapObserver.observe(document.body, { childList: true, subtree: true });
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();
