# Release Notes

## v1.0.0

### Highlights

- New framework-agnostic Khalti SDK built for production use.
- Verification-first flow: return query is never trusted without backend status lookup.
- Explicit no-webhook guidance for Khalti ePayment checkout flow.
- Idempotency-ready verification model to prevent duplicate fulfillment.
- Retry strategy for transient API/transport instability.
- Typed domain models, enums, and value objects.
- CI + tests + static analysis included from first release.

### Recommended rollout checklist

1. Run sandbox end-to-end create -> pay -> verify flow.
2. Enable idempotency storage in your app layer.
3. Validate amount comparison with stored order snapshot.
4. Observe mismatch counters and verification status distribution.
5. Only then enable production fulfillment.
