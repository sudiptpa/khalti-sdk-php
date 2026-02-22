<?php

declare(strict_types=1);

namespace Khalti\Enum;

enum PaymentStatus: string
{
    case Completed = 'Completed';
    case Pending = 'Pending';
    case Initiated = 'Initiated';
    case Refunded = 'Refunded';
    case PartiallyRefunded = 'Partially Refunded';
    case UserCanceled = 'User canceled';
    case Expired = 'Expired';

    public static function fromNullable(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        foreach (self::cases() as $case) {
            if (strcasecmp($case->value, $value) === 0) {
                return $case;
            }
        }

        return null;
    }
}
