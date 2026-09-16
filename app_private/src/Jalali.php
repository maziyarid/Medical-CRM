<?php
declare(strict_types=1);
namespace App;
use InvalidArgumentException;

final class Jalali {
    /** Gregorian -> Jalali conversion for civil dates. */
    public static function fromGregorian(int $gy, int $gm, int $gd): array {
        if (!checkdate($gm, $gd, $gy)) throw new InvalidArgumentException('Invalid Gregorian date.');
        $gdm = [31,28,31,30,31,30,31,31,30,31,30,31];
        $gy2 = $gy - 1600; $gm2 = $gm - 1; $gd2 = $gd - 1;
        $gDayNo = 365 * $gy2 + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400);
        for ($i=0; $i<$gm2; $i++) $gDayNo += $gdm[$i];
        $leap = (($gy % 4 === 0 && $gy % 100 !== 0) || $gy % 400 === 0);
        if ($gm2 > 1 && $leap) $gDayNo++;
        $gDayNo += $gd2;
        $jDayNo = $gDayNo - 79;
        $jNp = intdiv($jDayNo, 12053); $jDayNo %= 12053;
        $jy = 979 + 33 * $jNp + 4 * intdiv($jDayNo, 1461); $jDayNo %= 1461;
        if ($jDayNo >= 366) { $jy += intdiv($jDayNo - 1, 365); $jDayNo = ($jDayNo - 1) % 365; }
        if ($jDayNo < 186) { $jm = 1 + intdiv($jDayNo, 31); $jd = 1 + ($jDayNo % 31); }
        else { $jm = 7 + intdiv($jDayNo - 186, 30); $jd = 1 + (($jDayNo - 186) % 30); }
        return ['year'=>$jy,'month'=>$jm,'day'=>$jd];
    }
}
