(function () {
  'use strict';

  var attempts = 0;
  var scheduled = false;
  var lastPath = '';

  function normalizedPath(url) {
    try {
      var value = new URL(url, window.location.origin);
      return value.origin === window.location.origin ? value.pathname.replace(/\/+$/, '') || '/' : '';
    } catch (error) { return ''; }
  }

  function replaceText(node) {
    if (!node || !node.nodeValue || !node.parentElement || node.parentElement.closest('script,style,noscript')) return;
    var value = node.nodeValue;
    value = value
      .replace(/شنبه(?:‌|\s)*تا(?:‌|\s)*چهارشنبه[^\n،؛.]*(?:۱۹|19)(?::۰۰|:00)?/g, 'شنبه تا سه‌شنبه، از ساعت ۱۵:۰۰ تا ۱۸:۰۰')
      .replace(/شنبه(?:‌|\s)*تا(?:‌|\s)*سه(?:‌|\s)*شنبه[^\n،؛.]*(?:۱۹|19)(?::۰۰|:00)?/g, 'شنبه تا سه‌شنبه، از ساعت ۱۵:۰۰ تا ۱۸:۰۰')
      .replace(/۱۵(?::۰۰)?\s*(?:تا|الی|–|—|-)\s*۱۹(?::۰۰)?/g, '۱۵:۰۰ تا ۱۸:۰۰')
      .replace(/(?:پس از|بعد از).*?برداشتن (?:آتل|گچ).*?پرواز[^.؟!]*(?:[.؟!]|$)/g, '۱۰ روز پس از جراحی امکان پرواز وجود دارد. ')
      .replace(/7\s*[-–—]\s*10 روز بعد از جراحی[^.؟!]*(?:[.؟!]|$)/gi, '۱۰ روز پس از جراحی امکان پرواز وجود دارد. ')
      .replace(/۷\s*[-–—]\s*۱۰ روز بعد از جراحی[^.؟!]*(?:[.؟!]|$)/g, '۱۰ روز پس از جراحی امکان پرواز وجود دارد. ');
    if (value !== node.nodeValue) node.nodeValue = value;
  }

  function repairAssets(data) {
    if (!data.themeUri) return;
    document.querySelectorAll('img[src], source[srcset]').forEach(function (asset) {
      var attribute = asset.hasAttribute('src') ? 'src' : 'srcset';
      var value = asset.getAttribute(attribute) || '';
      var marker = '/wp-content/themes/drbastaninejad-theme/';
      var index = value.indexOf(marker);
      if (index !== -1) asset.setAttribute(attribute, data.themeUri.replace(/\/$/, '') + '/' + value.slice(index + marker.length));
      else if (value.indexOf('/images/Certificates/') === 0) asset.setAttribute(attribute, data.themeUri.replace(/\/$/, '') + '/assets/dist6' + value);
    });
    document.querySelectorAll('a[href*="maps"], a[href*="google"], a[href*="neshan"], a[href*="balad"], a[href*="waze"]').forEach(function (link) {
      var image = link.querySelector('img');
      if (!image || image.dataset.drbFallbackBound) return;
      image.dataset.drbFallbackBound = '1';
      image.addEventListener('error', function () {
        var badge = document.createElement('span');
        badge.className = 'drb-map-icon-fallback';
        badge.setAttribute('aria-hidden', 'true');
        badge.textContent = (link.textContent || link.getAttribute('aria-label') || 'نقشه').trim().slice(0, 1);
        image.replaceWith(badge);
      }, { once: true });
    });
  }

  function ensureAddressMapLinks(data) {
    if (!data.themeUri) return;
    var maps = [
      ['Google Maps', 'https://maps.google.com/?q=35.758881,51.413824', 'GoogleMap.webp', 'maps.google'],
      ['نشان', 'https://nshn.ir/gNbNgYZt_Rxg', 'Neshan.webp', 'nshn.ir'],
      ['بلد', 'https://balad.ir/p/3cuwGGif58f8hT', 'Balad.webp', 'balad.ir'],
      ['Waze', 'https://waze.com/ul/htnke3vwqs', 'Waze.webp', 'waze.com'],
    ];
    var kept = [];
    document.querySelectorAll('#root main p, #root main address').forEach(function (address) {
      if (!/(?:نلسون\s*ماندلا|ساختمان\s*نور)/u.test(address.textContent || '')) return;
      var card = address.closest('article,[class*="rounded"],section');
      if (!card) return;
      var hasAddressHeading = Array.prototype.some.call(card.querySelectorAll('h2,h3,h4,strong'), function (heading) {
        return /آدرس/u.test(heading.textContent || '');
      });
      if (!hasAddressHeading) return;
      var group = card.querySelector('.drb-auto-map-links');
      if (!group) {
        group = document.createElement('div');
        group.className = 'drb-auto-map-links';
        group.setAttribute('aria-label', 'مسیریاب‌ها');
      }
      maps.forEach(function (map) {
        var link = card.querySelector('a[href*="' + map[3] + '"]');
        if (!link) link = document.createElement('a');
        link.href = map[1]; link.target = '_blank'; link.rel = 'noopener noreferrer'; link.classList.add('drb-map-link');
        link.innerHTML = '<img src="' + data.themeUri.replace(/\/$/, '') + '/assets/dist6/images/Icons/' + map[2] + '" alt="" width="18" height="18"><span>' + map[0] + '</span>';
        group.appendChild(link);
        kept.push(link);
      });
      if (group.childElementCount) address.insertAdjacentElement('afterend', group);
    });
    document.querySelectorAll('#root main a[href*="maps"],#root main a[href*="google"],#root main a[href*="neshan"],#root main a[href*="nshn"],#root main a[href*="balad"],#root main a[href*="waze"]').forEach(function (link) {
      if (kept.indexOf(link) === -1) link.remove();
    });
  }

  function removeProhibitedPromotions() {
    document.querySelectorAll('#root main a, #root main article, #root main li').forEach(function (item) {
      var text = (item.textContent || '').replace(/\s+/g, ' ').trim();
      var href = item.getAttribute && (item.getAttribute('href') || '');
      if (/بینی(?:‌|\s)*(?:گوشتی|فانتزی|عروسکی)/u.test(text) || /(?:fleshy|fantasy|unavailable-(?:thick-skin|stylized))/i.test(href)) {
        var card = item.closest('li,article') || item.closest('a') || item;
        if (card) card.hidden = true;
      }
    });
  }

  function removeEmptyCtas() {
    document.querySelectorAll('#root main a, #root main button').forEach(function (item) {
      if ((item.textContent || '').trim() || item.querySelector('img,svg')) return;
      item.hidden = true;
      item.setAttribute('aria-hidden', 'true');
    });
  }

  function repairLightControlContrast() {
    function rgb(value) {
      var match = String(value || '').match(/rgba?\((\d+)[, ]+(\d+)[, ]+(\d+)(?:[, /]+([\d.]+))?\)/i);
      return match ? [Number(match[1]), Number(match[2]), Number(match[3]), match[4] === undefined ? 1 : Number(match[4])] : null;
    }
    function luminance(color) {
      return (0.2126 * color[0] + 0.7152 * color[1] + 0.0722 * color[2]) / 255;
    }
    document.querySelectorAll('#root main a, #root main button').forEach(function (item) {
      if (item.hidden) return;
      var style = window.getComputedStyle(item);
      var background = rgb(style.backgroundColor);
      var foreground = rgb(style.color);
      if (!background || !foreground || background[3] < .85) return;
      var bgLight = luminance(background);
      var fgLight = luminance(foreground);
      if (bgLight > .82 && Math.abs(bgLight - fgLight) < .28) item.classList.add('drb-light-control-contrast');
    });
  }

  function hideLegacySummaryBlocks(page) {
    var labels = page.slug === 'home'
      ? ['دانشیار دانشگاه', 'رتبه ۴ بورد', 'دبیر کمیته', 'مجری ۱۰']
      : page.slug === 'gallery' ? ['سال تجربه', 'عمل موفق', 'نمونه گالری', 'رضایت بیمار'] : [];
    if (!labels.length) return;
    function score(block) {
      var text = (block.textContent || '').replace(/\s+/g, ' ');
      return labels.filter(function (label) { return text.indexOf(label) !== -1; }).length;
    }
    var candidates = Array.prototype.filter.call(document.querySelectorAll('#root main section, #root main div'), function (block) {
      return !block.classList.contains('drb-policy-strip') && score(block) >= 3;
    });
    candidates.forEach(function (block) {
      var smaller = candidates.some(function (other) { return other !== block && block.contains(other); });
      if (!smaller) block.hidden = true;
    });
  }

  function placeNutritionQuicklink() {
    var link = document.getElementById('drb-nutrition-quicklink');
    var nav = document.querySelector('#root .site-nav, #root nav');
    if (!link || !nav) return;
    if (link.previousElementSibling !== nav) nav.insertAdjacentElement('afterend', link);
    link.classList.add('is-placed');
  }

  function makeGalleriesImageless() {
    document.querySelectorAll('#root main section').forEach(function (section) {
      var heading = section.querySelector('h1,h2,h3');
      if (!heading || !/(?:قبل|پیش)\s*(?:و|\/).*?(?:بعد|پس)|before\s*(?:&|and|\/)\s*after/iu.test(heading.textContent || '')) return;
      section.querySelectorAll('img,picture,video').forEach(function (media) { media.remove(); });
      if (!section.querySelector('.drb-gallery-empty')) {
        var empty = document.createElement('div');
        empty.className = 'drb-gallery-empty';
        empty.innerHTML = '<strong>گالری تصاویر در حال بازبینی است</strong><p>تا تکمیل روتوش، تأیید رضایت انتشار و بازبینی نهایی، هیچ تصویر خام یا تأییدنشده‌ای نمایش داده نمی‌شود.</p>';
        section.appendChild(empty);
      }
    });
  }

  function createNode(tag, className, text) {
    var node = document.createElement(tag);
    if (className) node.className = className;
    if (text !== undefined && text !== null) node.textContent = text;
    return node;
  }

  function galleryImages(item) {
    return Array.isArray(item.images) ? item.images.filter(function (image) { return image && image.src; }) : [];
  }

  function setupCaseLightbox(cases) {
    var old = document.querySelector('.drb-case-lightbox');
    if (old) old.remove();
    var modal = createNode('div', 'drb-case-lightbox');
    modal.hidden = true;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'drb-case-lightbox-title');
    modal.innerHTML = '<button type="button" class="drb-case-lightbox__backdrop" aria-label="بستن گالری"></button><div class="drb-case-lightbox__dialog"><div class="drb-case-lightbox__stage"><button type="button" class="drb-case-lightbox__close" aria-label="بستن">×</button><button type="button" class="drb-case-lightbox__prev" aria-label="تصویر قبلی">→</button><img class="drb-case-lightbox__image" alt=""><button type="button" class="drb-case-lightbox__next" aria-label="تصویر بعدی">←</button></div><aside class="drb-case-lightbox__panel"><h2 id="drb-case-lightbox-title"></h2><p class="drb-case-lightbox__caption"></p><div class="drb-case-lightbox__thumbs" aria-label="نماهای این مراجعه"></div></aside></div>';
    document.body.appendChild(modal);
    // Arrow direction is physical, not linguistic: previous points toward the
    // previous side of the active reading direction. The Persian-first bundle
    // used RTL glyphs unconditionally, which made LTR galleries feel reversed.
    var isRtl = (document.documentElement.getAttribute('dir') || 'rtl') === 'rtl';
    var prevButton = modal.querySelector('.drb-case-lightbox__prev');
    var nextButton = modal.querySelector('.drb-case-lightbox__next');
    if (prevButton) prevButton.textContent = isRtl ? '→' : '←';
    if (nextButton) nextButton.textContent = isRtl ? '←' : '→';
    var title = modal.querySelector('h2');
    var caption = modal.querySelector('.drb-case-lightbox__caption');
    var image = modal.querySelector('.drb-case-lightbox__image');
    var thumbs = modal.querySelector('.drb-case-lightbox__thumbs');
    var activeCase = null;
    var activeIndex = 0;
    var returnFocus = null;

    function show(index) {
      var images = galleryImages(activeCase);
      if (!images.length) return;
      activeIndex = (index + images.length) % images.length;
      var selected = images[activeIndex];
      image.src = selected.src;
      image.alt = selected.alt || ((activeCase.label || 'نمونه جراحی') + ' — ' + (selected.label || 'نمای بالینی'));
      title.textContent = activeCase.label || 'نمونه جراحی';
      caption.textContent = [selected.label, selected.interval || activeCase.interval, activeCase.procedure].filter(Boolean).join(' · ');
      thumbs.querySelectorAll('button').forEach(function (button, buttonIndex) { button.setAttribute('aria-current', buttonIndex === activeIndex ? 'true' : 'false'); });
    }

    function close() {
      modal.hidden = true;
      document.body.classList.remove('drb-lightbox-open');
      if (returnFocus) returnFocus.focus();
    }

    modal.openCase = function (caseItem, trigger) {
      activeCase = caseItem;
      activeIndex = 0;
      returnFocus = trigger;
      thumbs.innerHTML = '';
      galleryImages(caseItem).forEach(function (caseImage, index) {
        var button = createNode('button', 'drb-case-lightbox__thumb');
        button.type = 'button';
        button.setAttribute('aria-label', caseImage.label || ('نمای ' + (index + 1)));
        var thumb = createNode('img');
        thumb.src = caseImage.thumb || caseImage.src;
        thumb.alt = '';
        thumb.loading = 'lazy';
        button.appendChild(thumb);
        button.addEventListener('click', function () { show(index); });
        thumbs.appendChild(button);
      });
      modal.hidden = false;
      document.body.classList.add('drb-lightbox-open');
      show(0);
      modal.querySelector('.drb-case-lightbox__close').focus();
    };

    modal.querySelector('.drb-case-lightbox__backdrop').addEventListener('click', close);
    modal.querySelector('.drb-case-lightbox__close').addEventListener('click', close);
    modal.querySelector('.drb-case-lightbox__prev').addEventListener('click', function () { show(activeIndex - 1); });
    modal.querySelector('.drb-case-lightbox__next').addEventListener('click', function () { show(activeIndex + 1); });
    modal.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') close();
      if (event.key === 'ArrowRight') show(activeIndex - 1);
      if (event.key === 'ArrowLeft') show(activeIndex + 1);
    });
    return modal;
  }

  function renderCaseGalleries(data, page) {
    var cases = Array.isArray(data.gallery) ? data.gallery.filter(function (item) { return galleryImages(item).length; }) : [];
    if (!cases.length) { makeGalleriesImageless(); return; }
    var isGalleryPage = page.slug === 'gallery';
    var section = null;
    if (isGalleryPage) {
      if (document.querySelector('.drb-case-gallery[data-gallery-page="true"]')) return;
      var main = document.querySelector('#root main');
      if (!main) return;
      var hero = Array.prototype.find.call(main.querySelectorAll(':scope > section'), function (item) { return /گالری/u.test(item.textContent || '') && item.querySelector('h1'); });
      Array.prototype.forEach.call(main.children, function (child) {
        if (child.tagName === 'DIV' && child.querySelector('button,[class*="grid"]') && !child.classList.contains('drb-policy-strip')) child.hidden = true;
      });
      section = createNode('section', 'drb-case-gallery');
      section.setAttribute('data-gallery-page', 'true');
      var anchor = main.querySelector('.drb-policy-strip') || hero;
      if (anchor) anchor.insertAdjacentElement('afterend', section); else main.appendChild(section);
    } else {
      section = document.querySelector('#root main #gallery');
      if (!section || section.querySelector('.drb-case-gallery__inner')) return;
      section.className = 'drb-case-gallery';
      section.innerHTML = '';
    }

    var visibleCases = isGalleryPage ? cases : cases.slice(0, 4);
    var inner = createNode('div', 'drb-case-gallery__inner');
    var heading = createNode('div', 'drb-case-gallery__heading');
    heading.appendChild(createNode('span', '', 'نتایج واقعی با رضایت انتشار'));
    heading.appendChild(createNode('h2', '', isGalleryPage ? 'هر مراجعه، یک گالری کامل' : 'نمونه‌های قبل و بعد'));
    heading.appendChild(createNode('p', '', 'برای هر بیمار، همه نماهای ثبت‌شده در همان مراجعه کنار هم قرار گرفته‌اند؛ روی هر نمونه بزنید تا جزئیات و تصاویر را ببینید.'));
    inner.appendChild(heading);

    var filters = null;
    if (isGalleryPage) {
      filters = createNode('div', 'drb-case-gallery__filters');
      [['all', 'همه نمونه‌ها'], ['primary', 'جراحی اولیه'], ['revision', 'جراحی ترمیمی']].forEach(function (filter, index) {
        var button = createNode('button', '', filter[1]);
        button.type = 'button';
        button.dataset.filter = filter[0];
        button.setAttribute('aria-pressed', index === 0 ? 'true' : 'false');
        filters.appendChild(button);
      });
      inner.appendChild(filters);
    }

    var grid = createNode('div', 'drb-case-gallery__grid');
    var modal = setupCaseLightbox(cases);
    visibleCases.forEach(function (caseItem) {
      var images = galleryImages(caseItem);
      var card = createNode('article', 'drb-case-card');
      card.dataset.kind = /ترمیم/u.test(caseItem.procedure || '') ? 'revision' : 'primary';
      var open = createNode('button', 'drb-case-card__open');
      open.type = 'button';
      open.setAttribute('aria-label', 'باز کردن گالری ' + (caseItem.label || 'نمونه جراحی'));
      var media = createNode('div', 'drb-case-card__media');
      var cover = createNode('img');
      cover.src = images[0].thumb || images[0].src;
      cover.alt = images[0].alt || (caseItem.label || 'نمونه جراحی بینی');
      cover.loading = 'lazy';
      cover.decoding = 'async';
      media.appendChild(cover);
      media.appendChild(createNode('span', 'drb-case-card__count', images.length + ' نما'));
      var body = createNode('div', 'drb-case-card__body');
      body.appendChild(createNode('h3', '', caseItem.label || 'نمونه جراحی'));
      var meta = createNode('div', 'drb-case-card__meta');
      [caseItem.procedure, caseItem.interval, caseItem.noseType].filter(Boolean).forEach(function (value) { meta.appendChild(createNode('span', '', value)); });
      body.appendChild(meta);
      open.appendChild(media); open.appendChild(body); card.appendChild(open); grid.appendChild(card);
      open.addEventListener('click', function () { modal.openCase(caseItem, open); });
    });
    inner.appendChild(grid);

    if (filters) filters.addEventListener('click', function (event) {
      var button = event.target.closest('button[data-filter]');
      if (!button) return;
      filters.querySelectorAll('button').forEach(function (item) { item.setAttribute('aria-pressed', item === button ? 'true' : 'false'); });
      grid.querySelectorAll('.drb-case-card').forEach(function (card) { card.hidden = button.dataset.filter !== 'all' && card.dataset.kind !== button.dataset.filter; });
    });

    inner.appendChild(createNode('p', 'drb-case-gallery__note', 'همه تصاویر با رضایت انتشار ارائه شده‌اند. زمان ثبت نتیجه روی هر پرونده درج می‌شود و نتیجه جراحی می‌تواند از فردی به فرد دیگر متفاوت باشد.'));
    if (!isGalleryPage && cases.length > visibleCases.length) {
      var more = createNode('div', 'drb-case-gallery__more');
      var link = createNode('a', '', 'مشاهده همه نمونه‌ها ' + (((document.documentElement.getAttribute('dir') || 'rtl') === 'rtl') ? '←' : '→'));
      link.href = '/gallery/';
      more.appendChild(link); inner.appendChild(more);
    }
    section.appendChild(inner);
  }

  function policyPanel(data) {
    var panel = document.createElement('section');
    panel.className = 'drb-policy-strip';
    panel.setAttribute('aria-label', 'اطلاعات ضروری پذیرش');
    panel.innerHTML = '<div class="drb-policy-strip__inner"><div class="drb-policy-strip__grid">'
      + '<div><strong>ساعات کاری</strong><span>شنبه تا سه‌شنبه، ۱۵:۰۰ تا ۱۸:۰۰</span><small>مراجعه بعد از ساعت ۱۸:۰۰ به هیچ‌وجه پذیرش نمی‌شود.</small></div>'
      + '<div><strong>شرایط پذیرش</strong><span>فقط ۱۸ تا ۴۵ سال</span><small>دیابت، فشار خون، بیماری کنترل‌نشده و سابقه سنترال لب پذیرفته نمی‌شوند.</small></div>'
      + '<div><strong>جراحی ترمیمی</strong><span>فقط پس از ۲۴ ماه کامل</span><small>برای کاهش خطر نکروز، عفونت و آسیب بافتی و تکمیل تغییرات بینی.</small></div>'
      + '<div><strong>رویکرد جراحی</strong><span>طبیعی و متناسب</span><small>جراحی بینی گوشتی و سبک فانتزی یا عروسکی انجام نمی‌شود.</small></div>'
      + '</div></div>';
    return panel;
  }

  function applyPolicyPanel(data) {
    if (document.querySelector('.drb-policy-strip')) return;
    var main = document.querySelector('#root main');
    if (!main) return;
    var first = main.querySelector(':scope > header, :scope > section');
    var panel = policyPanel(data);
    if (first) first.insertAdjacentElement('afterend', panel);
    else main.prepend(panel);
  }

  function applyWordPressContent() {
    scheduled = false;
    var data = window.__DRB_WP__ || {};
    var page = data.page || {};
    var h1 = document.querySelector('#root main h1, #root h1');
    if (!h1 && attempts++ < 80) { window.setTimeout(schedule, 100); return; }

    repairAssets(data);
    placeNutritionQuicklink();
    ensureAddressMapLinks(data);
    document.querySelectorAll('#root main *, #root footer *').forEach(function (element) {
      Array.prototype.forEach.call(element.childNodes || [], function (node) { if (node.nodeType === 3) replaceText(node); });
    });
    document.querySelectorAll('#root a[class~="bg-white"], #root button[class~="bg-white"]').forEach(function (item) { item.classList.add('drb-white-button'); });

    if (h1 && page.heroTitle) {
      h1.textContent = page.heroTitle;
      var hero = h1.closest('header, section');
      if (hero) {
        var eyebrow = hero.querySelector('span');
        var description = h1.parentElement && h1.parentElement.querySelector('p');
        if (eyebrow && page.heroEyebrow) eyebrow.textContent = page.heroEyebrow;
        if (description && page.heroDescription) description.textContent = page.heroDescription;
      }
    }

    if (Array.isArray(data.menu) && data.menu.length) {
      var menuByPath = {};
      data.menu.forEach(function (item) { var path = normalizedPath(item.href); if (path) menuByPath[path] = item; });
      document.querySelectorAll('#root a[href]').forEach(function (link) {
        var item = menuByPath[normalizedPath(link.getAttribute('href'))];
        if (item && link.childElementCount === 0) { link.textContent = item.label; link.href = item.href; }
      });
    }

    applyPolicyPanel(data);
    removeProhibitedPromotions();
    removeEmptyCtas();
    repairLightControlContrast();
    hideLegacySummaryBlocks(page);
    renderCaseGalleries(data, page);

    var designedPage = ['home', 'about', 'gallery', 'faq', 'contact', 'booking', 'sitemap', 'services', 'blog'].indexOf(page.slug) !== -1;
    if (page.content && !designedPage && !document.querySelector('.drb-classic-page-content')) {
      var contentPanel = document.createElement('section');
      contentPanel.className = 'drb-classic-page-content max-w-4xl mx-auto px-4 sm:px-6 py-12';
      contentPanel.setAttribute('aria-label', 'محتوای تکمیلی صفحه');
      contentPanel.innerHTML = '<div class="legacy-content bg-white rounded-2xl border border-[#DDE2DD] p-6 sm:p-8">' + page.content + '</div>';
      var main = document.querySelector('#root main');
      if (main) {
        var policy = main.querySelector('.drb-policy-strip');
        if (policy) policy.insertAdjacentElement('afterend', contentPanel);
        else main.appendChild(contentPanel);
      }
    }
  }

  function schedule() {
    if (scheduled) return;
    scheduled = true;
    window.requestAnimationFrame(applyWordPressContent);
  }

  function start() {
    schedule();
    lastPath = window.location.pathname;
    var observer = new MutationObserver(function () {
      if (window.location.pathname !== lastPath) {
        lastPath = window.location.pathname;
        document.querySelectorAll('.drb-policy-strip,.drb-classic-page-content,.drb-case-lightbox').forEach(function (item) { item.remove(); });
        document.body.classList.remove('drb-lightbox-open');
      }
      schedule();
    });
    var root = document.getElementById('root');
    if (root) observer.observe(root, { childList: true, subtree: true });
    window.addEventListener('popstate', schedule);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
}());
