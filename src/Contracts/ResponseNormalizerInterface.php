<?php

declare(strict_types=1);

namespace Khalti\Contracts;

interface ResponseNormalizerInterface
{
    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    public function normalize(string $path, array $payload): array;
}
