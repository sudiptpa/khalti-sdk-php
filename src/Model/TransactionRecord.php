<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class TransactionRecord
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public ?string $idx,
        public ?int $amount,
        public ?string $mobile,
        public ?string $state,
        public ?string $token,
        public ?string $transactionId,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $state = null;
        if (isset($raw['state']) && is_array($raw['state'])) {
            $stateName = $raw['state']['name'] ?? null;
            $state = is_string($stateName) ? $stateName : null;
        } elseif (isset($raw['state']) && is_string($raw['state'])) {
            $state = $raw['state'];
        }

        return new self(
            idx: isset($raw['idx']) && is_string($raw['idx']) ? $raw['idx'] : null,
            amount: isset($raw['amount']) ? (int) $raw['amount'] : null,
            mobile: isset($raw['mobile']) && is_string($raw['mobile']) ? $raw['mobile'] : null,
            state: $state,
            token: isset($raw['token']) && is_string($raw['token']) ? $raw['token'] : null,
            transactionId: isset($raw['transaction_id']) && is_string($raw['transaction_id']) ? $raw['transaction_id'] : null,
            raw: $raw,
        );
    }
}
