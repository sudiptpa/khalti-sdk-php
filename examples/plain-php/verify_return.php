<?php

declare(strict_types=1);

use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\ValueObject\MoneyPaisa;
use Khalti\Verification\VerificationContext;

require __DIR__.'/../../vendor/autoload.php';

$khalti = Khalti::client(new ClientConfig(secretKey: $_ENV['KHALTI_SECRET_KEY'] ?? ''));

$payload = $khalti->verification()->parseReturnQuery($_GET);
$result = $khalti->verification()->verify(
    payload: $payload,
    context: new VerificationContext(
        orderId: 'ORD-1001',
        pidx: $payload->pidx,
        expectedAmount: MoneyPaisa::of(1000),
        receivedAtUnix: time(),
    )
);

var_dump($result->status->value, $result->fulfillable, $result->reason);
