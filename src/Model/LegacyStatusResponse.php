<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class LegacyStatusResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public bool $success,
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
            raw: $raw
        );
    }
}
