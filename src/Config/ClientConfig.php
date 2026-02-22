<?php

declare(strict_types=1);

namespace Khalti\Config;

use InvalidArgumentException;
use Khalti\Contracts\ClockInterface;
use Khalti\Contracts\RequestNormalizerInterface;
use Khalti\Contracts\ResponseNormalizerInterface;
use Khalti\Enum\Environment;
use Khalti\Support\SystemClock;

readonly class ClientConfig
{
    /**
     * @param list<int> $retryHttpStatusCodes
     * @param list<RequestNormalizerInterface> $requestNormalizers
     * @param list<ResponseNormalizerInterface> $responseNormalizers
     */
    public function __construct(
        public string $secretKey,
        public Environment $environment = Environment::Sandbox,
        public ?string $baseUrl = null,
        public int $timeoutSeconds = 30,
        public int $maxRetries = 1,
        public int $retryBackoffMs = 150,
        public int $retryMaxBackoffMs = 1000,
        public array $retryHttpStatusCodes = [429, 500, 502, 503, 504],
        public array $requestNormalizers = [],
        public array $responseNormalizers = [],
        public ?ClockInterface $clock = null
    ) {
        if (trim($this->secretKey) === '') {
            throw new InvalidArgumentException('Khalti secret key cannot be empty.');
        }

        if ($this->timeoutSeconds < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second.');
        }

        if ($this->maxRetries < 0) {
            throw new InvalidArgumentException('maxRetries must be zero or greater.');
        }

        if ($this->retryBackoffMs < 0) {
            throw new InvalidArgumentException('retryBackoffMs must be zero or greater.');
        }

        if ($this->retryMaxBackoffMs < 0) {
            throw new InvalidArgumentException('retryMaxBackoffMs must be zero or greater.');
        }

        if ($this->retryBackoffMs > $this->retryMaxBackoffMs) {
            throw new InvalidArgumentException('retryBackoffMs cannot be greater than retryMaxBackoffMs.');
        }
    }

    public function resolvedBaseUrl(): string
    {
        return rtrim($this->baseUrl ?? $this->environment->baseUrl(), '/');
    }

    public function resolvedClock(): ClockInterface
    {
        return $this->clock ?? new SystemClock();
    }
}
