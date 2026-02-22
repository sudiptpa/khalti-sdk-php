<?php

declare(strict_types=1);

namespace Khalti\Resource;

use InvalidArgumentException;
use Khalti\Enum\PaymentStatus;
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

    public function waitForCompletion(string $pidx, int $timeoutSeconds = 30, int $intervalSeconds = 2): EpaymentLookupResponse
    {
        if ($timeoutSeconds < 1) {
            throw new InvalidArgumentException('timeoutSeconds must be at least 1.');
        }

        if ($intervalSeconds < 1) {
            throw new InvalidArgumentException('intervalSeconds must be at least 1.');
        }

        $deadline = time() + $timeoutSeconds;

        do {
            $lookup = $this->status($pidx);
            if ($lookup->status === PaymentStatus::Completed
                || $lookup->status === PaymentStatus::Refunded
                || $lookup->status === PaymentStatus::PartiallyRefunded
                || $lookup->status === PaymentStatus::Expired
                || $lookup->status === PaymentStatus::UserCanceled
            ) {
                return $lookup;
            }

            if (time() >= $deadline) {
                return $lookup;
            }

            sleep($intervalSeconds);
        } while (true);
    }
}
