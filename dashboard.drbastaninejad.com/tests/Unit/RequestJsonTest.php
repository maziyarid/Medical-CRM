<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestJsonTest extends TestCase
{
    public function testEmptyBodyIsEmptyArray(): void
    {
        $this->assertSame([], Request::decodeJsonBody(''));
        $this->assertSame([], Request::decodeJsonBody('   '));
    }

    public function testObjectBodyDecodes(): void
    {
        $this->assertSame(['patient_id' => 1], Request::decodeJsonBody('{"patient_id":1}'));
    }

    public function testMalformedJsonThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed JSON');
        Request::decodeJsonBody('{not json');
    }

    public function testJsonNullThrowsRatherThanBecomingEmptyPayload(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed JSON');
        Request::decodeJsonBody('null');
    }

    public function testJsonScalarThrows(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Malformed JSON');
        Request::decodeJsonBody('"hello"');
    }
}
