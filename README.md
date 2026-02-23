# Khalti PHP SDK

Framework-agnostic Khalti SDK for modern ePayment integrations in PHP.

[![Tests](https://github.com/sudiptpa/khalti-sdk-php/actions/workflows/ci.yml/badge.svg)](https://github.com/sudiptpa/khalti-sdk-php/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/sudiptpa/khalti-sdk-php.svg)](https://packagist.org/packages/sudiptpa/khalti-sdk-php)
[![Total Downloads](https://img.shields.io/packagist/dt/sudiptpa/khalti-sdk-php.svg)](https://packagist.org/packages/sudiptpa/khalti-sdk-php)
[![License](https://img.shields.io/packagist/l/sudiptpa/khalti-sdk-php.svg)](LICENSE)

## Highlights

- Modern resource API: `payments()`, `verification()`, `legacyPayments()`, `transactions()`
- ePayment KPG-2 create/status flow with strict backend verification
- First-class payload models (`CustomerInfo`, `AmountBreakdownItem`, `ProductDetail`)
- Polling helper: `waitForCompletion()`
- Idempotency-friendly verification model for safe order fulfillment
- Retry policy for transient failures (`429`, `5xx`, transport)
- Framework agnostic core with pluggable `TransportInterface`

## Requirements

- PHP `8.2+`
- `ext-json`

`ext-curl` is optional. It is only required when using the built-in `CurlTransport`.

## Installation

```bash
composer require sudiptpa/khalti-sdk-php
```

## Basic Usage (default CurlTransport)

```php
use Khalti\Config\ClientConfig;
use Khalti\Khalti;

$khalti = Khalti::client(new ClientConfig(
    secretKey: $_ENV['KHALTI_SECRET_KEY'],
));
```

If `ext-curl` is not installed, default transport throws a clear exception telling you to install `ext-curl` or pass your own transport.

## Transport Examples

### 1) Custom Transport (framework-agnostic)

```php
use Khalti\Exception\TransportException;
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Transport\TransportInterface;

final class MyTransport implements TransportInterface
{
    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        // Send request with your preferred HTTP client.
        throw new TransportException('Implement transport send logic.');
    }
}

$khalti = Khalti::client(
    new ClientConfig(secretKey: $_ENV['KHALTI_SECRET_KEY']),
    new MyTransport(),
);
```

### 2) PSR-18 / Guzzle adapter example

```php
use GuzzleHttp\Client;
use Http\Adapter\Guzzle7\Client as GuzzleAdapter;
use Khalti\Config\ClientConfig;
use Khalti\Exception\TransportException;
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Khalti;
use Khalti\Transport\TransportInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

final class Psr18Transport implements TransportInterface
{
    public function __construct(
        private readonly \Psr\Http\Client\ClientInterface $client,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        try {
            $psrRequest = $this->requestFactory
                ->createRequest($request->method, $request->url);

            foreach ($request->headers as $name => $value) {
                $psrRequest = $psrRequest->withHeader($name, $value);
            }

            if ($request->body !== '') {
                $psrRequest = $psrRequest->withBody($this->streamFactory->createStream($request->body));
            }

            $psrResponse = $this->client->sendRequest($psrRequest);
        } catch (Throwable $e) {
            throw new TransportException('PSR-18 transport failed.', 0, $e);
        }

        $headers = [];
        foreach ($psrResponse->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

        return new HttpResponse(
            $psrResponse->getStatusCode(),
            (string) $psrResponse->getBody(),
            $headers
        );
    }
}

$psr18 = new GuzzleAdapter(new Client());

$khalti = Khalti::client(
    new ClientConfig(secretKey: $_ENV['KHALTI_SECRET_KEY']),
    new Psr18Transport($psr18, $requestFactory, $streamFactory),
);
```

Note: This PSR-18 example requires user-land packages (for example `guzzlehttp/guzzle`, `php-http/guzzle7-adapter`, and PSR-17 factories). They are optional and not required by this SDK.

### 3) Built-in CurlTransport

```php
use Khalti\Transport\CurlTransport;

$khalti = Khalti::client(
    new ClientConfig(secretKey: $_ENV['KHALTI_SECRET_KEY']),
    new CurlTransport(),
);
```

### 4) Custom Laravel Transport

```php
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
                ->send($request->method, $request->url, ['body' => $request->body]);
        } catch (Throwable $e) {
            throw new TransportException('Laravel HTTP transport failed.', 0, $e);
        }

        $headers = [];
        foreach ($response->headers() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

        return new HttpResponse($response->status(), $response->body(), $headers);
    }
}
```

## ePayment Flow

```php
use Khalti\Model\AmountBreakdownItem;
use Khalti\Model\CustomerInfo;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\Model\ProductDetail;

$request = EpaymentInitiateRequest::make(
    returnUrl: 'https://example.com/payments/khalti/return',
    websiteUrl: 'https://example.com',
    amount: 1000,
    purchaseOrderId: 'ORD-1001',
    purchaseOrderName: 'Pro Subscription',
)
    ->setCustomerInfo(new CustomerInfo(
        name: 'Sujip Thapa',
        email: 'sudiptpa@gmail.com',
        phone: '9800000000',
    ))
    ->addAmountBreakdownItem(new AmountBreakdownItem('Subtotal', 900))
    ->addAmountBreakdownItem(new AmountBreakdownItem('Tax', 100))
    ->addProductDetail(new ProductDetail(
        identity: 'SKU-1001',
        name: 'Pro Subscription',
        totalPrice: 1000,
        quantity: 1,
        unitPrice: 1000,
    ));

$session = $khalti->payments()->initiate($request); // alias: create($request)
$status = $khalti->payments()->lookup($session->pidx); // alias: status($session->pidx)
```

## Important: Khalti ePayment Has No Checkout Webhook

Khalti ePayment does **not** provide a dedicated payment webhook for this checkout flow.

You must manually verify payment on your backend before order fulfillment.

Never trust return query params alone.

## Return Verification (Backend)

```php
use Khalti\ValueObject\MoneyPaisa;
use Khalti\Verification\VerificationContext;

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

if (! $result->fulfillable) {
    // pending/failed/refunded/duplicate
    return;
}

// fulfill once
```

## Idempotency (Processed-once Pattern)

Use `IdempotencyStoreInterface` in your app:

```php
$idempotencyStore = new App\Payments\Khalti\RedisIdempotencyStore($redis);

$result = $khalti->verification()->verify(
    payload: $payload,
    context: new VerificationContext(
        orderId: $orderId,
        pidx: $payload->pidx,
        expectedAmount: MoneyPaisa::of($expectedAmount),
    ),
    idempotencyStore: $idempotencyStore,
);
```

If the same payment return is received again, status becomes `duplicate` and fulfillment is blocked.

## Polling Helper

```php
$status = $khalti->payments()->waitForCompletion(
    pidx: $session->pidx,
    timeoutSeconds: 30,
    intervalSeconds: 2,
);
```

## Retry Policy

```php
$config = new ClientConfig(
    secretKey: $_ENV['KHALTI_SECRET_KEY'],
    maxRetries: 2,
    retryBackoffMs: 200,
    retryMaxBackoffMs: 1200,
    retryHttpStatusCodes: [429, 500, 502, 503, 504],
);
```

## Transaction APIs

```php
$list = $khalti->transactions()->all(page: 1, pageSize: 20);
$detail = $khalti->transactions()->find('txn_idx');

foreach ($list->records as $row) {
    echo $row->idx . ' => ' . $row->state . PHP_EOL;
}
```

## Legacy APIs

```php
$verify = $khalti->legacyPayments()->verify($token, 1000);
$status = $khalti->legacyPayments()->status($token, 1000);
```

## Optional Extension Points

- `RequestNormalizerInterface`
- `ResponseNormalizerInterface`
- `IdempotencyStoreInterface`
- `MismatchCounterInterface`
- `ClockInterface`

These are optional and do not add runtime dependencies.

## Troubleshooting

### Common auth errors

- `AuthenticationException` (401/403): wrong secret key or wrong environment key.
- Check sandbox vs production key mismatch.

### Amount mismatch causes

- Stored order amount in paisa differs from Khalti lookup amount.
- Tax/fee math done at UI but not stored in backend order snapshot.
- Verifying wrong order against wrong `pidx`.

### Return verification checklist

- Parse query with `parseReturnQuery()`.
- Verify with `VerificationContext` (`orderId`, `pidx`, `expectedAmount`).
- Enforce idempotency before fulfillment.
- Fulfill only when `OrderVerificationResult::isPaid()` and `fulfillable === true`.

## Testing & Quality

```bash
composer test
composer stan
composer lint
```

## Package About (GitHub suggestion)

`Framework-agnostic Khalti PHP SDK for ePayment create/status verification, idempotent backend confirmation, and production-safe order fulfillment.`

## Architecture

See `ARCHITECTURE.md`.

## License

MIT
