<?php

declare(strict_types=1);

namespace Khalti\Model;

use Khalti\Enum\PaymentStatus;
use Khalti\Exception\UnexpectedResponseException;

readonly class EpaymentLookupResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $pidx,
        public ?PaymentStatus $status,
        public ?string $transactionId,
        public ?int $totalAmount,
        public ?int $fee,
        public bool $refunded,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $pidx = $raw['pidx'] ?? null;
        if (!is_string($pidx) || $pidx === '') {
            throw new UnexpectedResponseException('Missing required ePayment lookup field: pidx.');
        }

        $transactionId = $raw['transaction_id'] ?? null;
        $status = isset($raw['status']) && is_string($raw['status'])
            ? PaymentStatus::fromNullable($raw['status'])
            : null;

        return new self(
            pidx: $pidx,
            status: $status,
            transactionId: is_string($transactionId) ? $transactionId : null,
            totalAmount: isset($raw['total_amount']) ? (int) $raw['total_amount'] : null,
            fee: isset($raw['fee']) ? (int) $raw['fee'] : null,
            refunded: (bool) ($raw['refunded'] ?? false),
            raw: $raw
        );
    }

    public function isCompleted(): bool
    {
        return $this->status === PaymentStatus::Completed;
    }
}
