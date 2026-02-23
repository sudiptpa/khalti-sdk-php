# Architecture

## Goals

- Framework-agnostic Khalti integration
- Verification-first fulfillment safety
- Low dependency surface
- Extendable contracts without framework lock-in
- First-class payload modeling for safer API usage

## Core Tree

- `src/Khalti.php`: client factory entry (`CurlTransport` default)
- `src/KhaltiClient.php`: resources (`payments`, `verification`, `transactions`, `legacyPayments`)
- `src/Resource/`: domain resources and API operations
- `src/Model/`: typed API models and payload entities
- `src/ValueObject/`: domain value objects (`MoneyPaisa`)
- `src/Verification/`: verification context objects
- `src/Internal/ApiClient.php`: auth, retry, HTTP orchestration, normalizer pipeline
- `src/Transport/`: `TransportInterface` + built-in `CurlTransport`
- `src/Contracts/`: optional extension contracts (idempotency, normalizers, counters, clock)

## Payment Lifecycle

1. Build payload using first-class models (`EpaymentInitiateRequest`, `CustomerInfo`, `AmountBreakdownItem`, `ProductDetail`).
2. Initiate payment via `payments()->initiate()` (alias: `create()`).
3. Lookup current status via `payments()->lookup()` (alias: `status()`).
4. Optionally poll using `payments()->waitForCompletion()` for asynchronous UX.

## Verification Lifecycle

1. Parse return query: `verification()->parseReturnQuery()`.
2. Create `VerificationContext` with expected order state.
3. Verify via server-side lookup: `verification()->verify()`.
4. Enforce idempotency (`IdempotencyStoreInterface`) to block duplicate fulfillment.
5. Use `OrderVerificationResult` (`paid/pending/failed/refunded/duplicate`) to decide action.

## Retry Lifecycle

`ApiClient` retries transient transport/HTTP failures when configured:

- retry count: `maxRetries`
- backoff: `retryBackoffMs` with exponential growth
- cap: `retryMaxBackoffMs`
- retryable status codes: `retryHttpStatusCodes`

## Transport Behavior

- Default is `CurlTransport`.
- If `ext-curl` is unavailable, `CurlTransport` throws a clear exception.
- Any application can replace transport by passing a custom `TransportInterface` implementation.

## Extension Points

- `RequestNormalizerInterface`: mutate outgoing request payloads.
- `ResponseNormalizerInterface`: normalize incoming payloads.
- `IdempotencyStoreInterface`: app-level storage (Redis/DB) for processed-once guards.
- `MismatchCounterInterface`: app metrics for mismatch reasons.
- `ClockInterface`: deterministic replay-window behavior in tests.

## Testing Strategy

- Unit tests for resources, retry behavior, verification logic.
- Unit tests for first-class payload model mapping/serialization.
- Contract tests backed by sanitized fixtures under `tests/Fixtures/khalti`.
- No live network calls in test suite.
