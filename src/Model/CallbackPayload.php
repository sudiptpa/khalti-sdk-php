<?php

declare(strict_types=1);

namespace Khalti\Model;

use Khalti\Enum\PaymentStatus;
use Khalti\Exception\ValidationException;

readonly class CallbackPayload
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $pidx,
        public ?PaymentStatus $status,
        public ?string $transactionId,
        public ?int $amount,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $query
     */
    public static function fromReturnQuery(array $query): self
    {
        $pidx = $query['pidx'] ?? null;
        if (!is_string($pidx) || trim($pidx) === '') {
            throw new ValidationException('Missing callback parameter: pidx', 400);
        }

        return new self(
            pidx: $pidx,
            status: isset($query['status']) && is_string($query['status'])
                ? PaymentStatus::fromNullable($query['status'])
                : null,
            transactionId: isset($query['transaction_id']) && is_string($query['transaction_id']) ? $query['transaction_id'] : null,
            amount: isset($query['amount']) ? (int) $query['amount'] : null,
            raw: $query
        );
    }
}
