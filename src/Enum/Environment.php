<?php

declare(strict_types=1);

namespace Khalti\Enum;

enum Environment: string
{
    case Sandbox = 'sandbox';
    case Production = 'production';

    public function baseUrl(): string
    {
        return match ($this) {
            self::Sandbox => 'https://dev.khalti.com/api/v2',
            self::Production => 'https://khalti.com/api/v2',
        };
    }
}
