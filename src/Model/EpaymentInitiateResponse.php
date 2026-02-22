<?php

declare(strict_types=1);

namespace Khalti\Model;

use Khalti\Exception\UnexpectedResponseException;

readonly class EpaymentInitiateResponse
{
    /**
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public string $pidx,
        public string $paymentUrl,
        public ?string $expiresAt,
        public ?int $expiresIn,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $pidx = $raw['pidx'] ?? null;
        $paymentUrl = $raw['payment_url'] ?? null;

        if (!is_string($pidx) || !is_string($paymentUrl)) {
            throw new UnexpectedResponseException('Missing required ePayment initiate response fields.');
        }

        return new self(
            pidx: $pidx,
            paymentUrl: $paymentUrl,
            expiresAt: isset($raw['expires_at']) && is_string($raw['expires_at']) ? $raw['expires_at'] : null,
            expiresIn: isset($raw['expires_in']) ? (int) $raw['expires_in'] : null,
            raw: $raw
        );
    }
}
