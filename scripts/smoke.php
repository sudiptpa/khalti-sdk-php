<?php

declare(strict_types=1);

use Khalti\Config\ClientConfig;
use Khalti\Khalti;

require __DIR__.'/../vendor/autoload.php';

$key = getenv('KHALTI_SECRET_KEY') ?: '';
$pidx = getenv('KHALTI_SMOKE_PIDX') ?: '';

if ($key === '' || $pidx === '') {
    fwrite(STDERR, "Set KHALTI_SECRET_KEY and KHALTI_SMOKE_PIDX before running smoke test.\n");
    exit(1);
}

$client = Khalti::client(new ClientConfig(secretKey: $key));
$status = $client->payments()->status($pidx);

printf(
    "pidx=%s status=%s amount=%s txn=%s\n",
    $status->pidx,
    $status->status?->value ?? 'unknown',
    (string) ($status->totalAmount ?? 0),
    $status->transactionId ?? 'n/a'
);
