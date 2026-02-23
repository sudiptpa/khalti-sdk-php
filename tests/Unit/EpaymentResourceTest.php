<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Enum\Environment;
use Khalti\Khalti;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class EpaymentResourceTest extends TestCase
{
    public function testCreateAndStatusFlowMapsModels(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-123',
            'payment_url' => 'https://test-pay.khalti.com/abc',
            'expires_at' => '2026-01-01T00:00:00+05:45',
            'expires_in' => 1800,
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-123',
            'status' => 'Completed',
            'transaction_id' => 'khalti-txn-1',
            'total_amount' => 1000,
            'fee' => 30,
            'refunded' => false,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig(
            secretKey: 'test_secret_key',
            environment: Environment::Sandbox
        ), $transport);

        $session = $client->payments()->create(new EpaymentInitiateRequest(
            returnUrl: 'https://example.com/khalti/callback',
            websiteUrl: 'https://example.com',
            amount: 1000,
            purchaseOrderId: 'order-100',
            purchaseOrderName: 'Premium Plan'
        ));

        $lookup = $client->payments()->status($session->pidx);

        $this->assertSame('pidx-123', $session->pidx);
        $this->assertSame('https://test-pay.khalti.com/abc', $session->paymentUrl);
        $this->assertTrue($lookup->isCompleted());
        $this->assertSame(1000, $lookup->totalAmount);
        $this->assertSame('khalti-txn-1', $lookup->transactionId);
    }

    public function testInitiateAndLookupAliasesUseSameFlow(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-200',
            'payment_url' => 'https://test-pay.khalti.com/xyz',
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-200',
            'status' => 'Pending',
            'total_amount' => 1500,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $session = $client->payments()->initiate(new EpaymentInitiateRequest(
            returnUrl: 'https://example.com/khalti/callback',
            websiteUrl: 'https://example.com',
            amount: 1500,
            purchaseOrderId: 'order-200',
            purchaseOrderName: 'Pro Plan'
        ));

        $lookup = $client->payments()->lookup($session->pidx);

        $this->assertSame('pidx-200', $session->pidx);
        $this->assertSame(1500, $lookup->totalAmount);
        $this->assertCount(2, $transport->requests);
    }

    public function testWaitForCompletionPollsUntilCompleted(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-poll',
            'status' => 'Pending',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-poll',
            'status' => 'Completed',
            'total_amount' => 1000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);
        $result = $client->payments()->waitForCompletion('pidx-poll', timeoutSeconds: 5, intervalSeconds: 1);

        $this->assertTrue($result->isCompleted());
        $this->assertCount(2, $transport->requests);
    }
}
