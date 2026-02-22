<?php

declare(strict_types=1);

namespace Khalti\Contracts;

interface ClockInterface
{
    public function nowUnix(): int;
}
