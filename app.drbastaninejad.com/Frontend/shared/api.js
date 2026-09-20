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
// The dashboard API base can be overridden at deploy time by setting
// window.__DRB_API_BASE__ before this module loads (e.g. in a small inline
// <script> in the page <head>). This lets ops point the frontend at a different
// dashboard host without editing this file — useful when SSL is being
// reprovisioned or when a maintenance mirror is used. Falls back to canonical.
const API_BASE = (typeof window !== 'undefined' && window.__DRB_API_BASE__)
  ? String(window.__DRB_API_BASE__).replace(/\/+$/, '')
  : 'https://dashboard.drbastaninejad.com/api/v1';
const LEGACY_TOKEN_KEY = 'mz_auth_token';
const TOKEN_KEYS = Object.freeze({
  patient: 'mz_patient_auth_token',
  staff: 'mz_staff_auth_token',
});
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
function assertAuthScope(scope) {
  if (scope !== 'patient' && scope !== 'staff') {
    throw new Error('Invalid authentication scope');
  }
  return scope;
}

function migrateLegacyToken(scope) {
  const key = TOKEN_KEYS[scope];
  if (sessionStorage.getItem(key)) return;
  const legacy = localStorage.getItem(LEGACY_TOKEN_KEY);
  if (!legacy) return;
  // One-time compatibility migration for a session opened before audience
  // separation. The calling page selects the intended audience explicitly.
  sessionStorage.setItem(key, legacy);
  localStorage.removeItem(LEGACY_TOKEN_KEY);
}

export function getToken(scope = 'patient') {
  scope = assertAuthScope(scope);
  migrateLegacyToken(scope);
  return sessionStorage.getItem(TOKEN_KEYS[scope]);
}

export function setToken(token, scope = 'patient') {
  scope = assertAuthScope(scope);
  sessionStorage.setItem(TOKEN_KEYS[scope], token);
  localStorage.removeItem(LEGACY_TOKEN_KEY);
}

export function clearToken(scope = 'patient') {
  scope = assertAuthScope(scope);
  sessionStorage.removeItem(TOKEN_KEYS[scope]);
  localStorage.removeItem(LEGACY_TOKEN_KEY);
}

export function isAuthenticated(scope = 'patient') {
  return !!getToken(scope);
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
  SSL_ERROR:               'اتصال امن به سرور برقرار نشد. ممکن است گواهی امنیتی سایت نامعتبر باشد. کمی بعد تلاش کنید.',
  DNS_ERROR:               'سرور پلتفرم پیدا نشد. لطفاً اتصال اینترنت را بررسی و دوباره تلاش کنید.',
  TIMEOUT_ERROR:           'پاسخ سرور طول کشید. لطفاً دوباره تلاش کنید.',
};

export function getPersianError(code) {
  return ERROR_MESSAGES[code] || ERROR_MESSAGES.SERVER_ERROR;
}

/**
 * Classify a fetch() network rejection into an actionable Persian error.
 * Bug J: patients saw a generic "خطای اتصال" that hid the real cause (SSL
 * certificate not yet valid on dashboard.drbastaninejad.com, DNS failure,
 * or the host being down). We inspect the browser error type/message to surface
 * a clearer hint without exposing internals.
 */
function classifyNetworkError(err) {
  const raw = err && err.message ? String(err.message) : String(err || '');
  const lower = raw.toLowerCase();
  // Chrome: "Failed to fetch"; Firefox: "NetworkError when attempting to fetch".
  // Safari throws a TypeError with "Load failed". All are generic.
  let code = 'NETWORK_ERROR';
  // SSL/TLS rejections usually include 'ssl', 'certificate', 'protocol', or
  // 'aborted' in some user agents; DNS failures include 'dns' or 'name'.
  if (/(ssl|certificate|tls|protocol|ERR_CERT|net::ERR_CERT)/i.test(lower)) {
    code = 'SSL_ERROR';
  } else if (/(dns|name resolution|getaddrinfo|ENOTFOUND|EAI_AGAIN)/i.test(lower)) {
    code = 'DNS_ERROR';
  } else if (/(timeout|timed out|aborted)/i.test(lower)) {
    code = 'TIMEOUT_ERROR';
  }
  return { code, message: getPersianError(code), raw: err };
}

// ---------------------------------------------------------------------------
// Core fetch wrapper
// ---------------------------------------------------------------------------
async function request(method, path, body = null, options = {}) {
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  const token = getToken('patient');
  if (token) headers['Authorization'] = `Bearer ${token}`;
  Object.assign(headers, options.headers || {});

  const config = { method, headers };
  if (body !== null) config.body = JSON.stringify(body);

  let response;
  try {
    response = await fetch(`${API_BASE}${path}`, config);
  } catch (networkErr) {
    throw classifyNetworkError(networkErr);
  }

  let data;
  try {
    data = await response.json();
  } catch {
    throw { code: 'SERVER_ERROR', message: getPersianError('SERVER_ERROR'), httpStatus: response.status };
  }

  if (!response.ok || data.ok === false || data.success === false) {
    const code = data?.error?.code || (response.status === 401 ? 'UNAUTHORIZED' : response.status === 403 ? 'FORBIDDEN' : response.status === 422 ? 'VALIDATION_FAILED' : 'SERVER_ERROR');
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
  async sendOtp(mobile, audience = 'patient') {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    return request('POST', '/auth/otp/send', { mobile: normalised, audience });
  },

  /**
   * Verify OTP and receive bearer token.
   * Automatically stores token in localStorage on success.
   * @param {string} mobile
   * @param {string} otp — Persian digits OK
   * @returns {Promise<{ success: true, token: string, expires_at: string }>}
   */
  async verifyOtp(mobile, otp, audience = 'patient') {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    const normalisedOtp = normalizePersianDigits(String(otp)).trim();
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    const result = await request('POST', '/auth/otp/verify', { mobile: normalised, otp: normalisedOtp, audience });
    // API_CONTRACT.md: token is in result.data.token (not result.token)
    const token = result && result.data && result.data.token;
    if (token) setToken(token, audience);
    return result;
  },

  async passwordLogin(mobile, password) {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    if (!password) throw { code: 'VALIDATION_FAILED', message: 'رمز عبور را وارد کنید.' };
    const result = await request('POST', '/auth/password', { mobile: normalised, password });
    const token = result?.data?.token;
    if (token) setToken(token, 'staff');
    return result;
  },

  async setStaffPassword(newPassword) {
    const result = await staffRequest('POST', '/auth/password/set', { new_password: newPassword });
    const token = result?.data?.session?.token;
    if (token) setToken(token, 'staff');
    return result;
  },

  async me(audience = 'staff') {
    return audience === 'staff'
      ? staffRequest('GET', '/auth/me')
      : request('GET', '/auth/me');
  },

  async requestRecovery(mobile, channel = 'sms', email = '') {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    return request('POST', '/auth/recovery/request', { mobile: normalised, channel, email });
  },

  async verifyRecovery(mobile, otp) {
    const normalised = normalizeMobile(normalizePersianDigits(mobile));
    const normalisedOtp = normalizePersianDigits(String(otp)).trim();
    if (!normalised) throw { code: 'INVALID_MOBILE', message: getPersianError('INVALID_MOBILE') };
    return request('POST', '/auth/recovery/verify', { mobile: normalised, otp: normalisedOtp });
  },

  async setRecoveredPassword(resetToken, newPassword) {
    return request('POST', '/auth/recovery/password', { reset_token: resetToken, new_password: newPassword });
  },

  /** Remove the selected audience token and redirect to its login page. */
  logout(redirectTo = '/Frontend/pages/auth/patient-login.html', audience = 'patient') {
    clearToken(audience);
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
    if (result.ok !== false && result.success !== false) clearIntakeUUID();
    return result;
  },
};

// ---------------------------------------------------------------------------
// Patient portal API
// All endpoints below require the patient-scoped bearer token stored for this browser session.
// The canonical API host is dashboard.drbastaninejad.com.
// ---------------------------------------------------------------------------
export const Patient = {
  /**
   * GET /api/v1/patient/overview  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md §GET /patient/overview:
   *   patient_name,
   *   next_appointment{ appointment_id, scheduled_at, duration_minutes, reason, status },
   *   total_intakes, total_documents, last_intake_date
   * next_appointment is null when no upcoming appointment exists.
   */
  async getOverview() {
    return request('GET', '/patient/overview');
  },

  /**
   * GET /api/v1/patient/profile  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md §GET /patient/profile:
   *   id, uuid, first_name, last_name, mobile, national_id, birth_date,
   *   home_address, insurance_status
   * Note: email, home_tel, father_name are returned by the backend but not
   * listed in the current contract. Backend team to confirm and update
   * docs/API_CONTRACT.md. See PROGRESS_LOG.md 2026-08-01 discrepancy note.
   */
  async getProfile() {
    return request('GET', '/patient/profile');
  },

  /**
   * PATCH /api/v1/patient/profile  ✅ LIVE (Phase C, 2026-07-31)
   * ⚠ CONTRACT DISCREPANCY (2026-08-01):
   *   docs/API_CONTRACT.md §PATCH /patient/profile lists writable fields as:
   *     { first_name, last_name, home_address }
   *   But profile.html sends: { email, home_tel, home_address }
   *   Both are plausible — backend must confirm which fields are actually
   *   accepted and update docs/API_CONTRACT.md before this discrepancy is resolved.
   *   The UI (profile.html) currently sends email+home_tel+home_address.
   *   Until confirmed, DO NOT change the fields sent — a backend fix may be needed.
   *
   * @param {{ email?: string, home_tel?: string, home_address?: string }} patch
   */
  async updateProfile(patch) {
    return request('PATCH', '/patient/profile', patch);
  },

  /**
   * GET /api/v1/patient/appointments  ✅ LIVE (Phase C, 2026-07-30)
   * Confirmed response fields from docs/API_CONTRACT.md §GET /patient/appointments:
   *   items[]: { appointment_id, scheduled_at, duration_minutes, reason, status, provider_name }
   *   pagination: { total, per_page, current_page, last_page }
   * Status enum: scheduled | confirmed | cancelled | completed
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
// Staff API — dashboard.drbastaninejad.com endpoints
// Uses the "ok" envelope (not "success") per the dashboard API contract.
// Documented in dashboard.drbastaninejad.com/docs/API_CONTRACT.md §Phase D
// ---------------------------------------------------------------------------

const STAFF_API_BASE = (typeof window !== 'undefined' && window.__DRB_STAFF_API_BASE__)
  ? String(window.__DRB_STAFF_API_BASE__).replace(/\/+$/, '')
  : API_BASE;

/** Core fetch for all staff (dashboard) endpoints. */
async function staffRequest(method, path, body = null) {
  const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json' };
  const token = getToken('staff');
  if (token) headers['Authorization'] = `Bearer ${token}`;
  const config = { method, headers };
  if (body !== null) config.body = JSON.stringify(body);

  let response;
  try {
    response = await fetch(`${STAFF_API_BASE}${path}`, config);
  } catch (networkErr) {
    throw classifyNetworkError(networkErr);
  }

  let data;
  try {
    data = await response.json();
  } catch {
    throw { code: 'SERVER_ERROR', message: getPersianError('SERVER_ERROR'), httpStatus: response.status };
  }

  // Dashboard backend uses "ok" discriminator (not "success")
  if (!response.ok || data.ok === false) {
    const code = (response.status === 401 ? 'UNAUTHORIZED'
                : response.status === 403 ? 'FORBIDDEN'
                : response.status === 422 ? 'VALIDATION_FAILED'
                : 'SERVER_ERROR');
    const serverMessage = data?.errors?.[0]?.message;
    throw { code, message: serverMessage || getPersianError(code), httpStatus: response.status, raw: data };
  }

  return data;
}

export const Staff = {

  // ── Intake / booking review queue ────────────────────────────────

  async listIntakes(params = {}) {
    const qs = new URLSearchParams();
    if (params.status) qs.set('status', params.status);
    if (params.source_type) qs.set('source_type', params.source_type);
    if (params.page) qs.set('page', String(params.page));
    if (params.per_page) qs.set('per_page', String(params.per_page));
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/intakes' + query);
  },

  async updateIntakeStatus(id, status) {
    return staffRequest('PATCH', `/intakes/${id}/status`, { status });
  },
  /**
   * GET /api/v1/dashboard/overview  ✅ LIVE
   * Returns: { metrics[], timeline[] }
   */
  async getOverview() {
    return staffRequest('GET', '/dashboard/overview');
  },

  /**
   * GET /api/v1/patients  ✅ LIVE
   * @param {{ q?: string, page?: number, per_page?: number, insurance_status?: string }} params
   */
  async listPatients(params = {}) {
    const qs = new URLSearchParams();
    if (params.q)                qs.set('q',                params.q);
    if (params.page)             qs.set('page',             String(params.page));
    if (params.per_page)         qs.set('per_page',         String(params.per_page));
    if (params.insurance_status) qs.set('insurance_status', params.insurance_status);
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/patients' + query);
  },

  /** GET /api/v1/patients/{id}  ✅ LIVE */
  async getPatient(id) {
    return staffRequest('GET', `/patients/${id}`);
  },

  async listBookingBlacklist() {
    return staffRequest('GET', '/admin/booking-blacklist');
  },

  async addBookingBlacklist(body) {
    return staffRequest('POST', '/admin/booking-blacklist', body);
  },

  async removeBookingBlacklist(id) {
    return staffRequest('DELETE', `/admin/booking-blacklist/${id}`);
  },

  // ── Appointments ──────────────────────────────────────────────────

  /**
   * GET /api/v1/appointments  ✅ LIVE
   * @param {{ from?: string, to?: string, provider_id?: number }} params
   */
  async listAppointments(params = {}) {
    const qs = new URLSearchParams();
    if (params.from)        qs.set('from', params.from);
    if (params.to)          qs.set('to',   params.to);
    if (params.provider_id) qs.set('provider_id', String(params.provider_id));
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/appointments' + query);
  },

  /**
   * GET /api/v1/appointments/{id}  ✅ LIVE
   */
  async getAppointment(id) {
    return staffRequest('GET', `/appointments/${id}`);
  },

  /**
   * POST /api/v1/appointments  ✅ LIVE
   * @param {{ patient_id, provider_id, scheduled_at, duration_minutes?, visit_reason?, room?, notes? }} body
   */
  async createAppointment(body) {
    return staffRequest('POST', '/appointments', body);
  },

  /**
   * PATCH /api/v1/appointments/{id}/reschedule  ✅ LIVE
   * @param {number} id
   * @param {{ scheduled_at: string, duration_minutes?: number }} body
   */
  async rescheduleAppointment(id, body) {
    return staffRequest('PATCH', `/appointments/${id}/reschedule`, body);
  },

  /**
   * PATCH /api/v1/appointments/{id}/status  ✅ LIVE
   * @param {number} id
   * @param {string} status — scheduled|confirmed|cancelled|completed
   */
  async setAppointmentStatus(id, status) {
    return staffRequest('PATCH', `/appointments/${id}/status`, { status });
  },

  /**
   * DELETE /api/v1/appointments/{id}  ✅ LIVE
   * @param {number} id
   * @param {string} reason
   */
  async cancelAppointment(id, reason) {
    return staffRequest('DELETE', `/appointments/${id}`, { reason });
  },

  // ── EMR ───────────────────────────────────────────────────────────

  /**
   * GET /api/v1/patients/{patientId}/emr  ✅ LIVE
   */
  async getEmr(patientId) {
    return staffRequest('GET', `/patients/${patientId}/emr`);
  },

  /**
   * POST /api/v1/patients/{patientId}/emr  ✅ LIVE
   * @param {number} patientId
   * @param {{ visit_type, subjective, objective, assessment, plan, is_draft? }} body
   */
  async createEmrNote(patientId, body) {
    return staffRequest('POST', `/patients/${patientId}/emr`, body);
  },

  /**
   * GET /api/v1/emr/templates  ✅ LIVE
   */
  async getEmrTemplates() {
    return staffRequest('GET', '/emr/templates');
  },

  /**
   * POST /api/v1/ai/emr-draft  ✅ LIVE
   * @param {{ patient_id: number, prompt: string, context?: Object }} body
   */
  async aiEmrDraft(body) {
    return staffRequest('POST', '/ai/emr-draft', body);
  },

  // ── Analytics ─────────────────────────────────────────────────────

  /**
   * GET /api/v1/analytics/summary  ✅ LIVE
   * @param {{ range?: '30d'|'90d'|'1y', date_from?: string, date_to?: string }} params
   */
  async getAnalytics(params = {}) {
    const qs = new URLSearchParams();
    if (params.range)     qs.set('range',     params.range);
    if (params.date_from) qs.set('date_from', params.date_from);
    if (params.date_to)   qs.set('date_to',   params.date_to);
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/analytics/summary' + query);
  },

  // ── Billing / Invoices ────────────────────────────────────────────

  /**
   * GET /api/v1/billing/invoices  ✅ LIVE
   * @param {{ q?: string, status?: string, gateway?: string, page?: number }} params
   */
  async listInvoices(params = {}) {
    const qs = new URLSearchParams();
    if (params.q)       qs.set('q',       params.q);
    if (params.status)  qs.set('status',  params.status);
    if (params.gateway) qs.set('gateway', params.gateway);
    if (params.page)    qs.set('page',    String(params.page));
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/billing/invoices' + query);
  },

  /**
   * GET /api/v1/billing/invoices/{id}  ✅ LIVE
   */
  async getInvoice(id) {
    return staffRequest('GET', `/billing/invoices/${id}`);
  },

  /**
   * POST /api/v1/billing/invoices  ✅ LIVE
   * @param {{ patient_id, amount_rials, gateway?, notes? }} body
   */
  async createInvoice(body) {
    return staffRequest('POST', '/billing/invoices', body);
  },

  /**
   * PATCH /api/v1/billing/invoices/{id}/status  ✅ LIVE
   * @param {number} id
   * @param {string} status — pending|paid|failed|insurance_pending
   */
  async updateInvoiceStatus(id, status) {
    return staffRequest('PATCH', `/billing/invoices/${id}/status`, { status });
  },

  // ── Tasks ─────────────────────────────────────────────────────────

  /**
   * GET /api/v1/tasks  ✅ LIVE
   * @param {{ status?: string, assignee_id?: number }} params
   */
  async listTasks(params = {}) {
    const qs = new URLSearchParams();
    if (params.status)      qs.set('status',      params.status);
    if (params.assignee_id) qs.set('assignee_id', String(params.assignee_id));
    const query = qs.toString() ? '?' + qs.toString() : '';
    return staffRequest('GET', '/tasks' + query);
  },

  /**
   * POST /api/v1/tasks  ✅ LIVE
   * @param {{ title, priority, assignee_id?, due_date?, notes? }} body
   */
  async createTask(body) {
    return staffRequest('POST', '/tasks', body);
  },

  /**
   * PATCH /api/v1/tasks/{id}/status  ✅ LIVE
   * @param {number} id
   * @param {string} status — todo|in_progress|done
   */
  async updateTaskStatus(id, status) {
    return staffRequest('PATCH', `/tasks/${id}/status`, { status });
  },

  /**
   * DELETE /api/v1/tasks/{id}  ✅ LIVE
   */
  async deleteTask(id) {
    return staffRequest('DELETE', `/tasks/${id}`);
  },

  // ── Settings ──────────────────────────────────────────────────────

  /**
   * GET /api/v1/settings/clinic  ✅ LIVE
   */
  async getClinicSettings() {
    return staffRequest('GET', '/settings/clinic');
  },

  async me() {
    return staffRequest('GET', '/auth/me');
  },

  async listStaffAccounts() {
    return staffRequest('GET', '/admin/staff');
  },

  async createStaffAccount(body) {
    return staffRequest('POST', '/admin/staff', body);
  },

  async updateStaffAccount(id, body) {
    return staffRequest('PATCH', `/admin/staff/${id}`, body);
  },

  async resendStaffInvite(id) {
    return staffRequest('POST', `/admin/staff/${id}/resend-invite`);
  },

  async getAppointmentIntegrationStatus() {
    return staffRequest('GET', '/admin/appointment-integrations');
  },

  /**
   * PATCH /api/v1/settings/clinic  ✅ LIVE
   * @param {{ name?, phone?, address?, timezone?, working_hours? }} body
   */
  async updateClinicSettings(body) {
    return staffRequest('PATCH', '/settings/clinic', body);
  },

  // Patient records are exposed by the same dashboard API through PatientExtended below.
};

// ---------------------------------------------------------------------------
// Patient extended — additional patient endpoints on the dashboard backend
// ---------------------------------------------------------------------------
export const PatientExtended = {
  /**
   * GET /api/v1/patient/records  ✅ LIVE (Phase E, 2026-08-01)
   * Paginated read-only timeline of the patient's signed EMR records.
   * Per docs/API_CONTRACT.md §GET /patient/records
   *
   * Response data:
   *   items[]: { id, chief_complaint, diagnosis, plan, ai_accepted, created_at }
   *   pagination: { total, per_page, current_page, last_page }
   *
   * @param {{ page?: number, per_page?: number }} params
   */
  async getRecords(params = {}) {
    const qs = new URLSearchParams();
    if (params.page)     qs.set('page',     String(params.page));
    if (params.per_page) qs.set('per_page', String(params.per_page));
    const query = qs.toString() ? '?' + qs.toString() : '';
    return request('GET', '/patient/records' + query);
  },
};

// ---------------------------------------------------------------------------
// HTML escape helper — exported so ES module pages can import it directly.
// Rule (SPACE_COORDINATION_PROTOCOL §6): every server-returned string written
// via innerHTML or template literals must pass through escHtml().
// Note: window.MAZCRM.escHtml is the same function exposed for non-module pages.
// ---------------------------------------------------------------------------
export function escHtml(s) {
  return String(s == null ? '' : s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

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
export function requireAuth(redirectTo = '/Frontend/pages/auth/patient-login.html', scope = 'patient') {
  if (!isAuthenticated(scope)) {
    window.location.replace(redirectTo);
    return false;
  }
  return true;
}

/**
 * Connectivity preflight (Bug J). Performs a lightweight HEAD/GET against the
 * dashboard health surface to surface SSL/DNS/host-down failures before the
 * patient submits the OTP form. Returns null when the dashboard is reachable,
 * or a classified error object (same shape thrown by request()) when it is not.
 * Call this on patient-login.html boot to show an actionable banner early.
 */
export async function checkConnectivity() {
  try {
    // A tiny no-op request to the canonical API base. We do not require a 2xx;
   // any HTTP response (even 404/405) proves the host is reachable and TLS is
    // valid. Only a network-level rejection means the dashboard is unreachable.
    await fetch(`${API_BASE}/auth/otp/send`, {
      method: 'HEAD',
      // HEAD is not registered; the server returns 405, which is fine here.
      cache: 'no-store',
    });
    return null;
  } catch (err) {
    return classifyNetworkError(err);
  }
}

// ---------------------------------------------------------------------------
// Mid-session 401 redirect.
// Call as the FIRST LINE of every data-loading catch block on staff/patient
// pages. If the error is a 401 the token is cleared and the user is sent to
// the login page — nothing else runs. Returns true when a redirect happened
// (so callers can early-return if they need to).
//
// Usage:
//   } catch (err) {
//     if (redirectOn401(err, '../auth/login.html')) return;
//     renderError(el, err);
//   }
// ---------------------------------------------------------------------------
export function redirectOn401(err, loginPath = '../auth/login.html', scope = 'patient') {
  if (err && err.httpStatus === 401) {
    clearToken(scope);
    window.location.replace(loginPath);
    return true;
  }
  return false;
}

/*
 * ============================================================================
 *  End of file — MAZ//ID
 * ============================================================================
 */
