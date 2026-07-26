# Medical CRM - UX, Mobile Apps, WordPress Redesign & Sync Strategy

**Version:** 1.0 · **Author:** MAZ//ID · **Date:** 26 July 2026

> **Executive Summary**
> This document outlines a **comprehensive plan** to improve the **UX of the Medical CRM**, develop **mobile apps (APK & iOS)**, **redesign the WordPress website**, and ensure **database synchronization** across all platforms. The goal is to create a **unified, mobile-friendly, and high-performance** ecosystem for Dr. Shahin Bastaninejad’s clinic.

---

## 🎯 Track 1: UX Improvements for Medical CRM
**Goal:** Make the CRM **faster, more intuitive, and Persian-first** while keeping the current stack (Laravel backend + static frontend).

---

### 🔥 High-Impact UX Ideas (Prioritized)

| Priority | Idea | Impact | Effort | Status |
|----------|------|--------|--------|--------|
| **P0** | Persian digit auto-conversion (fa/ar → en) + display as Persian | ⭐⭐⭐⭐⭐ | Low | **Do Now** |
| **P0** | RTL-aware form validation (e.g., mobile starts with `09`) | ⭐⭐⭐⭐⭐ | Low | **Do Now** |
| **P0** | Jalali (Shamsi) date picker with visual calendar | ⭐⭐⭐⭐⭐ | Medium | **Do Now** |
| **P0** | Offline-first intake form (save to `localStorage`, sync later) | ⭐⭐⭐⭐ | Medium | **Do Now** |
| **P1** | One-tap OTP auto-fill (Android/iOS) | ⭐⭐⭐⭐ | Medium | **Do Next** |
| **P1** | Smart search for patients (fuzzy Persian name matching) | ⭐⭐⭐⭐ | Medium | **Do Next** |
| **P1** | Voice-to-text for EMR notes (Persian) | ⭐⭐⭐ | High | **Later** |
| **P1** | Drag-and-drop calendar (Alpine.js) | ⭐⭐⭐ | Medium | **Later** |
| **P2** | High-contrast mode for low-vision users | ⭐⭐⭐ | Low | **Later** |
| **P2** | Touch-friendly buttons (48x48px min) | ⭐⭐⭐ | Low | **Do Now** |

---

### 🛠️ Implementation: UX Improvements

#### 1. Persian Digit Auto-Conversion
**Problem:** Users input Persian/Arabic digits (۰۱۲۳۴۵۶۷۸۹), but backend expects English (0123456789).
**Solution:** Auto-convert on input, display as Persian.

**Code (JavaScript - add to `app.js`):**
```javascript
// Auto-convert Persian/Arabic digits to English
function normalizePersianDigits(input) {
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
  let result = input;
  for (let i = 0; i < 10; i++) {
    result = result.replace(new RegExp(persianDigits[i], 'g'), i.toString());
    result = result.replace(new RegExp(arabicDigits[i], 'g'), i.toString());
  }
  return result;
}

// Apply to all input fields
document.querySelectorAll('input[type="text"], input[type="number"], textarea').forEach(el => {
  el.addEventListener('input', (e) => {
    e.target.value = normalizePersianDigits(e.target.value);
  });
});

// Display numbers as Persian (for output)
function toPersianDigits(num) {
  const persianDigits = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
  return num.toString().replace(/\d/g, d => persianDigits[parseInt(d)]);
}
```

**Backend (PHP - add to `PersianDigits.php`):**
```php
class PersianDigits {
    public static function normalize(string $input): string {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $english = range(0, 9);
        return str_replace(array_merge($persian, $arabic), $english, $input);
    }

    public static function toPersian(string $input): string {
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        return str_replace(range(0, 9), $persian, $input);
    }
}
```

---

#### 2. RTL-Aware Form Validation
**Problem:** Persian mobile numbers start with `09`, but users might forget or mistype.
**Solution:** Auto-format and validate Persian-specific fields.

**Code (JavaScript - extend `app.js`):**
```javascript
// Validate Iranian mobile (09XXXXXXXXX)
function validateIranianMobile(mobile) {
  const normalized = normalizePersianDigits(mobile);
  return /^09\d{9}$/.test(normalized);
}

// Auto-format mobile input (0912 123 4567)
function formatMobileInput(input) {
  let normalized = normalizePersianDigits(input.replace(/\D/g, ''));
  if (normalized.startsWith('09') && normalized.length <= 11) {
    if (normalized.length > 4 && normalized.length <= 7) {
      normalized = normalized.replace(/^(\d{4})(\d{0,3})/, '$1 $2');
    } else if (normalized.length > 7) {
      normalized = normalized.replace(/^(\d{4})(\d{3})(\d{0,4})/, '$1 $2 $3');
    }
  }
  return normalized;
}

// Apply to mobile inputs
document.querySelectorAll('input.mobile').forEach(el => {
  el.addEventListener('input', (e) => {
    e.target.value = formatMobileInput(e.target.value);
  });
  el.addEventListener('blur', (e) => {
    if (!validateIranianMobile(e.target.value)) {
      e.target.setCustomValidity('شماره موبایل معتبر نیست (فرمت: ۰۹۱۲۳۴۵۶۷۸۹)');
    } else {
      e.target.setCustomValidity('');
    }
  });
});
```

---

#### 3. Jalali (Shamsi) Date Picker
**Problem:** Users need to input dates in Shamsi (Jalali) calendar.
**Solution:** Use a **Persian date picker** library.

**Implementation:**
1. Add [`persian-datepicker`](https://github.com/behzadi/persian-datepicker) to your project:
   ```html
   <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/persian-datepicker@1.0.6/dist/css/persian-datepicker.min.css">
   <script src="https://cdn.jsdelivr.net/npm/persian-datepicker@1.0.6/dist/js/persian-datepicker.min.js"></script>
   ```
2. Initialize for input fields:
   ```html
   <input type="text" id="birth_date" class="persian-datepicker" data-jdp>
   ```
   ```javascript
   document.querySelectorAll('[data-jdp]').forEach(el => {
     $(el).persianDatepicker({
       format: 'YYYY/MM/DD',
       autoClose: true,
     });
   });
   ```

---

#### 4. Offline-First Intake Form
**Problem:** Users in areas with poor connectivity need to submit intake forms later.
**Solution:** Save form data to `localStorage` and sync when online.

**Code (JavaScript - add to `intake.html`):**
```javascript
const intakeForm = document.getElementById('intake-form');

// Save form data to localStorage
intakeForm.addEventListener('input', (e) => {
  const formData = new FormData(intakeForm);
  const data = {};
  formData.forEach((value, key) => {
    data[key] = value;
  });
  localStorage.setItem('intakeDraft', JSON.stringify(data));
});

// Load saved draft
document.addEventListener('DOMContentLoaded', () => {
  const draft = localStorage.getItem('intakeDraft');
  if (draft) {
    const data = JSON.parse(draft);
    for (const [key, value] of Object.entries(data)) {
      const input = intakeForm.querySelector(`[name="${key}"]`);
      if (input) input.value = value;
    }
  }
});

// Submit with offline support
intakeForm.addEventListener('submit', async (e) => {
  e.preventDefault();
  if (!navigator.onLine) {
    alert('اتصال به اینترنت برقرار نیست. فرم ذخیره شد و پس از اتصال ارسال خواهد شد.');
    return;
  }
  // ... (submit logic)
});

// Sync on reconnect
window.addEventListener('online', async () => {
  const draft = localStorage.getItem('intakeDraft');
  if (draft && confirm('اتصال برقرار شد. آیا می‌خواهید فرم ذخیره‌شده را ارسال کنید؟')) {
    // Submit draft
  }
});
```

---

#### 5. One-Tap OTP Auto-Fill
**Implementation:**
- **Android:** Use SMS Retriever API (via Capacitor plugin).
- **iOS:** Use `autocomplete="one-time-code"`.

```html
<input type="text" id="otp" inputmode="numeric" pattern="[0-9]*" autocomplete="one-time-code">
```

---

#### 6. Smart Patient Search
**Backend (Laravel - add to `PatientController.php`):**
```php
public function index(Request $request) {
    $query = Patient::query();
    if ($request->has('q')) {
        $searchTerm = PersianDigits::normalize($request->q);
        $query->where(function($q) use ($searchTerm) {
            $q->where('first_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('mobile', 'LIKE', "%{$searchTerm}%");
        });
    }
    return $query->paginate(20);
}
```

---

#### 7. Drag-and-Drop Calendar (Alpine.js)
**Implementation:**
1. Add Alpine.js:
   ```html
   <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
   ```
2. Create a drag-and-drop calendar (see full code in the original document).

---

#### 8. Voice-to-Text for EMR Notes
**Implementation:**
```html
<div x-data="{ listening: false }">
  <textarea id="emr-notes"></textarea>
  <button @click="
    if (listening) {
      recognition.stop();
      listening = false;
    } else {
      recognition.start();
      listening = true;
    }
  " x-text="listening ? 'توقف' : 'ضبط صدا'"></button>
</div>
```
**Note:** Use a Persian speech recognition API (e.g., [Vocalink](https://vocalink.ir/)) for better accuracy.

---

#### 9. High-Contrast Mode
**Code (CSS + JavaScript):**
```html
<body data-theme="default">
  <button id="contrast-toggle">A</button>
</body>
```
```css
:root {
  --text-primary: #25272C;
  --bg-primary: #F7F8F6;
}
[data-theme="high-contrast"] {
  --text-primary: #000000;
  --bg-primary: #FFFFFF;
  --border: #000000;
}
```
```javascript
document.getElementById('contrast-toggle').addEventListener('click', () => {
  const html = document.documentElement;
  const newTheme = html.dataset.theme === 'high-contrast' ? 'default' : 'high-contrast';
  html.dataset.theme = newTheme;
  localStorage.setItem('theme', newTheme);
});
```

---

#### 10. Touch-Friendly Buttons
**Code (CSS):**
```css
button, input[type="button"], input[type="submit"] {
  min-width: 48px;
  min-height: 48px;
  padding: 12px 16px;
}
```

---

---

## 📱 Track 2: Mobile Apps (APK & iOS)
**Goal:** Build **native-like mobile apps** for the dashboard that **sync with the Laravel API** and **share the same database**.

---

### 🔥 Mobile Strategy: Capacitor (Recommended)
**Why Capacitor?**
✅ Reuses existing frontend code (no rewrite needed).
✅ Single codebase for Android (APK) and iOS.
✅ Access to native features (camera, notifications, contacts).
✅ Works with Laravel API (no backend changes).
✅ Iran-friendly (no Google Play Services dependency).

---

### 🛠️ Implementation: Capacitor Mobile Apps

#### Step 1: Set Up Capacitor Project
```bash
npm init -y
npm install @capacitor/core @capacitor/cli
npx cap init
npm install @capacitor/android @capacitor/ios
npx cap add android
npx cap add ios
```

#### Step 2: Configure Capacitor
**`capacitor.config.ts`:**
```typescript
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.mazid.medicalcrm',
  appName: 'MΛZ Medical CRM',
  webDir: 'app.drbastaninejad.com/Frontend',
  server: {
    androidScheme: 'https',
    hostname: 'app.drbastaninejad.com',
  },
};

export default config;
```

#### Step 3: Add Native Plugins
```bash
npm install @capacitor/camera @capacitor/notifications @capacitor/device
npx cap sync
```

#### Step 4: Modify Frontend for Mobile
- Detect mobile environment.
- Add mobile-specific features (e.g., camera for media uploads).

#### Step 5: Configure for Persian/RTL
- Set RTL direction in `index.html`.
- Add Persian fonts (Vazirmatn).
- Override Capacitor’s default styles.

#### Step 6: Build and Test
```bash
npx cap copy
npx cap sync
npx cap run android
npx cap run ios
```

#### Step 7: Configure for Production
- Set API base URL.
- Configure CORS in Laravel.

#### Step 8: Build APK and iOS App
- **Android:** Build APK in Android Studio.
- **iOS:** Archive in Xcode.

---

---

## 🌐 Track 3: WordPress Redesign
**Goal:** Redesign the **public-facing WordPress site** to match the CRM design system, fetch data from Laravel API, and include all current pages/posts.

---

### 🔥 WordPress Strategy: API-Driven Theme
**Approach:**
1. Keep WordPress for CMS features (blog, pages).
2. Create a custom theme that:
   - Matches the CRM design system (`tokens.css`).
   - Fetches dynamic data from Laravel API.
   - Embeds CRM forms (e.g., intake, contact).

---

### 🛠️ Implementation: WordPress Redesign

#### Step 1: Set Up a Custom Theme
1. Create `wp-content/themes/maz-medical-crm/`.
2. Add `style.css` (theme header).
3. Add `functions.php`.

#### Step 2: Match CRM Design System
- Copy `tokens.css` from CRM to WordPress theme.
- Create `rtl.css` for RTL fixes.

#### Step 3: Fetch Data from Laravel API
- Create custom shortcodes to fetch clinic info, doctors, services.
- Cache API responses.

**Example Shortcode:**
```php
function maz_clinic_info_shortcode() {
    $response = wp_remote_get('https://app.drbastaninejad.com/api/v1/clinic');
    $clinic = json_decode(wp_remote_retrieve_body($response));
    // Render clinic info
}
add_shortcode('maz_clinic_info', 'maz_clinic_info_shortcode');
```

#### Step 4: Embed CRM Intake Form
- Use iframe or API integration.

#### Step 5: Sync WordPress and CRM Data
- Push WordPress posts to Laravel via webhook.
- Pull data from Laravel to WordPress via API.

---

---

## 🔗 Track 4: Database Sync & Interconnection
**Goal:** Ensure **WordPress, Mobile Apps, and CRM** share **consistent data** via the **Laravel API**.

---

### 🔥 Sync Strategy: API-Centric Architecture
```
Laravel API (Single Source of Truth)
├── Web CRM (Static HTML)
├── WordPress (API-Driven)
└── Mobile Apps (Capacitor)
```

---

### 🛠️ Implementation: Database Sync

#### 1. Laravel API Endpoints for Public Data
Add endpoints for clinic info, doctors, services, news.

**Example:**
```php
Route::get('/clinic', [ClinicController::class, 'show']);
Route::get('/doctors', [DoctorController::class, 'index']);
```

#### 2. WordPress Data Sync
- Use cached API calls in WordPress.
- Push WordPress posts to Laravel via webhook.

#### 3. Mobile App Data Sync
- Use the same Laravel API as the web CRM.
- Implement offline caching (e.g., `localForage`).

#### 4. Real-Time Sync with Webhooks
- Use Laravel webhooks or polling.

#### 5. Database Schema for Sync Tracking
Add a `sync_logs` table to track data changes.

---

---

## 📅 Combined Roadmap (All Tracks)

| Week | Track 1: UX Improvements | Track 2: Mobile Apps | Track 3: WordPress Redesign | Track 4: Database Sync |
|------|--------------------------|---------------------|----------------------------|----------------------|
| **1** | Persian digits, RTL validation, touch buttons | Set up Capacitor project | Create custom theme folder | Laravel API endpoints |
| **2** | Jalali date picker, offline intake | Configure Capacitor, add plugins | Add `functions.php`, `style.css` | WordPress API caching |
| **3** | Smart search, OTP auto-fill | Mobile-specific features | Copy `tokens.css`, create RTL fixes | Webhook for WordPress → Laravel |
| **4** | Drag-and-drop calendar, voice-to-text | Test on Android/iOS | Embed intake form, create templates | Offline caching in mobile |
| **5** | High-contrast mode, error handling | Build APK | Deploy theme | Sync testing |
| **6** | UX polish | Build iOS app | Configure menus | Final sync testing |
| **7** | User testing | Deploy to app stores | Content migration | Monitor sync health |
| **8** | Bug fixes | Monitor app performance | SEO optimization | Monitor sync health |

---

---

## 📦 Deliverables

| Deliverable | Description | Timeline |
|-------------|-------------|----------|
| **UX-Improved CRM** | Persian digits, RTL validation, offline forms, Jalali dates | Week 2 |
| **Capacitor Mobile App (APK)** | Native-like app with camera, OTP, offline support | Week 4 |
| **Capacitor Mobile App (iOS)** | iOS version of the app | Week 6 |
| **WordPress Theme** | Custom theme matching CRM design, API-driven | Week 6 |
| **Synced Database** | Laravel API + WordPress + Mobile all in sync | Week 8 |

---

---

## 💡 Key Technical Decisions

| Decision | Rationale |
|----------|-----------|
| **Capacitor for mobile apps** | Reuses existing frontend, fast to deploy, Iran-friendly |
| **API-centric sync** | Single source of truth (Laravel), no direct DB access |
| **WordPress + Laravel API** | Keep WordPress for CMS, fetch dynamic data from Laravel |
| **Cached API responses** | Reduce load on Laravel, improve WordPress performance |
| **Offline-first mobile** | Works in areas with poor connectivity (common in Iran) |
| **Persian-first UX** | RTL, Jalali dates, Persian digits, voice-to-text |

---

---

## ⚠️ Risks & Mitigations

| Risk | Impact | Mitigation |
|------|--------|------------|
| Capacitor performance on old devices | Medium | Test on low-end Android phones, optimize JS |
| WordPress API caching issues | Low | Use short TTL (5–10 minutes), monitor cache hits |
| Sync conflicts (WordPress ↔ Laravel) | Low | Laravel is source of truth; WordPress only reads |
| App Store rejection (iOS) | Medium | Follow Apple guidelines, avoid sensitive data |
| Google Play restrictions (Iran) | High | Deploy APK directly on website + Iranian app stores |
| Offline sync conflicts | Medium | Use timestamps for conflict resolution |
| Persian speech recognition accuracy | Medium | Use a Persian-specific API (e.g., Vocalink) |

---

---

## 🚀 Next Steps

### Immediate (This Week)
1. Start with UX improvements:
   - Persian digit normalization.
   - RTL-aware validation.
   - Jalali date picker.
   - Offline intake form.
2. Set up Capacitor project.
3. Create WordPress theme skeleton.

### Short-Term (Next 2 Weeks)
1. Complete UX improvements:
   - Smart patient search.
   - Drag-and-drop calendar.
   - High-contrast mode.
2. Build mobile app features:
   - Camera plugin.
   - OTP auto-fill.
   - Test on real devices.
3. Develop WordPress theme:
   - Laravel API shortcodes.
   - Embed intake form.
   - Create page templates.

### Long-Term (Next Month)
1. Deploy mobile apps:
   - Build APK and deploy to Café Bazaar/Myket.
   - Build iOS app and deploy to App Store.
2. Deploy WordPress theme:
   - Upload and activate theme.
   - Migrate content to new design.
3. Final sync testing:
   - Test WordPress ↔ Laravel ↔ Mobile data flow.
   - Monitor sync logs for conflicts.

---

_M•Z · MAZ//ID · © 2026 Dr. Shahin Bastaninejad_