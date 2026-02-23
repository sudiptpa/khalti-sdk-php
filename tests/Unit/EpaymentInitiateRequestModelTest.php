<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Model\AmountBreakdownItem;
use Khalti\Model\CustomerInfo;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\Model\ProductDetail;
use PHPUnit\Framework\TestCase;

final class EpaymentInitiateRequestModelTest extends TestCase
{
    public function testModelObjectsSerializeToExpectedPayload(): void
    {
        $request = EpaymentInitiateRequest::make(
            returnUrl: 'https://example.com/khalti/return',
            websiteUrl: 'https://example.com',
            amount: 1000,
            purchaseOrderId: 'ORD-1001',
            purchaseOrderName: 'Pro Subscription',
        )
            ->setCustomerInfo(new CustomerInfo(
                name: 'Sujip Thapa',
                email: 'sudiptpa@gmail.com',
                phone: '9800000000',
            ))
            ->addAmountBreakdownItem(new AmountBreakdownItem('Subtotal', 900))
            ->addAmountBreakdownItem(new AmountBreakdownItem('Tax', 100))
            ->addProductDetail(new ProductDetail(
                identity: 'SKU-1001',
                name: 'Pro Subscription',
                totalPrice: 1000,
                quantity: 1,
                unitPrice: 1000,
            ))
            ->setMerchantExtra(['ref' => 'INV-1001']);

        $payload = $request->toArray();

        $this->assertSame('https://example.com/khalti/return', $payload['return_url']);
        $this->assertSame('ORD-1001', $payload['purchase_order_id']);
        $this->assertSame('Sujip Thapa', $payload['customer_info']['name']);
        $this->assertSame('Subtotal', $payload['amount_breakdown'][0]['name']);
        $this->assertSame('SKU-1001', $payload['product_details'][0]['identity']);
        $this->assertSame('INV-1001', $payload['merchant_extra']['ref']);
    }

    public function testArrayPayloadsAreStillAcceptedForBackwardCompatibility(): void
    {
        $request = new EpaymentInitiateRequest(
            returnUrl: 'https://example.com/khalti/return',
            websiteUrl: 'https://example.com',
            amount: 1000,
            purchaseOrderId: 'ORD-1001',
            purchaseOrderName: 'Pro Subscription',
            customerInfo: [
                'name' => 'Sujip Thapa',
                'email' => 'sudiptpa@gmail.com',
                'phone' => '9800000000',
            ],
            amountBreakdown: [
                ['name' => 'Subtotal', 'amount' => 900],
                ['name' => 'Tax', 'amount' => 100],
            ],
            productDetails: [
                [
                    'identity' => 'SKU-1001',
                    'name' => 'Pro Subscription',
                    'total_price' => 1000,
                    'quantity' => 1,
                    'unit_price' => 1000,
                ],
            ],
        );

        $payload = $request->toArray();

        $this->assertSame('Sujip Thapa', $payload['customer_info']['name']);
        $this->assertSame(2, count($payload['amount_breakdown']));
        $this->assertSame(1, count($payload['product_details']));
    }
}
