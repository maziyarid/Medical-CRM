<?php
declare(strict_types=1);

namespace App\Validators;

/**
 * ValidatorService — Phase A+B deliverable
 *
 * Provides server-side validation for:
 *   - Iranian mobile numbers (normalise fa/ar digits, strip country code)
 *   - Code Meli / National ID (mod-11 checksum)
 *   - Jalali (Solar Hijri) dates → convert to Gregorian (Y-m-d)
 *   - Persian/Arabic digit normalisation
 *
 * All methods are static — no state, easy to unit-test.
 *
 * NOTE: this file lives in app/Validators/ (namespace App\Validators) which is
 * the namespace used by all controllers.  The legacy copy in app/Services/ is
 * kept for backward-compatibility but should be considered deprecated.
 */
final class ValidatorService
{
    // -------------------------------------------------------------------------
    // Persian / Arabic digit normalisation
    // -------------------------------------------------------------------------

    public static function normalizePersianDigits(string $str): string
    {
        $persian = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        $arabic  = ['٠','١','٢','٣','٤','٥','٦','٧','٨','٩'];
        $western = ['0','1','2','3','4','5','6','7','8','9'];

        $str = str_replace($persian, $western, $str);
        $str = str_replace($arabic,  $western, $str);
        return $str;
    }

    // -------------------------------------------------------------------------
    // Mobile
    // -------------------------------------------------------------------------

    /**
     * Normalise to 11-digit 09XXXXXXXXX format.
     * Accepts: +98..., 0098..., 98..., 09..., 9...
     */
    public static function normalizeMobile(string $raw): string
    {
        $digits = preg_replace('/\D/', '', self::normalizePersianDigits($raw));
        if ($digits === null) {
            return '';
        }
        // Strip country code
        if (str_starts_with($digits, '0098')) {
            $digits = '0' . substr($digits, 4);
        } elseif (str_starts_with($digits, '98') && strlen($digits) === 12) {
            $digits = '0' . substr($digits, 2);
        } elseif (!str_starts_with($digits, '0') && strlen($digits) === 10) {
            $digits = '0' . $digits;
        }
        return $digits;
    }

    public static function isValidMobile(string $normalised): bool
    {
        // Must be 11 digits starting with 09
        return (bool)preg_match('/^09[0-9]{9}$/', $normalised);
    }

    // -------------------------------------------------------------------------
    // Code Meli — mod-11 checksum
    // -------------------------------------------------------------------------

    public static function isValidNationalId(string $id): bool
    {
        $id = self::normalizePersianDigits($id);

        if (!preg_match('/^\d{10}$/', $id)) {
            return false;
        }

        // All same digits (e.g. 0000000000) are invalid
        if (strlen(count_chars($id, 3)) === 1) {
            return false;
        }

        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$id[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $check     = (int)$id[9];

        return ($remainder < 2 && $check === $remainder)
            || ($remainder >= 2 && $check === (11 - $remainder));
    }

    /**
     * Alias used by PatientController (isValidCodeMeli).
     */
    public static function isValidCodeMeli(string $id): bool
    {
        return self::isValidNationalId($id);
    }

    // -------------------------------------------------------------------------
    // Jalali date validation + Gregorian conversion
    // -------------------------------------------------------------------------

    /**
     * Accept formats: 1370/01/01  |  1370-01-01  |  ۱۳۷۰/۰۱/۰۱
     * Returns true if the Jalali date is structurally and calendrically valid.
     */
    public static function isValidJalaliDate(string $date): bool
    {
        $date = self::normalizePersianDigits($date);
        $date = str_replace('-', '/', $date);

        if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $date, $m)) {
            return false;
        }

        [$_, $year, $month, $day] = array_map('intval', $m);

        if ($year < 1200 || $year > 1500) {
            return false;
        }
        if ($month < 1 || $month > 12) {
            return false;
        }
        $maxDay = $month <= 6 ? 31 : ($month <= 11 ? 30 : (self::isJalaliLeapYear($year) ? 30 : 29));
        return $day >= 1 && $day <= $maxDay;
    }

    /**
     * Convert a validated Jalali date string to Gregorian Y-m-d.
     * Uses the algorithmic conversion (no external library required).
     */
    public static function jalaliToGregorian(string $jalali): string
    {
        $jalali = self::normalizePersianDigits($jalali);
        $jalali = str_replace('-', '/', $jalali);

        if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $jalali, $m)) {
            return '';
        }

        $jy = (int)$m[1];
        $jm = (int)$m[2];
        $jd = (int)$m[3];

        if (!self::isValidJalaliDate($jalali)) {
            return '';
        }

        $jy += 1595;
        $days = -355668
              + (365 * $jy)
              + (intdiv($jy, 33) * 8)
              + intdiv(($jy % 33) + 3, 4)
              + $jd
              + ($jm < 7 ? (($jm - 1) * 31) : ((($jm - 7) * 30) + 186));

        $gy = 400 * intdiv($days, 146097);
        $days %= 146097;

        if ($days > 36524) {
            $days--;
            $gy += 100 * intdiv($days, 36524);
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $gy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;
        $monthDays = [
            0, 31,
            (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0) ? 29 : 28,
            31, 30, 31, 30, 31, 31, 30, 31, 30, 31,
        ];

        $gm = 1;
        while ($gm <= 12 && $gd > $monthDays[$gm]) {
            $gd -= $monthDays[$gm];
            $gm++;
        }

        if ($gm > 12) {
            return '';
        }

        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }

    /**
     * Calculate completed age directly in the Persian (Jalali) calendar.
     * Today's date is evaluated in Asia/Tehran.
     */
    public static function ageFromJalaliDate(string $jalali): ?int
    {
        $jalali = self::normalizePersianDigits($jalali);
        $jalali = str_replace('-', '/', $jalali);

        if (!self::isValidJalaliDate($jalali)
            || !preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $jalali, $m)) {
            return null;
        }

        $birthY = (int)$m[1];
        $birthM = (int)$m[2];
        $birthD = (int)$m[3];

        $today = new \DateTimeImmutable('today', new \DateTimeZone('Asia/Tehran'));
        [$todayY, $todayM, $todayD] = self::gregorianToJalali(
            (int)$today->format('Y'),
            (int)$today->format('n'),
            (int)$today->format('j')
        );

        $age = $todayY - $birthY;
        if ($todayM < $birthM || ($todayM === $birthM && $todayD < $birthD)) {
            $age--;
        }

        return $age >= 0 ? $age : null;
    }

    /**
     * Convert Gregorian Y/M/D to Jalali [Y, M, D].
     */
    public static function gregorianToJalali(int $gy, int $gm, int $gd): array
    {
        $gdm = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];

        if ($gy > 1600) {
            $jy = 979;
            $gy -= 1600;
        } else {
            $jy = 0;
            $gy -= 621;
        }

        $gy2 = $gm > 2 ? $gy + 1 : $gy;
        $days = (365 * $gy)
              + intdiv($gy2 + 3, 4)
              - intdiv($gy2 + 99, 100)
              + intdiv($gy2 + 399, 400)
              - 80
              + $gd
              + $gdm[$gm - 1];

        $jy += 33 * intdiv($days, 12053);
        $days %= 12053;
        $jy += 4 * intdiv($days, 1461);
        $days %= 1461;

        if ($days > 365) {
            $jy += intdiv($days - 1, 365);
            $days = ($days - 1) % 365;
        }

        if ($days < 186) {
            $jm = 1 + intdiv($days, 31);
            $jd = 1 + ($days % 31);
        } else {
            $jm = 7 + intdiv($days - 186, 30);
            $jd = 1 + (($days - 186) % 30);
        }

        return [$jy, $jm, $jd];
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private static function isJalaliLeapYear(int $year): bool
    {
        $leapCycle = [1, 5, 9, 13, 17, 22, 26, 30];
        return in_array($year % 33, $leapCycle, true);
    }
}
