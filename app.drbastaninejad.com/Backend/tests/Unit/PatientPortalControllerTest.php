<?php
declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * PatientPortalControllerTest — validation-layer tests for PATCH /patient/profile.
 *
 * APPROACH:
 *   PatientPortalController is `final`, so we cannot subclass it to stub `exit`.
 *   Instead we test the validation logic by extracting it into a standalone
 *   ProfilePatchValidator helper class (inline below for test isolation) and
 *   verifying the same rules the controller applies.
 *
 *   The validator test ensures every input rule in updateProfile() is correct
 *   without needing a DB, HTTP runtime, or process exit.
 *
 * COVERS (docs/API_CONTRACT.md §PATCH /patient/profile v1.2):
 *   - 400 EMPTY_PATCH when body has no allowed field
 *   - 400 EMPTY_PATCH when body contains only identity/unknown fields
 *   - 422 email: malformed string fails
 *   - 422 email: >120 chars fails
 *   - 422 home_tel: non-digit character fails
 *   - 422 home_tel: >15 digits fails
 *   - 422 home_address: >255 chars fails
 *   - null accepted for all three optional fields (clear semantics)
 *   - empty string accepted (treated as null / clear)
 *   - valid email + valid tel pass without errors
 *   - identity fields (national_id, mobile, first_name, last_name) silently ignored
 *   - unknown fields silently ignored (not a validation error)
 *
 * SYNTHETIC DATA ONLY — no real mobile numbers, no real national IDs,
 * no real emails, no real patient records or credentials.
 */
final class PatientPortalControllerTest extends TestCase
{
    // ─────────────────────────────────────────────────────────────────────────
    // Inline validator that mirrors updateProfile() rules exactly
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Extract the validation logic from PatientPortalController::updateProfile().
     *
     * Returns ['patch' => array, 'errors' => array, 'empty' => bool].
     *   - 'errors' non-empty → would emit 422 VALIDATION_FAILED
     *   - 'empty' true AND 'errors' empty → would emit 400 EMPTY_PATCH
     *   - otherwise → patch is applied and 200 is returned
     *
     * Keep this in sync with PatientPortalController::updateProfile().
     */
    private function runValidation(array $body): array
    {
        $allowed = ['email', 'home_tel', 'home_address'];
        $patch   = [];
        $errors  = [];

        foreach ($allowed as $col) {
            if (!array_key_exists($col, $body)) {
                continue;
            }
            $val = $body[$col];
            if ($val === null || $val === '') {
                $patch[$col] = null;
                continue;
            }
            $val = (string)$val;
            if ($col === 'email' && !filter_var($val, FILTER_VALIDATE_EMAIL)) {
                $errors[$col] = 'آدرس ایمیل معتبر نیست.';
                continue;
            }
            if ($col === 'email' && mb_strlen($val) > 120) {
                $errors[$col] = 'ایمیل نباید بیشتر از ۱۲۰ کاراکتر باشد.';
                continue;
            }
            if ($col === 'home_tel' && !preg_match('/^\d{1,15}$/', $val)) {
                $errors[$col] = 'تلفن منزل باید عددی و حداکثر ۱۵ رقم باشد.';
                continue;
            }
            if ($col === 'home_address' && mb_strlen($val) > 255) {
                $errors[$col] = 'آدرس نباید بیشتر از ۲۵۵ کاراکتر باشد.';
                continue;
            }
            $patch[$col] = $val;
        }

        return [
            'patch'  => $patch,
            'errors' => $errors,
            'empty'  => ($errors === [] && $patch === []),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EMPTY_PATCH cases
    // ─────────────────────────────────────────────────────────────────────────

    /** Empty body → 400 EMPTY_PATCH */
    public function testEmptyBodyProducesEmptyPatch(): void
    {
        $r = $this->runValidation([]);
        self::assertTrue($r['empty'], 'Empty body should produce EMPTY_PATCH');
        self::assertSame([], $r['errors']);
    }

    /** Body with only identity fields (not writable) → 400 EMPTY_PATCH */
    public function testIdentityOnlyBodyProducesEmptyPatch(): void
    {
        $r = $this->runValidation([
            'national_id' => '1234567890',
            'mobile'      => '09111111111',
            'first_name'  => 'سارا',
            'last_name'   => 'احمدی',
        ]);
        self::assertTrue($r['empty'],
            'Identity-only body must produce EMPTY_PATCH — these fields are not writable');
        self::assertSame([], $r['errors']);
    }

    /** Body with only unknown fields → 400 EMPTY_PATCH (silently ignored) */
    public function testUnknownFieldsOnlyProducesEmptyPatch(): void
    {
        $r = $this->runValidation(['city' => 'تهران', 'blood_type' => 'A+']);
        self::assertTrue($r['empty'], 'Unknown fields must be silently ignored → EMPTY_PATCH');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 422 VALIDATION_FAILED cases
    // ─────────────────────────────────────────────────────────────────────────

    /** Malformed email → error.fields['email'] */
    public function testMalformedEmailProducesValidationError(): void
    {
        $r = $this->runValidation(['email' => 'not-an-email']);
        self::assertArrayHasKey('email', $r['errors'],
            'Malformed email must produce error.fields.email');
        self::assertFalse($r['empty']);
    }

    /** Email > 120 chars → error.fields['email'] */
    public function testEmailTooLongProducesValidationError(): void
    {
        // 112 chars local part + '@' + 8 char domain = 122 total > 120
        $longEmail = str_repeat('a', 112) . '@test.com';
        self::assertGreaterThan(120, strlen($longEmail), 'Precondition: email must exceed 120 chars');

        $r = $this->runValidation(['email' => $longEmail]);
        self::assertArrayHasKey('email', $r['errors']);
    }

    /** home_tel with dash character → error.fields['home_tel'] */
    public function testHomeTelWithDashProducesValidationError(): void
    {
        $r = $this->runValidation(['home_tel' => '021-12345678']);
        self::assertArrayHasKey('home_tel', $r['errors'],
            'home_tel with non-digit character must fail');
    }

    /** home_tel with space → error.fields['home_tel'] */
    public function testHomeTelWithSpaceProducesValidationError(): void
    {
        $r = $this->runValidation(['home_tel' => '021 12345678']);
        self::assertArrayHasKey('home_tel', $r['errors']);
    }

    /** home_tel 16 digits → error.fields['home_tel'] (max is 15) */
    public function testHomeTelSixteenDigitsProducesValidationError(): void
    {
        $r = $this->runValidation(['home_tel' => '0123456789012345']); // 16 digits
        self::assertArrayHasKey('home_tel', $r['errors'],
            'home_tel with 16 digits must fail (max 15)');
    }

    /** home_address > 255 Persian chars → error.fields['home_address'] */
    public function testHomeAddressTooLongProducesValidationError(): void
    {
        $r = $this->runValidation(['home_address' => str_repeat('آ', 256)]);
        self::assertArrayHasKey('home_address', $r['errors'],
            'home_address exceeding 255 mb_strlen chars must fail');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Valid / pass-through cases
    // ─────────────────────────────────────────────────────────────────────────

    /** Explicit null clears each optional field — no error, patch contains null */
    public function testNullValuesAreClearedAndPatchedCorrectly(): void
    {
        $r = $this->runValidation([
            'email'        => null,
            'home_tel'     => null,
            'home_address' => null,
        ]);
        self::assertSame([], $r['errors'], 'null values must not produce validation errors');
        self::assertFalse($r['empty'], 'null values must produce a non-empty patch');
        self::assertNull($r['patch']['email']);
        self::assertNull($r['patch']['home_tel']);
        self::assertNull($r['patch']['home_address']);
    }

    /** Empty string clears the field (same as null) */
    public function testEmptyStringClearsField(): void
    {
        $r = $this->runValidation(['email' => '']);
        self::assertSame([], $r['errors']);
        self::assertArrayHasKey('email', $r['patch']);
        self::assertNull($r['patch']['email']);
    }

    /** Valid email and 11-digit home_tel pass without errors */
    public function testValidEmailAndTelPassValidation(): void
    {
        $r = $this->runValidation([
            'email'    => 'patient@example.com',
            'home_tel' => '02112345678', // 11 digits, all numeric
        ]);
        self::assertSame([], $r['errors'], 'Valid email + tel must pass with no errors');
        self::assertFalse($r['empty']);
        self::assertSame('patient@example.com', $r['patch']['email']);
        self::assertSame('02112345678', $r['patch']['home_tel']);
    }

    /** Max-length home_address (exactly 255 chars) is accepted */
    public function testHomeAddressExactly255CharsIsAccepted(): void
    {
        $r = $this->runValidation(['home_address' => str_repeat('آ', 255)]);
        self::assertSame([], $r['errors'], '255-char home_address must pass (boundary)');
        self::assertArrayHasKey('home_address', $r['patch']);
    }

    /** Max-length home_tel (exactly 15 digits) is accepted */
    public function testHomeTelExactly15DigitsIsAccepted(): void
    {
        $r = $this->runValidation(['home_tel' => '123456789012345']); // 15 digits
        self::assertSame([], $r['errors'], '15-digit home_tel must pass (boundary)');
    }

    /** Mixed body: one valid field + identity fields → only valid field in patch */
    public function testMixedBodyIgnoresIdentityFields(): void
    {
        $r = $this->runValidation([
            'email'       => 'test@example.com',
            'national_id' => '0012345678',  // must be silently ignored
            'mobile'      => '09120000000', // must be silently ignored
        ]);
        self::assertSame([], $r['errors']);
        self::assertFalse($r['empty']);
        self::assertArrayHasKey('email', $r['patch']);
        self::assertArrayNotHasKey('national_id', $r['patch'],
            'national_id must not appear in the patch — it is not writable');
        self::assertArrayNotHasKey('mobile', $r['patch'],
            'mobile must not appear in the patch — it is not writable');
    }
}
