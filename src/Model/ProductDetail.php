<?php

declare(strict_types=1);

namespace Khalti\Model;

use InvalidArgumentException;

final class ProductDetail
{
    public function __construct(
        private string $identity,
        private string $name,
        private int $totalPrice,
        private int $quantity,
        private int $unitPrice
    ) {
        $this->validate();
    }

    public function getIdentity(): string
    {
        return $this->identity;
    }

    public function setIdentity(string $identity): self
    {
        $this->identity = $identity;
        $this->validate();

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        $this->validate();

        return $this;
    }

    public function getTotalPrice(): int
    {
        return $this->totalPrice;
    }

    public function setTotalPrice(int $totalPrice): self
    {
        $this->totalPrice = $totalPrice;
        $this->validate();

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = $quantity;
        $this->validate();

        return $this;
    }

    public function getUnitPrice(): int
    {
        return $this->unitPrice;
    }

    public function setUnitPrice(int $unitPrice): self
    {
        $this->unitPrice = $unitPrice;
        $this->validate();

        return $this;
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            identity: isset($raw['identity']) && is_string($raw['identity']) ? $raw['identity'] : '',
            name: isset($raw['name']) && is_string($raw['name']) ? $raw['name'] : '',
            totalPrice: isset($raw['total_price']) ? (int) $raw['total_price'] : 0,
            quantity: isset($raw['quantity']) ? (int) $raw['quantity'] : 0,
            unitPrice: isset($raw['unit_price']) ? (int) $raw['unit_price'] : 0,
        );
    }

    /**
     * @return array{identity:string,name:string,total_price:int,quantity:int,unit_price:int}
     */
    public function toArray(): array
    {
        return [
            'identity' => $this->identity,
            'name' => $this->name,
            'total_price' => $this->totalPrice,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
        ];
    }

    private function validate(): void
    {
        if (trim($this->identity) === '') {
            throw new InvalidArgumentException('ProductDetail identity is required.');
        }

        if (trim($this->name) === '') {
            throw new InvalidArgumentException('ProductDetail name is required.');
        }

        if ($this->totalPrice < 0 || $this->quantity < 0 || $this->unitPrice < 0) {
            throw new InvalidArgumentException('ProductDetail price and quantity values cannot be negative.');
        }
    }
}
