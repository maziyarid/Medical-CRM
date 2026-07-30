/**
 * ============================================================================
 *  MΛZ Medical CRM — API Adapter
 *  Client / Product: Dr. Shahin Bastaninejad Multi-Specialty Medical Platform
 * ----------------------------------------------------------------------------
 *  Project : Medical CRM (app.drbastaninejad.com)
 *  Author  : MAZ//ID (Maziyar)
 *  Contact : maziyarid@gmail.com — https://maziyarid.com
 *  License : Proprietary — © 2026 Maziyar / Dr. Shahin Bastaninejad
 *  Version : 1.0.0  ·  27 July 2026
 * ============================================================================
 *
 *  Single shared module for all API calls. Import via:
 *    <script type="module" src="/Frontend/shared/api.js"></script>
 *  Or as ES module in other scripts:
 *    import { Api, Auth, Intake } from '/Frontend/shared/api.js';
 *
 *  No framework dependency. Vanilla ES2020. Targets modern Android + Safari.
 * ============================================================================
 */

'use strict';

// ---------------------------------------------------------------------------
// Config
// ---------------------------------------------------------------------------
const API_BASE = 'https://app.drbastaninejad.com/api/v1';
const TOKEN_KEY = 'mz_auth_token';
const UUID_KEY  = 'mz_intake_uuid';

// ---------------------------------------------------------------------------
// Persian / Arabic digit normalisation (mirrors ValidatorService.php)
// ---------------------------------------------------------------------------
export function normalizePersianDigits(str) {
  if (!str) return str;
  return String(str)
    .replace(/[\u06F0-\u06F9]/g, d => d.charCodeAt(0) - 0x06F0)  // Persian ۰–۹
    .replace(/[\u0660-\u0669]/g, d => d.charCodeAt(0) - 0x0660); // Arabic ٠–٩
}

// ---------------------------------------------------------------------------
// Mobile normalisation — mirrors ValidatorService::normalizeMobile()
// Accepts: +989xxxxxxxx / 00989xxxxxxxx / 989xxxxxxxx / 09xxxxxxxx
// Returns: 09XXXXXXXXX (11 digits) or null if invalid
// ---------------------------------------------------------------------------
export function normalizeMobile(raw) {
  if (!raw) return null;
  let m = normalizePersianDigits(String(raw)).trim().replace(/\s|-/g, '');
  if (m.startsWith('+98')) m = '0' + m.slice(3);
  else if (m.startsWith('0098')) m = '0' + m.slice(4);
  else if (m.startsWith('98') && m.length === 12) m = '0' + m.slice(2);
  if (/^09\d{9}$/.test(m)) return m;
  return null;
}

// ---------------------------------------------------------------------------
// UUIDv4 — crypto.randomUUID() with polyfill for older Android WebViews
// ---------------------------------------------------------------------------
export function generateUUID() {
  if (typeof crypto !== 'undefined' && crypto.randomUUID) {
    return crypto.randomUUID();
  }
  // Polyfill (RFC4122 v4)
  return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
    const r = (Math.random() * 16) | 0;
    return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
  });
}

// ---------------------------------------------------------------------------
// Intake UUID — idempotency: one UUID per session, persisted so page refresh
// does not create a duplicate submission.
// ---------------------------------------------------------------------------
export function getOrCreateIntakeUUID() {
  let uuid = sessionStorage.getItem(UUID_KEY);
  if (!uuid) {
    uuid = generateUUID();
    sessionStorage.setItem(UUID_KEY, uuid);
  }
  return uuid;
}

export function clearIntakeUUID() {
  sessionStorage.removeItem(UUID_KEY);
}

// ---------------------------------------------------------------------------
// Auth token helpers
// ---------------------------------------------------------------------------
export function getToken() {
  return localStorage.getItem(TOKEN_KEY);
}

export function setToken(token) {
  localStorage.setItem(TOKEN_KEY, token);
}

export function clearToken() {
  localStorage.removeItem(TOKEN_KEY);
}

export function isAuthenticated() {
  return !!getToken();
}

// ---------------------------------------------------------------------------
// Error code → Persian UI message map
// Extend as backend adds new error codes.
// ---------------------------------------------------------------------------
const ERROR_MESSAGES = {
  VALIDATION_FAILED:       'اطلاعات وارد شده صحیح نیست. لطفاً بررسی کنید.',
  INVALID_NATIONAL_ID:     'کد ملی وارد شده معتبر نیست.',
  INVALID_MOBILE:          'شماره موبایل وارد شده معتبر نیست.',
  INVALID_JALALI_DATE:     'تاریخ تولد وارد شده صحیح نیست.',
  DUPLICATE_SUBMISSION:    'این فرم قبلاً ثبت شده است.',
  OTP_RATE_LIMITED:        'درخواست کد زیاد است. لطفاً چند دقیقه صبر کنید.',
  OTP_INVALID:             'کد وارد شده صحیح نیست.',
  OTP_EXPIRED:             'کد منقضی شده است. کد جدید درخواست کنید.',
  UNAUTHORIZED:            'لطفاً ابتدا وارد شوید.',
  FORBIDDEN:               'دسترسی شما به این بخش مجاز نیست.',
  NOT_FOUND:               'اطلاعات مورد نظر یافت نشد.',
  SERVER_ERROR:            'خطای سرور. لطفاً دوباره تلاش کنید.',
  NETWORK_ERROR:           'خطای اتصال. اینترنت را بررسی کنید.',
};

export function getPersianError(code) {
  return ERROR_MESSAGES[code] || ERROR_MESSAGES.SERVER_ERROR;
}

// ---------------------------------------------------------------------------
// Core fetch wrapper
// ---------------------------------------------------------------------------
async function request(method, path, body = null, options = {}) {
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  const token = getToken();
  if (token) headers['Authorization'] = `Bearer ${token}`;
  Object.assign(headers, options.headers || {});

  const config = { method, headers };
  if (body !== null) config.body = JSON.stringify(body);

  let response;
  try {
    response = await fetch(`${API_BASE}${path}`, config);
  } catch (networkErr) {
    throw { code: 'NETWORK_ERROR', message: getPersianError('NETWORK_ERROR'), raw: networkErr };
  }

  let data;
  try {
    data = await response.json();
  } catch {
    throw { code: 'SERVER_ERROR', message: getPersianError('SERVER_ERROR'), httpStatus: response.status };
  }

  if (!response.ok || data.success === false) {
    const code = data?.error?.code || (response.status === 401 ? 'UNAUTHORIZED' : 'SERVER_ERROR');
    throw { code, message: getPersianError(code), httpStatus: response.status, raw: data };
  }

  return data;
}

// ---------------------------------------------------------------------------
// Auth API
// ---------------------------------------------------------------------------
export const Auth = {
  /**
   * Send OTP to mobile number.
   * @param {string} mobile — raw input (Persian digits OK, will be normalised)
   * @returns {Promise<{ success: true, expires_in: number }>}
   */
  async sendOtp(mobile) {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    return request('POST', '/auth/otp/send', { mobile: normalised });
  },

  /**
   * Verify OTP and receive bearer token.
   * Automatically stores token in localStorage on success.
   * @param {string} mobile
   * @param {string} otp — Persian digits OK
   * @returns {Promise<{ success: true, token: string, expires_at: string }>}
   */
  async verifyOtp(mobile, otp) {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    const normalisedOtp = normalizePersianDigits(String(otp)).trim();
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    const result = await request('POST', '/auth/otp/verify', { mobile: normalised, otp: normalisedOtp });
    // API_CONTRACT.md: token is in result.data.token (not result.token)
    const token = result && result.data && result.data.token;
    if (token) setToken(token);
    return result;
  },

  /** Remove token and redirect to login page. */
  logout(redirectTo = '/Frontend/pages/auth/patient-login.html') {
    clearToken();
    window.location.href = redirectTo;
  },
};

// ---------------------------------------------------------------------------
// Intake API
// ---------------------------------------------------------------------------
export const Intake = {
  /**
   * Submit patient intake form.
   * Automatically attaches idempotency UUID from sessionStorage.
   * On duplicate (same UUID) server returns 200 with original data — not an error.
   *
   * @param {Object} formData — must contain all required intake fields
   * @returns {Promise<{ success: true, intake_id: number, patient_id: number }>}
   */
  async submit(formData) {
    const uuid = getOrCreateIntakeUUID();
    const payload = {
      submission_uuid: uuid,
      ...formData,
      // Normalise mobile before send
      mobile: normalizeMobile(normalizePersianDigits(formData.mobile)),
    };
    const result = await request('POST', '/intakes', payload);
    // Clear UUID only after confirmed success so refresh is safe
    if (result.success) clearIntakeUUID();
    return result;
  },
};

// ---------------------------------------------------------------------------
// Patient portal API
// All endpoints below require a valid bearer token (mz_auth_token).
// If the endpoint is not yet live on the backend, the function throws with
// code PENDING_BACKEND so the UI can render the placeholder state.
// ---------------------------------------------------------------------------
export const Patient = {
  /**
   * GET /api/v1/patient/overview  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md:
   *   patient_name, next_appointment{date_jalali,time,reason,status},
   *   total_intakes, total_documents, last_intake_date
   */
  async getOverview() {
    return request('GET', '/patient/overview');
  },

  /**
   * GET /api/v1/patient/profile  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md §GET /patient/profile:
   *   first_name, last_name, father_name, national_id, birth_date,
   *   mobile, email, home_tel, home_address
   */
  async getProfile() {
    return request('GET', '/patient/profile');
  },

  /**
   * PATCH /api/v1/patient/profile  ✅ LIVE (Phase C, 2026-07-31)
   * Writable fields: email, home_tel, home_address (partial update).
   * Returns full updated profile on success (200).
   * Per docs/API_CONTRACT.md §PATCH /patient/profile (v1.2)
   *
   * @param {{ email?: string, home_tel?: string, home_address?: string }} patch
   */
  async updateProfile(patch) {
    return request('PATCH', '/patient/profile', patch);
  },

  /**
   * GET /api/v1/patient/appointments  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md §GET /patient/appointments:
   *   items[]: { appointment_id, date_jalali, time, reason, status, provider_name }
   *   pagination: { total, per_page, current_page, last_page }
   * Status enum: confirmed | scheduled | cancelled | completed
   */
  async getAppointments() {
    return request('GET', '/patient/appointments');
  },

  /**
   * GET /api/v1/patient/documents  ✅ LIVE (Phase C, 2026-07-30)
   * Returns media list with signed short-TTL URLs (1-hour TTL).
   * NOTE: signed_url will be null if CDN_BASE_URL is not set in .env.
   * Per docs/API_CONTRACT.md §GET /patient/documents
   */
  async getDocuments() {
    return request('GET', '/patient/documents');
  },

  /**
   * GET /api/v1/patient/notification-preferences  ✅ LIVE (Phase C, 2026-07-30)
   * Per docs/API_CONTRACT.md §GET /patient/notification-preferences
   */
  async getNotificationPreferences() {
    return request('GET', '/patient/notification-preferences');
  },

  /**
   * PATCH /api/v1/patient/notification-preferences  ✅ LIVE (Phase C, 2026-07-30)
   * Send only the keys you want to change (partial update).
   * Per docs/API_CONTRACT.md §PATCH /patient/notification-preferences
   *
   * @param {{ sms_appointment_reminder?: boolean, sms_status_change?: boolean,
   *           email_appointment_reminder?: boolean, email_marketing?: boolean }} prefs
   */
  async updateNotificationPreferences(prefs) {
    return request('PATCH', '/patient/notification-preferences', prefs);
  },
};

// ---------------------------------------------------------------------------
// UI helpers — skeleton / placeholder / error banner
// ---------------------------------------------------------------------------

/**
 * Render a loading skeleton inside `container` using `count` rows.
 * Compatible with any element that accepts innerHTML.
 */
export function renderSkeleton(container, count = 3) {
  if (!container) return;
  container.innerHTML = Array.from({ length: count }, () =>
    `<div class="skeleton-row" aria-hidden="true">
      <div class="skeleton skeleton-line" style="width:60%"></div>
      <div class="skeleton skeleton-line" style="width:40%"></div>
    </div>`
  ).join('');
}

/**
 * Render a "pending backend" placeholder.
 * Used for Patient Portal endpoints not yet live.
 */
export function renderPendingBackend(container, label = 'در انتظار پیاده‌سازی بک‌اند') {
  if (!container) return;
  container.innerHTML =
    `<div class="state-pending" role="status">
      <span class="state-icon" aria-hidden="true">⏳</span>
      <p>${label}</p>
    </div>`;
}

/**
 * Render an error state with Persian message.
 * @param {string|{code,message}} err
 */
export function renderError(container, err) {
  if (!container) return;
  const msg = (typeof err === 'string') ? err
    : (err?.message || getPersianError(err?.code || 'SERVER_ERROR'));
  container.innerHTML =
    `<div class="state-error" role="alert" aria-live="polite">
      <p>${msg}</p>
    </div>`;
}

/**
 * Show a toast notification.
 * Requires a #mz-toast element in the page (added by base.css / chrome.js convention).
 * Creates one if not found.
 */
export function showToast(message, type = 'info', duration = 4000) {
  let toast = document.getElementById('mz-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'mz-toast';
    toast.setAttribute('role', 'status');
    toast.setAttribute('aria-live', 'polite');
    document.body.appendChild(toast);
  }
  toast.textContent = message;
  toast.className = `mz-toast mz-toast--${type} mz-toast--visible`;
  clearTimeout(toast._timer);
  toast._timer = setTimeout(() => toast.classList.remove('mz-toast--visible'), duration);
}

// ---------------------------------------------------------------------------
// Guard: redirect to login if no token present.
// Call at top of any patient-portal page.
// ---------------------------------------------------------------------------
export function requireAuth(redirectTo = '/Frontend/pages/auth/patient-login.html') {
  if (!isAuthenticated()) {
    window.location.replace(redirectTo);
    return false;
  }
  return true;
}

/*
 * ============================================================================
 *  End of file — MAZ//ID
 * ============================================================================
 */
