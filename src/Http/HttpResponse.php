<?php

declare(strict_types=1);

namespace Khalti\Http;

readonly class HttpResponse
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        public int $statusCode,
        public string $body,
        public array $headers = []
    ) {
    }
}
