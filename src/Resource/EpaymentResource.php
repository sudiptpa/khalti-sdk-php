<?php

declare(strict_types=1);

namespace Khalti\Resource;

use Khalti\Internal\ApiClient;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\Model\EpaymentInitiateResponse;
use Khalti\Model\EpaymentLookupResponse;

final class EpaymentResource
{
    public function __construct(
        private readonly ApiClient $apiClient
    ) {
    }

    public function create(EpaymentInitiateRequest $request): EpaymentInitiateResponse
    {
        $raw = $this->apiClient->post('/epayment/initiate/', $request->toArray());

        return EpaymentInitiateResponse::fromArray($raw);
    }

    public function status(string $pidx): EpaymentLookupResponse
    {
        $raw = $this->apiClient->post('/epayment/lookup/', ['pidx' => $pidx]);

        return EpaymentLookupResponse::fromArray($raw);
    }
}
