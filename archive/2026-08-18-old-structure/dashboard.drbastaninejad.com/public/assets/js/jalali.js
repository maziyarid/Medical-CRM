// Jalali (Shamsi) calendar utilities — client-side, no dependency.
// Gregorian storage/UTC everywhere in the backend; Jalali is a display-only layer,
// per Medical CRM.md §2.4/§6.7 (non-negotiable Iranian market constraint).
const Jalali = (() => {
  const WEEKDAYS_FA = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنجشنبه','جمعه','شنبه'];
  const MONTHS_FA = ['فروردین','اردیبهشت','خرداد','تیر','مرداد','شهریور','مهر','آبان','آذر','دی','بهمن','اسفند'];
  const PERSIAN_DIGITS = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];

  function div(a, b) { return ~~(a / b); }

  // Accurate Gregorian -> Jalali (Borkowski / jalaali-js algorithm, public-domain reference)
  function gregorianToJalali(gy, gm, gd) {
    const g_d_m = [0,31,59,90,120,151,181,212,243,273,304,334];
    let jy = (gy <= 1600) ? 0 : 979;
    gy -= (gy <= 1600) ? 621 : 1600;
    const gy2 = (gm > 2) ? (gy + 1) : gy;
    let days = (365 * gy) + div((gy2 + 3), 4) - div((gy2 + 99), 100)
      + div((gy2 + 399), 400) - 80 + gd + g_d_m[gm - 1];
    jy += 33 * div(days, 12053);
    days %= 12053;
    jy += 4 * div(days, 1461);
    days %= 1461;
    if (days > 365) {
      jy += div(days - 1, 365);
      days = (days - 1) % 365;
    }
    let jm, jd;
    if (days < 186) {
      jm = 1 + div(days, 31);
      jd = 1 + (days % 31);
    } else {
      jm = 7 + div(days - 186, 30);
      jd = 1 + ((days - 186) % 30);
    }
    return [jy, jm, jd];
  }

  function jalaliToGregorian(jy, jm, jd) {
    let gy = (jy <= 979) ? 621 : 1600;
    jy -= (jy <= 979) ? 0 : 979;
    let days = (365 * jy) + (div(jy, 33) * 8) + div((jy % 33 + 3), 4) + 78 + jd
      + ((jm < 7) ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);
    gy += 400 * div(days, 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * div(--days, 36524);
      days %= 36524;
      if (days >= 365) days++;
    }
    gy += 4 * div(days, 1461);
    days %= 1461;
    if (days > 365) {
      gy += div(days - 1, 365);
      days = (days - 1) % 365;
    }
    let gd = days + 1;
    const sal_a = [0, 31, ((gy % 4 === 0 && gy % 100 !== 0) || (gy % 400 === 0)) ? 29 : 28,
      31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let gm = 0;
    for (gm = 1; gm <= 12 && gd > sal_a[gm]; gm++) gd -= sal_a[gm];
    return [gy, gm, gd];
  }

  function toJalali(dateObj) {
    const [jy, jm, jd] = gregorianToJalali(dateObj.getFullYear(), dateObj.getMonth() + 1, dateObj.getDate());
    return { jy, jm, jd };
  }

  function fromJalali(jy, jm, jd) {
    const [gy, gm, gd] = jalaliToGregorian(jy, jm, jd);
    return new Date(gy, gm - 1, gd);
  }

  function toPersianDigits(str) {
    return String(str).replace(/[0-9]/g, (d) => PERSIAN_DIGITS[+d]);
  }

  function formatFull(dateObj) {
    const { jy, jm, jd } = toJalali(dateObj);
    const weekday = WEEKDAYS_FA[dateObj.getDay()];
    return `${weekday} ${toPersianDigits(jd)} ${MONTHS_FA[jm - 1]} ${toPersianDigits(jy)}`;
  }

  function formatShort(dateObj) {
    const { jy, jm, jd } = toJalali(dateObj);
    return `${toPersianDigits(jd)} ${MONTHS_FA[jm - 1]}`;
  }

  function formatNumeric(dateObj) {
    const { jy, jm, jd } = toJalali(dateObj);
    const pad = (n) => String(n).padStart(2, '0');
    return toPersianDigits(`${jy}/${pad(jm)}/${pad(jd)}`);
  }

  function monthLength(jy, jm) {
    if (jm <= 6) return 31;
    if (jm <= 11) return 30;
    const isLeap = ((((jy - (jy > 0 ? 474 : 473)) % 2820) + 474 + 38) * 682) % 2816 < 682;
    return isLeap ? 30 : 29;
  }

  return { toJalali, fromJalali, formatFull, formatShort, formatNumeric, toPersianDigits, MONTHS_FA, WEEKDAYS_FA, monthLength };
})();
