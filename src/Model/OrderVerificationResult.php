<?php

declare(strict_types=1);

namespace Khalti\Model;

use Khalti\Enum\OrderVerificationStatus;

readonly class OrderVerificationResult
{
    /**
     * @param array<string,mixed> $meta
     */
    public function __construct(
        public OrderVerificationStatus $status,
        public bool $verified,
        public bool $fulfillable,
        public string $reason,
        public CallbackPayload $callback,
        public EpaymentLookupResponse $lookup,
        public array $meta = []
    ) {
    }

    public function isPaid(): bool
    {
        return $this->status === OrderVerificationStatus::Paid;
    }
}
