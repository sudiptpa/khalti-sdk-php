<?php

declare(strict_types=1);

use Khalti\Config\ClientConfig;
use Khalti\Khalti;

require __DIR__.'/../../vendor/autoload.php';

$khalti = Khalti::client(new ClientConfig(secretKey: $_ENV['KHALTI_SECRET_KEY'] ?? ''));

$pendingOrders = []; // fetch from DB
foreach ($pendingOrders as $order) {
    $status = $khalti->payments()->status($order['khalti_pidx']);

    if ($status->isCompleted() && (int) $status->totalAmount === (int) $order['amount_paisa']) {
        // mark paid in your storage
    }
}
