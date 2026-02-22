<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class CallbackDecision
{
    public function __construct(
        public bool $verified,
        public bool $successful,
        public string $message,
        public CallbackPayload $callback,
        public EpaymentLookupResponse $lookup
    ) {
    }
}
