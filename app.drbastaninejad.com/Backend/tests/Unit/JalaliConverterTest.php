<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\JalaliConverter;
use PHPUnit\Framework\TestCase;

/**
 * JalaliConverterTest — unit tests for the pure-PHP Jalali↔Gregorian converter.
 *
 * No database, no network. Purely algorithmic.
 *
 * Known-correct conversion pairs verified against:
 *   - The official Iranian calendar tables
 *   - The jalali.js client-side implementation (must produce identical results)
 *
 * SYNTHETIC DATA ONLY — birth dates used are fictitious.
 */
final class JalaliConverterTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // toGregorian()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider jalaliToGregorianPairs
     */
    public function testToGregorianKnownPairs(
        int $jy, int $jm, int $jd,
        int $gy, int $gm, int $gd,
        string $label
    ): void {
        [$resY, $resM, $resD] = JalaliConverter::toGregorian($jy, $jm, $jd);
        self::assertSame([$gy, $gm, $gd], [$resY, $resM, $resD], $label);
    }

    public static function jalaliToGregorianPairs(): array
    {
        return [
            // [jy, jm, jd, gy, gm, gd, label]
            [1370,  5, 12, 1991,  8,  3, '1370/05/12 → 1991-08-03'],
            [1400,  1,  1, 2021,  3, 21, 'Nowruz 1400 → 2021-03-21'],
            [1403,  6, 31, 2024,  9, 21, 'Last day of Shahrivar 1403'],
            [1404, 12, 29, 2026,  3, 20, 'Last day of non-leap Esfand 1404'],
            [1379,  1,  1, 2000,  3, 20, 'Nowruz 1379 (Y2K year)'],
            [1357, 11, 22, 1979,  2, 11, 'Revolution Day 1357'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // toJalali()
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider gregorianToJalaliPairs
     */
    public function testToJalaliKnownPairs(
        int $gy, int $gm, int $gd,
        int $jy, int $jm, int $jd,
        string $label
    ): void {
        [$resY, $resM, $resD] = JalaliConverter::toJalali($gy, $gm, $gd);
        self::assertSame([$jy, $jm, $jd], [$resY, $resM, $resD], $label);
    }

    public static function gregorianToJalaliPairs(): array
    {
        return [
            // [gy, gm, gd, jy, jm, jd, label]
            [1991,  8,  3, 1370,  5, 12, '1991-08-03 → 1370/05/12'],
            [2021,  3, 21, 1400,  1,  1, '2021-03-21 → Nowruz 1400'],
            [2024,  9, 21, 1403,  6, 31, '2024-09-21 → 1403/06/31'],
            [1979,  2, 11, 1357, 11, 22, '1979-02-11 → 1357/11/22'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Round-trip: Jalali → Gregorian → Jalali
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * @dataProvider roundTripDates
     */
    public function testRoundTripJalaliToGregorianAndBack(int $jy, int $jm, int $jd): void
    {
        [$gy, $gm, $gd] = JalaliConverter::toGregorian($jy, $jm, $jd);
        [$rjy, $rjm, $rjd] = JalaliConverter::toJalali($gy, $gm, $gd);
        self::assertSame([$jy, $jm, $jd], [$rjy, $rjm, $rjd],
            "Round-trip failed for {$jy}/{$jm}/{$jd}");
    }

    public static function roundTripDates(): array
    {
        return [
            [1370,  1,  1],
            [1380,  6, 31],
            [1390, 12, 29],
            [1400,  7, 15],
            [1404,  1,  1],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // jalaliStringToGregorian()
    // ─────────────────────────────────────────────────────────────────────────

    public function testJalaliStringToGregorianValidInput(): void
    {
        self::assertSame('1991-08-03', JalaliConverter::jalaliStringToGregorian('1370/05/12'));
    }

    public function testJalaliStringToGregorianNowruz(): void
    {
        self::assertSame('2021-03-21', JalaliConverter::jalaliStringToGregorian('1400/01/01'));
    }

    public function testJalaliStringToGregorianReturnsNullOnBadFormat(): void
    {
        self::assertNull(JalaliConverter::jalaliStringToGregorian('12-05-1370'));
    }

    public function testJalaliStringToGregorianReturnsNullOnEmpty(): void
    {
        self::assertNull(JalaliConverter::jalaliStringToGregorian(''));
    }

    public function testJalaliStringToGregorianReturnsNullOnPartial(): void
    {
        self::assertNull(JalaliConverter::jalaliStringToGregorian('1370/05'));
    }

    public function testJalaliStringToGregorianPadsMonthAndDay(): void
    {
        // Single-digit month/day must also work
        self::assertSame('1991-08-03', JalaliConverter::jalaliStringToGregorian('1370/5/12'));
    }
}
