<?php

declare(strict_types=1);

namespace Khalti\Support;

use Khalti\Contracts\ClockInterface;

final class SystemClock implements ClockInterface
{
    public function nowUnix(): int
    {
        return time();
    }
}
