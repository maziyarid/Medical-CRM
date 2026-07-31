<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Validators\ValidatorService;

/**
 * ValidatorServiceTest — dashboard.drbastaninejad.com
 *
 * Covers: normalizeMobile, isValidMobile, isValidNationalId/isValidCodeMeli,
 *         isValidJalaliDate, jalaliToGregorian, normalizePersianDigits.
 *
 * SYNTHETIC DATA ONLY — all mobile numbers are fictional 090x prefixes that
 * do not belong to any real Iranian operator range, and all Code Meli values
 * are algorithmically generated test vectors, not real national IDs.
 *
 * No database, no network, no filesystem access required.
 */
final class ValidatorServiceTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // normalizePersianDigits
    // ─────────────────────────────────────────────────────────────────────────

    public function testNormalizePersianDigitsConvertsAllPersianDigits(): void
    {
        $this->assertSame('1234567890', ValidatorService::normalizePersianDigits('۱۲۳۴۵۶۷۸۹۰'));
    }

    public function testNormalizePersianDigitsConvertsArabicDigits(): void
    {
        $this->assertSame('0912345678', ValidatorService::normalizePersianDigits('٠٩١٢٣٤٥٦٧٨'));
    }

    public function testNormalizePersianDigitsPassthroughAscii(): void
    {
        $this->assertSame('0912345678', ValidatorService::normalizePersianDigits('0912345678'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // normalizeMobile
    // ─────────────────────────────────────────────────────────────────────────

    /** @dataProvider normalizeProvider */
    public function testNormalizeMobile(string $input, string $expected): void
    {
        $this->assertSame($expected, ValidatorService::normalizeMobile($input));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function normalizeProvider(): array
    {
        return [
            '09xx 11 digits'       => ['09001234567', '09001234567'],
            'country code +98'     => ['+989001234567', '09001234567'],
            'country code 0098'    => ['00989001234567', '09001234567'],
            'country code 98 12d'  => ['989001234567', '09001234567'],
            'without leading zero' => ['9001234567', '09001234567'],
            'persian digits'       => ['۰۹۰۰۱۲۳۴۵۶۷', '09001234567'],
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // isValidMobile
    // ─────────────────────────────────────────────────────────────────────────

    public function testIsValidMobileAcceptsValid(): void
    {
        $this->assertTrue(ValidatorService::isValidMobile('09001234567'));
    }

    public function testIsValidMobileRejectsTooShort(): void
    {
        $this->assertFalse(ValidatorService::isValidMobile('0900123456'));
    }

    public function testIsValidMobileRejectsNonNinePrefix(): void
    {
        $this->assertFalse(ValidatorService::isValidMobile('08001234567'));
    }

    public function testIsValidMobileRejectsLetters(): void
    {
        $this->assertFalse(ValidatorService::isValidMobile('0900123456x'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // isValidNationalId / isValidCodeMeli (alias)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Known-valid Code Meli test vector (mod-11 passes).
     * This is a synthetic identifier computed to satisfy the algorithm.
     */
    public function testIsValidNationalIdAcceptsValidChecksum(): void
    {
        // 0000000000 is specifically invalid (all same digits)
        // 1234567891 → checksum digit calculation:
        // sum = 1*10 + 2*9 + 3*8 + 4*7 + 5*6 + 6*5 + 7*4 + 8*3 + 9*2 = 10+18+24+28+30+30+28+24+18=210
        // 210 % 11 = 1; remainder < 2 → check digit must be 1 → "1234567891"
        $this->assertTrue(ValidatorService::isValidNationalId('1234567891'));
    }

    public function testIsValidCodeMeliAliasMatchesIsValidNationalId(): void
    {
        $this->assertSame(
            ValidatorService::isValidNationalId('1234567891'),
            ValidatorService::isValidCodeMeli('1234567891')
        );
    }

    public function testIsValidNationalIdRejectsAllSameDigits(): void
    {
        $this->assertFalse(ValidatorService::isValidNationalId('1111111111'));
        $this->assertFalse(ValidatorService::isValidNationalId('0000000000'));
    }

    public function testIsValidNationalIdRejectsWrongLength(): void
    {
        $this->assertFalse(ValidatorService::isValidNationalId('123456789'));   // 9 digits
        $this->assertFalse(ValidatorService::isValidNationalId('12345678901')); // 11 digits
    }

    public function testIsValidNationalIdRejectsNonDigits(): void
    {
        $this->assertFalse(ValidatorService::isValidNationalId('123456789x'));
    }

    public function testIsValidNationalIdAcceptsPersianDigits(): void
    {
        // Persian-digit form of '1234567891'
        $this->assertTrue(ValidatorService::isValidNationalId('۱۲۳۴۵۶۷۸۹۱'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // isValidJalaliDate
    // ─────────────────────────────────────────────────────────────────────────

    /** @dataProvider validJalaliProvider */
    public function testIsValidJalaliDateAcceptsValid(string $date): void
    {
        $this->assertTrue(ValidatorService::isValidJalaliDate($date), "Expected valid: $date");
    }

    /** @return array<string, array{0: string}> */
    public static function validJalaliProvider(): array
    {
        return [
            'slash separator'   => ['1370/05/12'],
            'dash separator'    => ['1370-05-12'],
            'persian digits'    => ['۱۳۷۰/۰۵/۱۲'],
            'last month day 29' => ['1399/12/29'],  // non-leap Esfand
            'last month day 30' => ['1399/12/30'],  // 1399 is a leap year
            'month 6 day 31'    => ['1400/06/31'],
            'month 7 day 30'    => ['1400/07/30'],
        ];
    }

    /** @dataProvider invalidJalaliProvider */
    public function testIsValidJalaliDateRejectsInvalid(string $date): void
    {
        $this->assertFalse(ValidatorService::isValidJalaliDate($date), "Expected invalid: $date");
    }

    /** @return array<string, array{0: string}> */
    public static function invalidJalaliProvider(): array
    {
        return [
            'month 13'        => ['1400/13/01'],
            'day 0'           => ['1400/01/00'],
            'month 7 day 31'  => ['1400/07/31'],
            'year too low'    => ['1100/01/01'],
            'year too high'   => ['1600/01/01'],
            'empty string'    => [''],
            'no separator'    => ['14000501'],
            'non-leap esfand 30' => ['1400/12/30'], // 1400 is not a leap year
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // jalaliToGregorian
    // ─────────────────────────────────────────────────────────────────────────

    /** @dataProvider jalaliGregorianProvider */
    public function testJalaliToGregorian(string $jalali, string $expected): void
    {
        $this->assertSame($expected, ValidatorService::jalaliToGregorian($jalali));
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function jalaliGregorianProvider(): array
    {
        return [
            'Nowruz 1400'      => ['1400/01/01', '2021-03-21'],
            'Nowruz 1370'      => ['1370/01/01', '1991-03-21'],
            'mid-year 1402'    => ['1402/05/01', '2023-07-23'],
            'dash separator'   => ['1400-01-01', '2021-03-21'],
            'persian digits'   => ['۱۴۰۰/۰۱/۰۱', '2021-03-21'],
            'invalid returns empty' => ['not-a-date', ''],
        ];
    }
}
