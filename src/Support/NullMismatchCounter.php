<?php

declare(strict_types=1);

namespace Khalti\Support;

use Khalti\Contracts\MismatchCounterInterface;

final class NullMismatchCounter implements MismatchCounterInterface
{
    public function increment(string $reason): void
    {
    }
}
