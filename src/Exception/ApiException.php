<?php

declare(strict_types=1);

namespace Khalti\Exception;

class ApiException extends KhaltiException
{
    /**
     * @param array<string,mixed> $context
     */
    public function __construct(
        string $message,
        public readonly int $statusCode,
        public readonly array $context = []
    ) {
        parent::__construct($message, $statusCode);
    }
}
