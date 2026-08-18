# Website Redesign - MΛZ Medical CRM

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 26 July 2026
**Project:** Full redesign of drbastaninejad.com with PWA, mobile-first, SEO-optimized, and interconnected with CRM dashboard.

---

## 🎯 Project Overview

### **Goals**
1. **Mobile-First Design**: Optimized for all devices, especially mobile (Iranian market focus).
2. **PWA (Progressive Web App)**: Offline support, fast loading, installable on devices.
3. **Instant Page Loading**: Near-zero latency navigation using preloading and caching.
4. **Easy Login**: OTP-based authentication with persistent sessions.
5. **Advanced SEO**: Full optimization for Persian search engines (Google, Parsijoo, etc.).
6. **WordPress Migration**: Include all existing pages/posts in the new design.
7. **Dashboard Integration**: Seamless connection to the Medical CRM backend.

---

## 🏗️ Site Architecture

### **Page Hierarchy**
```
🌐 drbastaninejad.com/
├── / (Homepage)
│   ├── Hero section (CTA: Book Appointment)
│   ├── Clinic intro
│   ├── Services overview
│   ├── Doctors showcase
│   ├── Testimonials
│   └── News/Updates (from WordPress)
│
├── /services/
│   ├── /rhinoplasty/ (Rhinoplasty - Primary specialty)
│   ├── /dermatology/
│   ├── /dentistry/
│   ├── /ophthalmology/
│   └── /orthopedics/
│
├── /doctors/
│   ├── /dr-shahin-bastaninejad/ (Primary)
│   └── /[doctor-slug]/ (Future doctors)
│
├── /appointment/ (Public Intake Form)
│   └── Multi-step wizard (OTP, personal info, medical history)
│
├── /patient/ (Patient Portal - Requires Login)
│   ├── /dashboard/ (Upcoming appointments, medical history)
│   ├── /appointments/ (List, details, reschedule)
│   ├── /documents/ (Invoices, prescriptions, test results)
│   ├── /profile/ (Personal info, settings)
│   └── /messages/ (Secure messaging with clinic)
│
├── /about/ (Clinic Information)
│   ├── /team/
│   ├── /facilities/
│   └── /certifications/
│
├── /contact/ (Multiple contact methods)
│   ├── Phone, WhatsApp, Telegram
│   ├── Location map (Google Maps alternative for Iran)
│   └── Contact form (connected to CRM)
│
├── /blog/ (WordPress Posts - Migrated)
│   ├── /[post-slug]/ (Single post)
│   ├── /category/[slug]/
│   └── /tag/[slug]/
│
├── /news/ (Clinic Updates)
│   └── /[news-slug]/
│
├── /faq/ (Frequently Asked Questions)
│   └── Searchable Q&A with Persian support
│
├── /login/ (Staff & Patient Login)
│   ├── OTP login (for patients)
│   └── Password login (for staff)
│
├── /staff/ (CRM Dashboard - Internal)
│   ├── /dashboard/ (Overview, KPIs)
│   ├── /patients/ (Full patient management)
│   ├── /calendar/ (Appointment scheduling)
│   ├── /emr/ (Electronic Medical Records)
│   ├── /billing/ (Invoices, payments)
│   ├── /reports/ (Analytics, exports)
│   └── /settings/ (Clinic configuration)
│
└── /admin/ (WordPress Admin - Hidden)
    └── Standard WordPress admin panel
```

---

### **Page Types & Templates**

| Template | Purpose | File | Notes |
|----------|---------|------|-------|
| **Homepage** | Landing page with CTAs | `index.html` | Hero, services, doctors, news |
| **Service** | Specialty service page | `service.html` | Dynamic content from Laravel API |
| **Doctor** | Doctor profile | `doctor.html` | Photo, bio, specialties, schedule |
| **Appointment** | Public intake form | `appointment.html` | Multi-step, OTP, offline support |
| **Patient Portal** | Patient dashboard | `patient/*.html` | Authenticated, RTL-optimized |
| **Blog** | WordPress posts | `blog.html`, `post.html` | Migrated from WordPress |
| **Static** | About, Contact, FAQ | `about.html`, `contact.html` | Standard content |
| **Auth** | Login/Registration | `login.html`, `register.html` | OTP + password |
| **CRM** | Staff dashboard | `staff/*.html` | Existing CRM pages |

---

## 🎨 Design System

### **1. Brand Tokens (from `tokens.css`)**

```css
:root {
  /* Primary (Clinical Evergreen) */
  --evergreen:      #2F7D32;
  --evergreen-dark: #246b28;
  --evergreen-soft: #E4F0E4;
  
  /* Neutrals */
  --graphite:       #25272C;
  --muted:          #6A7078;
  --border:         #DDE2DD;
  --porcelain:      #F7F8F6;
  --surface:        #FFFFFF;
  
  /* Accents */
  --brass:          #B6905E;
  --maz-primary:    #0EA5FF;
  --maz-accent:     #A8FF4D;
  
  /* Semantic Colors */
  --error:          #D32F2F;
  --error-soft:     #FFEBEE;
  --success:        #388E3C;
  --success-soft:   #E8F5E9;
  --warning:        #F57C00;
  --warning-soft:   #FFF3E0;
  --info:           #1976D2;
  --info-soft:      #E3F2FD;
  
  /* Typography */
  --font-persian:   'Vazirmatn', sans-serif;
  --font-latin:     'Inter', sans-serif;
  --font-code:      'JetBrains Mono', monospace;
  
  /* Spacing */
  --space-xs:       0.25rem;
  --space-sm:       0.5rem;
  --space-md:       1rem;
  --space-lg:       1.5rem;
  --space-xl:       2rem;
  
  /* Border Radius */
  --radius-sm:      0.5rem;
  --radius-md:      0.75rem; /* Buttons/inputs */
  --radius-lg:      1rem;    /* Cards */
  
  /* Shadows */
  --shadow-sm:      0 1px 2px rgba(0, 0, 0, 0.1);
  --shadow-md:      0 4px 6px rgba(0, 0, 0, 0.1);
  --shadow-lg:      0 10px 15px rgba(0, 0, 0, 0.1);
  
  /* Transitions */
  --transition-fast: 0.15s ease;
  --transition-normal: 0.3s ease;
}
```

---

### **2. Typography**

| Type | Persian Font | Latin Font | Size (Mobile) | Size (Desktop) | Line Height |
|------|--------------|------------|---------------|----------------|-------------|
| **H1** | Vazirmatn Bold | Inter Bold | `1.75rem` | `2.5rem` | 1.2 |
| **H2** | Vazirmatn SemiBold | Inter SemiBold | `1.5rem` | `2rem` | 1.3 |
| **H3** | Vazirmatn Medium | Inter Medium | `1.25rem` | `1.75rem` | 1.4 |
| **Body** | Vazirmatn Regular | Inter Regular | `1rem` | `1.1rem` | **1.6+** (Critical for Persian) |
| **Small** | Vazirmatn Light | Inter Light | `0.875rem` | `0.95rem` | 1.5 |
| **Code** | JetBrains Mono | JetBrains Mono | `0.85rem` | `0.9rem` | 1.5 |

**Critical Rules:**
- **Persian text must never have line-height < 1.6** (avoids overlap).
- **H1 max width:** `clamp(1.4rem, 3vw, 1.75rem)` (prevents overflow on mobile).
- **Direction:** All text containers must have `dir="rtl"` for Persian, `dir="ltr"` for Latin (e.g., numbers, drug names).

---

### **3. Color Usage Guidelines**

| Element | Color | Usage |
|---------|-------|-------|
| **Primary Text** | `--graphite` | Body text, headings |
| **Secondary Text** | `--muted` | Subtext, placeholders |
| **Background** | `--porcelain` | Page background |
| **Surface** | `--surface` | Cards, modals |
| **Primary CTA** | `--evergreen` | Buttons, links |
| **Secondary CTA** | `--brass` | Secondary actions |
| **Accent** | `--maz-primary` | Highlights, MAZ//ID branding |
| **Error** | `--error` | Validation errors, alerts |
| **Success** | `--success` | Confirmation messages |

---

## 📱 Mobile-First Design

### **1. Responsive Breakpoints**

```css
/* Mobile-first approach */
:root {
  --screen-sm: 576px;
  --screen-md: 768px;
  --screen-lg: 992px;
  --screen-xl: 1200px;
}

/* Base styles (mobile) - no media query needed */

/* Small Tablet */
@media (min-width: 576px) { }

/* Tablet / Small Desktop */
@media (min-width: 768px) { }

/* Desktop */
@media (min-width: 992px) { }

/* Large Desktop */
@media (min-width: 1200px) { }
```

---

### **2. Touch Targets**

- **Minimum size:** 48x48px for all interactive elements.
- **Spacing:** At least 8px between touch targets.

```css
button,
[role="button"],
a,
input,
select,
textarea {
  min-width: 48px;
  min-height: 48px;
  padding: 12px;
  margin: 4px;
}
```

---

### **3. Mobile Navigation**

#### **Bottom Navigation (Mobile)**
```html
<nav class="maz-bottom-nav">
  <a href="/" class="maz-bottom-nav__item">
    <svg><use xlink:href="#icon-home"></use></svg>
    <span>خانه</span>
  </a>
  <a href="/appointment" class="maz-bottom-nav__item">
    <svg><use xlink:href="#icon-calendar"></use></svg>
    <span>نوبت</span>
  </a>
  <a href="/patient/dashboard" class="maz-bottom-nav__item">
    <svg><use xlink:href="#icon-user"></use></svg>
    <span>پروفایل</span>
  </a>
  <a href="/login" class="maz-bottom-nav__item">
    <svg><use xlink:href="#icon-login"></use></svg>
    <span>ورود</span>
  </a>
</nav>
```

```css
.maz-bottom-nav {
  position: fixed;
  bottom: 0;
  left: 0;
  right: 0;
  display: flex;
  justify-content: space-around;
  background: var(--surface);
  border-top: 1px solid var(--border);
  padding: 0.5rem 0;
  z-index: 100;
  box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
}

.maz-bottom-nav__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.25rem;
  padding: 0.5rem;
  color: var(--muted);
  text-decoration: none;
  font-size: 0.7rem;
  min-width: 64px;
}

.maz-bottom-nav__item svg {
  width: 24px;
  height: 24px;
}

.maz-bottom-nav__item.active {
  color: var(--evergreen);
}
```

---

#### **Sidebar Navigation (Tablet/Desktop)**
```html
<aside class="maz-sidebar">
  <div class="maz-sidebar__header">
    <img src="/assets/img/logo.svg" alt="MΛZ Medical CRM" class="maz-sidebar__logo">
  </div>
  <nav class="maz-sidebar__nav">
    <a href="/" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-home"></use></svg>
      <span>خانه</span>
    </a>
    <a href="/appointment" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-calendar"></use></svg>
      <span>نوبت‌دهی</span>
    </a>
    <a href="/services" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-services"></use></svg>
      <span>خدمات</span>
    </a>
    <a href="/doctors" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-doctors"></use></svg>
      <span>پزشکان</span>
    </a>
    <a href="/about" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-info"></use></svg>
      <span>درباره ما</span>
    </a>
    <a href="/contact" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-contact"></use></svg>
      <span>تماس با ما</span>
    </a>
    <a href="/blog" class="maz-sidebar__link">
      <svg><use xlink:href="#icon-blog"></use></svg>
      <span>وبلاگ</span>
    </a>
    <a href="/login" class="maz-sidebar__link maz-sidebar__link--login">
      <svg><use xlink:href="#icon-login"></use></svg>
      <span>ورود / ثبت‌نام</span>
    </a>
  </nav>
</aside>
```

```css
.maz-sidebar {
  position: fixed;
  top: 0;
  left: 0;
  bottom: 0;
  width: 240px;
  background: var(--surface);
  border-right: 1px solid var(--border);
  padding: var(--space-lg) 0;
  display: flex;
  flex-direction: column;
  z-index: 100;
}

.maz-sidebar__logo {
  width: 100%;
  padding: 0 var(--space-lg);
  margin-bottom: var(--space-lg);
}

.maz-sidebar__nav {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0 var(--space-sm);
}

.maz-sidebar__link {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: 0.75rem var(--space-lg);
  color: var(--graphite);
  text-decoration: none;
  border-radius: var(--radius-md);
  transition: background var(--transition-fast);
  font-size: 0.9rem;
}

.maz-sidebar__link:hover {
  background: var(--porcelain);
}

.maz-sidebar__link.active {
  background: var(--evergreen-soft);
  color: var(--evergreen);
}

.maz-sidebar__link svg {
  width: 20px;
  height: 20px;
  flex-shrink: 0;
}

.maz-sidebar__link--login {
  margin-top: auto;
  border-top: 1px solid var(--border);
  padding-top: var(--space-lg);
}
```

---

### **4. Mobile-Specific Optimizations**

| Optimization | Implementation | Benefit |
|-------------|----------------|---------|
| **Viewport Meta Tag** | `<meta name="viewport" content="width=device-width, initial-scale=1.0">` | Proper scaling |
| **No Horizontal Scroll** | `overflow-x: hidden` on `<html>` | Prevents accidental scrolling |
| **Touch Scrolling** | `-webkit-overflow-scrolling: touch` | Smooth scrolling on iOS |
| **Prevent Zoom** | `maximum-scale=1.0, user-scalable=no` | Prevents accidental zoom |
| **Fast Tap** | `touch-action: manipulation` | Reduces 300ms delay on taps |
| **Input Types** | Use `type="tel"`, `type="email"` | Better mobile keyboards |
| **Inputmode** | `inputmode="numeric"` for numbers | Shows numeric keyboard |

---

## 🚀 PWA Implementation

### **1. Web App Manifest (`manifest.json`)**

```json
{
  "name": "MΛZ Medical CRM",
  "short_name": "MΛZ CRM",
  "description": "سیستم مدیریت پزشکی دکتر شهین باستانی‌نژاد",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#F7F8F6",
  "theme_color": "#2F7D32",
  "dir": "rtl",
  "lang": "fa-IR",
  "icons": [
    {
      "src": "/assets/img/icons/icon-72x72.png",
      "sizes": "72x72",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-96x96.png",
      "sizes": "96x96",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-128x128.png",
      "sizes": "128x128",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-144x144.png",
      "sizes": "144x144",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-152x152.png",
      "sizes": "152x152",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-192x192.png",
      "sizes": "192x192",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-384x384.png",
      "sizes": "384x384",
      "type": "image/png"
    },
    {
      "src": "/assets/img/icons/icon-512x512.png",
      "sizes": "512x512",
      "type": "image/png"
    }
  ],
  "screenshots": [
    {
      "src": "/assets/img/screenshots/screenshot-desktop.png",
      "sizes": "1280x720",
      "type": "image/png",
      "form_factor": "wide",
      "label": "MΛZ Medical CRM - Desktop"
    },
    {
      "src": "/assets/img/screenshots/screenshot-mobile.png",
      "sizes": "720x1280",
      "type": "image/png",
      "form_factor": "narrow",
      "label": "MΛZ Medical CRM - Mobile"
    }
  ],
  "related_applications": [],
  "prefer_related_applications": false,
  "iarc_ratings_id": "",
  "handle_links": "preferred",
  "launch_handler": {
    "client_mode": "navigate-existing"
  },
  "share_target": {
    "action": "/appointment",
    "method": "POST",
    "enctype": "multipart/form-data",
    "params": {
      "title": "رزرو نوبت",
      "text": "نوبت خود را در کلینیک دکتر شهین باستانی‌نژاد رزرو کنید",
      "url": "/appointment"
    }
  }
}
```

---

### **2. Service Worker (`sw.js`)**

```javascript
// Cache names
const CACHE_NAME = 'maz-medical-crm-v1';
const ASSETS_CACHE = 'maz-assets-v1';
const API_CACHE = 'maz-api-v1';

// Assets to cache (critical for offline)
const ASSETS_TO_CACHE = [
  '/',
  '/index.html',
  '/appointment.html',
  '/login.html',
  '/about.html',
  '/contact.html',
  '/faq.html',
  '/services.html',
  '/doctors.html',
  '/patient/dashboard.html',
  '/staff/dashboard.html',
  '/assets/css/tokens.css',
  '/assets/css/base.css',
  '/assets/css/rtl.css',
  '/assets/css/components.css',
  '/assets/js/app.js',
  '/assets/js/chrome.js',
  '/assets/img/logo.svg',
  '/assets/img/logo-white.svg',
  '/assets/img/icons/icon-192x192.png',
  'https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700&display=swap',
];

// Install service worker
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(ASSETS_CACHE)
      .then((cache) => cache.addAll(ASSETS_TO_CACHE))
  );
});

// Fetch handling
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);
  
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;
  
  // Cache API responses (with short TTL)
  if (url.pathname.startsWith('/api/v1/')) {
    event.respondWith(
      caches.open(API_CACHE)
        .then((cache) => {
          return cache.match(event.request)
            .then((cachedResponse) => {
              // Return cached response if exists and not stale
              if (cachedResponse) {
                const now = Date.now();
                const cachedTime = new Date(cachedResponse.headers.get('Date')).getTime();
                if (now - cachedTime < 5 * 60 * 1000) { // 5 minutes
                  return cachedResponse;
                }
              }
              // Fetch fresh and cache
              return fetch(event.request)
                .then((response) => {
                  cache.put(event.request, response.clone());
                  return response;
                });
            });
        })
    );
    return;
  }
  
  // Cache static assets
  if (ASSETS_TO_CACHE.includes(url.pathname) || 
      url.pathname.endsWith('.css') || 
      url.pathname.endsWith('.js') ||
      url.pathname.endsWith('.svg') ||
      url.pathname.endsWith('.png')) {
    event.respondWith(
      caches.match(event.request)
        .then((cachedResponse) => {
          return cachedResponse || fetch(event.request)
            .then((response) => {
              const responseClone = response.clone();
              caches.open(ASSETS_CACHE)
                .then((cache) => cache.put(event.request, responseClone));
              return response;
            });
        })
    );
    return;
  }
  
  // Fallback to network
  event.respondWith(fetch(event.request));
});

// Activate service worker (clean old caches)
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME && name !== ASSETS_CACHE && name !== API_CACHE)
          .map((name) => caches.delete(name))
      );
    })
  );
});

// Listen for messages from client (e.g., cache updates)
self.addEventListener('message', (event) => {
  if (event.data.type === 'CACHE_CLEAR') {
    caches.delete(ASSETS_CACHE);
    caches.delete(API_CACHE);
  }
});
```

---

### **3. Register Service Worker**

```html
<!-- In index.html head -->
<link rel="manifest" href="/manifest.json">

<!-- In index.html body (end) -->
<script>
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    // Register service worker
    navigator.serviceWorker.register('/sw.js')
      .then((registration) => {
        console.log('ServiceWorker registration successful');
        
        // Update service worker when new version is available
        registration.onupdatefound = () => {
          const newWorker = registration.installing;
          newWorker.onstatechange = () => {
            if (newWorker.state === 'installed') {
              // New version available
              if (navigator.serviceWorker.controller) {
                showUpdatePrompt();
              }
            }
          };
        };
      })
      .catch((err) => {
        console.log('ServiceWorker registration failed: ', err);
      });
  });
  
  // Check for updates every 24 hours
  setInterval(() => {
    if (navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage({
        type: 'CHECK_FOR_UPDATES'
      });
    }
  }, 24 * 60 * 60 * 1000);
}

// Show update prompt
function showUpdatePrompt() {
  const updateToast = document.createElement('div');
  updateToast.className = 'maz-toast';
  updateToast.innerHTML = `
    <svg class="maz-toast__icon"><use xlink:href="#icon-refresh"></use></svg>
    <span class="maz-toast__message">نسخه جدید در دسترس است</span>
    <button class="maz-button maz-button--small" onclick="window.location.reload()">بارگذاری مجدد</button>
  `;
  document.body.appendChild(updateToast);
  setTimeout(() => updateToast.remove(), 10000);
}
</script>
```

---

### **4. PWA Features**

| Feature | Implementation | Benefit |
|---------|----------------|---------|
| **Install Prompt** | `beforeinstallprompt` event | Encourages app installation |
| **Offline Mode** | Service Worker caching | Works without internet |
| **Splash Screen** | `manifest.json` + custom HTML | Professional loading screen |
| **Push Notifications** | Service Worker + Push API | Appointment reminders |
| **Background Sync** | Service Worker + Sync API | Sync when online |

---

#### **Install Prompt**

```javascript
// In app.js
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  // Prevent the mini-infobar from appearing on mobile
  e.preventDefault();
  // Stash the event so it can be triggered later
  deferredPrompt = e;
  
  // Show install button
  const installButton = document.getElementById('install-button');
  if (installButton) {
    installButton.style.display = 'block';
    installButton.addEventListener('click', () => {
      // Hide the install button
      installButton.style.display = 'none';
      // Show the prompt
      deferredPrompt.prompt();
      // Wait for the user to respond to the prompt
      deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
          console.log('User accepted the install prompt');
        } else {
          console.log('User dismissed the install prompt');
        }
        deferredPrompt = null;
      });
    });
  }
});

// Add to HTML
<button id="install-button" class="maz-button" style="display: none;">
  نصب برنامه
</button>
```

---

#### **Splash Screen (`splash.html`)**

```html
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MΛZ Medical CRM</title>
  <link rel="stylesheet" href="/assets/css/tokens.css">
  <style>
    body {
      margin: 0;
      padding: 0;
      background: var(--evergreen);
      color: var(--surface);
      font-family: var(--font-persian);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100vh;
      text-align: center;
    }
    .splash-logo {
      width: 120px;
      height: 120px;
      margin-bottom: 1rem;
      animation: pulse 2s infinite;
    }
    .splash-title {
      font-size: 1.5rem;
      font-weight: 700;
    }
    .splash-loader {
      width: 40px;
      height: 40px;
      border: 4px solid rgba(255, 255, 255, 0.3);
      border-top-color: var(--surface);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      margin-top: 1rem;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); opacity: 1; }
      50% { transform: scale(0.95); opacity: 0.8; }
    }
    @keyframes spin {
      to { transform: rotate(360deg); }
    }
  </style>
</head>
<body>
  <img src="/assets/img/logo-white.svg" alt="MΛZ Medical CRM" class="splash-logo">
  <h1 class="splash-title">کلینیک پزشکی دکتر باستانی‌نژاد</h1>
  <div class="splash-loader"></div>
  <script>
    // Redirect to main app after 2 seconds
    setTimeout(() => {
      window.location.href = '/';
    }, 2000);
  </script>
</body>
</html>
```

---

## ⚡ Instant Page Loading

### **1. Preload Critical Pages**

```html
<!-- In index.html head -->
<link rel="preload" href="/appointment.html" as="document">
<link rel="preload" href="/login.html" as="document">
<link rel="preload" href="/about.html" as="document">
<link rel="preload" href="/services.html" as="document">
<link rel="preload" href="/doctors.html" as="document">
<link rel="preload" href="/assets/css/base.css" as="style">
<link rel="preload" href="/assets/css/tokens.css" as="style">
<link rel="preload" href="/assets/js/app.js" as="script">
```

---

### **2. Prefetch Links on Hover**

```javascript
// In chrome.js
document.addEventListener('DOMContentLoaded', () => {
  // Prefetch links on hover (desktop)
  document.querySelectorAll('a[href]').forEach(link => {
    link.addEventListener('mouseenter', () => {
      const href = link.getAttribute('href');
      if (href && !href.startsWith('http') && !href.startsWith('mailto:') && !href.startsWith('tel:')) {
        const prefetchLink = document.createElement('link');
        prefetchLink.rel = 'prefetch';
        prefetchLink.href = href;
        document.head.appendChild(prefetchLink);
      }
    });
  });
  
  // Prefetch critical pages on idle
  if ('requestIdleCallback' in window) {
    window.requestIdleCallback(() => {
      const criticalPages = ['/appointment', '/login', '/services', '/doctors'];
      criticalPages.forEach(page => {
        const prefetchLink = document.createElement('link');
        prefetchLink.rel = 'prefetch';
        prefetchLink.href = page;
        document.head.appendChild(prefetchLink);
      });
    });
  }
});
```

---

### **3. Turbo.js for SPA-Like Navigation**

**Implementation:** Use **Turbo.js** (lightweight, no build step) for SPA-like navigation.

1. **Add Turbo.js:**
   ```html
   <script src="https://unpkg.com/@hotwired/turbo@7.3.0/dist/turbo.es5-umd.js"></script>
   ```

2. **Configure Turbo:**
   ```javascript
   // In app.js
   Turbo.session.drive = true; // Enable for all links
   Turbo.setProgressBarDelay(100); // Show progress bar after 100ms
   
   // Persist elements across navigation (e.g., header, sidebar)
   document.addEventListener('turbo:load', () => {
     // Reinitialize Alpine.js if used
     if (window.Alpine) {
       window.Alpine.start();
     }
     
     // Reapply RTL fixes
     applyRtlFixes();
   });
   
   function applyRtlFixes() {
     // Ensure all inputs are RTL
     document.querySelectorAll('input[type="text"], textarea').forEach(el => {
       if (!el.hasAttribute('dir')) {
         el.dir = 'rtl';
       }
     });
   }
   ```

3. **Add Turbo Progress Bar:**
   ```css
   .turbo-progress-bar {
     position: fixed;
     top: 0;
     left: 0;
     height: 3px;
     background: var(--evergreen);
     z-index: 9999;
     transition: width var(--transition-normal), opacity var(--transition-normal);
   }
   ```

---

### **4. Lazy Loading**

```html
<!-- Images -->
<img 
  src="/assets/img/placeholder.svg" 
  data-src="/assets/img/doctor-1.jpg" 
  alt="دکتر شهین باستانی‌نژاد" 
  loading="lazy" 
  class="lazyload"
>

<!-- Iframes -->
<iframe 
  src="about:blank" 
  data-src="https://example.com/embed" 
  loading="lazy" 
  class="lazyload"
></iframe>
```

```javascript
// In app.js
if ('IntersectionObserver' in window) {
  const lazyImages = document.querySelectorAll('.lazyload');
  const imageObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
        }
        if (img.dataset.srcdoc) {
          img.srcdoc = img.dataset.srcdoc;
        }
        img.classList.remove('lazyload');
        observer.unobserve(img);
      }
    });
  });
  lazyImages.forEach(img => imageObserver.observe(img));
}
```

---

## 🔐 Easy Login System

### **1. OTP Login Flow**

**Steps:**
1. User enters mobile number.
2. System sends OTP via SMS (Kavenegar).
3. User enters OTP.
4. System verifies OTP and issues JWT token.
5. Token is stored in `localStorage` or `sessionStorage`.

**Frontend (`login.html`):**

```html
<div class="maz-login" x-data="loginForm()" x-init="init()">
  <!-- Step 1: Enter Mobile -->
  <div x-show="step === 1" x-transition>
    <div class="maz-login__header">
      <img src="/assets/img/logo.svg" alt="MΛZ Medical CRM" class="maz-login__logo">
      <h1>به حساب کاربری خود وارد شوید</h1>
      <p>یا <a href="/appointment">بدون ورود نوبت رزرو کنید</a></p>
    </div>
    
    <form @submit.prevent="sendOtp" class="maz-form">
      <div class="maz-form__group">
        <label for="mobile">شماره موبایل</label>
        <div class="maz-input-group">
          <span class="maz-input-group__prefix">+98</span>
          <input 
            type="text" 
            id="mobile" 
            x-model="mobile" 
            class="maz-form__input maz-form__input--mobile" 
            placeholder="۹۱۲۳۴۵۶۷۸۹"
            inputmode="numeric"
            required
            @input="formatMobile"
          >
        </div>
        <span class="maz-form__error" x-text="errors.mobile" x-show="errors.mobile"></span>
      </div>
      
      <button type="submit" class="maz-button maz-button--full-width" :disabled="isLoading">
        <span x-show="!isLoading">ارسال کد تایید</span>
        <span x-show="isLoading">
          <span class="maz-loader__spinner maz-loader__spinner--small"></span>
          در حال ارسال...
        </span>
      </button>
    </form>
    
    <div class="maz-login__divider">
      <span>یا</span>
    </div>
    
    <button 
      class="maz-button maz-button--secondary maz-button--full-width" 
      @click="step = 2"
    >
      ورود با رمز عبور (برای پرسنل)
    </button>
  </div>
  
  <!-- Step 2: Enter OTP -->
  <div x-show="step === 2" x-transition>
    <div class="maz-login__header">
      <img src="/assets/img/logo.svg" alt="MΛZ Medical CRM" class="maz-login__logo">
      <h1>کد تایید را وارد کنید</h1>
      <p>کد تایید به شماره <span x-text="displayMobile"></span> ارسال شد.</p>
    </div>
    
    <form @submit.prevent="verifyOtp" class="maz-form">
      <div class="maz-form__group">
        <label for="otp">کد تایید</label>
        <input 
          type="text" 
          id="otp" 
          x-model="otp" 
          class="maz-form__input maz-form__input--otp" 
          placeholder="۱۲۳۴۵"
          inputmode="numeric"
          pattern="[0-9]*"
          autocomplete="one-time-code"
          maxlength="5"
          required
          @input="formatOtp"
        >
        <span class="maz-form__error" x-text="errors.otp" x-show="errors.otp"></span>
      </div>
      
      <div class="maz-form__actions">
        <button type="button" class="maz-button maz-button--secondary" @click="step = 1">
          ویرایش شماره
        </button>
        <button type="submit" class="maz-button" :disabled="isLoading">
          <span x-show="!isLoading">تایید</span>
          <span x-show="isLoading">
            <span class="maz-loader__spinner maz-loader__spinner--small"></span>
            در حال تایید...
          </span>
        </button>
      </div>
    </form>
    
    <div class="maz-otp-resend" x-show="canResend">
      <p>
        کد دریافت نشد؟ 
        <a href="#" @click.prevent="sendOtp" x-show="!isLoading">ارسال مجدد</a>
        <span x-show="isLoading">در حال ارسال...</span>
      </p>
      <p x-show="countdown > 0">
        ارسال مجدد پس از <span x-text="countdown">۳۰</span> ثانیه
      </p>
    </div>
  </div>
  
  <!-- Step 3: Success -->
  <div x-show="step === 3" x-transition>
    <div class="maz-login__success">
      <svg class="maz-login__success-icon"><use xlink:href="#icon-check"></use></svg>
      <h1>ورود موفق!</h1>
      <p>شما با موفقیت وارد شدید.</p>
      <button class="maz-button maz-button--full-width" @click="redirectToDashboard">
        ادامه
      </button>
    </div>
  </div>
  
  <!-- Step 4: Password Login (for Staff) -->
  <div x-show="step === 4" x-transition>
    <div class="maz-login__header">
      <img src="/assets/img/logo.svg" alt="MΛZ Medical CRM" class="maz-login__logo">
      <h1>ورود پرسنل</h1>
    </div>
    
    <form @submit.prevent="loginWithPassword" class="maz-form">
      <div class="maz-form__group">
        <label for="email">ایمیل یا نام کاربری</label>
        <input 
          type="text" 
          id="email" 
          x-model="email" 
          class="maz-form__input" 
          dir="ltr"
          placeholder="example@clinic.com"
          required
        >
        <span class="maz-form__error" x-text="errors.email" x-show="errors.email"></span>
      </div>
      
      <div class="maz-form__group">
        <label for="password">رمز عبور</label>
        <div class="maz-input-group">
          <input 
            type="password" 
            id="password" 
            x-model="password" 
            class="maz-form__input" 
            dir="ltr"
            placeholder="••••••••"
            required
          >
          <button 
            type="button" 
            class="maz-input-group__suffix" 
            @click="togglePasswordVisibility"
          >
            <svg x-show="!showPassword"><use xlink:href="#icon-eye"></use></svg>
            <svg x-show="showPassword"><use xlink:href="#icon-eye-off"></use></svg>
          </button>
        </div>
        <span class="maz-form__error" x-text="errors.password" x-show="errors.password"></span>
      </div>
      
      <div class="maz-form__group">
        <label class="maz-checkbox">
          <input type="checkbox" x-model="rememberMe">
          <span class="maz-checkbox__checkmark"></span>
          <span>مرا به خاطر بسپار</span>
        </label>
      </div>
      
      <button type="submit" class="maz-button maz-button--full-width" :disabled="isLoading">
        <span x-show="!isLoading">ورود</span>
        <span x-show="isLoading">
          <span class="maz-loader__spinner maz-loader__spinner--small"></span>
          در حال ورود...
        </span>
      </button>
      
      <div class="maz-login__footer">
        <a href="/forgot-password">رمز عبور را فراموش کرده‌ام</a>
      </div>
    </form>
  </div>
</div>

<script>
function loginForm() {
  return {
    step: 1,
    mobile: '',
    otp: '',
    email: '',
    password: '',
    showPassword: false,
    rememberMe: false,
    errors: {
      mobile: '',
      otp: '',
      email: '',
      password: '',
    },
    isLoading: false,
    canResend: false,
    countdown: 0,
    
    get displayMobile() {
      const normalized = this.mobile.replace(/\D/g, '');
      return normalized ? `۰${normalized}` : '';
    },
    
    init() {
      // Check if already logged in
      if (Auth.isLoggedIn()) {
        this.redirectToDashboard();
      }
    },
    
    formatMobile() {
      // Remove all non-digit characters
      this.mobile = this.mobile.replace(/\D/g, '');
    },
    
    formatOtp() {
      // Remove all non-digit characters
      this.otp = this.otp.replace(/\D/g, '');
    },
    
    togglePasswordVisibility() {
      this.showPassword = !this.showPassword;
      const passwordInput = document.getElementById('password');
      passwordInput.type = this.showPassword ? 'text' : 'password';
    },
    
    async sendOtp() {
      this.isLoading = true;
      this.errors.mobile = '';
      
      const normalizedMobile = normalizePersianDigits(this.mobile);
      if (!validateIranianMobile(normalizedMobile)) {
        this.errors.mobile = 'شماره موبایل معتبر نیست (فرمت: ۰۹۱۲۳۴۵۶۷۸۹)';
        this.isLoading = false;
        return;
      }
      
      try {
        const response = await fetch('/api/v1/auth/otp/send', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            mobile: normalizedMobile,
            purpose: 'login'
          }),
        });
        
        if (!response.ok) {
          const error = await response.json();
          throw new Error(error.errors?.[0]?.message || 'خطا در ارسال کد');
        }
        
        this.step = 2;
        this.startCountdown();
      } catch (error) {
        this.errors.mobile = error.message;
      } finally {
        this.isLoading = false;
      }
    },
    
    startCountdown() {
      this.canResend = false;
      this.countdown = 30;
      const timer = setInterval(() => {
        this.countdown--;
        if (this.countdown <= 0) {
          clearInterval(timer);
          this.canResend = true;
        }
      }, 1000);
    },
    
    async verifyOtp() {
      this.isLoading = true;
      this.errors.otp = '';
      
      const normalizedOtp = normalizePersianDigits(this.otp);
      if (normalizedOtp.length !== 5) {
        this.errors.otp = 'کد تایید باید ۵ رقم باشد';
        this.isLoading = false;
        return;
      }
      
      try {
        const response = await fetch('/api/v1/auth/otp/verify', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            mobile: normalizePersianDigits(this.mobile),
            otp: normalizedOtp,
          }),
        });
        
        if (!response.ok) {
          const error = await response.json();
          throw new Error(error.errors?.[0]?.message || 'کد تایید نامعتبر است');
        }
        
        const data = await response.json();
        
        // Store token and user
        if (this.rememberMe) {
          localStorage.setItem('token', data.data.token);
          localStorage.setItem('user', JSON.stringify(data.data.user));
        } else {
          sessionStorage.setItem('token', data.data.token);
          sessionStorage.setItem('user', JSON.stringify(data.data.user));
        }
        
        this.step = 3;
      } catch (error) {
        this.errors.otp = error.message;
      } finally {
        this.isLoading = false;
      }
    },
    
    async loginWithPassword() {
      this.isLoading = true;
      this.errors.email = '';
      this.errors.password = '';
      
      try {
        const response = await fetch('/api/v1/auth/password', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
          body: JSON.stringify({
            email: this.email,
            password: this.password,
          }),
        });
        
        if (!response.ok) {
          const error = await response.json();
          const errorMessage = error.errors?.[0]?.message || 'ایمیل یا رمز عبور اشتباه است';
          if (errorMessage.includes('ایمیل') || errorMessage.includes('email')) {
            this.errors.email = errorMessage;
          } else {
            this.errors.password = errorMessage;
          }
          throw new Error(errorMessage);
        }
        
        const data = await response.json();
        
        // Store token and user
        if (this.rememberMe) {
          localStorage.setItem('token', data.data.token);
          localStorage.setItem('user', JSON.stringify(data.data.user));
        } else {
          sessionStorage.setItem('token', data.data.token);
          sessionStorage.setItem('user', JSON.stringify(data.data.user));
        }
        
        this.redirectToDashboard();
      } catch (error) {
        // Errors already set above
      } finally {
        this.isLoading = false;
      }
    },
    
    redirectToDashboard() {
      // Redirect to patient or staff dashboard based on role
      const user = this.rememberMe 
        ? JSON.parse(localStorage.getItem('user') || '{}')
        : JSON.parse(sessionStorage.getItem('user') || '{}');
      
      if (user.role === 'patient') {
        window.location.href = '/patient/dashboard';
      } else if (user.role === 'doctor' || user.role === 'receptionist' || user.role === 'nurse' || user.role === 'super_admin') {
        window.location.href = '/staff/dashboard';
      } else {
        window.location.href = '/';
      }
    },
  };
}
</script>
```

---

### **2. Session Management**

```javascript
// In app.js
class Auth {
  static isLoggedIn() {
    return !!localStorage.getItem('token') || !!sessionStorage.getItem('token');
  }
  
  static getToken() {
    return localStorage.getItem('token') || sessionStorage.getItem('token');
  }
  
  static getUser() {
    const user = localStorage.getItem('user') || sessionStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  }
  
  static logout() {
    localStorage.removeItem('token');
    localStorage.removeItem('user');
    sessionStorage.removeItem('token');
    sessionStorage.removeItem('user');
    window.location.href = '/login';
  }
  
  static async checkAuth() {
    if (!this.isLoggedIn()) {
      window.location.href = '/login';
      return false;
    }
    
    try {
      const response = await fetch('/api/v1/me', {
        headers: {
          'Authorization': `Bearer ${this.getToken()}`,
        },
      });
      
      if (!response.ok) {
        this.logout();
        return false;
      }
      
      const data = await response.json();
      if (this.getToken() === localStorage.getItem('token')) {
        localStorage.setItem('user', JSON.stringify(data.data));
      } else {
        sessionStorage.setItem('user', JSON.stringify(data.data));
      }
      return true;
    } catch (error) {
      this.logout();
      return false;
    }
  }
  
  static async refreshToken() {
    // Implement if using refresh tokens
  }
}

// Check auth on page load (for protected pages)
document.addEventListener('DOMContentLoaded', () => {
  const protectedPages = [
    '/patient/',
    '/staff/',
  ];
  
  const currentPath = window.location.pathname;
  if (protectedPages.some(page => currentPath.startsWith(page))) {
    Auth.checkAuth();
  }
});
```

---

### **3. Protected Routes Middleware**

**Laravel Middleware (`VerifyJWT.php`):**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class VerifyJWT
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            throw new UnauthorizedHttpException('Unauthorized', 'Token not provided');
        }
        
        try {
            $user = \App\Models\User::where('remember_token', $token)->first();
            
            if (!$user) {
                throw new UnauthorizedHttpException('Unauthorized', 'Invalid token');
            }
            
            // Check token expiration if needed
            
            $request->merge(['user' => $user]);
            return $next($request);
        } catch (\Exception $e) {
            throw new UnauthorizedHttpException('Unauthorized', 'Invalid token');
        }
    }
}
```

---

## 🔍 Advanced SEO Optimization

### **1. Meta Tags (Dynamic per Page)**

```html
<!-- In chrome.js, add to <head> -->
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="description" content="" x-text="metaDescription">
<meta name="keywords" content="" x-text="metaKeywords">
<meta name="author" content="MΛZ Medical CRM - دکتر شهین باستانی‌نژاد">
<meta name="robots" content="index, follow">
<meta name="googlebot" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
<meta name="bingbot" content="index, follow">
<meta name="revisit-after" content="1 days">

<!-- OpenGraph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:url" content="" x-text="currentUrl">
<meta property="og:title" content="" x-text="ogTitle">
<meta property="og:description" content="" x-text="ogDescription">
<meta property="og:image" content="" x-text="ogImage">
<meta property="og:site_name" content="MΛZ Medical CRM">
<meta property="og:locale" content="fa_IR">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:url" content="" x-text="currentUrl">
<meta property="twitter:title" content="" x-text="ogTitle">
<meta property="twitter:description" content="" x-text="ogDescription">
<meta property="twitter:image" content="" x-text="ogImage">
<meta property="twitter:image:alt" content="" x-text="ogTitle">

<!-- Canonical URL -->
<link rel="canonical" href="" x-text="currentUrl">

<!-- Alternate Languages -->
<link rel="alternate" hreflang="fa" href="" x-text="currentUrl">
<link rel="alternate" hreflang="en" href="" x-text="currentUrl + '?lang=en'">

<!-- Favicon -->
<link rel="icon" type="image/png" href="/assets/img/icons/favicon-32x32.png" sizes="32x32">
<link rel="icon" type="image/png" href="/assets/img/icons/favicon-16x16.png" sizes="16x16">
<link rel="apple-touch-icon" href="/assets/img/icons/icon-192x192.png">
<meta name="msapplication-TileImage" content="/assets/img/icons/icon-144x144.png">
<meta name="msapplication-TileColor" content="#2F7D32">
```

---

### **2. SEO Data per Page**

```javascript
// In chrome.js
const seoData = {
  '/': {
    title: 'کلینیک پزشکی دکتر شهین باستانی‌نژاد | MΛZ Medical CRM',
    description: 'مراقبت‌های پزشکی تخصصی با کیفیت برای تمام خانواده. رزرو نوبت آنلاین، مشاوره با پزشکان متخصص در زمینه جراحی بینی، پوست، دندانپزشکی و چشم‌پزشکی.',
    keywords: 'پزشک, کلینیک, دکتر شهین باستانی‌نژاد, جراحی بینی, رینوپلاستی, رزرو نوبت آنلاین, مشاوره پزشکی, متخصص پوست, دندانپزشکی, چشم پزشکی, تهران',
    ogTitle: 'کلینیک پزشکی دکتر شهین باستانی‌نژاد | MΛZ Medical CRM',
    ogDescription: 'مراقبت‌های پزشکی تخصصی با کیفیت برای تمام خانواده. رزرو نوبت آنلاین و مشاوره با پزشکان متخصص.',
    ogImage: '/assets/img/og/og-home.jpg',
    structuredData: {
      "@context": "https://schema.org",
      "@type": "MedicalClinic",
      "name": "کلینیک پزشکی دکتر شهین باستانی‌نژاد",
      "alternateName": "MΛZ Medical CRM",
      "description": "مراقبت‌های پزشکی تخصصی با کیفیت برای تمام خانواده",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "تهران، خیابان ولیعصر، پلاک ۱۲۳",
        "addressLocality": "تهران",
        "addressRegion": "تهران",
        "addressCountry": "IR",
        "postalCode": "۱۲۳۴۵۶۷۸۹۰"
      },
      "telephone": "+98-21-12345678",
      "openingHours": "Mo,Tu,We,Th,Fr,Sa 09:00-18:00",
      "hasMap": "https://www.google.com/maps/place/35.7219,51.3880",
      "sameAs": [
        "https://instagram.com/drbastaninejad",
        "https://telegram.me/drbastaninejad",
        "https://twitter.com/drbastaninejad"
      ],
      "image": "/assets/img/logo-512x512.png",
      "priceRange": "$$$",
      "servesCuisine": "Medical Services",
      "hasSpecialty": [
        "Rhinoplasty",
        "Dermatology",
        "Dentistry",
        "Ophthalmology",
        "Orthopedics"
      ],
      "founder": {
        "@type": "Person",
        "name": "دکتر شهین باستانی‌نژاد",
        "description": "جراح و متخصص پلاستیک و زیبایی",
        "sameAs": "https://instagram.com/drbastaninejad"
      }
    }
  },
  '/appointment': {
    title: 'رزرو نوبت آنلاین | کلینیک دکتر باستانی‌نژاد',
    description: 'نوبت‌دهی آنلاین سریع و آسان در کلینیک دکتر شهین باستانی‌نژاد. بدون انتظار، بدون اتلاف وقت. رزرو نوبت برای جراحی بینی، پوست، دندان و چشم.',
    keywords: 'رزرو نوبت, نوبت دهی آنلاین, دکتر شهین باستانی‌نژاد, کلینیک پزشکی, جراحی بینی, مشاوره پزشکی, تهران',
    ogTitle: 'رزرو نوبت آنلاین | MΛZ Medical CRM',
    ogDescription: 'نوبت‌دهی آنلاین سریع و آسان در کلینیک دکتر شهین باستانی‌نژاد.',
    ogImage: '/assets/img/og/og-appointment.jpg',
    structuredData: {
      "@context": "https://schema.org",
      "@type": "MedicalProcedure",
      "name": "رزرو نوبت آنلاین",
      "description": "نوبت‌دهی آنلاین برای خدمات پزشکی",
      "provider": {
        "@type": "MedicalClinic",
        "name": "کلینیک پزشکی دکتر شهین باستانی‌نژاد"
      },
      "potentialAction": {
        "@type": "OrderAction",
        "target": {
          "@type": "EntryPoint",
          "urlTemplate": "https://drbastaninejad.com/appointment",
          "inLanguage": "fa"
        },
        "expectsAcceptanceOf": {
          "@type": "Offer",
          "category": "free"
        }
      }
    }
  },
  '/services/rhinoplasty': {
    title: 'جراحی بینی (رینوپلاستی) | دکتر شهین باستانی‌نژاد',
    description: 'جراحی بینی (رینوپلاستی) توسط دکتر شهین باستانی‌نژاد، متخصص جراحی پلاستیک و زیبایی. بهبود ظاهری و عملکردی بینی.',
    keywords: 'جراحی بینی, رینوپلاستی, دکتر شهین باستانی‌نژاد, جراحی پلاستیک, جراحی زیبایی بینی, بهبود ظاهری بینی, جراحی عملکردی بینی',
    ogTitle: 'جراحی بینی (رینوپلاستی) | MΛZ Medical CRM',
    ogDescription: 'جراحی بینی توسط دکتر شهین باستانی‌نژاد، متخصص جراحی پلاستیک و زیبایی.',
    ogImage: '/assets/img/og/og-rhinoplasty.jpg',
    structuredData: {
      "@context": "https://schema.org",
      "@type": "MedicalProcedure",
      "name": "جراحی بینی (رینوپلاستی)",
      "alternateName": ["Rhinoplasty", "Nose Surgery"],
      "description": "جراحی بینی برای بهبود ظاهری یا عملکردی بینی",
      "provider": {
        "@type": "MedicalClinic",
        "name": "کلینیک پزشکی دکتر شهین باستانی‌نژاد"
      },
      "prepTime": "PT30M",
      "performTime": "PT2H",
      "recoveryTime": "P4W",
      "bodyLocation": "Nose",
      "howPerformed": "Surgical",
      "category": "Plastic Surgery"
    }
  },
  '/doctors/dr-shahin-bastaninejad': {
    title: 'دکتر شهین باستانی‌نژاد | جراح و متخصص پلاستیک و زیبایی',
    description: 'دکتر شهین باستانی‌نژاد، جراح و متخصص پلاستیک و زیبایی با سال‌ها تجربه در زمینه جراحی بینی، ابرو، پلک و دیگر جراحی‌های زیبایی.',
    keywords: 'دکتر شهین باستانی‌نژاد, جراح پلاستیک, متخصص زیبایی, جراحی بینی, جراحی پلک, جراحی ابرو, پزشک خوب, تهران',
    ogTitle: 'دکتر شهین باستانی‌نژاد | MΛZ Medical CRM',
    ogDescription: 'دکتر شهین باستانی‌نژاد، جراح و متخصص پلاستیک و زیبایی.',
    ogImage: '/assets/img/og/og-doctor-shahin.jpg',
    structuredData: {
      "@context": "https://schema.org",
      "@type": "Physician",
      "name": "دکتر شهین باستانی‌نژاد",
      "description": "جراح و متخصص پلاستیک و زیبایی",
      "specialty": ["Plastic Surgery", "Cosmetic Surgery"],
      "affiliation": {
        "@type": "MedicalClinic",
        "name": "کلینیک پزشکی دکتر شهین باستانی‌نژاد"
      },
      "telephone": "+98-21-12345678",
      "email": "info@drbastaninejad.com",
      "sameAs": [
        "https://instagram.com/drbastaninejad",
        "https://linkedin.com/in/drbastaninejad"
      ],
      "hasCredential": [
        {
          "@type": "EducationalOccupationalCredential",
          "name": "بورد تخصصی جراحی پلاستیک",
          "educationalLevel": "Specialty"
        }
      ]
    }
  }
};

// Set SEO data on page load
document.addEventListener('DOMContentLoaded', () => {
  const path = window.location.pathname;
  const data = seoData[path] || seoData['/'];
  
  // Set meta tags
  document.querySelector('meta[name="description"]').setAttribute('content', data.description);
  document.querySelector('meta[name="keywords"]').setAttribute('content', data.keywords);
  
  // OpenGraph
  document.querySelector('meta[property="og:title"]').setAttribute('content', data.ogTitle);
  document.querySelector('meta[property="og:description"]').setAttribute('content', data.ogDescription);
  document.querySelector('meta[property="og:image"]').setAttribute('content', data.ogImage);
  
  // Twitter
  document.querySelector('meta[property="twitter:title"]').setAttribute('content', data.ogTitle);
  document.querySelector('meta[property="twitter:description"]').setAttribute('content', data.ogDescription);
  document.querySelector('meta[property="twitter:image"]').setAttribute('content', data.ogImage);
  
  // Canonical
  document.querySelector('link[rel="canonical"]').setAttribute('href', window.location.href);
  
  // Page title
  document.title = data.title;
  
  // Structured data
  const script = document.createElement('script');
  script.type = 'application/ld+json';
  script.text = JSON.stringify(data.structuredData);
  document.head.appendChild(script);
});
```

---

### **3. Structured Data (Schema.org)**

**Examples:**
- **MedicalClinic** (for homepage)
- **MedicalProcedure** (for services)
- **Physician** (for doctors)
- **FAQPage** (for FAQ)
- **BreadcrumbList** (for breadcrumbs)

---

### **4. Sitemap Generation**

**`sitemap.xml` (Dynamic):**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <!-- Static Pages -->
  <url>
    <loc>https://drbastaninejad.com/</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://drbastaninejad.com/appointment</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://drbastaninejad.com/about</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://drbastaninejad.com/contact</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://drbastaninejad.com/faq</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.7</priority>
  </url>
  
  <!-- Services -->
  <url>
    <loc>https://drbastaninejad.com/services/rhinoplasty</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://drbastaninejad.com/services/dermatology</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  
  <!-- Doctors -->
  <url>
    <loc>https://drbastaninejad.com/doctors/dr-shahin-bastaninejad</loc>
    <lastmod>2026-07-26</lastmod>
    <changefreq>monthly</changefreq>
    <priority>0.8</priority>
  </url>
  
  <!-- Blog Posts (dynamic) -->
  <url>
    <loc>https://drbastaninejad.com/blog/post-1</loc>
    <lastmod>2026-07-20</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
  
  <!-- News -->
  <url>
    <loc>https://drbastaninejad.com/news/news-1</loc>
    <lastmod>2026-07-25</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.7</priority>
  </url>
</urlset>
```

**Laravel Sitemap Endpoint:**

```php
// routes/api.php
Route::get('/sitemap.xml', [SitemapController::class, 'index']);

// SitemapController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Models\Doctor;
use App\Models\News;

class SitemapController extends Controller
{
    public function index()
    {
        $urls = [];
        
        // Static pages
        $staticPages = [
            ['loc' => '/', 'lastmod' => now(), 'changefreq' => 'daily', 'priority' => 1.0],
            ['loc' => '/appointment', 'lastmod' => now(), 'changefreq' => 'weekly', 'priority' => 0.9],
            ['loc' => '/about', 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => 0.8],
            ['loc' => '/contact', 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => 0.8],
            ['loc' => '/faq', 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => 0.7],
            ['loc' => '/services', 'lastmod' => now(), 'changefreq' => 'weekly', 'priority' => 0.8],
            ['loc' => '/doctors', 'lastmod' => now(), 'changefreq' => 'monthly', 'priority' => 0.8],
            ['loc' => '/blog', 'lastmod' => now(), 'changefreq' => 'weekly', 'priority' => 0.7],
        ];
        
        // Services
        $services = Service::all();
        foreach ($services as $service) {
            $urls[] = [
                'loc' => "/services/{$service->slug}",
                'lastmod' => $service->updated_at,
                'changefreq' => 'weekly',
                'priority' => 0.9,
            ];
        }
        
        // Doctors
        $doctors = Doctor::all();
        foreach ($doctors as $doctor) {
            $urls[] = [
                'loc' => "/doctors/{$doctor->slug}",
                'lastmod' => $doctor->updated_at,
                'changefreq' => 'monthly',
                'priority' => 0.8,
            ];
        }
        
        // News
        $newsItems = News::all();
        foreach ($newsItems as $news) {
            $urls[] = [
                'loc' => "/news/{$news->slug}",
                'lastmod' => $news->updated_at,
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ];
        }
        
        // Blog posts (from WordPress API)
        $wordpressPosts = $this->getWordPressPosts();
        foreach ($wordpressPosts as $post) {
            $urls[] = [
                'loc' => "/blog/{$post['slug']}",
                'lastmod' => $post['modified'],
                'changefreq' => 'weekly',
                'priority' => 0.7,
            ];
        }
        
        $urls = array_merge($staticPages, $urls);
        
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            $xml .= '<url>';
            $xml .= "<loc>https://drbastaninejad.com{$url['loc']}</loc>";
            $xml .= "<lastmod>{$url['lastmod']->format('Y-m-d')}</lastmod>";
            $xml .= "<changefreq>{$url['changefreq']}</changefreq>";
            $xml .= "<priority>{$url['priority']}</priority>";
            $xml .= '</url>';
        }
        $xml .= '</urlset>';
        
        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
    
    private function getWordPressPosts()
    {
        // Fetch from WordPress API
        $response = file_get_contents('https://drbastaninejad.com/blog/wp-json/wp/v2/posts?per_page=100');
        $posts = json_decode($response, true);
        
        $formattedPosts = [];
        foreach ($posts as $post) {
            $formattedPosts[] = [
                'slug' => $post['slug'],
                'modified' => $post['modified'],
            ];
        }
        
        return $formattedPosts;
    }
}
```

---

### **5. robots.txt**

```
User-agent: *
Disallow: /staff/
Disallow: /patient/
Disallow: /login
Disallow: /api/
Disallow: /assets/
Allow: /

Sitemap: https://drbastaninejad.com/sitemap.xml

# Googlebot
User-agent: Googlebot
Disallow: /staff/
Disallow: /patient/
Disallow: /login
Disallow: /api/
Allow: /
Crawl-delay: 2

# Bingbot
User-agent: Bingbot
Disallow: /staff/
Disallow: /patient/
Disallow: /login
Disallow: /api/
Allow: /
Crawl-delay: 2

# Parsijoo (Persian search engine)
User-agent: ParsijooBot
Disallow: /staff/
Disallow: /patient/
Disallow: /login
Disallow: /api/
Allow: /
Crawl-delay: 2

# Yandex
User-agent: YandexBot
Disallow: /staff/
Disallow: /patient/
Disallow: /login
Disallow: /api/
Allow: /
Crawl-delay: 2
```

---

## 🔗 Dashboard Integration

### **1. Connection Points**

| CRM Feature | Website Integration | Implementation |
|-------------|---------------------|----------------|
| **Appointment Booking** | Public intake form | Embed `/appointment.html` or API form |
| **Patient Portal** | Login link | `/login` → `/patient/dashboard` |
| **Staff Dashboard** | Internal access | `/staff/` (protected) |
| **Clinic Info** | Dynamic display | Laravel API (`/api/v1/clinic`) |
| **Doctors List** | Dynamic display | Laravel API (`/api/v1/doctors`) |
| **Services List** | Dynamic display | Laravel API (`/api/v1/services`) |
| **News/Updates** | Blog + static | WordPress + Laravel API |

---

### **2. Authentication Flow**

```
User
  │
  ▼
Login Page (/login)
  │
  ├── OTP Login (Patients) ──────────────► Verify OTP ─────────► Patient Dashboard
  │                                         (POST /api/v1/auth/otp/verify)
  │
  └── Password Login (Staff) ────────────► Verify Credentials ───► Staff Dashboard
                                            (POST /api/v1/auth/password)
  │
  ▼
JWT Token (Stored in localStorage/sessionStorage)
  │
  ▼
Authenticated Requests
  │
  ├── Patient: /patient/* ──────────────► Laravel API (with Bearer token)
  │
  └── Staff: /staff/* ──────────────────► Laravel API (with Bearer token)
```

---

### **3. Data Flow**

```
Website (Static HTML)
  │
  ├── Public Data (No Auth)
  │   ├── Clinic Info ───────────────────► GET /api/v1/clinic
  │   ├── Doctors List ─────────────────► GET /api/v1/doctors
  │   ├── Services List ────────────────► GET /api/v1/services
  │   └── News/Updates ──────────────────► GET /api/v1/news
  │
  ├── User-Specific Data (Auth Required)
  │   ├── Patient Data ──────────────────► GET /api/v1/me
  │   ├── Appointments ──────────────────► GET /api/v1/appointments
  │   ├── EMR Records ───────────────────► GET /api/v1/emr/records
  │   └── Invoices ──────────────────────► GET /api/v1/invoices
  │
  └── Actions (Auth Required)
      ├── Book Appointment ───────────────► POST /api/v1/appointments
      ├── Update Profile ─────────────────► PATCH /api/v1/patients/{id}
      ├── Upload Media ───────────────────► POST /api/v1/media/presign
      └── AI Copilot ─────────────────────► POST /api/v1/ai/draft
```

---

### **4. API Endpoints Summary**

#### **Public Endpoints (No Auth)**

| Method | Endpoint | Purpose | Consumer |
|--------|----------|---------|----------|
| GET | `/api/v1/clinic` | Clinic info | Website, WordPress |
| GET | `/api/v1/doctors` | Doctors list | Website, WordPress |
| GET | `/api/v1/doctors/{slug}` | Single doctor | Website |
| GET | `/api/v1/services` | Services list | Website, WordPress |
| GET | `/api/v1/services/{slug}` | Single service | Website |
| GET | `/api/v1/news` | News/updates | Website, WordPress |
| GET | `/api/v1/news/{slug}` | Single news item | Website |
| POST | `/api/v1/intakes` | Public intake | Website |
| POST | `/api/v1/auth/otp/send` | Send OTP | Website |
| POST | `/api/v1/auth/otp/verify` | Verify OTP | Website |

#### **Authenticated Endpoints**

| Method | Endpoint | Purpose | Consumer |
|--------|----------|---------|----------|
| GET | `/api/v1/me` | Current user | Website, Mobile |
| GET | `/api/v1/patients/{id}` | Patient details | Website, Mobile |
| GET | `/api/v1/appointments` | Appointments list | Website, Mobile |
| POST | `/api/v1/appointments` | Create appointment | Website, Mobile |
| PATCH | `/api/v1/appointments/{id}` | Update appointment | Website, Mobile |
| GET | `/api/v1/emr/records` | EMR records | Website, Mobile |
| POST | `/api/v1/emr/records` | Create EMR record | Website, Mobile |
| GET | `/api/v1/invoices` | Invoices list | Website, Mobile |
| POST | `/api/v1/payments/zarinpal` | Zarinpal payment | Website, Mobile |
| POST | `/api/v1/ai/draft` | AI Copilot draft | Website, Mobile |
| POST | `/api/v1/media/presign` | Presigned URL | Website, Mobile |
| GET | `/api/v1/patient/documents` | Patient documents | Website, Mobile |
| POST | `/api/v1/messages` | Send message | Website, Mobile |
| GET | `/api/v1/messages` | Get messages | Website, Mobile |

---

## 📁 File Structure (Final)

```
drbastaninejad.com/
├── public_html/
│   ├── index.html                  # Homepage
│   ├── appointment.html            # Public intake form
│   ├── login.html                  # Login page
│   ├── about.html                  # About page
│   ├── contact.html                # Contact page
│   ├── faq.html                    # FAQ page
│   ├── services.html               # Services list
│   ├── service.html                # Single service template
│   ├── doctors.html                # Doctors list
│   ├── doctor.html                 # Single doctor template
│   ├── blog.html                   # Blog list (or WordPress)
│   ├── post.html                   # Single post template
│   ├── patient/                    # Patient portal
│   │   ├── dashboard.html         # Patient dashboard
│   │   ├── appointments.html       # Appointments list
│   │   ├── appointment.html        # Single appointment
│   │   ├── documents.html          # Documents list
│   │   ├── profile.html            # Profile settings
│   │   └── messages.html           # Secure messaging
│   ├── staff/                      # Staff dashboard (CRM)
│   │   ├── dashboard.html         # Overview
│   │   ├── patients.html           # Patient management
│   │   ├── patient.html            # Single patient view
│   │   ├── calendar.html           # Appointment scheduling
│   │   ├── emr.html                # EMR records
│   │   ├── emr-record.html         # Single EMR record
│   │   ├── billing.html            # Invoices & payments
│   │   ├── invoice.html            # Single invoice
│   │   ├── reports.html            # Analytics
│   │   └── settings.html           # Clinic settings
│   ├── blog/                       # WordPress (optional)
│   │   └── (WordPress files)       # If keeping WordPress
│   ├── assets/
│   │   ├── css/
│   │   │   ├── tokens.css          # Design tokens (SSOT)
│   │   │   ├── base.css           # Base styles
│   │   │   ├── rtl.css            # RTL fixes
│   │   │   ├── components.css     # Reusable components
│   │   │   ├── layout.css         # Layout styles
│   │   │   ├── pages/             # Page-specific CSS
│   │   │   │   ├── home.css
│   │   │   │   ├── appointment.css
│   │   │   │   └── ...
│   │   │   └── print.css          # Print styles
│   │   ├── js/
│   │   │   ├── app.js             # Core functionality
│   │   │   ├── chrome.js          # Shared chrome (header, footer, nav)
│   │   │   ├── auth.js            # Authentication logic
│   │   │   ├── api.js             # API client
│   │   │   ├── pages/             # Page-specific JS
│   │   │   │   ├── home.js
│   │   │   │   ├── appointment.js
│   │   │   │   └── ...
│   │   │   └── plugins/           # Third-party plugins
│   │   │       ├── turbo.js        # Turbo.js (local copy)
│   │   │       └── alpine.js       # Alpine.js (local copy)
│   │   ├── img/
│   │   │   ├── logo.svg           # Main logo
│   │   │   ├── logo-white.svg     # White logo
│   │   │   ├── logo-icon.svg      # Icon-only logo
│   │   │   ├── icons/             # SVG icons
│   │   │   │   ├── icon-home.svg
│   │   │   │   ├── icon-calendar.svg
│   │   │   │   ├── icon-user.svg
│   │   │   │   ├── icon-login.svg
│   │   │   │   ├── icon-check.svg
│   │   │   │   ├── icon-eye.svg
│   │   │   │   └── ...
│   │   │   ├── icons/             # PWA icons
│   │   │   │   ├── icon-72x72.png
│   │   │   │   ├── icon-96x96.png
│   │   │   │   ├── icon-128x128.png
│   │   │   │   ├── icon-144x144.png
│   │   │   │   ├── icon-152x152.png
│   │   │   │   ├── icon-192x192.png
│   │   │   │   ├── icon-384x384.png
│   │   │   │   └── icon-512x512.png
│   │   │   ├── screenshots/       # App screenshots
│   │   │   │   ├── screenshot-desktop.png
│   │   │   │   └── screenshot-mobile.png
│   │   │   ├── og/                # OpenGraph images
│   │   │   │   ├── og-home.jpg
│   │   │   │   ├── og-appointment.jpg
│   │   │   │   ├── og-rhinoplasty.jpg
│   │   │   │   └── ...
│   │   │   └── placeholder.svg     # Placeholder image
│   │   └── fonts/
│   │       └── vazirmatn/          # Self-hosted Persian font
│   │           ├── Vazirmatn-Regular.woff2
│   │           ├── Vazirmatn-Medium.woff2
│   │           ├── Vazirmatn-SemiBold.woff2
│   │           └── Vazirmatn-Bold.woff2
│   ├── app_private/               # Laravel backend
│   │   ├── app/
│   │   │   ├── Http/
│   │   │   │   ├── Controllers/
│   │   │   │   │   ├── Api/
│   │   │   │   │   │   ├── V1/
│   │   │   │   │   │   │   ├── ClinicController.php
│   │   │   │   │   │   │   ├── DoctorController.php
│   │   │   │   │   │   │   ├── ServiceController.php
│   │   │   │   │   │   │   ├── NewsController.php
│   │   │   │   │   │   │   ├── AuthController.php
│   │   │   │   │   │   │   ├── PatientController.php
│   │   │   │   │   │   │   ├── AppointmentController.php
│   │   │   │   │   │   │   ├── EmrController.php
│   │   │   │   │   │   │   ├── BillingController.php
│   │   │   │   │   │   │   ├── MediaController.php
│   │   │   │   │   │   │   └── AiController.php
│   │   │   │   │   │   └── SitemapController.php
│   │   │   │   │   └── Middleware/
│   │   │   │   │       └── VerifyJWT.php
│   │   │   ├── Models/
│   │   │   │   ├── Clinic.php
│   │   │   │   ├── Doctor.php
│   │   │   │   ├── Service.php
│   │   │   │   ├── News.php
│   │   │   │   ├── User.php
│   │   │   │   ├── Patient.php
│   │   │   │   ├── Appointment.php
│   │   │   │   ├── EmrRecord.php
│   │   │   │   ├── Invoice.php
│   │   │   │   └── Media.php
│   │   │   └── Modules/
│   │   │       ├── AI/
│   │   │       ├── Billing/
│   │   │       ├── Media/
│   │   │       └── SMS/
│   │   ├── config/
│   │   ├── database/
│   │   │   ├── migrations/
│   │   │   └── seeders/
│   │   ├── public/               # Laravel entry point
│   │   │   └── index.php
│   │   ├── routes/
│   │   │   ├── api.php
│   │   │   └── web.php
│   │   └── ...
│   ├── .htaccess                  # Apache config
│   ├── .env                       # Environment variables
│   ├── manifest.json              # PWA manifest
│   ├── sw.js                      # Service worker
│   ├── sitemap.xml                # SEO sitemap (or generated via API)
│   └── robots.txt                 # Crawler instructions
│
└── app.drbastaninejad.com/       # Development (symlink to public_html)
    └── Frontend/                  # Source files
```

---

## 📅 Implementation Timeline

| Week | Task | Owner | Status |
|------|------|-------|--------|
| **1** | Finalize page hierarchy and wireframes | Maziyar | ⬜ |
| **1** | Define design system (tokens, typography, components) | Maziyar | ⬜ |
| **1** | Create base HTML/CSS/JS templates | Maziyar | ⬜ |
| **1** | Set up development environment | Maziyar | ⬜ |
| **2** | Implement PWA (manifest, service worker, splash screen) | Maziyar | ⬜ |
| **2** | Add instant page loading (Turbo.js, preloading) | Maziyar | ⬜ |
| **2** | Build easy login system (OTP + password) | Maziyar | ⬜ |
| **2** | Add SEO optimization (meta tags, structured data) | Maziyar | ⬜ |
| **3** | Create all page templates (home, services, about, etc.) | Maziyar | ⬜ |
| **3** | Implement mobile-first design (responsive, touch-friendly) | Maziyar | ⬜ |
| **3** | Connect to Laravel API (public endpoints) | Maziyar | ⬜ |
| **3** | Add dashboard integration (patient/staff portals) | Maziyar | ⬜ |
| **4** | Migrate WordPress content (pages, posts, media) | Maziyar | ⬜ |
| **4** | Set up WordPress in `/blog/` (optional) | Maziyar | ⬜ |
| **4** | Test on all devices (mobile, tablet, desktop) | Maziyar | ⬜ |
| **5** | Performance testing (Lighthouse, PageSpeed) | Maziyar | ⬜ |
| **5** | SEO testing (Rich Results, Mobile-Friendly) | Maziyar | ⬜ |
| **5** | Security testing (penetration, XSS) | Maziyar | ⬜ |
| **6** | Set up 301 redirects from old WordPress URLs | Maziyar | ⬜ |
| **6** | Submit to search engines (Google, Bing, Parsijoo) | Maziyar | ⬜ |
| **6** | Final testing & bug fixes | Maziyar + Staff | ⬜ |
| **7** | Deploy to staging for review | Maziyar | ⬜ |
| **7** | Train staff on new system | Maziyar | ⬜ |
| **8** | Deploy to production | Maziyar | ⬜ |
| **8** | Monitor traffic, errors, performance | Maziyar | ⬜ |

---

## 🎯 Success Metrics

| Metric | Target | Measurement Tool |
|--------|--------|------------------|
| **Mobile Lighthouse Score** | ≥ 95 | Chrome DevTools |
| **Desktop Lighthouse Score** | ≥ 95 | Chrome DevTools |
| **First Contentful Paint** | < 1.0s | PageSpeed Insights |
| **Largest Contentful Paint** | < 2.0s | PageSpeed Insights |
| **Cumulative Layout Shift** | < 0.1 | PageSpeed Insights |
| **Time to Interactive** | < 2.0s | PageSpeed Insights |
| **Bounce Rate** | < 40% | Google Analytics |
| **Conversion Rate (Appointment Booking)** | > 15% | Google Analytics |
| **SEO Traffic** | +30% in 3 months | Google Search Console |
| **Mobile Traffic** | > 70% | Google Analytics |
| **PWA Install Rate** | > 10% | Custom tracking |
| **Offline Usage** | > 5% of sessions | Custom tracking |
| **Login Success Rate** | > 95% | Laravel API logs |
| **Page Load Time (Repeated Visits)** | < 0.5s | WebPageTest |

---

## 🚀 Next Steps

### **Immediate (This Week)**
1. **Finalize page hierarchy** and confirm with Dr. Bastaninejad.
2. **Design wireframes** for key pages:
   - Homepage
   - Services list
   - Single service (Rhinoplasty)
   - Appointment form
   - Patient dashboard
   - Staff dashboard
3. **Set up development environment** for new frontend.
4. **Create base templates** (HTML, CSS, JS).

### **Short-Term (Next 2 Weeks)**
1. **Implement PWA** (manifest, service worker, splash screen).
2. **Add instant page loading** (Turbo.js, preloading).
3. **Build login system** (OTP + password).
4. **Add SEO optimization** (meta tags, structured data).
5. **Connect to Laravel API** (public endpoints).
6. **Begin WordPress content migration**.

### **Long-Term (Next Month)**
1. **Complete all page templates**.
2. **Test on all devices** (mobile, tablet, desktop).
3. **Deploy to staging** for review by Dr. Bastaninejad.
4. **Train staff** on new system.
5. **Launch to production**.

---

## 💬 Questions for Dr. Bastaninejad

1. **WordPress Migration:** Should we **keep WordPress for the blog** or **migrate everything to static/Laravel**?
   - **Recommendation:** Keep WordPress for the blog (easier content management for non-technical users).

2. **Domain Structure:** Should the **patient portal** and **staff dashboard** be on the same domain (`drbastaninejad.com/patient/`) or a subdomain (`patient.drbastaninejad.com`)?
   - **Recommendation:** Same domain (better SEO, simpler cookie management, no CORS issues).

3. **Branding:** Should we use **"MΛZ Medical CRM"** or **"کلینیک دکتر شهین باستانی‌نژاد"** as the primary brand name?
   - **Recommendation:** Use both (e.g., **"MΛZ | کلینیک دکتر شهین باستانی‌نژاد"**).

4. **Content Priority:** Which **pages/services** should be highlighted on the homepage?
   - **Recommendation:** Rhinoplasty (primary specialty), then other services (Dermatology, Dentistry, etc.).

5. **Contact Methods:** Which **contact methods** should be prominently displayed?
   - **Recommendation:**
     - Mobile (with click-to-call)
     - WhatsApp (with deep link)
     - Telegram (with deep link)
     - Email
     - Address (with map link)

6. **Social Media:** Which **social media links** should be included?
   - **Recommendation:** Instagram, Telegram, WhatsApp, LinkedIn (if applicable).

7. **Testimonials:** Should we include **patient testimonials** on the homepage?
   - **Recommendation:** Yes, but ensure **HIPAA/compliance** (no real patient names without consent).

8. **Pricing:** Should we display **pricing information** for services?
   - **Recommendation:** **No** (Iranian market prefers private consultation for pricing).

9. **Online Payments:** Should we allow **online payments** for invoices?
   - **Recommendation:** Yes (via Zarinpal, already integrated in CRM).

10. **Chat Support:** Should we add **live chat** (e.g., Telegram, WhatsApp)?
    - **Recommendation:** Yes (Telegram/WhatsApp deep links).

---

## 📌 Checklist

### **Design**
- [ ] Finalize page hierarchy
- [ ] Create wireframes for all pages
- [ ] Define design system (colors, typography, spacing)
- [ ] Create component library (buttons, cards, forms, modals, etc.)
- [ ] Design mobile, tablet, and desktop layouts
- [ ] Design PWA splash screen and icons
- [ ] Review with Dr. Bastaninejad

### **Development**
- [ ] Set up project structure
- [ ] Create base HTML templates
- [ ] Implement CSS (tokens, components, layouts, RTL)
- [ ] Add JavaScript (app.js, chrome.js, auth.js, api.js)
- [ ] Implement PWA (manifest, service worker, install prompt)
- [ ] Add instant page loading (Turbo.js, preloading, lazy loading)
- [ ] Build login system (OTP + password + remember me)
- [ ] Add SEO optimization (meta tags, structured data, sitemap)
- [ ] Connect to Laravel API (public + authenticated endpoints)
- [ ] Create all page templates
- [ ] Implement mobile-first design (responsive, touch-friendly)
- [ ] Add dashboard integration (patient/staff portals)
- [ ] Add error handling and loading states
- [ ] Implement offline support (service worker caching)

### **Content Migration**
- [ ] Export WordPress content (pages, posts, media)
- [ ] Migrate static pages (home, about, contact, etc.)
- [ ] Migrate dynamic content (services, doctors)
- [ ] Set up WordPress in `/blog/` (or migrate blog to Laravel)
- [ ] Migrate media library (images, PDFs)
- [ ] Set up 301 redirects from old URLs
- [ ] Update internal links

### **Testing**
- [ ] Test on all devices (mobile, tablet, desktop)
- [ ] Test all user flows (appointment, login, etc.)
- [ ] Test PWA features (offline, install, push notifications)
- [ ] Performance testing (Lighthouse, PageSpeed, WebPageTest)
- [ ] SEO testing (Rich Results, Mobile-Friendly, Structured Data)
- [ ] Security testing (penetration, XSS, CSRF)
- [ ] Cross-browser testing (Chrome, Firefox, Safari, Edge)
- [ ] RTL testing (Persian text, numbers, forms)

### **Deployment**
- [ ] Deploy to staging for review
- [ ] Fix bugs from testing
- [ ] Submit to search engines (Google, Bing, Parsijoo)
- [ ] Set up analytics (Google Analytics, custom tracking)
- [ ] Deploy to production
- [ ] Monitor traffic, errors, performance
- [ ] Train staff on new system

---

## 📚 Additional Resources

### **Design Tools**
- **Figma/Adobe XD:** For wireframing and high-fidelity designs.
- **Whimsical:** For flowcharts and sitemaps.
- **Coolors:** For color palette generation.

### **Development Tools**
- **VS Code:** Code editor with extensions for HTML/CSS/JS.
- **Live Server:** Local development server.
- **Browser DevTools:** For debugging and testing.
- **Lighthouse:** For performance audits.
- **PageSpeed Insights:** For real-world performance data.

### **Testing Tools**
- **BrowserStack:** Cross-browser testing.
- **LambdaTest:** Cross-device testing.
- **Google Search Console:** SEO monitoring.
- **Screaming Frog:** SEO auditing.

### **Hosting & Deployment**
- **cPanel:** For file management and hosting.
- **FileZilla:** For FTP uploads.
- **GitHub Actions:** For CI/CD (optional).

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_