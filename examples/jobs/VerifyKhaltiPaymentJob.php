<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Khalti\KhaltiClient;

final class VerifyKhaltiPaymentJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public function __construct(private readonly int $orderId)
    {
    }

    public function handle(KhaltiClient $khalti): void
    {
        $order = Order::query()->findOrFail($this->orderId);
        $status = $khalti->payments()->status($order->khalti_pidx);

        if ($status->isCompleted() && (int) $status->totalAmount === (int) $order->amount_paisa) {
            $order->markAsPaid();
            $order->save();
        }
    }
}
