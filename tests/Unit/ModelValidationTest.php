<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use InvalidArgumentException;
use Khalti\Config\ClientConfig;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\ValueObject\MoneyPaisa;
use PHPUnit\Framework\TestCase;

final class ModelValidationTest extends TestCase
{
    public function testClientConfigRequiresSecretKey(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClientConfig(' ');
    }

    public function testClientConfigRequiresPositiveTimeout(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClientConfig('test_key', timeoutSeconds: 0);
    }

    public function testClientConfigRejectsInvalidRetryBounds(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ClientConfig('test_key', retryBackoffMs: 1000, retryMaxBackoffMs: 100);
    }

    public function testInitiateRequestRequiresPositiveAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new EpaymentInitiateRequest(
            returnUrl: 'https://example.test/return',
            websiteUrl: 'https://example.test',
            amount: 0,
            purchaseOrderId: 'ord-1',
            purchaseOrderName: 'Order 1'
        );
    }

    public function testMoneyPaisaRejectsNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        MoneyPaisa::of(-1);
    }
}
