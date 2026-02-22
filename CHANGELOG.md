# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

- No changes yet.

## [1.0.0] - 2026-02-22

### Added

- Initial public release of `sudiptpa/khalti-sdk-php`.
- Modern framework-agnostic client architecture.
- ePayment create/status support.
- Verification resource with server-side lookup enforcement.
- Polling helper: `waitForCompletion()`.
- Retry policy for transient transport/HTTP failures.
- Typed verification result model (`OrderVerificationResult`).
- Strict money value object (`MoneyPaisa`).
- Idempotency support contract for processed-once fulfillment.
- Transaction typed models for list/detail responses.
- Optional request/response normalizer extension points.
- Contract test fixtures and CI matrix (`8.2`-`8.5`).
