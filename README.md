# Khalti PHP SDK

Framework-agnostic Khalti SDK for modern ePayment integrations in PHP.

[![Tests](https://github.com/sudiptpa/khalti-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/khalti-sdk-php/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/sudiptpa/khalti-sdk-php.svg)](https://packagist.org/packages/sudiptpa/khalti-sdk-php)
[![Total Downloads](https://img.shields.io/packagist/dt/sudiptpa/khalti-sdk-php.svg)](https://packagist.org/packages/sudiptpa/khalti-sdk-php)
[![License](https://img.shields.io/packagist/l/sudiptpa/khalti-sdk-php.svg)](LICENSE)

## Highlights

- Modern resource API: `payments()`, `verification()`, `legacyPayments()`, `transactions()`
- First-class ePayment flow (KPG-2 style): create and status
- Redirect verification flow that performs secure server-side lookup
- Typed models and enums for safer integration code
- Dependency-light transport abstraction with built-in `CurlTransport`
- Framework agnostic by design (works in plain PHP, Laravel, Symfony, Slim, etc.)

## Requirements

- PHP `8.2+`
- `ext-json`

## Installation

```bash
composer require sudiptpa/khalti-sdk-php
```

## Quick Start

```php
<?php

declare(strict_types=1);

use Khalti\Config\ClientConfig;
use Khalti\Khalti;

$khalti = Khalti::client(new ClientConfig(
    secretKey: $_ENV['KHALTI_SECRET_KEY'],
));
```

## ePayment Flow

### 1) Initiate payment

```php
use Khalti\Model\EpaymentInitiateRequest;

$request = new EpaymentInitiateRequest(
    returnUrl: 'https://example.com/payments/khalti/callback',
    websiteUrl: 'https://example.com',
    amount: 1000, // paisa
    purchaseOrderId: 'ORD-1001',
    purchaseOrderName: 'Pro Subscription'
);

$init = $khalti->payments()->create($request);

// Redirect user to Khalti hosted page
header('Location: ' . $init->paymentUrl);
exit;
```

### 2) Lookup payment status

```php
$lookup = $khalti->payments()->status($init->pidx);

if ($lookup->isCompleted()) {
    // fulfill order
}
```

## Payment Verification (Recommended)

### Important: No Payment Webhook in Khalti ePayment

Khalti ePayment does **not** provide a dedicated payment webhook endpoint for checkout completion in this integration flow.

Because of that, your backend must **manually verify payment** using Khalti status lookup (`pidx`) before marking any order as paid.

```php
$returnPayload = $khalti->verification()->parseReturnQuery($_GET);
$decision = $khalti->verification()->verify($returnPayload, expectedAmount: 1000);

if ($decision->successful) {
    // payment verified
} else {
    // reject and investigate
}
```

`verify()` performs a server-side lookup so your app does not trust return query parameters alone.

Mandatory fulfillment rule:

- Never fulfill an order based only on return/redirect query parameters.
- Always call backend status verification first.
- Mark order paid only after verification confirms successful payment.

### Backend Verification Example (Order-safe)

```php
// Example: verify from backend using your stored order record
$order = $orderRepository->findById($orderId);

if ($order === null || $order->khaltiPidx === null) {
    throw new RuntimeException('Order is not linked with Khalti payment session.');
}

$status = $khalti->payments()->status($order->khaltiPidx);

if (! $status->isCompleted()) {
    // keep order as unpaid / pending
    return;
}

if ($status->totalAmount !== $order->expectedAmountPaisa) {
    // prevent amount tampering
    throw new RuntimeException('Amount mismatch during Khalti verification.');
}

if ($order->isPaid()) {
    // idempotency: avoid double fulfillment
    return;
}

$order->markPaid(
    gateway: 'khalti',
    transactionId: $status->transactionId,
    paidAmount: $status->totalAmount ?? 0
);

$orderRepository->save($order);
```

## Legacy API Support

```php
$verify = $khalti->legacyPayments()->verify($token, 1000);
$status = $khalti->legacyPayments()->status($token, 1000);
```

## Transaction APIs

```php
$list = $khalti->transactions()->all(page: 1, pageSize: 20);
$detail = $khalti->transactions()->find('transaction_idx');
```

## Laravel Integration Example

Laravel support: this SDK is framework-agnostic and works cleanly with Laravel `v12` and `v13`.

Register singleton in a service provider:

```php
use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\KhaltiClient;

$this->app->singleton(KhaltiClient::class, function () {
    return Khalti::client(new ClientConfig(
        secretKey: config('services.khalti.secret_key'),
    ));
});
```

Use in a controller:

```php
public function verifyPaymentReturn(Request $request, KhaltiClient $khalti)
{
    $returnPayload = $khalti->verification()->parseReturnQuery($request->query());
    $decision = $khalti->verification()->verify($returnPayload, expectedAmount: 1000);

    if (! $decision->successful) {
        abort(400, $decision->message);
    }

    return response()->json(['status' => 'ok']);
}
```

### Optional: Laravel Transport Example (v12/v13)

If you prefer Laravel's HTTP client over cURL transport, implement a custom transport in your app:

```php
<?php

declare(strict_types=1);

namespace App\Support\Payments\Khalti;

use Illuminate\Support\Facades\Http;
use Khalti\Exception\TransportException;
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Transport\TransportInterface;
use Throwable;

final class LaravelTransport implements TransportInterface
{
    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        try {
            $response = Http::timeout($timeoutSeconds)
                ->withHeaders($request->headers)
                ->send($request->method, $request->url, [
                    'body' => $request->body,
                ]);
        } catch (Throwable $e) {
            throw new TransportException('Laravel HTTP transport failed.', 0, $e);
        }

        /** @var array<string,string> $headers */
        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

        return new HttpResponse(
            statusCode: $response->status(),
            body: $response->body(),
            headers: $headers,
        );
    }
}
```

Then wire it in your service provider:

```php
use App\Support\Payments\Khalti\LaravelTransport;
use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\KhaltiClient;

$this->app->singleton(KhaltiClient::class, function () {
    return Khalti::client(
        new ClientConfig(secretKey: config('services.khalti.secret_key')),
        new LaravelTransport(),
    );
});
```

## Error Handling

```php
use Khalti\Exception\AuthenticationException;
use Khalti\Exception\ValidationException;
use Khalti\Exception\ApiException;
use Khalti\Exception\TransportException;
use Khalti\Exception\UnexpectedResponseException;

try {
    $result = $khalti->payments()->status($pidx);
} catch (AuthenticationException $e) {
    // invalid key or permission issue
} catch (ValidationException $e) {
    // request payload issue
} catch (TransportException|UnexpectedResponseException $e) {
    // network failure or malformed response
} catch (ApiException $e) {
    // all other API-level failures
}
```

## Custom Transport

Implement `Khalti\Transport\TransportInterface` to plug your own HTTP layer.

```php
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Transport\TransportInterface;

final class MyTransport implements TransportInterface
{
    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        // your client logic
        return new HttpResponse(200, '{"ok":true}');
    }
}
```

Then inject it:

```php
$khalti = Khalti::client(new ClientConfig('secret_key'), new MyTransport());
```

## Testing & Quality

```bash
composer test
composer stan
composer lint
```

## Architecture

See `ARCHITECTURE.md` for package internals and extension points.

## License

MIT
