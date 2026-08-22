<?php
declare(strict_types=1);

namespace Tests\Unit;

use App\Services\LogSmsProvider;
use App\Services\SmsProvider;
use App\Services\SmsProviderChain;
use PHPUnit\Framework\TestCase;

final class SmsProviderChainTest extends TestCase
{
    public function testTypedSuccessFromConfiguredProvider(): void
    {
        $_ENV['APP_ENV'] = 'testing';
        $chain = new SmsProviderChain([new LogSmsProvider()]);
        $result = $chain->sendMessage('09120000000', 'hello');
        $this->assertTrue($result['ok']);
        $this->assertNotEmpty($result['message_id']);
        $this->assertNull($result['error']);
    }

    public function testRejectedProviderIsNotTreatedAsSuccess(): void
    {
        $fake = new class implements SmsProvider {
            public function sendOtp(string $mobile, string $code): array
            {
                return $this->sendReminder($mobile, $code);
            }
            public function sendReminder(string $mobile, string $message): array
            {
                unset($mobile, $message);
                return ['ok' => false, 'message_id' => null, 'error' => 'provider rejected'];
            }
        };
        $chain = new SmsProviderChain([$fake]);
        $result = $chain->sendMessage('09120000000', 'hello');
        $this->assertFalse($result['ok']);
        $this->assertSame('provider rejected', $result['error']);
    }

    public function testEmptyChainFailsClosed(): void
    {
        $chain = new SmsProviderChain([]);
        $result = $chain->sendOtp('09120000000', '12345');
        $this->assertFalse($result['ok']);
        $this->assertSame('No SMS provider configured', $result['error']);
    }
}
