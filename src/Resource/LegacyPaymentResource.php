<?php

declare(strict_types=1);

namespace Khalti\Resource;

use Khalti\Internal\ApiClient;
use Khalti\Model\LegacyStatusResponse;
use Khalti\Model\LegacyVerifyResponse;

final class LegacyPaymentResource
{
    public function __construct(
        private readonly ApiClient $apiClient
    ) {
    }

    public function verify(string $token, int $amount): LegacyVerifyResponse
    {
        $raw = $this->apiClient->post('/payment/verify/', [
            'token' => $token,
            'amount' => $amount,
        ]);

        return LegacyVerifyResponse::fromArray($raw);
    }

    public function status(string $token, int $amount): LegacyStatusResponse
    {
        $raw = $this->apiClient->get('/payment/status/', [
            'token' => $token,
            'amount' => $amount,
        ]);

        return LegacyStatusResponse::fromArray($raw);
    }
}
