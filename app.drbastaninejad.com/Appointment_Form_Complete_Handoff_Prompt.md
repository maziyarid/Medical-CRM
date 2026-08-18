# Appointment Form — Complete Copy/Paste Handoff Prompt

## How to use this document

Copy everything in this file into a new chat. It is intended to replace the need to re-upload the source merely to explain the project. It contains the current architecture, every form field and mapping, the complete method inventory, security and storage behavior, the latest fixes, known current-state constraints, deployment mapping, and an authoritative source snapshot.

If a future task requires editing the live files, work from the source snapshot below or from the ZIP that accompanies this document. Never invent credentials. Never print, copy, or commit real .env values, SMS credentials, the Google Sheet shared secret, stored OTP records, signatures, logs, or patient submissions.

---

# BEGIN PROMPT FOR A NEW CHAT

You are maintaining the Persian RTL online patient intake/appointment form for Dr. Shahin Bastaninejad. Treat this handoff as the complete current project context and source of truth. Preserve all unrelated behavior. Ask before making any assumption that materially changes data collection, validation, treatment wording, storage, API behavior, or deployment structure.

## 1. Project identity and current status

- Canonical site: https://app.drbastaninejad.com/
- UI language and direction: Persian (fa-IR), right-to-left.
- Main purpose: new-patient intake, identity verification by SMS OTP, personal information collection, medical history, consent, signature, and append-only submission to a Google Sheet.
- Current public frontend cache revision: v=11 for both config.js and app.js.
- Current handoff date: 2026-07-28.
- Current Google spreadsheet:
  - Spreadsheet ID: 1skaf_j5lkfKY1l3UiwWwgl9M-laW_gllQFMpxOlQuIg
  - Title: Appointments
  - Tab: SmartFormat
  - Time zone: Asia/Tehran
  - Frozen header row: row 1
  - The sheet was inspected read-only. Do not edit it unless explicitly asked.

## 2. Latest requested fixes already implemented

These changes are complete and must not be reverted:

1. Optional referrer number:
   - The domain field is morefmob.
   - The frontend input still has DOM id work-phone for compatibility, but its visible label is now “شماره معرف (اختیاری)”.
   - It has no required attribute.
   - The frontend sends digits only and sends an empty string when blank.
   - IntakeService has an explicit REQUIRED_COLUMNS constant and morefmob is deliberately absent from it.
   - The Google Sheet column remains present so a blank cell is written in the expected position.

2. OTP is validated only at activation:
   - The SMS code is checked in OtpService.verify().
   - After a successful check, the OTP record status becomes verified.
   - verified_at and verified_expires_at are recorded.
   - code_hash is removed after activation.
   - A repeated verify request for an already-verified, unexpired token returns the remaining verified-session lifetime without checking the SMS code again.
   - Final submission never resubmits or rechecks the SMS code. IntakeService only calls assertVerified() to confirm that the server-side token belongs to the patient phone, has verified status, and is still within the verified-session window.

3. Separate exact 30-minute verified-session lifetime:
   - The short pre-verification SMS-code TTL remains separate and configurable through SMS_OTP_TTL_SECONDS, clamped by OtpService.send() to 60–600 seconds, default 180 seconds.
   - Successful activation starts a new, exact 1,800-second window using OtpService::VERIFIED_TTL_SECONDS.
   - verify() returns expiresIn=1800 and expiresAt on first activation.
   - assertVerified() checks verified_expires_at, not the original SMS-code expires_at.
   - Legacy verified records without verified_expires_at fall back to verified_at + 1800 seconds.

4. Frontend countdown:
   - config.js defines VERIFIED_SESSION_TTL_SECONDS: 1800.
   - The countdown begins only after successful SMS activation.
   - It uses an absolute Date.now()-based deadline so background tabs do not pause the real expiry.
   - It is shown globally above the form and displays Persian digits.
   - It turns warning/orange at five minutes or less.
   - At 00:00 it marks the verification expired, clears the token and code, hides code controls, changes the send button to “ارسال کد جدید”, preserves already-entered non-OTP form values, returns the user to step 1, and requests a new code.
   - Submission checks the local remaining time before posting; the backend remains authoritative.
   - The timer is stopped and hidden after successful submission.

5. Red contraindication notice:
   - There is exactly one red notice.
   - It was moved out of the final consent box and placed immediately after the introductory section, before the progress navigation and before any form fields.
   - Users therefore see the restrictions before deciding whether to continue.
   - Its treatment wording and list were not changed.

6. Frontend asset cache:
   - index.html loads /config.js?v=11 and /app.js?v=11.

## 3. Deployment layout

The uploaded filenames had numeric suffixes, but their real deployment names and locations are:

| Uploaded snapshot | Deployment path | Purpose |
| --- | --- | --- |
| index(5).html | DocumentRoot/index.html | Complete RTL form and inline CSS |
| app(2).js | DocumentRoot/app.js | Frontend state, validation, OTP, signature, payload, submission |
| config(3).js | DocumentRoot/config.js | Public API/config constants only |
| logo(3).svg | DocumentRoot/logo.svg | Brand/fav icon |
| robots(4).txt | DocumentRoot/robots.txt | Search crawler rules |
| sitemap(1).xml | DocumentRoot/sitemap.xml | Canonical sitemap |
| llms(1).txt | DocumentRoot/llms.txt | LLM-readable site description |
| .user(1).ini | DocumentRoot/.user.ini | PHP runtime limits and error logging |
| _bootstrap(1).php | DocumentRoot/api/_bootstrap.php | Finds and loads the private bootstrap |
| health(1).php | DocumentRoot/api/health.php | Public backend health response |
| send(1).php | DocumentRoot/api/otp/send.php | OTP send endpoint |
| verify(1).php | DocumentRoot/api/otp/verify.php | OTP activation endpoint |
| submit(1).php | DocumentRoot/api/intake/submit.php | Final intake endpoint |
| bootstrap(1).php | app_private/bootstrap.php | Private autoload/config/storage bootstrap |
| helpers(1).php | app_private/src/helpers.php | Shared global PHP helpers |
| Env(1).php | app_private/src/Env.php | .env parser |
| Security(1).php | app_private/src/Security.php | Normalization and signature security |
| Http(1).php | app_private/src/Http.php | HTTP transport |
| TsmsClient(2).php | app_private/src/TsmsClient.php | TSMS integration |
| SheetClient(2).php | app_private/src/SheetClient.php | Apps Script/Google Sheet integration |
| OtpService(1).php | app_private/src/OtpService.php | OTP lifecycle |
| IntakeService(1).php | app_private/src/IntakeService.php | Intake validation, backup, and Sheet append |
| .htaccess(4) | app_private/.htaccess | Denies direct web access |

The private directory must remain outside the public DocumentRoot. The public API bootstrap searches APP_PRIVATE_ROOT first and then several parent-directory app_private locations.

## 4. End-to-end request flow

1. Browser opens index.html.
2. config.js creates frozen window.TAJ_CONFIG.
3. app.js merges defaults with TAJ_CONFIG and initializes selects, dates, conditional fields, signature capture, and event handlers.
4. A GET health check calls /api/health.php with a 15-second timeout. Warnings appear in the orange api-warning box.
5. Step 1 collects first name, last name, and the patient mobile.
6. “ارسال کد” POSTs JSON {phone} to /api/otp/send.php.
7. OtpService normalizes the phone, applies phone/IP rate limits, generates a cryptographically random token and code (or configured demo code), stores a hashed code, sends through TSMS, and returns token plus the short code TTL.
8. The browser exposes the OTP field and supports manual activation and WebOTP auto-detection.
9. “تأیید کد” POSTs {phone, token, code} to /api/otp/verify.php.
10. OtpService validates the code only during activation, marks the record verified, removes code_hash, starts the 1,800-second verified window, and returns its remaining lifetime.
11. The browser starts the visible 30-minute timer and moves to step 2.
12. Step 2 gathers personal, address, insurance, contact, reason, and referral information.
13. Step 3 gathers optional email, medical history, medication use, specific-condition details, consent, and a handwritten canvas signature.
14. buildPayload() constructs the backend/Sheet payload and places otpToken, signature, insurance type, and user agent in _meta.
15. Final submit checks local activated state and remaining time; it does not call the OTP verify endpoint.
16. /api/intake/submit.php sends the JSON to IntakeService.submit().
17. IntakeService checks the honeypot, sanitizes the defined columns, enforces REQUIRED_COLUMNS, validates core values, calls assertVerified(), validates/saves the PNG signature, and writes a pending JSON backup.
18. SheetClient sends the row to the configured Google Apps Script Web App as application/x-www-form-urlencoded with fields secret and payload.
19. If the Sheet append succeeds, the pending file is moved to submitted, the OTP token is marked consumed, and a reference plus Sheet row number is returned.
20. The frontend hides the form/progress/timer and shows the success panel.

## 5. Public frontend configuration

window.TAJ_CONFIG is frozen and currently contains:

- OTP_SEND_URL: /api/otp/send.php
- OTP_VERIFY_URL: /api/otp/verify.php
- SUBMIT_URL: /api/intake/submit.php
- HEALTH_URL: /api/health.php
- API_TIMEOUT_MS: 45000
- WEB_OTP_ENABLED: true
- OTP_CODE_LENGTH: 5
- VERIFIED_SESSION_TTL_SECONDS: 1800
- SMS_PROVIDER_LABEL: سامانه پیامک طوبی (TSMS)

Never put TSMS usernames, passwords, Sheet secrets, or other private settings in config.js.

## 6. Frontend state

app.js keeps one closure-local state object:

- step: active wizard step, initially 1.
- otpToken: server-issued 48-character token.
- otpVerified: local activated/unexpired flag.
- otpAbort: AbortController for WebOTP listening.
- verifiedUntil: absolute browser timestamp for the activated session.
- verifiedTimer: interval handle for the one-second countdown.
- submitting: prevents expiry navigation while a final request is in flight.

## 7. Complete app.js method inventory

- $(id): document.getElementById shorthand.
- toEnglish(value): converts Persian and Arabic-Indic numerals to Latin digits.
- digits(value): normalizes numerals and removes every non-digit.
- mobile(value): validates an Iranian mobile as exactly 09 plus nine digits.
- isValidNationalId(code): frontend Iranian national-ID checksum; accepts 8–10 digits, left-pads to 10, rejects repeated digits, and checks modulo 11.
- toast(message,type,duration): displays one timed toast and clears its previous timer.
- fieldGroup(el): finds the nearest .input-group.
- invalid(el,message): applies invalid styling and writes its field error.
- valid(el): clears invalid styling and error text.
- setBusy(button,busy,busyText,normalText): disables/enables a button and swaps text.
- toPersianDigits(value): converts Latin timer digits to Persian digits.
- verifiedSecondsRemaining(): calculates remaining seconds from verifiedUntil and Date.now().
- formatRemainingTime(seconds): renders MM:SS in Persian digits.
- stopVerificationTimer(hide): clears the interval and optionally hides/resets the timer box.
- expireVerifiedSession(showToast): clears verified state/token/code, switches the OTP UI to “get a new code”, shows 00:00, and returns to step 1 unless submit is in flight.
- updateVerificationTimer(): refreshes text, enables the five-minute warning style, and expires at zero.
- startVerificationTimer(seconds): uses the server expiresIn value or the 1800-second config fallback and starts an absolute-deadline interval.
- api(url,payload): XMLHttpRequest JSON POST wrapper with credentials, JSON parsing, timeout/error handling, and server message propagation.
- updateSelect(select): toggles has-value for floating select labels.
- fillBirthDates(): creates day 1–31, month 1–12, and Jalali year 1405 down to 1300 options.
- goToStep(number): swaps active form/progress classes, resizes signature on step 3, and scrolls to the top.
- resetOtp(): invalidates and hides OTP UI/timer whenever the patient phone changes.
- validateIdentity(): validates first name, last name, and patient mobile before sending an OTP.
- verifyOtp(automatic): validates code length, posts activation once, starts the verified-session timer, and advances to step 2.
- listenForWebOtp(): uses the WebOTP Credential Management API on a secure context when supported.
- updateInsurance(): exposes and requires insurance-other only when “سایر موارد” is chosen.
- updateTehran(): exposes the Tehran district select only for province “تهران”.
- validateStep2(): checks required personal/address/referral fields, father/city lengths, national ID, optional emergency mobile rules, and conditional insurance text.
- signature.position(event): maps mouse/touch/pointer coordinates into canvas space.
- signature.configure(): sets rounded dark pen stroke parameters.
- signature.resize(preserve): applies device-pixel-ratio sizing and preserves an existing drawing.
- signature.start(event): begins drawing and marks the signature nonempty.
- signature.move(event): draws the current stroke.
- signature.stop(event): ends drawing.
- signature.resize, signature.isEmpty, signature.data: public closure interface.
- validateStep3(): requires consent and signature and requires an explanation only when the patient selected “دارم”.
- insuranceName(): returns either selected insurance or custom insurance text.
- buildPayload(): builds all API/Sheet fields and metadata.
- patient-form submit handler: enforces activated/unexpired state, step-3 validation, busy state, final POST, success state, and expiry recovery.
- final health-check IIFE: GETs the health endpoint and exposes backend/configuration warnings.

Event wiring also covers select label updates, phone-reset behavior, send/verify buttons, automatic OTP-length activation, insurance/province conditionals, specific-condition radios, step navigation, canvas pointer/touch/mouse events, signature clearing, responsive signature resizing, and orientation change.

## 8. Form steps, fields, and exact payload mapping

### Step 1 — identity

| UI id | Meaning | Requirement | Payload/API |
| --- | --- | --- | --- |
| first-name | نام | Required; 2–30 characters | FirstName |
| last-name | نام خانوادگی | Required; 2–30 characters | LastName |
| verification-phone | شماره همراه بیمار | Required Iranian mobile | Mobile and OTP phone |
| verification-code | SMS code | Required only for activation; 4–6 digits | verify endpoint code only |

### Step 2 — personal information

| UI id or derived value | Meaning | Requirement | Payload |
| --- | --- | --- | --- |
| father-name | نام پدر | Required; frontend 2–30 | FatherName |
| occupation | شغل | Required | CodeJob |
| national-id | کد ملی | Required/checksummed | CodeMeli |
| insurance-type | نوع بیمه | Optional | Used in Description and _meta.insuranceType |
| insurance-other | نام بیمه یا سازمان | Required only for “سایر موارد” | Same as above |
| birth-day | روز تولد | Required | TavalodDay |
| birth-month | ماه تولد | Required | TavalodMonth |
| birth-year | سال تولد شمسی | Required | TavalodYear |
| province | استان | Required | Part of HomeAd |
| city | شهر | Required; 2–40 | Part of HomeAd |
| tehran-district | منطقه تهران | Conditionally shown; currently not required | Part of HomeAd |
| address-details | جزئیات آدرس | Required; HTML minlength 5 | Part of HomeAd |
| home-phone | تلفن منزل | Optional | HomeTel |
| work-phone | شماره معرف | Optional | morefmob |
| mobile-phone-2 | شماره همراه اضطراری | Frontend optional, must differ if present | Mobile2 |
| visit-reason | علت مراجعه | Required | VisitReason and embedded in Description |
| referral | نحوه آشنایی | Required | CodeAshnaei |
| fixed value | کد بیمه | Fixed | CodeBimeh='1' |
| fixed value | انتقال | Fixed | IsTransfer='0' |

Occupation codes:

- 1 آزاد
- 2 کارمند
- 3 دانش آموز
- 4 خانه دار
- 5 معلم
- 6 مهندس
- 1003 دانشجو
- 1004 حسابدار و مالی
- 1005 مدیر
- 1006 پزشک یا رشته های پزشکی
- 1007 هنرمند

Referral codes:

- 41 اینترنت، سایت، گوگل
- 43 اینستاگرام
- 46 بیماران قبلی
- 48 تابلو
- 54 آریوژن
- 55 پزشکان کلینیک
- 57 پرسنل کلینیک
- 45 نزدیکی محل سکونت
- 58 چت سایت
- 52 کانتر نیایش
- 50 بیمه ها، سایت بیمه
- 47 پیامک
- 44 بنر تبلیغاتی
- 42 تراکت تبلیغاتی
- 56 مراکز همکار

Visit reasons currently offered:

- جراحی بینی اولیه
- جراحی ترمیمی

Province options contain every Iranian province plus “سایر”. Tehran district options are 1–22. The full insurance option list is authoritative in index.html below.

HomeAd is composed as: province، city، optional “منطقه N”، address-details.

Description is composed from nonempty segments separated by “ | ”:

- علت مراجعه: selected visit reason
- ناراحتی خاص: دارم/ندارم
- توضیح: specific-condition explanation
- نوع بیمه: selected/custom insurance

### Step 3 — medical history and consent

| UI id/class | Meaning | Payload |
| --- | --- | --- |
| patient-email | Optional email | Email (see known current-state notes) |
| .med-hist | Medical-history checkboxes | difficult as slash-separated codes |
| specific-condition radio | Any other notable condition | Embedded in Description |
| specific-condition-explanation | Conditional details | Embedded in Description |
| .drug-chk | Current medications | drugs as slash-separated codes |
| confirmation-check | Consent | Client-side validation only |
| signature-pad | Handwritten PNG signature | _meta.signature |

Medical-history values:

- 1 cardiovascular disease
- 2 diabetes
- 3 high blood pressure
- 5 thyroid over/underactivity
- 7 ovarian cyst
- 8 neurological disease/epilepsy
- 9 drug/food allergy
- 10 pulmonary disease/asthma
- 11 kidney disease
- 12 pregnancy

Drug values:

- 1 blood-pressure medication
- 2 immunosuppressant/corticosteroid
- 3 anticoagulant
- 4 thyroid medication
- 5 psychiatric/neurological medication
- 6 aspirin

### Metadata and protection fields

- _website: hidden honeypot; any nonempty value rejects the submission.
- _source: location.href, limited to 500 characters server-side.
- _meta.otpToken: activated server token.
- _meta.signature: canvas PNG data URL.
- _meta.insuranceType: normalized selected/custom insurance.
- _meta.ua: navigator.userAgent; sent but not currently copied into the saved IntakeService record.

## 9. Backend Sheet schema

IntakeService::COLUMNS and the live SmartFormat header are:

1. FirstName
2. LastName
3. FatherName
4. TavalodDay
5. TavalodMonth
6. TavalodYear
7. HomeTel
8. Mobile
9. Mobile2
10. CodeAshnaei
11. CodeBimeh
12. CodeMeli
13. CodeJob
14. HomeAd
15. Description
16. IsTransfer
17. drugs
18. difficult
19. morefmob

IntakeService::REQUIRED_COLUMNS currently contains:

- FirstName
- LastName
- FatherName
- TavalodDay
- TavalodMonth
- TavalodYear
- Mobile
- Mobile2
- CodeAshnaei
- CodeMeli
- CodeJob
- HomeAd
- IsTransfer

morefmob is intentionally not required.

## 10. Known current-state constraints and mismatches

These are factual properties of the current code. Do not silently change them unless the user explicitly requests it:

1. The frontend labels Mobile2 (emergency mobile) optional, but IntakeService still includes Mobile2 in REQUIRED_COLUMNS and always applies Security::mobile(). A truly blank Mobile2 will therefore be rejected by the current backend.
2. The frontend national-ID validator accepts 8–10 digits and left-pads internally for checksum validation, but buildPayload sends the entered digits unchanged and Security::nationalId() requires exactly 10 digits.
3. buildPayload sends Email, but Email is not in IntakeService::COLUMNS, so the current backend discards it.
4. buildPayload sends VisitReason separately, but VisitReason is not a Sheet column. It is still preserved inside Description.
5. _meta.ua is sent but is not saved into IntakeService’s record.
6. Tehran district is conditionally shown but not required.
7. morefmob is digit-normalized in the browser but is only generic sanitized text in IntakeService; there is no separate backend phone-format rule for it.
8. CodeBimeh is hard-coded to '1'; the human-readable insurance name is stored in Description, the pending/submitted backup record’s insurance field, and not in a separate Sheet insurance-name column.
9. The consent checkbox itself is enforced only in the frontend; the server verifies the signature but receives no separate consent boolean.
10. Http.php(1).bak is an old non-authoritative backup and must not replace the active Http.php.

## 11. PHP helper and class method inventory

### Global helpers.php

- mb_strlen fallback: Unicode-aware character counting if mbstring is unavailable.
- mb_substr fallback: Unicode-aware slicing if mbstring is unavailable.
- env(key,default): reads $_ENV/getenv and treats false/null/empty as default.
- env_bool(key,default): FILTER_VALIDATE_BOOL with safe fallback.
- request_json(max): enforces CONTENT_LENGTH, reads php://input, JSON-decodes to an array, and throws on malformed input.
- json_response(data,status): no-store JSON response using Unicode/slash-preserving encoding, then exits.
- require_post(): returns HTTP 405 JSON for non-POST requests.
- client_ip(): currently uses REMOTE_ADDR only.
- app_log(event,context): appends one JSON line to storage/logs/app.log.

### App\Env

- load(path): parses nonempty, noncomment KEY=VALUE lines; strips matching quotes; translates escaped newline/carriage/tab; stores in $_ENV and putenv.

### App\Security

- digits(value): Persian/Arabic numeral conversion and non-digit removal.
- mobile(value): requires /^09\d{9}$/ or throws.
- nationalId(value): requires exactly 10 digits or throws; backend does not repeat the frontend checksum.
- text(value,max): trims, removes control characters, and Unicode-truncates.
- saveSignature(dataUrl): requires PNG base64 data URL, strict base64 decode, 100–1,500,000 bytes, PNG magic bytes, randomized filename, LOCK_EX write, and mode 0640.

### App\Http

- request(method,url,options): cURL-first HTTP client with method, headers, body, timeouts, compression, no automatic redirect following, and one manually handled redirect. It falls back to allow_url_fopen streams. It returns status, body, headers, error, and effective URL.

### App\TsmsClient

- send(phone,message): normalizes the mobile, supports demo logging, validates TSMS configuration, sends a GET request, maps documented TSMS error codes, validates a conservative printable message ID, and returns messageId/raw.
- credit(): calls the TSMS credit mode and returns the parsed result.
- configuration(): reads TSMS_API_URL, TSMS_USERNAME, TSMS_PASSWORD, and TSMS_FROM; requires configured credentials; permits only http/https, host tsms.ir or www.tsms.ir, and exact path /url/tsmshttp.php.
- parseResponse(raw): removes UTF-8 BOM, tags, entities, and whitespace; rejects empty results.

Mapped TSMS error codes:

- 1 server error
- 2 UDH too long
- 3 invalid destination
- 4 invalid variables
- 5 empty text
- 6 empty destination
- 7 invalid username/password
- 8 temporary provider error
- 9 service disconnected
- 14 insufficient credit

### App\SheetClient

- append(row): demo-logs when SHEET_DRIVER=demo; otherwise validates the Apps Script URL and shared secret, posts form-encoded secret plus JSON payload, handles transport/HTTP/non-JSON/rejection errors, and returns the Apps Script JSON response.
- health(): GETs the Web App and requires JSON with ok=true.

### App\OtpService

- VERIFIED_TTL_SECONDS: private constant 1800.
- send(phone,ip): mobile normalization, rate limiting, code/token generation, short code TTL, hashed code storage, TSMS send, status updates, and response.
- verify(phone,token,code): validates phone/token; returns remaining lifetime for already-verified records; for sent records checks short expiry, attempts, and password hash; marks verified; creates verified expiry; removes code hash; returns the 30-minute lifetime.
- assertVerified(phone,token): checks phone ownership, verified status, and verified-session remaining time. It does not validate the SMS code.
- consume(token): marks a token consumed with consumed_at.
- path(token): maps token to storage/otp/TOKEN.json.
- load(token): requires 48 hexadecimal characters and JSON-decodes the record.
- save(record): JSON-encodes with LOCK_EX and mode 0640.
- verifiedExpiry(record): uses verified_expires_at or legacy verified_at + 1800.
- verifiedRemaining(record): max(0, expiry-time()).
- rateLimit(phone,ip): scans OTP JSON records, removes records older than one day, counts phone requests over 10 minutes and IP requests over one hour, and enforces configured limits.

### App\IntakeService

- COLUMNS: the exact 19-column Sheet order.
- REQUIRED_COLUMNS: explicit required backend fields; excludes morefmob.
- submit(payload,ip):
  1. rejects honeypot;
  2. extracts _meta/otpToken;
  3. builds and sanitizes row in COLUMNS order;
  4. enforces REQUIRED_COLUMNS;
  5. validates first/last lengths;
  6. normalizes Mobile/Mobile2 and national ID;
  7. rejects equal patient/emergency mobiles;
  8. calls OtpService.assertVerified();
  9. saves signature;
  10. creates UUID and pending backup;
  11. appends to Sheet;
  12. moves pending to submitted;
  13. consumes OTP;
  14. returns success, a ten-character uppercase reference prefix, and Sheet row.

## 12. API endpoints

### GET /api/health.php

- Loads private bootstrap.
- Checks writable logs, otp, signatures, pending, submitted directories.
- Warns if neither cURL nor allow_url_fopen is available.
- Warns if TSMS_FROM is missing/placeholder.
- Warns if SHEET_WEBHOOK_URL is not a Google Apps Script exec URL.
- Returns success, PHP version, cURL availability, privateRoot=true, warnings.

### POST /api/otp/send.php

- Maximum JSON request size: 50,000 bytes.
- Request: {phone}
- Success: HTTP 200 with success, token, expiresIn, message.
- Failure: HTTP 422 with success=false and message; logs otp_send_error.

### POST /api/otp/verify.php

- Maximum JSON request size: 50,000 bytes.
- Request: {phone,token,code}
- Success: HTTP 200 with success, token, verified-session expiresIn/expiresAt, message.
- Failure: HTTP 422; logs otp_verify_error.

### POST /api/intake/submit.php

- Maximum JSON request size: 2,500,000 bytes.
- Success: HTTP 201 with success, reference, sheetRow.
- Failure: HTTP 422; logs intake_submit_error.

Public API bootstrap also sends X-Content-Type-Options: nosniff and Referrer-Policy: strict-origin-when-cross-origin.

## 13. Private bootstrap, filesystem, and retention behavior

- APP_ROOT is the app_private directory.
- PSR-like autoload maps App\ClassName to app_private/src/ClassName.php.
- helpers.php is required directly.
- .env is loaded from app_private/.env.
- Default timezone is Asia/Tehran.
- The following private storage directories are created with 0750:
  - logs
  - otp
  - signatures
  - pending
  - submitted
- OTP and saved-record files use LOCK_EX where written.
- OTP/signature files are chmod 0640.
- OTP cleanup currently occurs opportunistically during send() and removes records older than 24 hours.
- app_private/.htaccess contains “Require all denied”.
- Public .user.ini disables display_errors, enables log_errors, and sets post_max_size=4M, max_input_time=60, and max_execution_time=60.

## 14. Environment key names

Values are intentionally excluded. Required/recognized names are:

- APP_ENV
- APP_DEBUG
- APP_URL
- APP_TIMEZONE
- SMS_PROVIDER
- TSMS_API_URL
- TSMS_USERNAME
- TSMS_PASSWORD
- TSMS_FROM
- TSMS_SEND_PARAM
- TSMS_CREDIT_PARAM
- TSMS_STATUS_PARAM
- SMS_OTP_LENGTH
- SMS_OTP_TTL_SECONDS
- SMS_MESSAGE_TEMPLATE
- OTP_MAX_ATTEMPTS
- OTP_PHONE_LIMIT_10_MIN
- OTP_IP_LIMIT_60_MIN
- SHEET_DRIVER
- SHEET_WEBHOOK_URL
- SHEET_SHARED_SECRET
- SMS_DEMO_CODE

Do not ask the user to paste secret values into chat. Use their server’s secure environment/configuration workflow.

## 15. UI, styling, responsiveness, accessibility, and SEO

- RTL Persian Tahoma/Arial/system font stack.
- White rounded form card on a porcelain dotted background.
- Core design variables:
  - evergreen #2F7D32
  - evergreen-dark #246b28
  - graphite #25272C
  - porcelain #F7F8F6
  - soft-sage #E4F0E4
  - brass #B6905E
  - muted #6A7078
  - border #DDE2DD
  - error #DC2626
  - success #16A34A
- Three-step progress navigation with active/done states.
- Floating-label text/select controls.
- Responsive two-column grids collapsing at 640px.
- Mobile safe-area padding, 16px inputs to avoid iOS zoom, full-width buttons, touch-safe signature pad, and no horizontal overflow.
- Canvas supports Pointer Events and touch/mouse fallback; device-pixel ratio is capped at 3.
- Toast messages use aria-live status.
- OTP status and countdown are visible status areas.
- Form uses novalidate because validation is custom.
- A hidden fixed-position website honeypot is inaccessible to ordinary users.
- Noscript warning explains that JavaScript is required.
- Success panel shows returned reference when present.
- Structured data includes WebPage, MedicalWebPage, Physician, RegisterAction, and BreadcrumbList.
- Canonical/hreflang, Open Graph, Twitter, robots, Googlebot, Bingbot, application/theme metadata, favicon, sitemap, robots.txt, and llms.txt are present.
- Canonical origin is app.drbastaninejad.com.

## 16. Red medical restriction notice — exact meaning

The top red notice says treatment may be impossible or require physician approval for:

- high blood pressure and related medication;
- diabetes, even controlled;
- autoimmune disease including MS, rheumatism, Wegener’s, Hashimoto’s, Graves’, lupus, scleroderma, and others;
- prior lip lift/central lift surgery;
- age over 45;
- epilepsy;
- kidney disorders;
- tumors or chemotherapy history.

It also warns that responsibility for consequences of concealed disease history or false information rests with the patient. Preserve this medical/legal wording unless the user explicitly supplies replacement wording.

## 17. Error and user-feedback behavior

- Frontend API errors use the backend message when supplied.
- Non-JSON backend response reports HTTP status.
- Status 0 reports connection/API path trouble.
- XHR error mentions PHP/API/SSL/private path.
- API timeout reports that the response took too long.
- OTP send/verify, final submit, and health errors are surfaced in Persian.
- Verified-session expiry has its own message and is no longer called “کد منقضی شده” after activation.
- Sheet write failure says a backup was saved but Google Sheet registration failed; the pending file remains.

## 18. Verification already performed for the latest fixes

- JavaScript syntax checks passed for app.js and config.js.
- Every supplied active PHP file parsed successfully.
- A simulated DOM/browser flow passed:
  - initial notice/order/IDs;
  - send code;
  - activate code;
  - show 30-minute timer;
  - navigate steps;
  - consent/signature;
  - submit once;
  - verify endpoint called only during activation;
  - blank morefmob preserved;
  - success hides timer;
  - one-second test lifetime returns to step 1 at 00:00.
- An actual PHP 8.3 runtime behavior test passed:
  - morefmob absent from REQUIRED_COLUMNS;
  - first activation returns 1800;
  - verified_expires_at - verified_at = 1800;
  - code_hash removed;
  - expired short code timestamp no longer invalidates an activated token;
  - repeat verify does not recheck the code;
  - expired verified session is rejected.

## 19. Rules for future modifications

1. Make only requested changes; preserve unrelated fields, mappings, copy, storage, endpoints, and styling.
2. Ask before changing medical/legal text, required fields, Sheet schema/order, authentication lifetime, or deployment layout.
3. Keep app_private outside DocumentRoot and never expose .env/storage.
4. Keep config.js public-only.
5. If app.js or config.js changes, bump both query-string revisions in index.html.
6. Keep the live Sheet header order synchronized with IntakeService::COLUMNS.
7. Do not validate the SMS code at final submit; only assert server-side activated token status/lifetime.
8. Keep the verified lifetime at exactly 1800 seconds unless explicitly asked otherwise.
9. Keep morefmob optional in both UI and backend.
10. Preserve the top-of-page red notice unless explicitly asked to change or remove it.
11. Re-run JavaScript syntax, PHP syntax/runtime, OTP lifecycle, timer expiry, empty-morefmob, full form submission, duplicate-ID, and mobile layout checks after relevant changes.
12. Never include real .env values, OTP JSON, logs, signatures, pending/submitted patient data, or Sheet rows in a prompt or archive.

## 20. Excluded sensitive or non-authoritative material

- Real app_private/.env values are intentionally excluded.
- storage/otp JSON records are intentionally excluded.
- storage/signatures, logs, pending, and submitted patient data are intentionally excluded.
- Http.php(1).bak is excluded because active Http.php is authoritative.
- No Google Sheet patient rows are included.

---

## 21. Authoritative current source snapshot

The following source files are the current project snapshot. Deploy them using the logical paths in each heading. Secrets and stored data remain excluded.

## public/index.html

```html
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1, viewport-fit=cover" name="viewport"/>
<title>تشکیل پرونده آنلاین بیمار | دکتر شاهین باستانی نژاد</title>
<meta content="تشکیل پرونده آنلاین و امن نزد دکتر شاهین باستانی نژاد در کمتر از ۵ دقیقه. ثبت اطلاعات هویتی، تاریخچه پزشکی و تأیید هویت با کد پیامکی." name="description"/>
<meta content="دکتر شاهین باستانی نژاد, تشکیل پرونده بیمار, فرم پذیرش بیمار, پرونده پزشکی, Dr Shahin Bastaninejad" name="keywords"/>
<meta content="Dr Shahin Bastaninejad" name="author"/>
<meta content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1" name="robots"/>
<meta content="index, follow" name="googlebot"/>
<meta content="index, follow" name="bingbot"/>
<meta content="#2F7D32" name="theme-color"/>
<meta content="تشکیل پرونده دکتر شاهین باستانی نژاد" name="application-name"/>
<link href="https://app.drbastaninejad.com/" rel="canonical"/>
<link href="https://app.drbastaninejad.com/" hreflang="fa-IR" rel="alternate"/>
<link href="https://app.drbastaninejad.com/" hreflang="x-default" rel="alternate"/>
<link href="/logo.svg" rel="icon" type="image/svg+xml"/>
<meta content="website" property="og:type"/>
<meta content="fa_IR" property="og:locale"/>
<meta content="دکتر شاهین باستانی نژاد" property="og:site_name"/>
<meta content="تشکیل پرونده — دکتر شاهین باستانی نژاد" property="og:title"/>
<meta content="تشکیل پرونده آنلاین و امن نزد دکتر شاهین باستانی نژاد در کمتر از ۵ دقیقه." property="og:description"/>
<meta content="https://app.drbastaninejad.com/" property="og:url"/>
<meta content="https://app.drbastaninejad.com/logo.svg" property="og:image"/>
<meta content="لوگوی دکتر شاهین باستانی نژاد" property="og:image:alt"/>
<meta content="summary" name="twitter:card"/>
<meta content="تشکیل پرونده آنلاین بیمار | دکتر شاهین باستانی نژاد" name="twitter:title"/>
<meta content="تشکیل پرونده آنلاین و امن نزد دکتر شاهین باستانی نژاد در کمتر از ۵ دقیقه." name="twitter:description"/>
<meta content="https://app.drbastaninejad.com/logo.svg" name="twitter:image"/>
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebPage",
    "name": "تشکیل پرونده — دکتر شاهین باستانی نژاد",
    "alternateName": "Dr Shahin Bastaninejad Patient Registration",
    "url": "https://app.drbastaninejad.com/",
    "description": "فرم رسمی تشکیل پرونده بیمار نزد دکتر شاهین باستانی نژاد.",
    "inLanguage": "fa-IR",
    "isPartOf": {
      "@type": "WebSite",
      "name": "دکتر شاهین باستانی نژاد",
      "url": "https://app.drbastaninejad.com/"
    },
    "mainEntity": {
      "@type": "Physician",
      "name": "دکتر شاهین باستانی نژاد",
      "alternateName": "Dr Shahin Bastaninejad",
      "url": "https://app.drbastaninejad.com/"
    },
    "potentialAction": {
      "@type": "RegisterAction",
      "target": "https://app.drbastaninejad.com/",
      "name": "تشکیل پرونده بیمار"
    }
  }
  </script>
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "MedicalWebPage",
    "name": "تشکیل پرونده آنلاین بیمار",
    "url": "https://app.drbastaninejad.com/",
    "inLanguage": "fa-IR",
    "specialty": "https://schema.org/Dermatologic",
    "lastReviewed": "2026-07-25",
    "about": {
      "@type": "MedicalProcedure",
      "name": "تشکیل پرونده بیمار"
    },
    "reviewedBy": {
      "@type": "Physician",
      "name": "دکتر شاهین باستانی نژاد"
    }
  }
  </script>
<script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
      {"@type": "ListItem", "position": 1, "name": "خانه", "item": "https://app.drbastaninejad.com/"},
      {"@type": "ListItem", "position": 2, "name": "تشکیل پرونده بیمار", "item": "https://app.drbastaninejad.com/"}
    ]
  }
  </script>
<style>
    :root {
      --evergreen: #2F7D32;
      --evergreen-dark: #246b28;
      --graphite: #25272C;
      --porcelain: #F7F8F6;
      --soft-sage: #E4F0E4;
      --brass: #B6905E;
      --muted: #6A7078;
      --border: #DDE2DD;
      --error: #DC2626;
      --success: #16A34A;
      --radius: 1rem;
      --shadow: 0 20px 40px -12px rgba(37, 39, 44, 0.12);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: Tahoma, Arial, system-ui, sans-serif;
      background: var(--porcelain);
      background-image: radial-gradient(circle at 1px 1px, #d5dbd5 1px, transparent 0);
      background-size: 24px 24px;
      color: var(--graphite);
      min-height: 100vh;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 2rem 1rem 4rem;
      line-height: 1.6;
    }
    .form-container {
      width: 100%;
      max-width: 920px;
      background: #fff;
      border-radius: 1.5rem;
      box-shadow: var(--shadow);
      padding: 2.5rem 3rem;
      border: 1px solid var(--border);
    }
    @media (max-width: 640px) {
      .form-container { padding: 1.5rem 1.25rem; border-radius: 1rem; }
    }
    .form-header {
      text-align: center;
      margin-bottom: 2rem;
      padding-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
    }
    .logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1rem;
      margin-bottom: 0.75rem;
    }
    .logo-wrap svg { width: 72px; height: 72px; flex-shrink: 0; }
    .form-header h1 {
      font-size: clamp(1.35rem, 3vw, 1.75rem);
      font-weight: 700;
      color: var(--graphite);
      line-height: 1.3;
    }
    .form-header p { color: var(--muted); font-size: 0.95rem; margin-top: 0.35rem; }
    .progress-bar { display: flex; gap: 0.75rem; margin-bottom: 2rem; }
    .progress-step {
      flex: 1; text-align: center; padding: 0.6rem 0.4rem 0.75rem;
      border-bottom: 3px solid var(--border); color: var(--muted);
      font-size: 0.85rem; font-weight: 500; transition: all 0.25s ease;
    }
    .progress-step.active { border-bottom-color: var(--evergreen); color: var(--evergreen); font-weight: 700; }
    .progress-step.done { border-bottom-color: var(--evergreen); color: var(--graphite); }
    @media (max-width: 480px) {
      .progress-step { font-size: 0.75rem; padding: 0.5rem 0.2rem 0.6rem; }
    }
    .form-step { display: none; }
    .form-step.active { display: block; animation: fadeIn 0.3s ease; }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(6px); }
      to { opacity: 1; transform: none; }
    }
    fieldset { border: none; padding: 0; }
    legend { font-size: 1.15rem; font-weight: 700; color: var(--graphite); margin-bottom: 1.5rem; padding: 0; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1.75rem; }
    @media (max-width: 640px) { .grid-2 { grid-template-columns: 1fr; } }
    .input-group { position: relative; margin-bottom: 1.5rem; }
    .form-input, .form-select {
      width: 100%; border: none; border-bottom: 2px solid var(--border);
      min-height: 48px; padding: 0.72rem 0; background-color: transparent;
      font-size: 1rem; font-family: inherit; color: var(--graphite); border-radius: 0;
      transition: border-color 0.2s, color 0.2s, background-color 0.2s;
    }
    .form-select {
      appearance: none; -webkit-appearance: none; -moz-appearance: none;
      color-scheme: light; cursor: pointer; background-color: #fff;
      color: var(--muted); padding-right: 0.2rem; padding-left: 2.25rem;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='9' viewBox='0 0 14 9'%3E%3Cpath fill='%236A7078' d='M1.64.5 7 5.86 12.36.5 14 2.14l-7 7-7-7z'/%3E%3C/svg%3E");
      background-repeat: no-repeat; background-position: left 0.75rem center;
      background-size: 12px 8px;
    }
    .form-select.has-value { color: var(--graphite); }
    .form-select option { color: var(--graphite); background: #fff; font-family: inherit; }
    .form-select option:disabled { color: #94a3b8; }
    .form-input:focus, .form-select:focus {
      outline: 2px solid transparent; outline-offset: 2px;
      border-bottom-color: var(--evergreen); background-color: #fff;
    }
    .form-label {
      position: absolute; top: 0.7rem; right: 0; color: var(--muted);
      font-size: 1rem; transition: all 0.2s ease; pointer-events: none; white-space: nowrap;
    }
    .form-input:focus ~ .form-label,
    .form-input:not(:placeholder-shown) ~ .form-label,
    .form-select:focus ~ .form-label,
    .form-select.has-value ~ .form-label {
      top: -1rem; font-size: 0.78rem; color: var(--evergreen); font-weight: 500;
    }
    .required-star { color: var(--error); font-weight: 700; margin-left: 2px; }
    .error-message { color: var(--error); font-size: 0.75rem; margin-top: 4px; min-height: 1rem; display: none; }
    .input-group.invalid .error-message { display: block; }
    .input-group.invalid .form-input,
    .input-group.invalid .form-select { border-bottom-color: var(--error); }
    .field-hint {
      display: block; margin-top: 0.35rem; color: var(--muted);
      font-size: 0.75rem; line-height: 1.55;
    }
    .otp-status {
      min-height: 1.35rem; margin: -0.65rem 0 1rem; color: var(--muted);
      font-size: 0.8rem; text-align: center;
    }
    .otp-status.listening { color: var(--evergreen); font-weight: 600; }
    .verification-timer {
      display: flex; align-items: center; justify-content: center; flex-wrap: wrap; gap: 0.55rem;
      margin: 0 0 1.5rem; padding: 0.7rem 0.9rem;
      border: 1px solid #bbf7d0; border-radius: 0.75rem;
      background: #f0fdf4; color: #166534; font-size: 0.85rem;
    }
    .verification-timer strong {
      min-width: 3.5rem; direction: ltr; text-align: center;
      font-size: 1rem; font-variant-numeric: tabular-nums;
    }
    .verification-timer.warning { border-color: #fed7aa; background: #fff7ed; color: #9a3412; }
    .verification-timer.expired { border-color: #fecaca; background: #fef2f2; color: #991b1b; }
    .subsection-title {
      margin: 0.35rem 0 1.35rem; padding: 0.65rem 0.85rem;
      border-right: 3px solid var(--evergreen); background: var(--soft-sage);
      border-radius: 0.5rem; font-size: 0.95rem; font-weight: 700;
    }
    .form-select optgroup { color: var(--graphite); font-weight: 700; background: #fff; }
    .birth-label { display: block; font-size: 0.85rem; font-weight: 500; color: var(--muted); margin-bottom: 0.5rem; }
    .birth-row { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 0.75rem; }
    .custom-checkbox-wrapper { display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem 1.25rem; }
    @media (max-width: 640px) { .custom-checkbox-wrapper { grid-template-columns: 1fr; } }
    .checkbox-label {
      display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer;
      font-size: 0.875rem; color: #475569; font-weight: 500; line-height: 1.4;
    }
    .checkbox-input {
      width: 1.15rem; height: 1.15rem; flex-shrink: 0; margin-top: 2px;
      accent-color: var(--evergreen); cursor: pointer;
    }
    .radio-row { display: flex; gap: 1.5rem; margin-top: 0.5rem; }
    .radio-label { display: flex; align-items: center; gap: 0.4rem; cursor: pointer; font-size: 0.95rem; }
    .confirmation-box {
      background: var(--soft-sage); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 1.25rem; margin-top: 1.75rem;
    }
    .final-check { display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer; flex-wrap: wrap; }
    .final-check input[type="checkbox"] {
      width: 1.2rem; height: 1.2rem; flex-shrink: 0; margin-top: 3px; accent-color: var(--evergreen);
    }
    .signature-pad-container {
      border: 2px solid var(--border); border-radius: 0.75rem; margin-top: 1rem;
      position: relative; background: #fff; overflow: hidden;
      box-shadow: inset 0 0 0 1px rgba(47,125,50,0.04);
    }
    .signature-pad {
      width: 100%; height: 200px; cursor: crosshair; display: block;
      background: #fff; touch-action: none; user-select: none; -webkit-user-select: none;
    }
    .clear-signature-btn {
      position: absolute; top: 8px; left: 8px; background: var(--porcelain);
      border: 1px solid var(--border); color: var(--muted); font-size: 0.75rem;
      padding: 4px 10px; border-radius: 6px; cursor: pointer; font-family: inherit;
    }
    .clear-signature-btn:hover { background: #fff; color: var(--graphite); }
    .btn, .btn-primary, .btn-secondary {
      font-family: inherit; font-weight: 600; padding: 0.8rem 1.75rem;
      border-radius: 0.75rem; border: none; cursor: pointer; transition: all 0.2s ease; font-size: 0.95rem;
    }
    .btn, .btn-primary {
      background: linear-gradient(135deg, var(--evergreen), var(--evergreen-dark));
      color: #fff; box-shadow: 0 4px 14px rgba(47, 125, 50, 0.28);
    }
    .btn:hover, .btn-primary:hover {
      transform: translateY(-2px); box-shadow: 0 6px 18px rgba(47, 125, 50, 0.35);
    }
    .btn:disabled, .btn-primary:disabled {
      background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none;
    }
    .btn-secondary { background: #e8ece8; color: var(--graphite); box-shadow: none; }
    .btn-secondary:hover { background: #d5dbd5; transform: none; }
    .btn-row { display: flex; justify-content: space-between; gap: 1rem; margin-top: 2rem; flex-wrap: wrap; }
    .btn-row.center { justify-content: center; }
    .hidden { display: none !important; }
    .toast {
      position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(20px);
      background: var(--graphite); color: #fff; padding: 0.75rem 1.5rem; border-radius: 0.75rem;
      z-index: 1000; opacity: 0; transition: opacity 0.3s, transform 0.3s;
      font-size: 0.9rem; max-width: 90vw; text-align: center; pointer-events: none;
    }
    .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
    .toast.error { background: var(--error); }
    .toast.success { background: var(--success); }
    .success-panel { text-align: center; padding: 3rem 1rem; }
    .success-panel .icon {
      width: 72px; height: 72px; margin: 0 auto 1.25rem; background: var(--soft-sage);
      border-radius: 50%; display: flex; align-items: center; justify-content: center;
      color: var(--evergreen); font-size: 2rem;
    }
    .success-panel h2 { font-size: 1.4rem; margin-bottom: 0.5rem; }
    .success-panel p { color: var(--muted); }
    .seo-intro {
      margin: -0.5rem 0 1.75rem;
      padding: 0.9rem 1rem;
      border: 1px solid var(--border);
      border-radius: 0.85rem;
      background: linear-gradient(135deg, rgba(228,240,228,0.72), rgba(247,248,246,0.92));
      color: #475569;
      font-size: 0.9rem;
      text-align: center;
    }
    .noscript-warning {
      margin-bottom: 1.5rem;
      padding: 0.85rem 1rem;
      border: 1px solid #fca5a5;
      border-radius: 0.75rem;
      background: #fef2f2;
      color: #991b1b;
      text-align: center;
      font-size: 0.9rem;
    }
    html { width: 100%; min-height: 100%; overflow-x: hidden; -webkit-text-size-adjust: 100%; }
    body { width: 100%; min-height: 100svh; overflow-x: hidden; }
    .form-container, .grid-2 > *, .birth-row > *, .input-group { min-width: 0; }
    .form-input, .form-select { font-size: 16px; }
    .signature-pad-container { overflow: hidden; background: #fff; }
    .signature-pad { display: block; width: 100%; height: 190px; background: #fff; touch-action: none; user-select: none; -webkit-user-select: none; }
    .api-warning { margin: 0 0 1rem; padding: .8rem 1rem; border-radius: .75rem; border: 1px solid #fed7aa; background: #fff7ed; color: #9a3412; font-size: .82rem; line-height: 1.7; }
    .contraindication-notice {
      margin: 0 0 1.5rem;
      padding: 1.1rem 1.25rem;
      border: 1px solid #fecaca;
      border-right: 4px solid var(--error);
      border-radius: 0.85rem;
      background: #fef2f2;
    }
    .contraindication-notice__title {
      display: flex; align-items: center; gap: 0.5rem;
      font-size: 1rem; font-weight: 700; color: #991b1b; margin-bottom: 0.65rem;
    }
    .contraindication-notice__icon { font-size: 1.1rem; }
    .contraindication-notice__intro { font-size: 0.85rem; color: #7f1d1d; margin-bottom: 0.6rem; line-height: 1.7; }
    .contraindication-notice__list {
      margin: 0 0 0.85rem; padding-right: 1.25rem;
      font-size: 0.85rem; color: #7f1d1d; line-height: 1.85;
    }
    .contraindication-notice__list li { margin-bottom: 0.15rem; }
    .contraindication-notice__warning {
      font-size: 0.8rem; color: #991b1b; background: #fee2e2;
      padding: 0.6rem 0.75rem; border-radius: 0.5rem; line-height: 1.7;
    }
    @media (max-width: 640px) {
      body { display: block; padding: max(.65rem, env(safe-area-inset-top)) max(.55rem, env(safe-area-inset-right)) calc(1.5rem + env(safe-area-inset-bottom)) max(.55rem, env(safe-area-inset-left)); }
      .form-container { max-width: none; padding: 1.15rem .9rem; border-radius: .9rem; }
      .logo-wrap { gap: .55rem; }
      .logo-wrap svg { width: 54px; height: 54px; }
      .form-header h1 { font-size: 1.12rem; }
      .form-header p, .seo-intro { font-size: .8rem; }
      .progress-bar { gap: .25rem; }
      .progress-step { font-size: .7rem; padding-inline: .1rem; }
      .birth-row { gap: .35rem; }
      .birth-row .form-select { padding-left: 1.5rem; background-position: left .35rem center; }
      .btn-row { flex-direction: column-reverse; }
      .btn-row.center { flex-direction: column; }
      .btn, .btn-primary, .btn-secondary { width: 100%; min-height: 48px; }
      .toast { width: calc(100% - 1rem); max-width: none; }
      .contraindication-notice { padding: 0.9rem 1rem; }
      .contraindication-notice__title { font-size: 0.92rem; }
    }

    @media (max-width: 640px) {
      .form-select {
        background-color: #fff !important;
        border-bottom: 2px solid var(--border) !important;
        -webkit-tap-highlight-color: transparent;
        color: var(--graphite) !important;
      }
      .form-select:invalid { color: var(--muted) !important; }
    }

    @media (max-width: 640px) {
      .contraindication-notice { margin: 0 0 1.1rem; padding: 0.85rem 0.9rem; border-radius: 0.7rem; }
      .contraindication-notice__title { font-size: 0.88rem; gap: 0.4rem; margin-bottom: 0.5rem; }
      .contraindication-notice__intro { font-size: 0.78rem; margin-bottom: 0.5rem; line-height: 1.6; }
      .contraindication-notice__list { font-size: 0.78rem; line-height: 1.7; padding-right: 1.05rem; margin-bottom: 0.65rem; }
      .contraindication-notice__warning { font-size: 0.74rem; padding: 0.55rem 0.65rem; line-height: 1.6; }
    }

    @media (max-width: 640px) {
      .signature-pad-container { touch-action: none; }
      .signature-pad { height: 170px; touch-action: none; -ms-touch-action: none; }
    }
  </style>
</head>
<body>
<div class="form-container">
<div class="form-header">
<div class="logo-wrap">
<svg aria-label="لوگوی دکتر شاهین باستانی نژاد" height="72" role="img" viewbox="0 0 512 512" width="72" xmlns="http://www.w3.org/2000/svg">
<defs>
<clippath id="naturalSide">
<path d="M205 10 C242 58 260 91 249 134 C239 174 223 207 238 242 C248 266 277 279 273 300 C270 316 244 318 246 334 C248 349 266 350 263 367 C260 384 237 390 231 409 C225 429 226 451 203 484 C119 469 57 384 57 256 C57 128 119 43 205 10 Z"></path>
</clippath>
<mask height="512" id="faceMask" maskunits="userSpaceOnUse" width="512" x="0" y="0">
<rect fill="white" height="512" width="512"></rect>
<path d="M205 10 C242 58 260 91 249 134 C239 174 223 207 238 242 C248 266 277 279 273 300 C270 316 244 318 246 334 C248 349 266 350 263 367 C260 384 237 390 231 409 C225 429 226 451 203 484" fill="none" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="23"></path>
<path d="M266 35 C299 87 303 126 291 166 C279 205 274 234 294 256 C307 271 331 278 329 296 C327 312 302 316 303 333 C304 348 323 350 320 366 C317 386 294 394 286 415 C280 433 280 451 263 470" fill="none" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="15"></path>
<path d="M283 61 C347 75 397 116 418 173 C426 195 430 219 427 239 C425 252 420 259 414 258 C406 257 408 239 407 227 C404 192 389 164 364 143 C344 126 324 117 303 111" fill="none" stroke="black" stroke-linecap="round" stroke-linejoin="round" stroke-width="13"></path>
</mask>
</defs>
<g clip-path="url(#naturalSide)" fill="#2F7D32">
<rect height="31" rx="15.5" width="150" x="112" y="35"></rect>
<rect height="35" rx="17.5" width="182" x="86" y="81"></rect>
<rect height="38" rx="19" width="202" x="69" y="130"></rect>
<rect height="40" rx="20" width="214" x="59" y="181"></rect>
<rect height="41" rx="20.5" width="219" x="56" y="235"></rect>
<rect height="40" rx="20" width="213" x="61" y="290"></rect>
<rect height="38" rx="19" width="197" x="73" y="344"></rect>
<rect height="35" rx="17.5" width="171" x="94" y="394"></rect>
<rect height="31" rx="15.5" width="129" x="126" y="440"></rect>
</g>
<path d="M211 18 C310 0 394 44 430 126 C446 163 449 204 440 242 C452 232 463 237 462 251 C461 270 446 284 435 295 C435 321 438 353 429 384 C418 423 389 456 352 478 C319 497 276 506 228 493 C251 482 268 467 280 449 C294 428 303 405 301 382 C299 359 282 345 286 326 C290 306 313 299 313 281 C313 261 287 249 276 231 C258 201 262 168 269 136 C277 102 259 69 211 18 Z" fill="#25272C" mask="url(#faceMask)"></path>
</svg>
<div>
<h1>دکتر شاهین باستانی نژاد</h1>
<p>فرم تشکیل پرونده بیمار</p>
</div>
</div>
</div>
<section aria-label="درباره تشکیل پرونده" class="seo-intro">
<p>
        برای تشکیل پرونده اولیه نزد دکتر شاهین باستانی نژاد،
        مراحل زیر را تکمیل کنید. اطلاعات ثبت‌شده مستقیماً برای واحد پذیرش ارسال می‌شود.
      </p>
</section>
<div aria-labelledby="contraindication-notice-title" class="contraindication-notice" role="note">
  <div class="contraindication-notice__title" id="contraindication-notice-title">
    <span aria-hidden="true" class="contraindication-notice__icon">⚠</span>
    موارد عدم پذیرش / محدودیت انجام درمان
  </div>
  <p class="contraindication-notice__intro">در صورت وجود هر یک از موارد زیر، انجام درمان امکان‌پذیر نبوده یا منوط به تأیید پزشک معالج خواهد بود:</p>
  <ul class="contraindication-notice__list">
    <li>سابقه فشار خون بالا و مصرف داروهای مرتبط</li>
    <li>ابتلا به دیابت (حتی در صورت کنترل کامل بیماری)</li>
    <li>ابتلا به بیماری‌های خودایمنی، از جمله: ام‌اس (MS)، روماتیسم، وگنر، تیروئید هاشیموتو، گریوز، لوپوس، اسکلرودرمی و سایر بیماری‌های خودایمنی</li>
    <li>سابقه جراحی لیفت لب (سانترال لیفت)</li>
    <li>سن بالای ۴۵ سال</li>
    <li>سابقه صرع</li>
    <li>اختلالات کلیوی</li>
    <li>ابتلا به تومورها یا سابقه شیمی‌درمانی</li>
  </ul>
  <p class="contraindication-notice__warning"><strong>توجه:</strong> در صورت کتمان هرگونه سابقه بیماری یا ارائه اطلاعات نادرست، مسئولیت عواقب احتمالی ناشی از درمان بر عهده بیمار خواهد بود.</p>
</div>
<noscript>
<div class="noscript-warning">
        برای تکمیل و ارسال فرم تشکیل پرونده، جاوااسکریپت مرورگر باید فعال باشد.
      </div>
</noscript>
<div aria-label="مراحل فرم" class="progress-bar" role="navigation">
<div class="progress-step active" id="progress-step-1">۱. احراز هویت</div>
<div class="progress-step" id="progress-step-2">۲. اطلاعات شخصی</div>
<div class="progress-step" id="progress-step-3">۳. اطلاعات پزشکی</div>
</div>
<div aria-live="off" class="verification-timer hidden" id="verification-timer">
<span>زمان باقی‌مانده از مهلت ۳۰ دقیقه‌ای ثبت فرم:</span>
<strong aria-label="زمان باقی‌مانده" id="verification-timer-value" role="timer">۳۰:۰۰</strong>
</div>
<div class="api-warning hidden" id="api-warning" role="alert"></div><form id="patient-form" novalidate="">
<div aria-hidden="true" style="position:fixed;top:0;right:0;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none;">
<label for="website-field">Website</label>
<input autocomplete="off" id="website-field" name="website" tabindex="-1" type="text"/>
</div>
<div class="form-step active" id="step-1">
<fieldset>
<legend>اطلاعات اولیه</legend>
<div class="grid-2">
<div class="input-group">
<input aria-describedby="first-name-hint" autocomplete="given-name" class="form-input" id="first-name" maxlength="30" minlength="2" placeholder=" " required="" type="text"/>
<label class="form-label" for="first-name"><span class="required-star">*</span>نام</label>
<div class="error-message"></div>
<small class="field-hint" id="first-name-hint">حداقل ۲ و حداکثر ۳۰ حرف</small>
</div>
<div class="input-group">
<input aria-describedby="last-name-hint" autocomplete="family-name" class="form-input" id="last-name" maxlength="30" minlength="2" placeholder=" " required="" type="text"/>
<label class="form-label" for="last-name"><span class="required-star">*</span>نام خانوادگی</label>
<div class="error-message"></div>
<small class="field-hint" id="last-name-hint">حداقل ۲ و حداکثر ۳۰ حرف</small>
</div>
</div>
<div class="input-group">
<input autocomplete="tel" class="form-input" id="verification-phone" inputmode="numeric" maxlength="11" placeholder="09" required="" type="tel"/>
<label class="form-label" for="verification-phone"><span class="required-star">*</span>شماره همراه بیمار</label>
<div class="error-message"></div>
</div>
<div class="input-group hidden" id="code-input-wrapper">
<input aria-describedby="otp-status" autocomplete="one-time-code" class="form-input text-center" enterkeyhint="done" id="verification-code" inputmode="numeric" maxlength="6" minlength="4" pattern="[0-9۰-۹٠-٩]{4,6}" placeholder=" " required="" style="letter-spacing:0.4em;" type="text"/>
<label class="form-label" for="verification-code"><span class="required-star">*</span>کد تأیید</label>
<div class="error-message"></div>
</div>
<p aria-live="polite" class="otp-status" id="otp-status"></p>
<div class="btn-row center">
<button class="btn-primary" id="send-code-btn" style="min-width:160px;" type="button">ارسال کد</button>
<button class="btn-primary hidden" id="verify-code-btn" style="min-width:160px;" type="button">تأیید کد</button>
</div>
</fieldset>
</div>
<div class="form-step" id="step-2">
<fieldset>
<legend>اطلاعات شخصی</legend>
<div class="grid-2">
<div class="input-group">
<input class="form-input" id="father-name" maxlength="30" minlength="2" placeholder=" " required="" type="text"/>
<label class="form-label" for="father-name"><span class="required-star">*</span>نام پدر</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<select class="form-select" id="occupation" required="">
<option disabled="" selected="" value="">انتخاب کنید</option>
<option value="1">آزاد</option>
<option value="2">کارمند</option>
<option value="3">دانش آموز</option>
<option value="4">خانه دار</option>
<option value="5">معلم</option>
<option value="6">مهندس</option>
<option value="1003">دانشجو</option>
<option value="1004">حسابدار و مالی</option>
<option value="1005">مدیر</option>
<option value="1006">پزشک یا رشته های پزشکی</option>
<option value="1007">هنرمند</option>
</select>
<label class="form-label" for="occupation"><span class="required-star">*</span>شغل</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<input class="form-input" id="national-id" inputmode="numeric" maxlength="10" placeholder=" " required="" type="text"/>
<label class="form-label" for="national-id"><span class="required-star">*</span>کد ملی</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<select class="form-select" id="insurance-type">
<option selected="" value="">انتخاب کنید (اختیاری)</option>
<optgroup label="بیمه‌های پایه و درمانی">
<option value="بدون بیمه / آزاد">بدون بیمه / آزاد</option>
<option value="بیمه تأمین اجتماعی">بیمه تأمین اجتماعی</option>
<option value="بیمه سلامت ایران">بیمه سلامت ایران</option>
<option value="تأمین اجتماعی نیروهای مسلح">تأمین اجتماعی نیروهای مسلح</option>
<option value="آتیه‌سازان حافظ">آتیه‌سازان حافظ</option>
<option value="کمک‌رسان ایران SOS">کمک‌رسان ایران SOS</option>
</optgroup>
<optgroup label="شرکت‌های بیمه بازرگانی">
<option value="بیمه ایران">بیمه ایران</option>
<option value="بیمه آسیا">بیمه آسیا</option>
<option value="بیمه البرز">بیمه البرز</option>
<option value="بیمه دانا">بیمه دانا</option>
<option value="بیمه پارسیان">بیمه پارسیان</option>
<option value="بیمه رازی">بیمه رازی</option>
<option value="بیمه کارآفرین">بیمه کارآفرین</option>
<option value="بیمه سینا">بیمه سینا</option>
<option value="بیمه ملت">بیمه ملت</option>
<option value="بیمه دی">بیمه دی</option>
<option value="بیمه سامان">بیمه سامان</option>
<option value="بیمه نوین">بیمه نوین</option>
<option value="بیمه پاسارگاد">بیمه پاسارگاد</option>
<option value="بیمه معلم">بیمه معلم</option>
<option value="بیمه میهن">بیمه میهن</option>
<option value="بیمه کوثر">بیمه کوثر</option>
<option value="بیمه ما">بیمه ما</option>
<option value="بیمه آرمان">بیمه آرمان</option>
<option value="بیمه تعاون">بیمه تعاون</option>
<option value="بیمه سرمد">بیمه سرمد</option>
<option value="بیمه تجارت نو">بیمه تجارت نو</option>
<option value="بیمه حکمت صبا">بیمه حکمت صبا</option>
<option value="بیمه هوشمند فردا">بیمه هوشمند فردا</option>
<option value="بیمه پردیس">بیمه پردیس</option>
<option value="بیمه امید">بیمه امید</option>
<option value="بیمه حافظ">بیمه حافظ</option>
<option value="بیمه آسماری">بیمه آسماری</option>
<option value="بیمه زندگی خاورمیانه">بیمه زندگی خاورمیانه</option>
<option value="بیمه باران">بیمه باران</option>
</optgroup>
<option value="سایر موارد">سایر موارد</option>
</select>
<label class="form-label" for="insurance-type">نوع بیمه</label>
<div class="error-message"></div>
</div>
<div class="input-group hidden" id="insurance-other-wrap">
<input class="form-input" id="insurance-other" maxlength="80" minlength="2" placeholder=" " type="text"/>
<label class="form-label" for="insurance-other">نام بیمه یا سازمان</label>
<div class="error-message"></div>
</div>
</div>
<div class="input-group">
<span class="birth-label"><span class="required-star">*</span>تاریخ تولد</span>
<div class="birth-row">
<select class="form-select" id="birth-day" required=""><option disabled="" selected="" value="">روز</option></select>
<select class="form-select" id="birth-month" required=""><option disabled="" selected="" value="">ماه</option></select>
<select class="form-select" id="birth-year" required=""><option disabled="" selected="" value="">سال</option></select>
</div>
<div class="error-message" id="birth-error"></div>
</div>
<h3 class="subsection-title">آدرس</h3>
<div class="grid-2">
<div class="input-group">
<select class="form-select" id="province" required="">
<option disabled="" selected="" value="">انتخاب کنید</option>
<option value="آذربایجان شرقی">آذربایجان شرقی</option>
<option value="آذربایجان غربی">آذربایجان غربی</option>
<option value="اردبیل">اردبیل</option>
<option value="اصفهان">اصفهان</option>
<option value="البرز">البرز</option>
<option value="ایلام">ایلام</option>
<option value="بوشهر">بوشهر</option>
<option value="تهران">تهران</option>
<option value="چهارمحال و بختیاری">چهارمحال و بختیاری</option>
<option value="خراسان جنوبی">خراسان جنوبی</option>
<option value="خراسان رضوی">خراسان رضوی</option>
<option value="خراسان شمالی">خراسان شمالی</option>
<option value="خوزستان">خوزستان</option>
<option value="زنجان">زنجان</option>
<option value="سمنان">سمنان</option>
<option value="سیستان و بلوچستان">سیستان و بلوچستان</option>
<option value="فارس">فارس</option>
<option value="قزوین">قزوین</option>
<option value="قم">قم</option>
<option value="کردستان">کردستان</option>
<option value="کرمان">کرمان</option>
<option value="کرمانشاه">کرمانشاه</option>
<option value="کهگیلویه و بویراحمد">کهگیلویه و بویراحمد</option>
<option value="گلستان">گلستان</option>
<option value="گیلان">گیلان</option>
<option value="لرستان">لرستان</option>
<option value="مازندران">مازندران</option>
<option value="مرکزی">مرکزی</option>
<option value="هرمزگان">هرمزگان</option>
<option value="همدان">همدان</option>
<option value="یزد">یزد</option>
<option value="سایر">سایر</option>
</select>
<label class="form-label" for="province"><span class="required-star">*</span>استان</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<input class="form-input" id="city" maxlength="40" minlength="2" placeholder=" " required="" type="text"/>
<label class="form-label" for="city"><span class="required-star">*</span>شهر</label>
<div class="error-message"></div>
</div>
</div>
<div class="input-group hidden" id="tehran-district-wrap">
<select class="form-select" id="tehran-district">
<option disabled="" selected="" value="">منطقه تهران را انتخاب کنید</option>
<option value="1">منطقه 1</option>
<option value="2">منطقه 2</option>
<option value="3">منطقه 3</option>
<option value="4">منطقه 4</option>
<option value="5">منطقه 5</option>
<option value="6">منطقه 6</option>
<option value="7">منطقه 7</option>
<option value="8">منطقه 8</option>
<option value="9">منطقه 9</option>
<option value="10">منطقه 10</option>
<option value="11">منطقه 11</option>
<option value="12">منطقه 12</option>
<option value="13">منطقه 13</option>
<option value="14">منطقه 14</option>
<option value="15">منطقه 15</option>
<option value="16">منطقه 16</option>
<option value="17">منطقه 17</option>
<option value="18">منطقه 18</option>
<option value="19">منطقه 19</option>
<option value="20">منطقه 20</option>
<option value="21">منطقه 21</option>
<option value="22">منطقه 22</option>
</select>
<label class="form-label" for="tehran-district">منطقه تهران</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<input class="form-input" id="address-details" maxlength="200" minlength="5" placeholder=" " required="" type="text"/>
<label class="form-label" for="address-details"><span class="required-star">*</span>جزئیات آدرس</label>
<div class="error-message"></div>
</div>
<div class="grid-2">
<div class="input-group">
<input class="form-input" id="home-phone" inputmode="numeric" maxlength="11" placeholder=" " type="tel"/>
<label class="form-label" for="home-phone">تلفن منزل</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<input aria-describedby="referrer-phone-hint" autocomplete="off" class="form-input" id="work-phone" inputmode="numeric" maxlength="11" placeholder="09" type="tel"/>
<label class="form-label" for="work-phone">شماره معرف (اختیاری)</label>
<div class="error-message"></div>
<small class="field-hint" id="referrer-phone-hint">اختیاری؛ در صورت نداشتن شماره معرف، این قسمت را خالی بگذارید.</small>
</div>
<div class="input-group">
<input aria-describedby="emergency-phone-hint" autocomplete="off" class="form-input" id="mobile-phone-2" inputmode="numeric" maxlength="11" name="emergency-phone" placeholder="09" type="tel"/>
<label class="form-label" for="mobile-phone-2">شماره همراه اضطراری (اختیاری)</label>
<div class="error-message"></div>
<small class="field-hint" id="emergency-phone-hint">اختیاری؛ در صورت تکمیل، باید با شماره همراه بیمار متفاوت باشد.</small>
</div>
</div>
<div class="input-group">
<select class="form-select" id="visit-reason" required="">
<option disabled="" selected="" value="">انتخاب کنید</option>
<option value="جراحی بینی اولیه">جراحی بینی اولیه</option>
<option value="جراحی ترمیمی">جراحی ترمیمی</option>
</select>
<label class="form-label" for="visit-reason"><span class="required-star">*</span>علت مراجعه</label>
<div class="error-message"></div>
</div>
<div class="input-group">
<select class="form-select" id="referral" required="">
<option disabled="" selected="" value="">انتخاب کنید</option>
<option value="41">اینترنت، سایت، گوگل</option>
<option value="43">اینستاگرام</option>
<option value="46">بیماران قبلی</option>
<option value="48">تابلو</option>
<option value="54">آریوژن</option>
<option value="55">پزشکان کلینیک</option>
<option value="57">پرسنل کلینیک</option>
<option value="45">نزدیکی محل سکونت</option>
<option value="58">چت سایت</option>
<option value="52">کانتر نیایش</option>
<option value="50">بیمه ها، سایت بیمه</option>
<option value="47">پیامک</option>
<option value="44">بنر تبلیغاتی</option>
<option value="42">تراکت تبلیغاتی</option>
<option value="56">مراکز همکار</option>
</select>
<label class="form-label" for="referral"><span class="required-star">*</span>نحوه آشنایی</label>
<div class="error-message"></div>
</div>
<div class="btn-row">
<button class="btn-secondary" id="prev-step-btn-2" type="button">قبلی</button>
<button class="btn" id="next-step-btn-2" type="button">ادامه</button>
</div>
</fieldset>
</div>
<div class="form-step" id="step-3">
<fieldset>
<legend>تاریخچه پزشکی</legend>
<div class="input-group">
<input autocomplete="email" class="form-input" id="patient-email" maxlength="80" placeholder=" " type="email"/>
<label class="form-label" for="patient-email">ایمیل (برای دریافت تأیید نوبت و تکمیل حساب کاربری)</label>
<div class="error-message"></div>
<small class="field-hint" id="patient-email-hint">اختیاری؛ در صورت تکمیل، ایمیل تأیید نوبت و راه‌اندازی حساب کاربری برای شما ارسال می‌شود.</small>
</div>
<div class="custom-checkbox-wrapper" id="medical-history-wrapper">
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="1"/><span>بیماری‌های قلبی و عروقی</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="2"/><span>دیابت</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="3"/><span>فشار خون بالا</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="5"/><span>پرکاری یا کم‌کاری تیروئید</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="7"/><span>کیست تخمدان</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="8"/><span>بیماری‌های عصبی و صرع</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="9"/><span>حساسیت به دارو و غذا</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="10"/><span>بیماری‌های ریوی و آسم</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="11"/><span>بیماری‌های کلیوی</span></label>
<label class="checkbox-label"><input class="checkbox-input med-hist" type="checkbox" value="12"/><span>حاملگی</span></label>
</div>
<div style="margin-top:1.5rem;">
<p style="font-size:0.9rem;font-weight:500;color:var(--muted);margin-bottom:0.5rem;">آیا ناراحتی خاصی دارید که در پرونده قابل ذکر باشد؟</p>
<div class="radio-row">
<label class="radio-label"><input name="specific-condition" type="radio" value="دارم"/> دارم</label>
<label class="radio-label"><input checked="" name="specific-condition" type="radio" value="ندارم"/> ندارم</label>
</div>
<div class="input-group" id="specific-explanation-wrap" style="margin-top:1rem;display:none;">
<input class="form-input" id="specific-condition-explanation" maxlength="300" placeholder=" " type="text"/>
<label class="form-label" for="specific-condition-explanation">توضیح ناراحتی</label>
<div class="error-message"></div>
</div>
</div>
<fieldset style="margin-top:1.75rem;">
<legend style="font-size:1rem;">داروهای مصرفی</legend>
<div class="custom-checkbox-wrapper">
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="1"/><span>داروهای فشار خون</span></label>
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="2"/><span>سرکوب‌کننده سیستم ایمنی - کورتون</span></label>
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="3"/><span>داروی ضد انعقادی</span></label>
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="4"/><span>داروهای تیروئیدی</span></label>
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="5"/><span>داروهای اعصاب</span></label>
<label class="checkbox-label"><input class="checkbox-input drug-chk" type="checkbox" value="6"/><span>آسپرین</span></label>
</div>
</fieldset>
</fieldset>
<div class="confirmation-box">
<label class="final-check">
<input id="confirmation-check" required="" type="checkbox"/>
<span id="confirmation-text" style="font-size:0.875rem;line-height:1.6;color:#475569;">
              اینجانب ضمن تأیید صحت پاسخ سوالات تاریخچه پزشکی، تمامی طرح درمان را پذیرفته و رضایت خود را با امضا تسلیم این مرکز می‌نمایم.
            </span>
<span class="required-star">*</span>
</label>
<div class="error-message checkbox-error-message"></div>
<div class="signature-pad-container">
<canvas class="signature-pad" id="signature-pad"></canvas>
<button class="clear-signature-btn" id="clear-signature-btn" type="button">پاک کردن</button>
</div>
</div>
<div class="btn-row">
<button class="btn-secondary" id="prev-step-btn-3" type="button">قبلی</button>
<button class="btn" id="submit-btn" type="submit">ثبت نهایی پرونده</button>
</div>
</div>
</form>
<div class="success-panel hidden" id="success-panel">
<div class="icon">✓</div>
<h2>پرونده با موفقیت ثبت شد</h2>
<p id="success-msg">از همکاری شما سپاسگزاریم. همکاران ما در اسرع وقت با شما تماس خواهند گرفت.</p>
</div>
</div>
<div aria-live="polite" class="toast" id="toast-element" role="status"></div>

<script defer="" src="/config.js?v=11"></script><script defer="" src="/app.js?v=11"></script></body>
</html>
```

## public/app.js

```javascript
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
    const description = [visitReason ? `علت مراجعه: ${visitReason}` : '', condition ? `ناراحتی خاص: ${condition}` : '', explanation ? `توضیح: ${explanation}` : '', insurance ? `نوع بیمه: ${insurance}` : ''].filter(Boolean).join(' | ');
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
```

## public/config.js

```javascript
/** Public configuration only. Never place TSMS credentials here. */
window.TAJ_CONFIG = Object.freeze({
  OTP_SEND_URL: '/api/otp/send.php',
  OTP_VERIFY_URL: '/api/otp/verify.php',
  SUBMIT_URL: '/api/intake/submit.php',
  HEALTH_URL: '/api/health.php',
  API_TIMEOUT_MS: 45000,
  WEB_OTP_ENABLED: true,
  OTP_CODE_LENGTH: 5,
  VERIFIED_SESSION_TTL_SECONDS: 1800,
  SMS_PROVIDER_LABEL: 'سامانه پیامک طوبی (TSMS)'
});
```

## public/logo.svg

```xml
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" role="img" aria-labelledby="title desc">
  <title id="title">Dr. Bastaninejad facial plastic surgery logo</title>
  <desc id="desc">A modern oval emblem combining natural green layers with nested facial profiles in graphite.</desc>
  <defs>
    <clipPath id="naturalSide">
      <path d="M205 10
               C242 58 260 91 249 134
               C239 174 223 207 238 242
               C248 266 277 279 273 300
               C270 316 244 318 246 334
               C248 349 266 350 263 367
               C260 384 237 390 231 409
               C225 429 226 451 203 484
               C119 469 57 384 57 256
               C57 128 119 43 205 10 Z"/>
    </clipPath>
    <mask id="faceMask" maskUnits="userSpaceOnUse" x="0" y="0" width="512" height="512">
      <rect width="512" height="512" fill="white"/>
      <!-- Main negative-space profile separating the natural and surgical sides -->
      <path d="M205 10 C242 58 260 91 249 134 C239 174 223 207 238 242 C248 266 277 279 273 300 C270 316 244 318 246 334 C248 349 266 350 263 367 C260 384 237 390 231 409 C225 429 226 451 203 484"
            fill="none" stroke="black" stroke-width="23" stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Inner nested facial profile -->
      <path d="M266 35 C299 87 303 126 291 166 C279 205 274 234 294 256 C307 271 331 278 329 296 C327 312 302 316 303 333 C304 348 323 350 320 366 C317 386 294 394 286 415 C280 433 280 451 263 470"
            fill="none" stroke="black" stroke-width="15" stroke-linecap="round" stroke-linejoin="round"/>
      <!-- Upper sweep suggesting the bridge and forehead -->
      <path d="M283 61 C347 75 397 116 418 173 C426 195 430 219 427 239 C425 252 420 259 414 258 C406 257 408 239 407 227 C404 192 389 164 364 143 C344 126 324 117 303 111"
            fill="none" stroke="black" stroke-width="13" stroke-linecap="round" stroke-linejoin="round"/>
    </mask>
  </defs>

  <!-- Natural layered side -->
  <g clip-path="url(#naturalSide)" fill="#2F7D32">
    <rect x="112" y="35" width="150" height="31" rx="15.5"/>
    <rect x="86" y="81" width="182" height="35" rx="17.5"/>
    <rect x="69" y="130" width="202" height="38" rx="19"/>
    <rect x="59" y="181" width="214" height="40" rx="20"/>
    <rect x="56" y="235" width="219" height="41" rx="20.5"/>
    <rect x="61" y="290" width="213" height="40" rx="20"/>
    <rect x="73" y="344" width="197" height="38" rx="19"/>
    <rect x="94" y="394" width="171" height="35" rx="17.5"/>
    <rect x="126" y="440" width="129" height="31" rx="15.5"/>
  </g>

  <!-- Graphite profile side -->
  <path d="M211 18
           C310 0 394 44 430 126
           C446 163 449 204 440 242
           C452 232 463 237 462 251
           C461 270 446 284 435 295
           C435 321 438 353 429 384
           C418 423 389 456 352 478
           C319 497 276 506 228 493
           C251 482 268 467 280 449
           C294 428 303 405 301 382
           C299 359 282 345 286 326
           C290 306 313 299 313 281
           C313 261 287 249 276 231
           C258 201 262 168 269 136
           C277 102 259 69 211 18 Z"
        fill="#25272C" mask="url(#faceMask)"/>
</svg>
```

## public/robots.txt

```text
User-agent: *
Allow: /

Sitemap: https://app.drbastaninejad.com/sitemap.xml
```

## public/sitemap.xml

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"><url><loc>https://app.drbastaninejad.com/</loc><changefreq>monthly</changefreq><priority>1.0</priority></url></urlset>
```

## public/llms.txt

```text
# app.drbastaninejad.com

> Official online patient intake (registration) form for Dr. Shahin Bastaninejad's clinic. Persian-language (fa-IR) medical practice based in Iran.

## About
Dr. Shahin Bastaninejad is a physician offering aesthetic/dermatologic and general medical consultation. This site hosts a secure online patient intake form used to collect identity information, medical history, and consent before an in-person visit.

## Key pages
- [Patient Intake Form](https://app.drbastaninejad.com/): Multi-step form for new patients to register (identity verification via SMS OTP, personal details, medical history, digital signature/consent).

## Primary use case
Patients fill out this form before their first appointment to save time at the clinic. The form requires:
1. Full name and mobile phone number, verified via one-time SMS code.
2. Personal details: father's name, national ID, birth date, occupation, address, insurance type.
3. Medical history: pre-existing conditions, current medications, and any specific concerns.
4. Digital signature confirming consent to treatment terms and contraindication disclosures.

## Contraindications disclosed on this form
Patients with the following conditions may face treatment restrictions requiring physician approval:
high blood pressure and related medication, diabetes, autoimmune diseases (MS, rheumatism, Wegener's, Hashimoto's thyroiditis, Graves' disease, lupus), history of lip lift (central lift) surgery, age over 45, epilepsy, kidney disorders, tumors or chemotherapy history.

## Data handling
Submitted data is stored securely and used solely for the patient's medical record at Dr. Bastaninejad's clinic. No data is shared with third parties for marketing purposes.

## Contact
For questions about this form or the clinic, visit https://app.drbastaninejad.com/
```

## public/.user.ini

```ini
display_errors=Off
log_errors=On
post_max_size=4M
max_input_time=60
max_execution_time=60
```

## public/api/_bootstrap.php

```php
<?php
declare(strict_types=1);
$documentRoot=rtrim((string)($_SERVER['DOCUMENT_ROOT']??dirname(__DIR__)),'/\\');
$candidates=[];$configured=trim((string)(getenv('APP_PRIVATE_ROOT')?:''));if($configured!=='')$candidates[]=rtrim($configured,'/\\').'/bootstrap.php';
$candidates[]=dirname($documentRoot).'/app_private/bootstrap.php';$candidates[]=dirname($documentRoot,2).'/app_private/bootstrap.php';$candidates[]=dirname(__DIR__,3).'/app_private/bootstrap.php';$candidates[]=dirname(__DIR__,2).'/app_private/bootstrap.php';
$bootstrap=null;foreach(array_unique($candidates) as $candidate)if(is_file($candidate)){$bootstrap=$candidate;break;}
if($bootstrap===null){http_response_code(500);header('Content-Type: application/json; charset=utf-8');echo json_encode(['success'=>false,'message'=>'app_private/bootstrap.php پیدا نشد. app_private را بیرون از DocumentRoot قرار دهید.'],JSON_UNESCAPED_UNICODE);exit;}
require $bootstrap;header('X-Content-Type-Options: nosniff');header('Referrer-Policy: strict-origin-when-cross-origin');
```

## public/api/health.php

```php
<?php
declare(strict_types=1);
require __DIR__ . '/_bootstrap.php';
$warnings=[];$required=['logs','otp','signatures','pending','submitted'];foreach($required as $dir){$path=APP_ROOT.'/storage/'.$dir;if(!is_writable($path))$warnings[]='پوشه storage/'.$dir.' قابل نوشتن نیست.';}
if(!function_exists('curl_init')&&!filter_var((string)ini_get('allow_url_fopen'),FILTER_VALIDATE_BOOL))$warnings[]='cURL نصب نیست و allow_url_fopen خاموش است.';
if((string)env('TSMS_FROM','')===''||str_starts_with((string)env('TSMS_FROM',''),'CHANGE_'))$warnings[]='TSMS_FROM (شماره خط ارسال‌کننده) هنوز تنظیم نشده است.';
if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',(string)env('SHEET_WEBHOOK_URL','')))$warnings[]='آدرس Web App شیت هنوز تنظیم نشده است.';
json_response(['success'=>true,'php'=>PHP_VERSION,'curl'=>function_exists('curl_init'),'privateRoot'=>true,'warnings'=>$warnings]);
```

## public/api/otp/send.php

```php
<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{$data=request_json(50000);json_response((new App\OtpService())->send((string)($data['phone']??''),client_ip()));}catch(Throwable $e){app_log('otp_send_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
```

## public/api/otp/verify.php

```php
<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{$d=request_json(50000);json_response((new App\OtpService())->verify((string)($d['phone']??''),(string)($d['token']??''),(string)($d['code']??'')));}catch(Throwable $e){app_log('otp_verify_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
```

## public/api/intake/submit.php

```php
<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_bootstrap.php';
require_post();
try{json_response((new App\IntakeService())->submit(request_json(2500000),client_ip()),201);}catch(Throwable $e){app_log('intake_submit_error',['error'=>$e->getMessage()]);json_response(['success'=>false,'message'=>$e->getMessage()],422);}
```

## app_private/.htaccess

```apache
Require all denied
```

## app_private/bootstrap.php

```php
<?php
declare(strict_types=1);
define('APP_ROOT',__DIR__);
spl_autoload_register(static function(string $class):void{$prefix='App\\';if(!str_starts_with($class,$prefix))return;$file=APP_ROOT.'/src/'.substr($class,strlen($prefix)).'.php';if(is_file($file))require $file;});
require APP_ROOT.'/src/helpers.php';
App\Env::load(APP_ROOT.'/.env');
date_default_timezone_set((string)env('APP_TIMEZONE','Asia/Tehran'));
foreach(['logs','otp','signatures','pending','submitted'] as $dir){$path=APP_ROOT.'/storage/'.$dir;if(!is_dir($path)&&!mkdir($path,0750,true)&&!is_dir($path))throw new RuntimeException('Cannot create storage directory: '.$dir);}
if(env_bool('APP_DEBUG',false)){ini_set('display_errors','1');error_reporting(E_ALL);}else{ini_set('display_errors','0');error_reporting(E_ALL);}
```

## app_private/src/helpers.php

```php
<?php
declare(strict_types=1);
if(!function_exists('mb_strlen')){function mb_strlen(string $value,?string $encoding=null):int{$chars=preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY);return is_array($chars)?count($chars):strlen($value);}}
if(!function_exists('mb_substr')){function mb_substr(string $value,int $start,?int $length=null,?string $encoding=null):string{$chars=preg_split('//u',$value,-1,PREG_SPLIT_NO_EMPTY);if(!is_array($chars))return substr($value,$start,$length);return implode('',array_slice($chars,$start,$length));}}
function env(string $key,?string $default=null):?string{$v=$_ENV[$key]??getenv($key);return($v===false||$v===null||$v==='')?$default:(string)$v;}
function env_bool(string $key,bool $default=false):bool{$v=env($key);return $v===null?$default:(filter_var($v,FILTER_VALIDATE_BOOL,FILTER_NULL_ON_FAILURE)??$default);}
function request_json(int $max=2500000):array{$length=(int)($_SERVER['CONTENT_LENGTH']??0);if($length>$max)throw new RuntimeException('درخواست بیش از حد بزرگ است.');$raw=file_get_contents('php://input');if($raw===false||trim($raw)==='')return[];$data=json_decode($raw,true,64,JSON_THROW_ON_ERROR);if(!is_array($data))throw new RuntimeException('فرمت درخواست نامعتبر است.');return$data;}
function json_response(array $data,int $status=200):never{http_response_code($status);header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function require_post():void{if(strtoupper($_SERVER['REQUEST_METHOD']??'')!=='POST')json_response(['success'=>false,'message'=>'Method not allowed'],405);}
function client_ip():string{return (string)($_SERVER['REMOTE_ADDR']??'0.0.0.0');}
function app_log(string $event,array $context=[]):void{$line=json_encode(['time'=>gmdate('c'),'event'=>$event,'context'=>$context],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);@file_put_contents(APP_ROOT.'/storage/logs/app.log',$line.PHP_EOL,FILE_APPEND|LOCK_EX);}
```

## app_private/src/Env.php

```php
<?php
declare(strict_types=1);
namespace App;
final class Env {
    public static function load(string $path): void {
        if (!is_file($path)) return;
        foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) continue;
            [$key,$value] = array_map('trim', explode('=', $line, 2));
            if ($key === '') continue;
            if ((str_starts_with($value,'"') && str_ends_with($value,'"')) || (str_starts_with($value,"'") && str_ends_with($value,"'"))) {
                $value = substr($value,1,-1);
            }
            $value = str_replace(['\\n','\\r','\\t'], ["\n","\r","\t"], $value);
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
```

## app_private/src/Security.php

```php
<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class Security {
    public static function digits(string $value): string {
        return preg_replace('/\D/u','',strtr($value,['۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9','٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'])) ?? '';
    }
    public static function mobile(string $value): string { $v=self::digits($value); if(!preg_match('/^09\d{9}$/',$v)) throw new RuntimeException('شماره همراه معتبر نیست.'); return $v; }
    public static function nationalId(string $value): string { $v=self::digits($value); if(!preg_match('/^\d{10}$/',$v)) throw new RuntimeException('کد ملی باید ۱۰ رقم باشد.'); return $v; }
    public static function text(mixed $value, int $max=500): string { $v=trim((string)$value); $v=preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u','',$v)??''; return mb_substr($v,0,$max); }
    public static function saveSignature(string $dataUrl): string {
        if(!preg_match('#^data:image/png;base64,([A-Za-z0-9+/=]+)$#',$dataUrl,$m)) throw new RuntimeException('امضا معتبر نیست.');
        $binary=base64_decode($m[1],true); if($binary===false || strlen($binary)<100 || strlen($binary)>1500000) throw new RuntimeException('حجم یا فرمت امضا معتبر نیست.');
        if(substr($binary,0,8)!=="\x89PNG\r\n\x1a\n") throw new RuntimeException('فرمت امضا PNG نیست.');
        $name=gmdate('Ymd_His').'_'.bin2hex(random_bytes(8)).'.png'; $path=APP_ROOT.'/storage/signatures/'.$name;
        if(file_put_contents($path,$binary,LOCK_EX)===false) throw new RuntimeException('ذخیره امضا ناموفق بود.'); @chmod($path,0640); return $name;
    }
}
```

## app_private/src/Http.php

```php
<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class Http {
    /** @return array{status:int,body:string,headers:array,error:string,url:string} */
    public static function request(string $method, string $url, array $options = []): array {
        $method = strtoupper($method);
        $headers = $options['headers'] ?? [];
        $body = (string)($options['body'] ?? '');
        $timeout = (int)($options['timeout'] ?? 25);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) throw new RuntimeException('cURL initialization failed.');
            $responseHeaders = [];
            curl_setopt_array($ch, [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 8,
                CURLOPT_CONNECTTIMEOUT => min(10,$timeout),
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERAGENT => 'DrBastaninejadIntake/2.0',
                CURLOPT_ENCODING => '',
                CURLOPT_HEADERFUNCTION => static function($ch, string $line) use (&$responseHeaders): int {
                    $trim=trim($line); if($trim!=='' && str_contains($trim,':')) { [$k,$v]=explode(':',$trim,2); $responseHeaders[strtolower(trim($k))]=trim($v); } return strlen($line);
                },
            ]);
            if ($method !== 'GET' && $body !== '') curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $result = curl_exec($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
            $effective = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $error = $result === false ? curl_error($ch) : '';
            curl_close($ch);
            if (in_array($status, [301,302,303,307,308], true) && isset($responseHeaders['location']) && $responseHeaders['location'] !== '') {
                $loc = $responseHeaders['location'];
                $ch2 = curl_init($loc);
                $responseHeaders2 = [];
                curl_setopt_array($ch2, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_FOLLOWLOCATION => false,
                    CURLOPT_CONNECTTIMEOUT => min(10,$timeout),
                    CURLOPT_TIMEOUT => $timeout,
                    CURLOPT_USERAGENT => 'DrBastaninejadIntake/2.0',
                    CURLOPT_ENCODING => '',
                    CURLOPT_HEADERFUNCTION => static function($ch2, string $line) use (&$responseHeaders2): int {
                        $trim=trim($line); if($trim!=='' && str_contains($trim,':')) { [$k,$v]=explode(':',$trim,2); $responseHeaders2[strtolower(trim($k))]=trim($v); } return strlen($line);
                    },
                ]);
                $result2 = curl_exec($ch2);
                $status2 = (int)curl_getinfo($ch2, CURLINFO_RESPONSE_CODE);
                $error2 = $result2 === false ? curl_error($ch2) : '';
                curl_close($ch2);
                return ['status'=>$status2,'body'=>$result2===false?'':(string)$result2,'headers'=>$responseHeaders2,'error'=>$error2,'url'=>$loc];
            }
            return ['status'=>$status,'body'=>$result===false?'':(string)$result,'headers'=>$responseHeaders,'error'=>$error,'url'=>$effective ?: $url];
        }
        if (!filter_var((string)ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            return ['status'=>0,'body'=>'','headers'=>[],'error'=>'cURL is unavailable and allow_url_fopen is disabled.','url'=>$url];
        }
        $context = stream_context_create(['http'=>[
            'method'=>$method,'timeout'=>$timeout,'ignore_errors'=>true,'follow_location'=>1,'max_redirects'=>8,
            'header'=>implode("\r\n",$headers) . "\r\nUser-Agent: DrBastaninejadIntake/2.0\r\n",
            'content'=>$method==='GET'?'':$body,
        ]]);
        $result = @file_get_contents($url,false,$context);
        $status=0; $responseHeaders=[];
        foreach($http_response_header??[] as $line){ if(preg_match('#^HTTP/\S+\s+(\d{3})#',$line,$m))$status=(int)$m[1]; elseif(str_contains($line,':')){[$k,$v]=explode(':',$line,2);$responseHeaders[strtolower(trim($k))]=trim($v);} }
        return ['status'=>$status,'body'=>$result===false?'':(string)$result,'headers'=>$responseHeaders,'error'=>$result===false?'HTTP request failed.':'','url'=>$url];
    }
}
```

## app_private/src/TsmsClient.php

```php
<?php
declare(strict_types=1);

namespace App;

use RuntimeException;

final class TsmsClient
{
    /** Error codes documented by Tooba/TSMS URL API. */
    private const ERRORS = [
        '1'  => 'خطا در سرورهای طوبی اس‌ام‌اس.',
        '2'  => 'متن UDH بیش از یک پیامک است.',
        '3'  => 'شماره همراه مقصد اشتباه است.',
        '4'  => 'متغیرهای ارسال نامعتبر هستند.',
        '5'  => 'متن پیامک خالی است.',
        '6'  => 'شماره همراه مقصد خالی است.',
        '7'  => 'نام کاربری یا گذرواژه TSMS اشتباه است.',
        '8'  => 'خطای موقت سرور پیامک؛ دوباره تلاش کنید.',
        '9'  => 'سرویس ارسال پیام کوتاه قطع است.',
        '14' => 'اعتبار پنل پیامک کافی نیست.',
    ];

    /** @return array{messageId:string,raw:string} */
    public function send(string $phone, string $message): array
    {
        $phone = Security::mobile($phone);
        $message = trim($message);
        if ($message === '') {
            throw new RuntimeException('متن پیامک خالی است.');
        }

        if (strtolower((string) env('SMS_PROVIDER', 'tsms')) === 'demo') {
            app_log('demo_sms', ['phone' => $phone, 'message' => $message]);
            return ['messageId' => 'demo', 'raw' => 'demo'];
        }

        [$url, $username, $password, $from] = $this->configuration();
        $query = http_build_query([
            'from'     => $from,
            'to'       => $phone,
            'username' => $username,
            'password' => $password,
            'message'  => $message,
        ], '', '&', PHP_QUERY_RFC3986);

        $response = Http::request(
            'GET',
            $url . (str_contains($url, '?') ? '&' : '?') . $query,
            ['headers' => ['Accept: text/plain,*/*'], 'timeout' => 30]
        );

        if ($response['error'] !== '') {
            throw new RuntimeException('خطای اتصال TSMS: ' . $response['error']);
        }
        if ($response['status'] < 200 || $response['status'] >= 400) {
            throw new RuntimeException('TSMS پاسخ HTTP ' . $response['status'] . ' برگرداند.');
        }

        $result = $this->parseResponse($response['body']);
        if (isset(self::ERRORS[$result])) {
            throw new RuntimeException(self::ERRORS[$result]);
        }
        if (str_starts_with($result, '-')) {
            throw new RuntimeException('TSMS خطای ' . $result . ' برگرداند.');
        }

        // The supplied TSMS documentation calls a successful result "smsid".
        // Accept a conservative printable identifier rather than assuming digits only.
        if (!preg_match('/^[0-9A-Za-z,._:\-]{1,190}$/', $result)) {
            throw new RuntimeException('پاسخ TSMS قابل تشخیص نبود: ' . mb_substr($result, 0, 80));
        }

        return ['messageId' => $result, 'raw' => $result];
    }

    public function credit(): string
    {
        [$url, $username, $password, $from] = $this->configuration();
        $query = http_build_query([
            'from'     => $from,
            'username' => $username,
            'password' => $password,
            'credit'   => 'what',
        ], '', '&', PHP_QUERY_RFC3986);

        $response = Http::request(
            'GET',
            $url . (str_contains($url, '?') ? '&' : '?') . $query,
            ['headers' => ['Accept: text/plain,*/*'], 'timeout' => 25]
        );
        if ($response['error'] !== '') {
            throw new RuntimeException('خطای اتصال TSMS: ' . $response['error']);
        }
        if ($response['status'] < 200 || $response['status'] >= 400) {
            throw new RuntimeException('TSMS پاسخ HTTP ' . $response['status'] . ' برگرداند.');
        }

        $result = $this->parseResponse($response['body']);
        if (isset(self::ERRORS[$result])) {
            throw new RuntimeException(self::ERRORS[$result]);
        }
        return $result;
    }

    /** @return array{0:string,1:string,2:string,3:string} */
    private function configuration(): array
    {
        $url = trim((string) env('TSMS_API_URL', 'https://tsms.ir/url/tsmshttp.php'));
        $username = trim((string) env('TSMS_USERNAME', ''));
        $password = (string) env('TSMS_PASSWORD', '');
        $from = trim((string) env('TSMS_FROM', ''));

        if ($username === '' || str_starts_with($username, 'CHANGE_') ||
            $password === '' || str_starts_with($password, 'CHANGE_')) {
            throw new RuntimeException('نام کاربری یا گذرواژه TSMS در .env تنظیم نشده است.');
        }
        if ($from === '' || str_starts_with($from, 'CHANGE_')) {
            throw new RuntimeException('شماره خط ارسال‌کننده TSMS (TSMS_FROM) تنظیم نشده است.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        if (!in_array($scheme, ['https', 'http'], true) ||
            !in_array($host, ['tsms.ir', 'www.tsms.ir'], true) ||
            $path !== '/url/tsmshttp.php') {
            throw new RuntimeException('آدرس API TSMS معتبر نیست.');
        }

        return [$url, $username, $password, $from];
    }

    private function parseResponse(string $raw): string
    {
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw) ?? $raw;
        $result = trim(html_entity_decode(strip_tags($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        // The documented responses are one compact code/id. Remove surrounding whitespace,
        // CR/LF and accidental spaces introduced by HTML output.
        $result = preg_replace('/\s+/u', '', $result) ?? $result;
        if ($result === '') {
            throw new RuntimeException('پاسخ TSMS خالی بود.');
        }
        return $result;
    }
}
```

## app_private/src/SheetClient.php

```php
<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class SheetClient {
    public function append(array $row): array {
        if(strtolower((string)env('SHEET_DRIVER','apps_script'))==='demo'){app_log('demo_sheet',$row);return ['ok'=>true,'row'=>0];}
        $url=trim((string)env('SHEET_WEBHOOK_URL',''));$secret=(string)env('SHEET_SHARED_SECRET','');
        if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',$url))throw new RuntimeException('آدرس Web App شیت تنظیم نشده یا معتبر نیست.');
        if($secret===''||str_starts_with($secret,'CHANGE_'))throw new RuntimeException('رمز مشترک Google Sheet تنظیم نشده است.');
        $payload=json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($payload===false)throw new RuntimeException('ساخت داده شیت ناموفق بود.');
        $body=http_build_query(['secret'=>$secret,'payload'=>$payload],'','&',PHP_QUERY_RFC3986);
        $r=Http::request('POST',$url,['headers'=>['Content-Type: application/x-www-form-urlencoded; charset=UTF-8','Accept: application/json,text/plain,*/*'],'body'=>$body,'timeout'=>35]);
        if($r['error']!=='')throw new RuntimeException('خطای اتصال Google Sheet: '.$r['error']);
        if($r['status']<200||$r['status']>=400)throw new RuntimeException('Web App شیت پاسخ HTTP '.$r['status'].' برگرداند.');
        $data=json_decode(trim($r['body']),true);
        if(!is_array($data)){app_log('sheet_non_json',['status'=>$r['status'],'body'=>mb_substr($r['body'],0,500),'url'=>$r['url']]);throw new RuntimeException('پاسخ شیت JSON نیست؛ Web App را با دسترسی Anyone دوباره Deploy کنید.');}
        if(!($data['ok']??false))throw new RuntimeException('شیت ثبت را رد کرد: '.(string)($data['error']??'خطای نامشخص'));
        return $data;
    }
    public function health(): array { $url=trim((string)env('SHEET_WEBHOOK_URL',''));if(!preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',$url))throw new RuntimeException('Sheet URL invalid.');$r=Http::request('GET',$url,['headers'=>['Accept: application/json'],'timeout'=>20]);if($r['error']!=='')throw new RuntimeException($r['error']);$d=json_decode(trim($r['body']),true);if(!is_array($d)||!($d['ok']??false))throw new RuntimeException('Sheet Web App health response invalid.');return $d; }
}
```

## app_private/src/OtpService.php

```php
<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class OtpService {
    private const VERIFIED_TTL_SECONDS=1800;

    public function send(string $phone,string $ip): array {
        $phone=Security::mobile($phone); $this->rateLimit($phone,$ip);
        $length=max(4,min(6,(int)env('SMS_OTP_LENGTH','5'))); $demo=strtolower((string)env('SMS_PROVIDER','tsms'))==='demo';
        $code=$demo?str_pad((string)env('SMS_DEMO_CODE','12345'),$length,'0',STR_PAD_LEFT):str_pad((string)random_int(0,(10**$length)-1),$length,'0',STR_PAD_LEFT);
        $token=bin2hex(random_bytes(24)); $ttl=max(60,min(600,(int)env('SMS_OTP_TTL_SECONDS','180')));
        $template=(string)env('SMS_MESSAGE_TEMPLATE',"کد تشکیل پرونده: {{code}}\n@app.drbastaninejad.com #{{code}}");
        $message=strtr($template,['{{code}}'=>$code,'{{phone}}'=>$phone]);
        $record=['token'=>$token,'phone'=>$phone,'code_hash'=>password_hash($code,PASSWORD_DEFAULT),'ip'=>$ip,'status'=>'pending','attempts'=>0,'expires_at'=>time()+$ttl,'created_at'=>time()];
        $this->save($record);
        try { $sent=(new TsmsClient())->send($phone,$message); $record['status']='sent'; $record['message_id']=$sent['messageId']??''; $this->save($record); }
        catch(\Throwable $e){ $record['status']='failed';$record['error']=$e->getMessage();$this->save($record);throw $e; }
        return ['success'=>true,'token'=>$token,'expiresIn'=>$ttl,'message'=>'کد تأیید ارسال شد.'];
    }
    public function verify(string $phone,string $token,string $code): array {
        $phone=Security::mobile($phone); $token=preg_replace('/[^a-f0-9]/i','',$token)??''; $code=Security::digits($code); $r=$this->load($token);
        if(!$r||($r['phone']??'')!==$phone)throw new RuntimeException('درخواست کد معتبر نیست.');
        if(($r['status']??'')==='verified'){
            $remaining=$this->verifiedRemaining($r);
            if($remaining<=0)throw new RuntimeException('مهلت تکمیل فرم منقضی شده است؛ دوباره کد بگیرید.');
            return ['success'=>true,'token'=>$token,'expiresIn'=>$remaining,'expiresAt'=>$this->verifiedExpiry($r),'message'=>'شماره با موفقیت تأیید شد.'];
        }
        if(($r['status']??'')!=='sent')throw new RuntimeException('درخواست کد معتبر نیست.');
        if((int)$r['expires_at']<time())throw new RuntimeException('کد منقضی شده است.');
        if((int)($r['attempts']??0)>=(int)env('OTP_MAX_ATTEMPTS','5'))throw new RuntimeException('تعداد تلاش بیش از حد مجاز است.');
        if(!password_verify($code,(string)$r['code_hash'])){$r['attempts']=(int)($r['attempts']??0)+1;$this->save($r);throw new RuntimeException('کد تأیید نادرست است.');}
        $now=time();$r['status']='verified';$r['verified_at']=$now;$r['verified_expires_at']=$now+self::VERIFIED_TTL_SECONDS;unset($r['code_hash']);$this->save($r);
        return ['success'=>true,'token'=>$token,'expiresIn'=>self::VERIFIED_TTL_SECONDS,'expiresAt'=>$r['verified_expires_at'],'message'=>'شماره با موفقیت تأیید شد.'];
    }
    public function assertVerified(string $phone,string $token): array { $phone=Security::mobile($phone);$r=$this->load($token);if(!$r||($r['phone']??'')!==$phone||($r['status']??'')!=='verified')throw new RuntimeException('تأیید شماره معتبر نیست؛ دوباره کد بگیرید.');if($this->verifiedRemaining($r)<=0)throw new RuntimeException('مهلت تکمیل فرم منقضی شده است؛ دوباره کد بگیرید.');return $r; }
    public function consume(string $token): void { $r=$this->load($token);if($r){$r['status']='consumed';$r['consumed_at']=time();$this->save($r);} }
    private function path(string $token): string { return APP_ROOT.'/storage/otp/'.$token.'.json'; }
    private function load(string $token): ?array { if(!preg_match('/^[a-f0-9]{48}$/i',$token))return null;$d=json_decode((string)@file_get_contents($this->path($token)),true);return is_array($d)?$d:null; }
    private function save(array $record): void { $json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);if($json===false||file_put_contents($this->path((string)$record['token']),$json,LOCK_EX)===false)throw new RuntimeException('فضای ذخیره OTP قابل نوشتن نیست.');@chmod($this->path((string)$record['token']),0640); }
    private function verifiedExpiry(array $record): int { $expiry=(int)($record['verified_expires_at']??0);if($expiry>0)return $expiry;$verifiedAt=(int)($record['verified_at']??0);return $verifiedAt>0?$verifiedAt+self::VERIFIED_TTL_SECONDS:0; }
    private function verifiedRemaining(array $record): int { return max(0,$this->verifiedExpiry($record)-time()); }
    private function rateLimit(string $phone,string $ip): void { $pc=0;$ic=0;$now=time();foreach(glob(APP_ROOT.'/storage/otp/*.json')?:[] as $file){$r=json_decode((string)@file_get_contents($file),true);if(!is_array($r))continue;$created=(int)($r['created_at']??0);if($created&&$now-$created>86400){@unlink($file);continue;}if(($r['phone']??'')===$phone&&$now-$created<=600)$pc++;if(($r['ip']??'')===$ip&&$now-$created<=3600)$ic++;}if($pc>=(int)env('OTP_PHONE_LIMIT_10_MIN','3'))throw new RuntimeException('تعداد درخواست کد برای این شماره زیاد است. کمی بعد تلاش کنید.');if($ic>=(int)env('OTP_IP_LIMIT_60_MIN','10'))throw new RuntimeException('محدودیت ارسال کد برای این اتصال فعال شده است.'); }
}
```

## app_private/src/IntakeService.php

```php
<?php
declare(strict_types=1);
namespace App;
use RuntimeException;
final class IntakeService {
    public const COLUMNS=['FirstName','LastName','FatherName','TavalodDay','TavalodMonth','TavalodYear','HomeTel','Mobile','Mobile2','CodeAshnaei','CodeBimeh','CodeMeli','CodeJob','HomeAd','Description','IsTransfer','drugs','difficult','morefmob'];
    private const REQUIRED_COLUMNS=['FirstName','LastName','FatherName','TavalodDay','TavalodMonth','TavalodYear','Mobile','Mobile2','CodeAshnaei','CodeMeli','CodeJob','HomeAd','IsTransfer'];
    public function submit(array $payload,string $ip): array {
        if(trim((string)($payload['_website']??''))!=='')throw new RuntimeException('درخواست رد شد.');
        $meta=is_array($payload['_meta']??null)?$payload['_meta']:[];$token=Security::text($meta['otpToken']??'',64);
        $row=[];foreach(self::COLUMNS as $column)$row[$column]=Security::text($payload[$column]??'',in_array($column,['HomeAd','Description'],true)?5000:500);
        foreach(self::REQUIRED_COLUMNS as $required)if($row[$required]==='')throw new RuntimeException('یک فیلد ضروری ناقص است: '.$required);
        foreach(['FirstName'=>'نام','LastName'=>'نام خانوادگی'] as $field=>$label){$len=mb_strlen($row[$field]);if($len<2||$len>30)throw new RuntimeException($label.' باید بین ۲ تا ۳۰ حرف باشد.');}
        $row['Mobile']=Security::mobile($row['Mobile']);$row['Mobile2']=Security::mobile($row['Mobile2']);$row['CodeMeli']=Security::nationalId($row['CodeMeli']);
        if($row['Mobile']===$row['Mobile2'])throw new RuntimeException('شماره همراه اضطراری نباید شماره خود بیمار باشد.');
        (new OtpService())->assertVerified($row['Mobile'],$token);
        $signature=Security::saveSignature(Security::text($meta['signature']??'',1600000));
        $uuid=bin2hex(random_bytes(16));$record=['uuid'=>$uuid,'created_at'=>gmdate('c'),'ip'=>$ip,'row'=>$row,'signature'=>$signature,'insurance'=>Security::text($meta['insuranceType']??'',100),'source'=>Security::text($payload['_source']??'',500)];
        $pending=APP_ROOT.'/storage/pending/'.$uuid.'.json';$json=json_encode($record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);if($json===false||file_put_contents($pending,$json,LOCK_EX)===false)throw new RuntimeException('فضای پشتیبان پرونده قابل نوشتن نیست.');@chmod($pending,0640);
        try{$sheet=(new SheetClient())->append($row);}catch(\Throwable $e){app_log('sheet_write_failed',['uuid'=>$uuid,'error'=>$e->getMessage()]);throw new RuntimeException('اطلاعات پشتیبان‌گیری شد اما ثبت در Google Sheet ناموفق بود: '.$e->getMessage());}
        rename($pending,APP_ROOT.'/storage/submitted/'.$uuid.'.json');(new OtpService())->consume($token);
        return ['success'=>true,'reference'=>strtoupper(substr($uuid,0,10)),'sheetRow'=>(int)($sheet['row']??0)];
    }
}
```

## app_private/tools/check.php

```php
<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';
$checks=['PHP'=>PHP_VERSION,'cURL'=>function_exists('curl_init')?'yes':'no','allow_url_fopen'=>ini_get('allow_url_fopen'),'env'=>is_file(APP_ROOT.'/.env')?'yes':'NO','storage'=>is_writable(APP_ROOT.'/storage')?'writable':'NOT writable','TSMS_FROM'=>(string)env('TSMS_FROM','')!==''?'set':'MISSING','Sheet URL'=>preg_match('#^https://script\.google\.com/macros/s/[^/]+/exec$#',(string)env('SHEET_WEBHOOK_URL',''))?'valid-looking':'MISSING/invalid'];foreach($checks as $k=>$v)echo str_pad($k,18).": $v\n";
```

## app_private/tools/test_sheet.php

```php
<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';try{print_r((new App\SheetClient())->health());}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
```

## app_private/tools/test_sheet_write.php

```php
<?php
declare(strict_types=1);

require dirname(__DIR__) . '/bootstrap.php';

$stamp = gmdate('c');
$row = [
    'FirstName'    => 'تست',
    'LastName'     => 'PHP Web App',
    'FatherName'   => '—',
    'TavalodDay'   => '1',
    'TavalodMonth' => '1',
    'TavalodYear'  => '1400',
    'HomeTel'      => '',
    'Mobile'       => '09120000000',
    'Mobile2'      => '09120000001',
    'CodeAshnaei'  => '41',
    'CodeBimeh'    => '1',
    'CodeMeli'     => '0000000000',
    'CodeJob'      => '1',
    'HomeAd'       => 'آزمایش نوشتن PHP به Google Sheet',
    'Description'  => 'PHP Web App write test — ' . $stamp,
    'IsTransfer'   => '1',
    'drugs'        => '',
    'difficult'    => '',
    'morefmob'     => '',
];

try {
    $result = (new App\SheetClient())->append($row);
    echo 'Sheet write succeeded. Row: ' . (string) ($result['row'] ?? '?') . PHP_EOL;
} catch (Throwable $error) {
    fwrite(STDERR, 'Sheet write failed: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
```

## app_private/tools/test_tsms.php

```php
<?php
declare(strict_types=1);require dirname(__DIR__).'/bootstrap.php';try{echo "Credit response: ".(new App\TsmsClient())->credit().PHP_EOL;}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
```

# END PROMPT FOR A NEW CHAT

End of complete handoff. Do not infer or expose any secret value omitted from this document.
