<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\BookingSheetRowBuilder;
use PHPUnit\Framework\TestCase;

final class BookingSheetRowBuilderTest extends TestCase
{
    public function testHeadersMatchWindowsImportContractExactly(): void
    {
        $this->assertSame([
            'FirstName', 'LastName', 'FatherName', 'TavalodDay', 'TavalodMonth', 'TavalodYear',
            'HomeTel', 'Mobile', 'Mobile2', 'CodeAshnaei', 'CodeBimeh', 'CodeMeli', 'CodeJob',
            'HomeAd', 'Description', 'IsTransfer', 'drugs', 'difficult', 'morefmob',
        ], BookingSheetRowBuilder::HEADERS);
    }

    public function testBuildMapsOnlyKnownBookingFieldsWithoutInventingInsuranceData(): void
    {
        $row = BookingSheetRowBuilder::build([
            'first_name' => 'نام آزمایشی',
            'last_name' => 'خانوادگی آزمایشی',
            'birth_date_jalali' => '1370/5/2',
            'mobile' => '09000000000',
            'national_id' => '1234567891',
            'email' => 'synthetic@example.test',
            'medical_history' => 'سابقه آزمایشی',
            'medications' => 'داروی آزمایشی',
            'doctor_request' => 'درخواست آزمایشی',
        ]);

        $this->assertSame(BookingSheetRowBuilder::HEADERS, array_keys($row));
        $this->assertSame('02', $row['TavalodDay']);
        $this->assertSame('05', $row['TavalodMonth']);
        $this->assertSame('1370', $row['TavalodYear']);
        $this->assertSame('', $row['CodeBimeh']);
        $this->assertSame('داروی آزمایشی', $row['drugs']);
        $this->assertSame('سابقه آزمایشی', $row['difficult']);
        $this->assertStringContainsString('synthetic@example.test', $row['Description']);
        $this->assertStringContainsString('درخواست آزمایشی', $row['Description']);
    }
}
