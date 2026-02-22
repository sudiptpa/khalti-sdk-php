<?php

declare(strict_types=1);

namespace Khalti\Http;

readonly class HttpRequest
{
    /**
     * @param array<string,string> $headers
     */
    public function __construct(
        public string $method,
        public string $url,
        public array $headers = [],
        public string $body = ''
    ) {
    }
}
