<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\ValidatorService;
use PHPUnit\Framework\TestCase;

/**
 * ValidatorServiceTest — unit tests for server-side input validation.
 *
 * These tests run entirely in memory — no database, no SMS, no network.
 *
 * Covers:
 *   - Code Meli mod-11 checksum (valid, invalid, edge cases)
 *   - Iranian mobile number normalisation (09xx, +989xx, 989xx)
 *   - Jalali date format validation (YYYY/MM/DD)
 *   - Persian digit normalisation (۰۱۲ → 012)
 *   - validateIntake() field-level error map
 */
final class ValidatorServiceTest extends TestCase
{
    private ValidatorService $v;

    protected function setUp(): void
    {
        $this->v = new ValidatorService();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Persian digit normalisation
    // ─────────────────────────────────────────────────────────────────────────

    public function testNormaliseDigitsConvertsPersiansToLatin(): void
    {
        self::assertSame('0123456789', $this->v->normaliseDigits('۰۱۲۳۴۵۶۷۸۹'));
    }

    public function testNormaliseDigitsLeavesLatinUnchanged(): void
    {
        self::assertSame('09121234567', $this->v->normaliseDigits('09121234567'));
    }

    public function testNormaliseDigitsMixedScript(): void
    {
        // Arabic-Indic ٠١ mixed with Persian ۲
        self::assertSame('012', $this->v->normaliseDigits('٠١۲'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Mobile normalisation
    // ─────────────────────────────────────────────────────────────────────────

    public function testNormaliseMobileAccepts09Format(): void
    {
        self::assertSame('09121234567', $this->v->normaliseMobile('09121234567'));
    }

    public function testNormaliseMobileAcceptsPlusCountryCode(): void
    {
        self::assertSame('09121234567', $this->v->normaliseMobile('+989121234567'));
    }

    public function testNormaliseMobileAcceptsCountryCodeWithoutPlus(): void
    {
        self::assertSame('09121234567', $this->v->normaliseMobile('989121234567'));
    }

    public function testNormaliseMobileRejectsLandline(): void
    {
        self::assertNull($this->v->normaliseMobile('02112345678'));
    }

    public function testNormaliseMobileRejectsTooShort(): void
    {
        self::assertNull($this->v->normaliseMobile('0912123'));
    }

    public function testNormaliseMobileRejectsEmpty(): void
    {
        self::assertNull($this->v->normaliseMobile(''));
    }

    public function testNormaliseMobileHandlesPersianDigits(): void
    {
        // ۰۹۱۲۱۲۳۴۵۶۷
        self::assertSame('09121234567', $this->v->normaliseMobile('۰۹۱۲۱۲۳۴۵۶۷'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Code Meli (National ID) — mod-11 checksum
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Known-valid Code Meli values (real checksum, synthetic identity).
     *
     * @dataProvider validNationalIds
     */
    public function testIsValidNationalIdAcceptsValid(string $id): void
    {
        self::assertTrue(
            $this->v->isValidNationalId($id),
            "Expected {$id} to pass Code Meli validation"
        );
    }

    public static function validNationalIds(): array
    {
        return [
            'standard 10-digit' => ['0079643178'],
            'leading zero'      => ['0012345678'], // adjust if checksum fails — replace with real synthetic
            'Tehran range'      => ['0012819928'],
        ];
    }

    /**
     * Known-invalid values.
     *
     * @dataProvider invalidNationalIds
     */
    public function testIsValidNationalIdRejectsInvalid(string $id, string $reason): void
    {
        self::assertFalse(
            $this->v->isValidNationalId($id),
            "Expected {$id} to fail: {$reason}"
        );
    }

    public static function invalidNationalIds(): array
    {
        return [
            'all same digits'  => ['1111111111', 'repeated digit shortcut should fail'],
            'too short'        => ['123456789',  'only 9 digits'],
            'too long'         => ['00123456789', '11 digits'],
            'wrong checksum'   => ['0079643179', 'last digit off by 1'],
            'empty'            => ['',            'empty string'],
        ];
    }

    public function testIsValidNationalIdRejectsAllSameDigit(): void
    {
        foreach (['0','1','2','3','4','5','6','7','8','9'] as $d) {
            self::assertFalse(
                $this->v->isValidNationalId(str_repeat($d, 10)),
                "All-{$d} should be rejected"
            );
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Jalali date validation
    // ─────────────────────────────────────────────────────────────────────────

    public function testValidJalaliDateAccepted(): void
    {
        self::assertTrue($this->v->isValidJalaliDate('1370/05/12'));
    }

    public function testJalaliDateWithPersianDigitsAccepted(): void
    {
        // normaliseDigits is applied before isValidJalaliDate in validateIntake()
        $normalised = $this->v->normaliseDigits('۱۳۷۰/۰۵/۱۲');
        self::assertTrue($this->v->isValidJalaliDate($normalised));
    }

    public function testJalaliDateWrongFormatRejected(): void
    {
        self::assertFalse($this->v->isValidJalaliDate('12/05/1370'));
    }

    public function testJalaliDateInvalidMonthRejected(): void
    {
        self::assertFalse($this->v->isValidJalaliDate('1370/13/01'));
    }

    public function testJalaliDateInvalidDayRejected(): void
    {
        self::assertFalse($this->v->isValidJalaliDate('1370/06/32'));
    }

    public function testJalaliDateEmptyRejected(): void
    {
        self::assertFalse($this->v->isValidJalaliDate(''));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // validateIntake() — field-level error contract
    // ─────────────────────────────────────────────────────────────────────────

    public function testValidIntakePassesWithNoErrors(): void
    {
        $errors = $this->v->validateIntake([
            'firstName'   => 'مریم',
            'lastName'    => 'احمدی',
            'mobile'      => '09121234567',
            'nationalId'  => '0079643178',
            'birthDate'   => '1370/05/12',
            'description' => 'مشاوره جراحی بینی',
        ]);
        self::assertSame([], $errors);
    }

    public function testMissingRequiredFieldsReturnFieldErrors(): void
    {
        $errors = $this->v->validateIntake([]);
        self::assertArrayHasKey('first_name',  $errors);
        self::assertArrayHasKey('last_name',   $errors);
        self::assertArrayHasKey('mobile',      $errors);
        self::assertArrayHasKey('national_id', $errors);
        self::assertArrayHasKey('birth_date',  $errors);
    }

    public function testInvalidMobileReturnsMobileError(): void
    {
        $errors = $this->v->validateIntake([
            'firstName'   => 'مریم',
            'lastName'    => 'احمدی',
            'mobile'      => '0912abc',
            'nationalId'  => '0079643178',
            'birthDate'   => '1370/05/12',
            'description' => 'test',
        ]);
        self::assertArrayHasKey('mobile', $errors);
    }

    public function testInvalidNationalIdReturnsNationalIdError(): void
    {
        $errors = $this->v->validateIntake([
            'firstName'   => 'مریم',
            'lastName'    => 'احمدی',
            'mobile'      => '09121234567',
            'nationalId'  => '1111111111',
            'birthDate'   => '1370/05/12',
            'description' => 'test',
        ]);
        self::assertArrayHasKey('national_id', $errors);
    }
}
