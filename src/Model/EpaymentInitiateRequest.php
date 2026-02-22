<?php

declare(strict_types=1);

namespace Khalti\Model;

use InvalidArgumentException;

readonly class EpaymentInitiateRequest
{
    /**
     * @param array<string,mixed>|null $customerInfo
     * @param array<int,array<string,mixed>>|null $amountBreakdown
     * @param array<int,array<string,mixed>>|null $productDetails
     * @param array<string,mixed>|null $merchantExtra
     */
    public function __construct(
        public string $returnUrl,
        public string $websiteUrl,
        public int $amount,
        public string $purchaseOrderId,
        public string $purchaseOrderName,
        public ?array $customerInfo = null,
        public ?array $amountBreakdown = null,
        public ?array $productDetails = null,
        public ?array $merchantExtra = null
    ) {
        if (trim($this->returnUrl) === '') {
            throw new InvalidArgumentException('returnUrl is required.');
        }

        if (trim($this->websiteUrl) === '') {
            throw new InvalidArgumentException('websiteUrl is required.');
        }

        if ($this->amount < 1) {
            throw new InvalidArgumentException('amount must be a positive integer.');
        }

        if (trim($this->purchaseOrderId) === '') {
            throw new InvalidArgumentException('purchaseOrderId is required.');
        }

        if (trim($this->purchaseOrderName) === '') {
            throw new InvalidArgumentException('purchaseOrderName is required.');
        }
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        $payload = [
            'return_url' => $this->returnUrl,
            'website_url' => $this->websiteUrl,
            'amount' => $this->amount,
            'purchase_order_id' => $this->purchaseOrderId,
            'purchase_order_name' => $this->purchaseOrderName,
        ];

        if ($this->customerInfo !== null) {
            $payload['customer_info'] = $this->customerInfo;
        }

        if ($this->amountBreakdown !== null) {
            $payload['amount_breakdown'] = $this->amountBreakdown;
        }

        if ($this->productDetails !== null) {
            $payload['product_details'] = $this->productDetails;
        }

        if ($this->merchantExtra !== null) {
            $payload['merchant_extra'] = $this->merchantExtra;
        }

        return $payload;
    }
}
