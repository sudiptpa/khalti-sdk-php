<?php

declare(strict_types=1);

namespace Khalti\Tests\Fakes;

use Khalti\Contracts\IdempotencyStoreInterface;

final class InMemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /** @var array<string,int> */
    private array $keys = [];

    public function has(string $key): bool
    {
        if (!isset($this->keys[$key])) {
            return false;
        }

        return $this->keys[$key] >= time();
    }

    public function put(string $key, int $ttlSeconds = 86400): void
    {
        $this->keys[$key] = time() + max(1, $ttlSeconds);
    }
}
