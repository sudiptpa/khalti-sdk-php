<?php

declare(strict_types=1);

namespace Khalti\Model;

use InvalidArgumentException;

final class CustomerInfo
{
    public function __construct(
        private ?string $name = null,
        private ?string $email = null,
        private ?string $phone = null
    ) {
        $this->validate();
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        $this->validate();

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        $this->validate();

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        $this->validate();

        return $this;
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        return new self(
            name: isset($raw['name']) && is_string($raw['name']) ? $raw['name'] : null,
            email: isset($raw['email']) && is_string($raw['email']) ? $raw['email'] : null,
            phone: isset($raw['phone']) && is_string($raw['phone']) ? $raw['phone'] : null,
        );
    }

    /**
     * @return array<string,string>
     */
    public function toArray(): array
    {
        $payload = [];

        if ($this->name !== null) {
            $payload['name'] = $this->name;
        }

        if ($this->email !== null) {
            $payload['email'] = $this->email;
        }

        if ($this->phone !== null) {
            $payload['phone'] = $this->phone;
        }

        return $payload;
    }

    private function validate(): void
    {
        if ($this->email !== null && trim($this->email) !== '' && filter_var($this->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidArgumentException('customerInfo.email must be a valid email address.');
        }

        if ($this->phone !== null && trim($this->phone) !== '' && !preg_match('/^[0-9+\-\s]{7,20}$/', $this->phone)) {
            throw new InvalidArgumentException('customerInfo.phone contains invalid characters.');
        }
    }
}
