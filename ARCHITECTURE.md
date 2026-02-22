# Architecture

## Goals

- Framework-agnostic Khalti integration
- Clear payment-domain naming
- Small dependency surface
- Safe defaults for production

## Directory Layout

- `src/Khalti.php`: entry point/factory
- `src/KhaltiClient.php`: resource access (`payments`, `verification`, `legacyPayments`, `transactions`)
- `src/Config/`: client configuration
- `src/Resource/`: public API resources
- `src/Model/`: request/response models
- `src/Enum/`: domain enums (`Environment`, `PaymentStatus`)
- `src/Internal/ApiClient.php`: HTTP orchestration + error mapping
- `src/Transport/`: transport contract and default `CurlTransport`
- `src/Exception/`: typed exception tree
- `tests/`: unit tests and fakes

## Request Lifecycle

1. App calls resource method (e.g., `payments()->create(...)`).
2. Resource serializes model (`toArray()`) and forwards to `ApiClient`.
3. `ApiClient` builds `HttpRequest` with auth header (`Key <secret>`).
4. `TransportInterface` sends request (`CurlTransport` by default).
5. `ApiClient` decodes JSON and maps errors to typed exceptions.
6. Resource maps payload to typed response model.

## Return Verification Lifecycle

1. Parse return query with `verification()->parseReturnQuery(...)`.
2. Validate required fields (`pidx` required).
3. Verify payment via `verification()->verify(...)`.
4. `verify()` performs server-side lookup via `payments()->status(...)`.
5. Optional amount check prevents return-URL tampering.
6. Returns `CallbackDecision` with verification outcome.

## Extension Points

- Custom HTTP client: implement `TransportInterface`.
- Additional Khalti APIs: add new resource under `src/Resource/` and map models in `src/Model/`.
- Domain types: extend with enums/value objects where useful.

## Error Model

- `AuthenticationException`: invalid key/permission failures.
- `ValidationException`: request validation errors returned by Khalti.
- `ApiException`: generic non-2xx API failures.
- `TransportException`: network/transport-level failures.
- `UnexpectedResponseException`: malformed or empty JSON responses.

## Testing Strategy

- Unit tests run with `FakeTransport` (no real network calls).
- Test API mapping, endpoint routing, and exception behavior.
- Keep transport logic isolated and testable.

## Backward Compatibility Strategy

- Keep resource methods stable and additive.
- Introduce new APIs under new resources instead of changing existing method contracts.
- Preserve existing model field names when extending payloads.
