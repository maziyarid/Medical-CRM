/* MAZ//ID · Dr. Shahin Bastaninejad Medical Platform */
/*
 * ============================================================================
 *  Jalali (Shamsi) calendar utilities — client-side, no dependency.
 * ----------------------------------------------------------------------------
 *  Algorithm-identical copy of app.drbastaninejad.com/Frontend/assets/js/jalali.js
 *  Kept in sync per SPACE_COORDINATION_PROTOCOL.md §6 (single algorithm authority).
 * ============================================================================
 */
const Jalali = (() => {
  const WEEKDAYS_FA = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
  const MONTHS_FA = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const PERSIAN_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

  function div(a, b) { return ~~(a / b); }

  function gregorianToJalali(gy, gm, gd) {
    const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    let jy = (gy <= 1600) ? 0 : 979;
    gy -= (gy <= 1600) ? 621 : 1600;
    const gy2 = (gm > 2) ? (gy + 1) : gy;
    let days = (365 * gy) + div((gy2 + 3), 4) - div((gy2 + 99), 100)
      + div((gy2 + 399), 400) - 80 + gd + g_d_m[gm - 1];
    jy += 33 * div(days, 12053); days %= 12053;
    jy += 4 * div(days, 1461);   days %= 1461;
    if (days > 365) { jy += div(days - 1, 365); days = (days - 1) % 365; }
    let jm, jd;
    if (days < 186) { jm = 1 + div(days, 31);       jd = 1 + (days % 31); }
    else            { jm = 7 + div(days - 186, 30);  jd = 1 + ((days - 186) % 30); }
    return [jy, jm, jd];
  }

  function toJalali(dateObj) {
    const [jy, jm, jd] = gregorianToJalali(dateObj.getFullYear(), dateObj.getMonth() + 1, dateObj.getDate());
    return { jy, jm, jd };
  }
  function toPersianDigits(str) {
    return String(str).replace(/[0-9]/g, (d) => PERSIAN_DIGITS[+d]);
  }
  function formatNumeric(dateObj) {
    const { jy, jm, jd } = toJalali(dateObj);
    const pad = (n) => String(n).padStart(2, '0');
    return toPersianDigits(`${jy}/${pad(jm)}/${pad(jd)}`);
  }

  return { toJalali, formatNumeric, toPersianDigits, MONTHS_FA, WEEKDAYS_FA };
})();

/* End of file — MAZ//ID · © 2026 Dr. Shahin Bastaninejad */
