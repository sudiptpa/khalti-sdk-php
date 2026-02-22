<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class TransactionListResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public array $raw
    ) {
    }
}
