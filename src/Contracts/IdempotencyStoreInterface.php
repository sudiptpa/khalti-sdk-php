<?php

declare(strict_types=1);

namespace Khalti\Contracts;

interface IdempotencyStoreInterface
{
    public function has(string $key): bool;

    public function put(string $key, int $ttlSeconds = 86400): void;
}
