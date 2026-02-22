<?php

declare(strict_types=1);

namespace Khalti\Verification;

use Khalti\ValueObject\MoneyPaisa;

readonly class VerificationContext
{
    public function __construct(
        public string $orderId,
        public string $pidx,
        public MoneyPaisa $expectedAmount,
        public ?int $receivedAtUnix = null,
        public ?string $idempotencyKey = null,
        public int $idempotencyTtlSeconds = 86400,
        public ?int $replayWindowSeconds = 300
    ) {
    }

    public function resolvedIdempotencyKey(): string
    {
        return $this->idempotencyKey ?? sprintf('khalti:order:%s:pidx:%s', $this->orderId, $this->pidx);
    }
}
