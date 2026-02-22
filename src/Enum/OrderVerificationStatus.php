<?php

declare(strict_types=1);

namespace Khalti\Enum;

enum OrderVerificationStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Duplicate = 'duplicate';
}
