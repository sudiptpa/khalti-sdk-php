<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class LegacyVerifyResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public bool $success,
        public ?string $token,
        public ?int $amount,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            success: true,
            token: isset($raw['token']) && is_string($raw['token']) ? $raw['token'] : null,
            amount: isset($raw['amount']) ? (int) $raw['amount'] : null,
            raw: $raw
        );
    }
}
