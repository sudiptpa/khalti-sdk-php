<?php

declare(strict_types=1);

namespace Khalti;

use Khalti\Config\ClientConfig;
use Khalti\Internal\ApiClient;
use Khalti\Resource\CallbackResource;
use Khalti\Resource\EpaymentResource;
use Khalti\Resource\LegacyPaymentResource;
use Khalti\Resource\TransactionResource;
use Khalti\Transport\TransportInterface;

final class KhaltiClient
{
    private readonly ApiClient $apiClient;
    private ?EpaymentResource $payments = null;
    private ?CallbackResource $verification = null;
    private ?LegacyPaymentResource $legacyPayments = null;
    private ?TransactionResource $transactions = null;

    public function __construct(
        private readonly ClientConfig $config,
        TransportInterface $transport
    ) {
        $this->apiClient = new ApiClient($config, $transport);
    }

    public function payments(): EpaymentResource
    {
        return $this->payments ??= new EpaymentResource($this->apiClient);
    }

    public function verification(): CallbackResource
    {
        return $this->verification ??= new CallbackResource($this->payments());
    }

    public function legacyPayments(): LegacyPaymentResource
    {
        return $this->legacyPayments ??= new LegacyPaymentResource($this->apiClient);
    }

    public function transactions(): TransactionResource
    {
        return $this->transactions ??= new TransactionResource($this->apiClient);
    }

    public function config(): ClientConfig
    {
        return $this->config;
    }
}
