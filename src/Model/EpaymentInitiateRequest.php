<?php

declare(strict_types=1);

namespace Khalti\Model;

use InvalidArgumentException;

final class EpaymentInitiateRequest
{
    /** @var list<AmountBreakdownItem|array<string,mixed>>|null */
    private ?array $amountBreakdown;

    /** @var list<ProductDetail|array<string,mixed>>|null */
    private ?array $productDetails;

    /** @var array<string,mixed>|null */
    private ?array $merchantExtra;

    /** @var CustomerInfo|array<string,mixed>|null */
    private CustomerInfo|array|null $customerInfo;

    /**
     * @param CustomerInfo|array<string,mixed>|null $customerInfo
     * @param list<AmountBreakdownItem|array<string,mixed>>|null $amountBreakdown
     * @param list<ProductDetail|array<string,mixed>>|null $productDetails
     * @param array<string,mixed>|null $merchantExtra
     */
    public function __construct(
        private string $returnUrl,
        private string $websiteUrl,
        private int $amount,
        private string $purchaseOrderId,
        private string $purchaseOrderName,
        CustomerInfo|array|null $customerInfo = null,
        ?array $amountBreakdown = null,
        ?array $productDetails = null,
        ?array $merchantExtra = null
    ) {
        $this->customerInfo = $customerInfo;
        $this->amountBreakdown = $amountBreakdown;
        $this->productDetails = $productDetails;
        $this->merchantExtra = $merchantExtra;

        $this->validate();
    }

    public static function make(
        string $returnUrl,
        string $websiteUrl,
        int $amount,
        string $purchaseOrderId,
        string $purchaseOrderName
    ): self {
        return new self(
            returnUrl: $returnUrl,
            websiteUrl: $websiteUrl,
            amount: $amount,
            purchaseOrderId: $purchaseOrderId,
            purchaseOrderName: $purchaseOrderName,
        );
    }

    public function getReturnUrl(): string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(string $returnUrl): self
    {
        $this->returnUrl = $returnUrl;
        $this->validate();

        return $this;
    }

    public function getWebsiteUrl(): string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;
        $this->validate();

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        $this->validate();

        return $this;
    }

    public function getPurchaseOrderId(): string
    {
        return $this->purchaseOrderId;
    }

    public function setPurchaseOrderId(string $purchaseOrderId): self
    {
        $this->purchaseOrderId = $purchaseOrderId;
        $this->validate();

        return $this;
    }

    public function getPurchaseOrderName(): string
    {
        return $this->purchaseOrderName;
    }

    public function setPurchaseOrderName(string $purchaseOrderName): self
    {
        $this->purchaseOrderName = $purchaseOrderName;
        $this->validate();

        return $this;
    }

    /** @return CustomerInfo|array<string,mixed>|null */
    public function getCustomerInfo(): CustomerInfo|array|null
    {
        return $this->customerInfo;
    }

    /** @param CustomerInfo|array<string,mixed>|null $customerInfo */
    public function setCustomerInfo(CustomerInfo|array|null $customerInfo): self
    {
        $this->customerInfo = $customerInfo;
        $this->validate();

        return $this;
    }

    /** @return list<AmountBreakdownItem|array<string,mixed>>|null */
    public function getAmountBreakdown(): ?array
    {
        return $this->amountBreakdown;
    }

    /** @param list<AmountBreakdownItem|array<string,mixed>>|null $amountBreakdown */
    public function setAmountBreakdown(?array $amountBreakdown): self
    {
        $this->amountBreakdown = $amountBreakdown;

        return $this;
    }

    /** @param AmountBreakdownItem|array<string,mixed> $item */
    public function addAmountBreakdownItem(AmountBreakdownItem|array $item): self
    {
        $this->amountBreakdown ??= [];
        $this->amountBreakdown[] = $item;

        return $this;
    }

    /** @return list<ProductDetail|array<string,mixed>>|null */
    public function getProductDetails(): ?array
    {
        return $this->productDetails;
    }

    /** @param list<ProductDetail|array<string,mixed>>|null $productDetails */
    public function setProductDetails(?array $productDetails): self
    {
        $this->productDetails = $productDetails;

        return $this;
    }

    /** @param ProductDetail|array<string,mixed> $item */
    public function addProductDetail(ProductDetail|array $item): self
    {
        $this->productDetails ??= [];
        $this->productDetails[] = $item;

        return $this;
    }

    /** @return array<string,mixed>|null */
    public function getMerchantExtra(): ?array
    {
        return $this->merchantExtra;
    }

    /** @param array<string,mixed>|null $merchantExtra */
    public function setMerchantExtra(?array $merchantExtra): self
    {
        $this->merchantExtra = $merchantExtra;

        return $this;
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
            $payload['customer_info'] = $this->customerInfo instanceof CustomerInfo
                ? $this->customerInfo->toArray()
                : $this->customerInfo;
        }

        if ($this->amountBreakdown !== null) {
            $payload['amount_breakdown'] = array_map(
                static fn (AmountBreakdownItem|array $item): array => $item instanceof AmountBreakdownItem ? $item->toArray() : $item,
                $this->amountBreakdown
            );
        }

        if ($this->productDetails !== null) {
            $payload['product_details'] = array_map(
                static fn (ProductDetail|array $item): array => $item instanceof ProductDetail ? $item->toArray() : $item,
                $this->productDetails
            );
        }

        if ($this->merchantExtra !== null) {
            $payload['merchant_extra'] = $this->merchantExtra;
        }

        return $payload;
    }

    private function validate(): void
    {
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

        if (is_array($this->customerInfo)) {
            CustomerInfo::fromArray($this->customerInfo);
        }
    }
}
