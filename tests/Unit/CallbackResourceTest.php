<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Enum\OrderVerificationStatus;
use Khalti\Exception\ValidationException;
use Khalti\Khalti;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Tests\Fakes\InMemoryIdempotencyStore;
use Khalti\Tests\Fakes\InMemoryMismatchCounter;
use Khalti\Http\HttpResponse;
use Khalti\ValueObject\MoneyPaisa;
use Khalti\Verification\VerificationContext;
use PHPUnit\Framework\TestCase;

final class CallbackResourceTest extends TestCase
{
    public function testVerifyReturnsPaidForCompletedMatchingPayment(): void
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

        $client = Khalti::client(new ClientConfig(secretKey: 'test_secret_key'), $transport);

        $payload = $client->verification()->parseReturnQuery([
            'pidx' => 'pidx-100',
            'status' => 'Completed',
            'amount' => '1000',
            'transaction_id' => 'txn-100',
        ]);

        $result = $client->verification()->verify(
            payload: $payload,
            context: new VerificationContext(
                orderId: 'ORD-100',
                pidx: 'pidx-100',
                expectedAmount: MoneyPaisa::of(1000),
            )
        );

        $this->assertSame(OrderVerificationStatus::Paid, $result->status);
        $this->assertTrue($result->fulfillable);
    }

    public function testVerifyMarksDuplicateWhenIdempotencyKeyAlreadyExists(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-dup',
            'status' => 'Completed',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);
        $store = new InMemoryIdempotencyStore();
        $context = new VerificationContext(
            orderId: 'ORD-200',
            pidx: 'pidx-dup',
            expectedAmount: MoneyPaisa::of(1000)
        );
        $store->put($context->resolvedIdempotencyKey());

        $result = $client->verification()->verify(
            payload: $client->verification()->parseReturnQuery(['pidx' => 'pidx-dup']),
            context: $context,
            idempotencyStore: $store
        );

        $this->assertSame(OrderVerificationStatus::Duplicate, $result->status);
        $this->assertFalse($result->fulfillable);
    }

    public function testVerifyFailsOnAmountMismatchAndIncrementsCounter(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-101',
            'status' => 'Completed',
            'total_amount' => 500,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);
        $counter = new InMemoryMismatchCounter();

        $result = $client->verification()->verify(
            payload: $client->verification()->parseReturnQuery(['pidx' => 'pidx-101']),
            context: new VerificationContext(
                orderId: 'ORD-101',
                pidx: 'pidx-101',
                expectedAmount: MoneyPaisa::of(1000)
            ),
            mismatchCounter: $counter
        );

        $this->assertSame(OrderVerificationStatus::Failed, $result->status);
        $this->assertSame(1, $counter->counts['amount_mismatch'] ?? 0);
    }

    public function testVerifyFailsWhenReplayWindowExceeded(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-replay',
            'status' => 'Completed',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $result = $client->verification()->verify(
            payload: $client->verification()->parseReturnQuery(['pidx' => 'pidx-replay']),
            context: new VerificationContext(
                orderId: 'ORD-REPLAY',
                pidx: 'pidx-replay',
                expectedAmount: MoneyPaisa::of(1000),
                receivedAtUnix: time() - 400,
                replayWindowSeconds: 60
            )
        );

        $this->assertSame(OrderVerificationStatus::Failed, $result->status);
        $this->assertFalse($result->verified);
    }

    public function testVerifyFailsWhenPidxMismatchesExpectedContext(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-query',
            'status' => 'Pending',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $result = $client->verification()->verify(
            payload: $client->verification()->parseReturnQuery(['pidx' => 'pidx-query']),
            context: new VerificationContext(
                orderId: 'ORD-X',
                pidx: 'pidx-expected',
                expectedAmount: MoneyPaisa::of(1000)
            )
        );

        $this->assertSame(OrderVerificationStatus::Failed, $result->status);
    }

    public function testFromQueryRequiresPidx(): void
    {
        $transport = new FakeTransport();
        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(ValidationException::class);
        $client->verification()->parseReturnQuery([]);
    }
}
