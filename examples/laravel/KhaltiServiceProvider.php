<?php

declare(strict_types=1);

namespace App\Providers;

use App\Support\Payments\Khalti\LaravelTransport;
use Illuminate\Support\ServiceProvider;
use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\KhaltiClient;

final class KhaltiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KhaltiClient::class, function () {
            return Khalti::client(
                new ClientConfig(secretKey: (string) config('services.khalti.secret_key')),
                new LaravelTransport(),
            );
        });
    }
}
