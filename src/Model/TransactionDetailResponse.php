<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class TransactionDetailResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public TransactionRecord $transaction,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            transaction: TransactionRecord::fromArray($raw),
            raw: $raw
        );
    }
}
