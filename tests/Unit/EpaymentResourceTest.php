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
    public function testNewPreferredPaymentsAliasesWork(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-200',
            'payment_url' => 'https://test-pay.khalti.com/new',
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-200',
            'status' => 'Completed',
            'transaction_id' => 'khalti-txn-200',
            'total_amount' => 2000,
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_secret_key'), $transport);

        $session = $client->payments()->create(new EpaymentInitiateRequest(
            returnUrl: 'https://example.com/khalti/callback',
            websiteUrl: 'https://example.com',
            amount: 2000,
            purchaseOrderId: 'order-200',
            purchaseOrderName: 'Business Plan'
        ));
        $status = $client->payments()->status($session->pidx);

        $this->assertSame('pidx-200', $session->pidx);
        $this->assertTrue($status->isCompleted());
    }

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

        $initiate = $client->payments()->create(new EpaymentInitiateRequest(
            returnUrl: 'https://example.com/khalti/callback',
            websiteUrl: 'https://example.com',
            amount: 1000,
            purchaseOrderId: 'order-100',
            purchaseOrderName: 'Premium Plan'
        ));

        $this->assertSame('pidx-123', $initiate->pidx);
        $this->assertSame('https://test-pay.khalti.com/abc', $initiate->paymentUrl);

        $lookup = $client->payments()->status($initiate->pidx);
        $this->assertTrue($lookup->isCompleted());
        $this->assertSame(1000, $lookup->totalAmount);
        $this->assertSame('khalti-txn-1', $lookup->transactionId);

        $this->assertCount(2, $transport->requests);
        $this->assertStringContainsString('/epayment/initiate/', $transport->requests[0]->url);
        $this->assertStringContainsString('/epayment/lookup/', $transport->requests[1]->url);
    }
}
