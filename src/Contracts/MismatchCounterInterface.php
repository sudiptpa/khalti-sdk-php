<?php

declare(strict_types=1);

namespace Khalti\Contracts;

interface MismatchCounterInterface
{
    public function increment(string $reason): void;
}
