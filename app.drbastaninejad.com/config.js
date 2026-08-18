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
