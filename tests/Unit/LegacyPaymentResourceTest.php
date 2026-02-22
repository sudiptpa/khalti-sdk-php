<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class LegacyPaymentResourceTest extends TestCase
{
    public function testVerifyAndStatusCallsLegacyEndpoints(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'token' => 'token-1',
            'amount' => 1000,
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'idx' => 'idx-1',
            'state' => [
                'name' => 'Complete',
            ],
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_secret_key'), $transport);

        $verify = $client->legacyPayments()->verify('token-1', 1000);
        $status = $client->legacyPayments()->status('token-1', 1000);

        $this->assertTrue($verify->success);
        $this->assertTrue($status->success);
        $this->assertCount(2, $transport->requests);
        $this->assertStringContainsString('/payment/verify/', $transport->requests[0]->url);
        $this->assertStringContainsString('/payment/status/', $transport->requests[1]->url);
    }
}
