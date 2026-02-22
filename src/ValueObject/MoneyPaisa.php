<?php

declare(strict_types=1);

namespace Khalti\ValueObject;

use InvalidArgumentException;

readonly class MoneyPaisa
{
    public function __construct(public int $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Money in paisa cannot be negative.');
        }
    }

    public static function of(int $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
