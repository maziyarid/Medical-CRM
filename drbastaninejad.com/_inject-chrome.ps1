##############################################################################
#  inject-chrome.ps1  ·  drbastaninejad.com
#  Injects:  new mega-menu nav  +  premium footer  +  chaty floater
#            + nav-footer.css link  + site-chrome.js script
#  Run from:  drbastaninejad.com/  directory
##############################################################################

$ErrorActionPreference = 'Stop'
Set-Location $PSScriptRoot

# ── Helper ────────────────────────────────────────────────────────────────────
function Encode-HTML ($s) { $s }   # pass-through (content is already UTF-8)

# ── NAV: ROOT variant (assets/ paths) ─────────────────────────────────────────
$NAV_ROOT = @'
<nav class="site-nav" id="site-nav" role="navigation" aria-label="ناوبری اصلی">
  <div class="nav-inner">
    <a href="/" class="nav-brand" aria-label="صفحه اصلی — دکتر شاهین باستانی نژاد">
      <img src="assets/images/logo.svg" class="nav-logo" alt="لوگوی کلینیک دکتر شاهین باستانی نژاد" width="120" height="44" loading="eager"/>
    </a>
    <ul class="nav-links" role="list">
      <li><a href="/"><i class="fa-solid fa-house nav-icon" aria-hidden="true"></i>خانه</a></li>
      <li><a href="/about"><i class="fa-solid fa-user-doctor nav-icon" aria-hidden="true"></i>درباره دکتر</a></li>
      <li>
        <a href="/services/rhinoplasty-primary"><i class="fa-solid fa-scalpel nav-icon" aria-hidden="true"></i>جراحی بینی<i class="fa-solid fa-chevron-down nav-chevron" aria-hidden="true"></i></a>
        <div class="nav-mega" role="menu" aria-label="زیرمنوی جراحی بینی">
          <div class="nav-mega-cols">
            <div class="nav-mega-col">
              <span class="nav-mega-label">بر اساس بافت</span>
              <a href="/services/rhinoplasty-primary" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-notes-medical" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">جراحی بینی اولیه</span><span class="mi-sub">راینوپلاستی برای اولین بار</span></span>
              </a>
              <a href="/services/rhinoplasty-bony" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-bone" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">بینی استخوانی</span><span class="mi-sub">اصلاح قوز و ساختار سخت</span></span>
              </a>
              <a href="/services/rhinoplasty-fleshy" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">بینی گوشتی</span><span class="mi-sub">پوست ضخیم — چالش‌برانگیزترین نوع</span></span>
              </a>
              <span class="nav-mega-label">بر اساس سبک</span>
              <a href="/services/rhinoplasty-natural" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-leaf" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">بینی طبیعی</span><span class="mi-sub">نتیجه هماهنگ با چهره</span></span>
              </a>
              <a href="/services/rhinoplasty-fantasy" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">بینی فانتزی</span><span class="mi-sub">طرح‌های خاص و شخصی‌سازی‌شده</span></span>
              </a>
            </div>
            <div class="nav-mega-col">
              <span class="nav-mega-label">جراحی تخصصی</span>
              <a href="/services/rhinoplasty-revision" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-rotate" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">جراحی ترمیمی</span><span class="mi-sub">اصلاح نتایج عمل قبلی</span></span>
              </a>
              <a href="/services/hump-removal" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-scissors" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">رفع قوز بینی</span><span class="mi-sub">حذف برجستگی پل بینی</span></span>
              </a>
              <span class="nav-mega-label">درمانی</span>
              <a href="/services/septoplasty" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-lungs" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">انحراف بینی (سپتوپلاستی)</span><span class="mi-sub">بهبود تنفس و اصلاح انحراف</span></span>
              </a>
              <a href="/services/turbinoplasty" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-wind" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">شاخک‌های بینی</span><span class="mi-sub">توربینوپلاستی برای تنفس بهتر</span></span>
              </a>
              <a href="/services/sinus-endoscopy" class="nav-mega-item" role="menuitem">
                <span class="mi-icon"><i class="fa-solid fa-microscope" aria-hidden="true"></i></span>
                <span class="mi-text"><span class="mi-title">آندوسکوپی سینوس</span><span class="mi-sub">درمان سینوزیت مزمن</span></span>
              </a>
            </div>
          </div>
          <div class="nav-mega-footer">
            <a href="/services" class="nav-mega-footer-link"><i class="fa-solid fa-grid-2" aria-hidden="true"></i>همه خدمات</a>
            <a href="https://app.drbastaninejad.com/" class="nav-mega-footer-link" rel="noopener"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i>نوبت‌گیری آنلاین</a>
          </div>
        </div>
      </li>
      <li><a href="/gallery"><i class="fa-solid fa-images nav-icon" aria-hidden="true"></i>نمونه کارها</a></li>
      <li><a href="/blog"><i class="fa-solid fa-newspaper nav-icon" aria-hidden="true"></i>مقالات</a></li>
      <li><a href="/faq"><i class="fa-solid fa-circle-question nav-icon" aria-hidden="true"></i>سوالات</a></li>
      <li><a href="/contact"><i class="fa-solid fa-phone nav-icon" aria-hidden="true"></i>تماس</a></li>
    </ul>
    <div class="nav-cta">
      <a href="tel:02186087250" class="tel-link" aria-label="تماس تلفنی"><i class="fa-solid fa-phone" aria-hidden="true"></i>۰۲۱–۸۶۰۸۷۲۵۰</a>
      <a href="/booking" class="btn btn-primary btn-sm">نوبت‌گیری</a>
    </div>
    <button class="nav-toggle" id="nav-toggle" aria-controls="nav-mobile" aria-expanded="false" aria-label="باز کردن منو"><span></span><span></span><span></span></button>
  </div>
  <!-- Mobile panel -->
  <div id="nav-mobile" class="nav-mobile" role="dialog" aria-modal="true" aria-label="منوی ناوبری">
    <div class="nm-header">
      <img src="assets/images/logo.svg" class="nm-logo" alt="لوگو" loading="eager"/>
      <button class="nm-close" id="nm-close" aria-label="بستن منو"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button>
    </div>
    <div class="nm-body">
      <a href="/" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-house" aria-hidden="true"></i></span>خانه</a>
      <a href="/about" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-user-doctor" aria-hidden="true"></i></span>درباره دکتر</a>
      <span class="nm-group-label">جراحی بینی</span>
      <a href="/services/rhinoplasty-primary" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-notes-medical" aria-hidden="true"></i></span>جراحی بینی اولیه</a>
      <a href="/services/rhinoplasty-bony" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-bone" aria-hidden="true"></i></span>بینی استخوانی</a>
      <a href="/services/rhinoplasty-fleshy" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-droplet" aria-hidden="true"></i></span>بینی گوشتی</a>
      <a href="/services/rhinoplasty-natural" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-leaf" aria-hidden="true"></i></span>بینی طبیعی</a>
      <a href="/services/rhinoplasty-fantasy" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-star" aria-hidden="true"></i></span>بینی فانتزی</a>
      <a href="/services/rhinoplasty-revision" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-rotate" aria-hidden="true"></i></span>جراحی ترمیمی</a>
      <a href="/services/hump-removal" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-scissors" aria-hidden="true"></i></span>رفع قوز بینی</a>
      <a href="/services/septoplasty" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-lungs" aria-hidden="true"></i></span>انحراف بینی</a>
      <a href="/services/turbinoplasty" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-wind" aria-hidden="true"></i></span>شاخک‌های بینی</a>
      <a href="/services/sinus-endoscopy" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-microscope" aria-hidden="true"></i></span>آندوسکوپی سینوس</a>
      <span class="nm-group-label">صفحات</span>
      <a href="/gallery" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-images" aria-hidden="true"></i></span>نمونه کارها</a>
      <a href="/blog" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-newspaper" aria-hidden="true"></i></span>مقالات</a>
      <a href="/faq" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>سوالات متداول</a>
      <a href="/contact" class="nm-item"><span class="nm-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>تماس با ما</a>
    </div>
    <div class="nm-footer">
      <a href="/booking" class="btn btn-outline"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i>نوبت‌گیری</a>
      <a href="https://app.drbastaninejad.com/" class="btn btn-primary" rel="noopener"><i class="fa-solid fa-file-medical" aria-hidden="true"></i>تشکیل پرونده</a>
    </div>
  </div>
</nav>
'@

# ── NAV: SERVICES variant (../assets/ paths) ──────────────────────────────────
$NAV_SERVICES = $NAV_ROOT `
  -replace 'href="index\.html"', 'href="../index.html"' `
  -replace 'href="about\.html"', 'href="/about"' `
  -replace 'href="services/', 'href="' `
  -replace 'href="gallery\.html"', 'href="/gallery"' `
  -replace 'href="blog\.html"', 'href="/blog"' `
  -replace 'href="faq\.html"', 'href="/faq"' `
  -replace 'href="contact\.html"', 'href="/contact"' `
  -replace 'href="booking\.html"', 'href="/booking"' `
  -replace 'href="services\.html"', 'href="/services"' `
  -replace 'src="assets/', 'src="../assets/' `
  -replace 'aria-label="صفحه اصلی — دکتر شاهین باستانی نژاد"', 'aria-label="صفحه اصلی — دکتر شاهین باستانی نژاد"'

# ── NAV: BLOG variant (../assets/ paths, same as services) ────────────────────
$NAV_BLOG = $NAV_SERVICES

# ── FOOTER: ROOT variant ──────────────────────────────────────────────────────
$FOOTER_ROOT = @'
<footer class="site-footer" role="contentinfo">
  <div class="container">
    <!-- Social bar -->
    <div class="footer-social" role="list" aria-label="شبکه‌های اجتماعی">
      <a href="https://www.instagram.com/dr.shahin.bastaninejad/" target="_blank" rel="noopener noreferrer" class="footer-social-link ig" role="listitem" aria-label="اینستاگرام دکتر باستانی‌نژاد">
        <span class="fs-icon"><i class="fa-brands fa-instagram" aria-hidden="true"></i></span>اینستاگرام
      </a>
      <a href="https://www.youtube.com/@Drshahinbastaninejad" target="_blank" rel="noopener noreferrer" class="footer-social-link yt" role="listitem" aria-label="یوتیوب دکتر باستانی‌نژاد">
        <span class="fs-icon"><i class="fa-brands fa-youtube" aria-hidden="true"></i></span>یوتیوب
      </a>
      <a href="https://www.aparat.com/dr.bastaninejad" target="_blank" rel="noopener noreferrer" class="footer-social-link ap" role="listitem" aria-label="آپارات دکتر باستانی‌نژاد">
        <span class="fs-icon"><i class="fa-solid fa-play" aria-hidden="true"></i></span>آپارات
      </a>
      <a href="https://t.me/dr_bastaninejad" target="_blank" rel="noopener noreferrer" class="footer-social-link tg" role="listitem" aria-label="تلگرام دکتر باستانی‌نژاد">
        <span class="fs-icon"><i class="fa-brands fa-telegram" aria-hidden="true"></i></span>تلگرام
      </a>
    </div>
    <!-- Grid -->
    <div class="footer-grid">
      <!-- Brand col -->
      <div class="footer-brand">
        <img src="assets/images/logo-monochrome.svg" class="footer-logo" alt="لوگوی کلینیک دکتر شاهین باستانی نژاد" width="130" height="50" loading="lazy"/>
        <p class="footer-brand-tagline">جراح و متخصص گوش، گلو و بینی<br/>جراح پلاستیک بینی — تهران</p>
        <div class="footer-stats" role="list" aria-label="آمار کلینیک">
          <div class="footer-stat" role="listitem"><span class="fs-num" data-count="15" data-suffix="+">+۱۵</span><span class="fs-lbl">سال تجربه</span></div>
          <div class="footer-stat" role="listitem"><span class="fs-num" data-count="2000" data-suffix="+">+۲۰۰۰</span><span class="fs-lbl">عمل موفق</span></div>
          <div class="footer-stat" role="listitem"><span class="fs-num" data-count="178" data-suffix="+">+۱۷۸</span><span class="fs-lbl">نمونه گالری</span></div>
        </div>
      </div>
      <!-- Quick links -->
      <nav class="footer-col" aria-label="لینک‌های سریع">
        <h4>لینک‌های سریع</h4>
        <ul role="list">
          <li><a href="/"><i class="fa-solid fa-house" aria-hidden="true"></i>خانه</a></li>
          <li><a href="/about"><i class="fa-solid fa-user-doctor" aria-hidden="true"></i>درباره دکتر</a></li>
          <li><a href="/services"><i class="fa-solid fa-grid-2" aria-hidden="true"></i>همه خدمات</a></li>
          <li><a href="/gallery"><i class="fa-solid fa-images" aria-hidden="true"></i>نمونه کارها</a></li>
          <li><a href="/blog"><i class="fa-solid fa-newspaper" aria-hidden="true"></i>مقالات</a></li>
          <li><a href="/faq"><i class="fa-solid fa-circle-question" aria-hidden="true"></i>سوالات متداول</a></li>
          <li><a href="/contact"><i class="fa-solid fa-location-dot" aria-hidden="true"></i>تماس و آدرس</a></li>
          <li><a href="/booking"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i>نوبت‌گیری</a></li>
        </ul>
      </nav>
      <!-- Services -->
      <nav class="footer-col" aria-label="خدمات">
        <h4>خدمات</h4>
        <ul role="list">
          <li><a href="/services/rhinoplasty-primary"><i class="fa-solid fa-notes-medical" aria-hidden="true"></i>جراحی بینی اولیه</a></li>
          <li><a href="/services/rhinoplasty-revision"><i class="fa-solid fa-rotate" aria-hidden="true"></i>جراحی ترمیمی</a></li>
          <li><a href="/services/rhinoplasty-fleshy"><i class="fa-solid fa-droplet" aria-hidden="true"></i>بینی گوشتی</a></li>
          <li><a href="/services/rhinoplasty-bony"><i class="fa-solid fa-bone" aria-hidden="true"></i>بینی استخوانی</a></li>
          <li><a href="/services/rhinoplasty-natural"><i class="fa-solid fa-leaf" aria-hidden="true"></i>بینی طبیعی</a></li>
          <li><a href="/services/rhinoplasty-fantasy"><i class="fa-solid fa-star" aria-hidden="true"></i>بینی فانتزی</a></li>
          <li><a href="/services/hump-removal"><i class="fa-solid fa-scissors" aria-hidden="true"></i>رفع قوز بینی</a></li>
          <li><a href="/services/septoplasty"><i class="fa-solid fa-lungs" aria-hidden="true"></i>انحراف بینی</a></li>
        </ul>
      </nav>
      <!-- Contact -->
      <div class="footer-col">
        <h4>تماس با ما</h4>
        <div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-solid fa-phone" aria-hidden="true"></i></div>
          <div class="footer-contact-text">
            <span class="fct-label">تلفن کلینیک</span>
            <a href="tel:02186087250" class="fct-value">۰۲۱–۸۶۰۸۷۲۵۰</a>
            <a href="tel:02188205606" class="fct-value">۰۲۱–۸۸۲۰۵۶۰۶</a>
          </div>
        </div>
        <div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-solid fa-clock" aria-hidden="true"></i></div>
          <div class="footer-contact-text">
            <span class="fct-label">ساعت پذیرش</span>
            <span class="fct-value">شنبه و سه‌شنبه  ۱۵:۰۰–۱۹:۰۰</span>
          </div>
        </div>
        <div class="footer-contact-item">
          <div class="footer-contact-icon"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></div>
          <div class="footer-contact-text">
            <span class="fct-label">آدرس</span>
            <span class="fct-value" style="font-size:.8rem;line-height:1.5">تهران، خ. نلسون ماندلا، خ. صانعی، ساختمان نور، پلاک ۱ واحد ۶</span>
          </div>
        </div>
      </div>
    </div>
    <!-- Bottom bar -->
    <div class="footer-bottom">
      <span>© ۱۴۰۵ کلینیک دکتر شاهین باستانی نژاد — تمامی حقوق محفوظ است.</span>
      <span class="maz-sig">طراحی توسط <a href="https://maziyarid.com" target="_blank" rel="noopener">MA<span class="z">Z</span></a></span>
    </div>
  </div>
</footer>
'@

# ── FOOTER: SERVICES & BLOG variant (../ prefixed paths) ─────────────────────
$FOOTER_SUB = $FOOTER_ROOT `
  -replace 'href="index\.html"', 'href="../index.html"' `
  -replace 'href="about\.html"', 'href="/about"' `
  -replace 'href="services\.html"', 'href="/services"' `
  -replace 'href="gallery\.html"', 'href="/gallery"' `
  -replace 'href="blog\.html"', 'href="/blog"' `
  -replace 'href="faq\.html"', 'href="/faq"' `
  -replace 'href="contact\.html"', 'href="/contact"' `
  -replace 'href="booking\.html"', 'href="/booking"' `
  -replace 'href="services/', 'href="../services/' `
  -replace 'src="assets/', 'src="../assets/'

# ── CHATY floater HTML ────────────────────────────────────────────────────────
$CHATY = @'
<!-- Backdrop -->
<div id="nav-backdrop" class="nav-backdrop" aria-hidden="true"></div>

<!-- Floating contact (Chaty) -->
<div class="chaty-wrap" id="chaty-wrap" aria-label="ارتباط سریع">
  <div class="chaty-channels" id="chaty-channels" aria-label="کانال‌های ارتباطی">
    <a href="https://wa.me/989124966590" target="_blank" rel="noopener noreferrer" class="chaty-channel" aria-label="واتس‌اپ">
      <span class="chaty-ch-icon wa"><i class="fa-brands fa-whatsapp" aria-hidden="true"></i></span>واتس‌اپ
    </a>
    <a href="https://t.me/dr_bastaninejad" target="_blank" rel="noopener noreferrer" class="chaty-channel" aria-label="تلگرام">
      <span class="chaty-ch-icon tg"><i class="fa-brands fa-telegram" aria-hidden="true"></i></span>تلگرام
    </a>
    <a href="tel:02186087250" class="chaty-channel" aria-label="تماس تلفنی ۱">
      <span class="chaty-ch-icon ph"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>۰۲۱–۸۶۰۸۷۲۵۰
    </a>
    <a href="tel:02188205606" class="chaty-channel" aria-label="تماس تلفنی ۲">
      <span class="chaty-ch-icon ph2"><i class="fa-solid fa-phone" aria-hidden="true"></i></span>۰۲۱–۸۸۲۰۵۶۰۶
    </a>
    <a href="https://app.drbastaninejad.com/" target="_blank" rel="noopener noreferrer" class="chaty-channel" aria-label="نوبت‌گیری آنلاین">
      <span class="chaty-ch-icon bk"><i class="fa-solid fa-calendar-check" aria-hidden="true"></i></span>نوبت‌گیری آنلاین
    </a>
  </div>
  <button class="chaty-trigger" id="chaty-trigger" aria-expanded="false" aria-controls="chaty-channels" aria-label="باز کردن گزینه‌های تماس">
    <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
    <span class="chaty-badge" aria-label="۱ پیام جدید">۱</span>
    <span class="chaty-tooltip">سوالی داشتین در خدمتیم</span>
  </button>
</div>
'@

# ── CSS link to inject (before </head>) ───────────────────────────────────────
$CSS_LINK_ROOT = '  <link rel="stylesheet" href="assets/css/nav-footer.css"/>'
$CSS_LINK_SUB  = '  <link rel="stylesheet" href="../assets/css/nav-footer.css"/>'

# ── JS scripts to inject (before </body>) ─────────────────────────────────────
$JS_ROOT = @'
<script src="assets/js/main.js"></script>
<script src="assets/js/premium.js"></script>
<script src="assets/js/site-chrome.js"></script>
'@
$JS_SUB = @'
<script src="../assets/js/main.js"></script>
<script src="../assets/js/premium.js"></script>
<script src="../assets/js/site-chrome.js"></script>
'@

# ── Define which files use which variant ──────────────────────────────────────
$rootFiles = @(
  'about.html','blog.html','booking.html','contact.html',
  'faq.html','gallery.html','index.html','services.html'
)
$servicesFiles = @(
  'services\hump-removal.html','services\rhinoplasty-bony.html',
  'services\rhinoplasty-fantasy.html','services\rhinoplasty-fleshy.html',
  'services\rhinoplasty-natural.html','services\rhinoplasty-primary.html',
  'services\rhinoplasty-revision.html','services\rhinoplasty.html',
  'services\septoplasty.html','services\sinus-endoscopy.html',
  'services\turbinoplasty.html'
)
$blogFiles = @(
  'blog\post-op-care.html','blog\pre-op-steps.html',
  'blog\rhinoplasty-fleshy.html','blog\rhinoplasty-revision.html',
  'blog\rhinoplasty.html'
)

# ── Process function ──────────────────────────────────────────────────────────
function Process-File {
  param($path, $navHTML, $footerHTML, $cssLink, $jsBlock)

  Write-Host "  Processing: $path" -ForegroundColor Cyan

  $raw = [System.IO.File]::ReadAllText($path, [System.Text.Encoding]::UTF8)

  # 1. Add nav-footer.css link (after design-refresh.css link, before </head>)
  if ($raw -notmatch 'nav-footer\.css') {
    $raw = $raw -replace '([ \t]*<link[^>]+design-refresh\.css[^>]*>)', "`$1`n$cssLink"
  }

  # 2. Replace the entire <nav class="site-nav"...>...</nav> block
  #    The regex uses DOTALL (?s) and matches from <nav class="site-nav to </nav>
  $raw = $raw -replace '(?s)<nav class="site-nav"[^>]*>.*?</nav>', $navHTML.Trim()

  # 3. Replace the entire <footer class="site-footer"...>...</footer> block
  $raw = $raw -replace '(?s)<footer class="site-footer"[^>]*>.*?</footer>', $footerHTML.Trim()

  # 4. Remove any old script block(s) for main.js / premium.js near </body>
  $raw = $raw -replace '(?s)(<script src="[^"]*main\.js[^"]*"></script>\s*<script src="[^"]*premium\.js[^"]*"></script>\s*)(<script src="[^"]*site-chrome\.js[^"]*"></script>\s*)?', ''

  # 5. Inject chaty + backdrop + new JS scripts before </body>
  if ($raw -notmatch 'chaty-wrap') {
    $raw = $raw -replace '(?s)(</body>)', "`n$CHATY`n$jsBlock`n`$1"
  } else {
    # Chaty already present — just re-inject JS scripts
    $raw = $raw -replace '(?s)(</body>)', "`n$jsBlock`n`$1"
  }

  [System.IO.File]::WriteAllText($path, $raw, [System.Text.Encoding]::UTF8)
  Write-Host "    Done." -ForegroundColor Green
}

# ── Run ───────────────────────────────────────────────────────────────────────
Write-Host "`n=== Injecting chrome into ROOT pages ===" -ForegroundColor Yellow
foreach ($f in $rootFiles) {
  if (Test-Path $f) {
    Process-File $f $NAV_ROOT $FOOTER_ROOT $CSS_LINK_ROOT $JS_ROOT
  } else {
    Write-Warning "Not found: $f"
  }
}

Write-Host "`n=== Injecting chrome into SERVICES pages ===" -ForegroundColor Yellow
foreach ($f in $servicesFiles) {
  if (Test-Path $f) {
    Process-File $f $NAV_SERVICES $FOOTER_SUB $CSS_LINK_SUB $JS_SUB
  } else {
    Write-Warning "Not found: $f"
  }
}

Write-Host "`n=== Injecting chrome into BLOG pages ===" -ForegroundColor Yellow
foreach ($f in $blogFiles) {
  if (Test-Path $f) {
    Process-File $f $NAV_BLOG $FOOTER_SUB $CSS_LINK_SUB $JS_SUB
  } else {
    Write-Warning "Not found: $f"
  }
}

Write-Host "`n=== ALL DONE ===" -ForegroundColor Green
