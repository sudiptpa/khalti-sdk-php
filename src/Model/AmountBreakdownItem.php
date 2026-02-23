<?php

declare(strict_types=1);

namespace Khalti\Model;

use InvalidArgumentException;

final class AmountBreakdownItem
{
    public function __construct(
        private string $label,
        private int $amount
    ) {
        $this->validate();
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        $this->validate();

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;
        $this->validate();

        return $this;
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $label = $raw['label'] ?? $raw['name'] ?? '';

        return new self(
            label: is_string($label) ? $label : '',
            amount: isset($raw['amount']) ? (int) $raw['amount'] : 0,
        );
    }

    /**
     * @return array{name:string,amount:int}
     */
    public function toArray(): array
    {
        return [
            'name' => $this->label,
            'amount' => $this->amount,
        ];
    }

    private function validate(): void
    {
        if (trim($this->label) === '') {
            throw new InvalidArgumentException('AmountBreakdownItem label is required.');
        }

        if ($this->amount < 0) {
            throw new InvalidArgumentException('AmountBreakdownItem amount cannot be negative.');
        }
    }
}
