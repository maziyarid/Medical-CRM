<?php
declare(strict_types=1);

namespace App\Services;

/**
 * JalaliConverter — pure PHP Jalali (Solar Hijri) ↔ Gregorian converter.
 *
 * No external dependencies. Algorithm: astronomical calculation matching
 * the official Iranian calendar (identical to the jalali.js client-side util).
 *
 * Usage:
 *   [$gy, $gm, $gd] = JalaliConverter::toGregorian(1370, 5, 12);
 *   [$jy, $jm, $jd] = JalaliConverter::toJalali(1991, 8, 3);
 *   $gDate = JalaliConverter::jalaliStringToGregorian('1370/05/12'); // '1991-08-03'
 *
 * Returns null from jalaliStringToGregorian() if the input is invalid.
 */
final class JalaliConverter
{
    /**
     * Convert a Jalali date to Gregorian.
     *
     * @return array{int,int,int}  [$year, $month, $day] in Gregorian
     */
    public static function toGregorian(int $jy, int $jm, int $jd): array
    {
        $jy += 1595;
        $days = -355779
            + (365 * $jy)
            + ((int)(($jy + 3) / 4))
            - ((int)(($jy + 99) / 100))
            + ((int)(($jy + 199) / 400))
            + $jd
            + (($jm <= 6)
                ? (($jm - 1) * 31)
                : ((($jm - 7) * 30) + 186));

        $gy = 400 * ((int)($days / 146097));
        $days %= 146097;

        if ($days > 36524) {
            $days--;
            $gy += 100 * ((int)($days / 36524));
            $days %= 36524;
            if ($days >= 365) {
                $days++;
            }
        }

        $gy  += 4 * ((int)($days / 1461));
        $days %= 1461;

        if ($days > 365) {
            $gy += (int)(($days - 1) / 365);
            $days = ($days - 1) % 365;
        }

        $gd = $days + 1;

        $months = [29, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        // Leap-year correction for February
        if ((($gy % 4 === 0) && ($gy % 100 !== 0)) || ($gy % 400 === 0)) {
            $months[2] = 29;
        }

        $gm = 1;
        foreach ($months as $i => $daysInMonth) {
            if ($i === 0) {
                continue; // index 0 = 29 (placeholder to keep 1-based month index)
            }
            if ($gd <= $daysInMonth) {
                $gm = $i;
                break;
            }
            $gd -= $daysInMonth;
        }

        return [$gy, $gm, $gd];
    }

    /**
     * Convert a Gregorian date to Jalali.
     *
     * @return array{int,int,int}  [$year, $month, $day] in Jalali
     */
    public static function toJalali(int $gy, int $gm, int $gd): array
    {
        $g_d_no = 365 * $gy
            + (int)(($gy + 3) / 4)
            - (int)(($gy + 99) / 100)
            + (int)(($gy + 399) / 400);

        for ($i = 0; $i < ($gm - 1); $i++) {
            $g_d_no += [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][$i + 1];
        }
        if ($gm > 2 && ((($gy % 4 === 0) && ($gy % 100 !== 0)) || ($gy % 400 === 0))) {
            $g_d_no++;
        }
        $g_d_no += $gd - 1;

        $j_d_no = $g_d_no - 79;

        $j_np  = (int)($j_d_no / 12053);
        $j_d_no %= 12053;

        $jy = 979 + 33 * $j_np + 4 * (int)($j_d_no / 1461);

        $j_d_no %= 1461;

        if ($j_d_no >= 366) {
            $jy += (int)(($j_d_no - 1) / 365);
            $j_d_no = ($j_d_no - 1) % 365;
        }

        for ($i = 0; $i < 11 && $j_d_no >= [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30][$i]; $i++) {
            $j_d_no -= [31, 31, 31, 31, 31, 31, 30, 30, 30, 30, 30][$i];
        }

        $jm = $i + 1;
        $jd = $j_d_no + 1;

        return [$jy, $jm, $jd];
    }

    /**
     * Parse a Jalali string 'YYYY/MM/DD' and return 'YYYY-MM-DD' Gregorian.
     * Returns null if the input does not match the expected format.
     */
    public static function jalaliStringToGregorian(string $jalali): ?string
    {
        if (!preg_match('/^(\d{4})\/(\d{1,2})\/(\d{1,2})$/', $jalali, $m)) {
            return null;
        }
        [$gy, $gm, $gd] = self::toGregorian((int)$m[1], (int)$m[2], (int)$m[3]);
        return sprintf('%04d-%02d-%02d', $gy, $gm, $gd);
    }
}
