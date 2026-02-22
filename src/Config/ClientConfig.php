<?php

declare(strict_types=1);

namespace Khalti\Config;

use Khalti\Enum\Environment;
use InvalidArgumentException;

readonly class ClientConfig
{
    public function __construct(
        public string $secretKey,
        public Environment $environment = Environment::Sandbox,
        public ?string $baseUrl = null,
        public int $timeoutSeconds = 30
    ) {
        if (trim($this->secretKey) === '') {
            throw new InvalidArgumentException('Khalti secret key cannot be empty.');
        }

        if ($this->timeoutSeconds < 1) {
            throw new InvalidArgumentException('Timeout must be at least 1 second.');
        }
    }

    public function resolvedBaseUrl(): string
    {
        return rtrim($this->baseUrl ?? $this->environment->baseUrl(), '/');
    }
}
