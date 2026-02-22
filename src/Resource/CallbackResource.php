<?php

declare(strict_types=1);

namespace Khalti\Resource;

use Khalti\Enum\PaymentStatus;
use Khalti\Model\CallbackDecision;
use Khalti\Model\CallbackPayload;

final class CallbackResource
{
    public function __construct(
        private readonly EpaymentResource $epayment
    ) {
    }

    /**
     * @param array<string,mixed> $query
     */
    public function parseReturnQuery(array $query): CallbackPayload
    {
        return CallbackPayload::fromReturnQuery($query);
    }

    public function verify(CallbackPayload $payload, ?int $expectedAmount = null): CallbackDecision
    {
        $lookup = $this->epayment->status($payload->pidx);

        if ($expectedAmount !== null && $lookup->totalAmount !== $expectedAmount) {
            return new CallbackDecision(
                verified: false,
                successful: false,
                message: 'Amount mismatch between expected order amount and Khalti lookup response.',
                callback: $payload,
                lookup: $lookup
            );
        }

        if ($lookup->status !== PaymentStatus::Completed) {
            return new CallbackDecision(
                verified: true,
                successful: false,
                message: 'Payment is not completed.',
                callback: $payload,
                lookup: $lookup
            );
        }

        return new CallbackDecision(
            verified: true,
            successful: true,
            message: 'Payment verified with Khalti lookup.',
            callback: $payload,
            lookup: $lookup
        );
    }
}
