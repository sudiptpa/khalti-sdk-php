<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Enum\Environment;
use Khalti\Khalti;
use Khalti\Exception\ValidationException;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class CallbackResourceTest extends TestCase
{
    public function testNewPreferredVerificationAliasesWork(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-102',
            'status' => 'Completed',
            'transaction_id' => 'txn-102',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_secret_key'), $transport);

        $returnPayload = $client->verification()->parseReturnQuery([
            'pidx' => 'pidx-102',
            'status' => 'Completed',
        ]);
        $decision = $client->verification()->verify($returnPayload, expectedAmount: 1000);

        $this->assertTrue($decision->verified);
        $this->assertTrue($decision->successful);
    }

    public function testConfirmReturnsSuccessfulDecisionWhenLookupIsCompletedAndAmountMatches(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-100',
            'status' => 'Completed',
            'transaction_id' => 'txn-100',
            'total_amount' => 1000,
            'fee' => 30,
            'refunded' => false,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig(
            secretKey: 'test_secret_key',
            environment: Environment::Sandbox
        ), $transport);

        $callback = $client->verification()->parseReturnQuery([
            'pidx' => 'pidx-100',
            'status' => 'Completed',
            'amount' => '1000',
            'transaction_id' => 'txn-100',
        ]);

        $decision = $client->verification()->verify($callback, expectedAmount: 1000);

        $this->assertTrue($decision->verified);
        $this->assertTrue($decision->successful);
    }

    public function testConfirmFailsOnAmountMismatch(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-101',
            'status' => 'Completed',
            'total_amount' => 500,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);
        $callback = $client->verification()->parseReturnQuery(['pidx' => 'pidx-101']);
        $decision = $client->verification()->verify($callback, expectedAmount: 1000);

        $this->assertFalse($decision->verified);
        $this->assertFalse($decision->successful);
    }

    public function testFromQueryRequiresPidx(): void
    {
        $transport = new FakeTransport();
        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(ValidationException::class);
        $client->verification()->parseReturnQuery([]);
    }
}
