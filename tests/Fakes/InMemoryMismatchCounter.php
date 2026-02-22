<?php

declare(strict_types=1);

namespace Khalti\Tests\Fakes;

use Khalti\Contracts\MismatchCounterInterface;

final class InMemoryMismatchCounter implements MismatchCounterInterface
{
    /** @var array<string,int> */
    public array $counts = [];

    public function increment(string $reason): void
    {
        $this->counts[$reason] = ($this->counts[$reason] ?? 0) + 1;
    }
}
