# API Index

## Entry

- `Khalti::client(ClientConfig $config, ?TransportInterface $transport = null): KhaltiClient`

## Client Resources

- `KhaltiClient::payments(): EpaymentResource`
- `KhaltiClient::verification(): CallbackResource`
- `KhaltiClient::transactions(): TransactionResource`
- `KhaltiClient::legacyPayments(): LegacyPaymentResource`

## Payments

- `EpaymentResource::create(EpaymentInitiateRequest $request): EpaymentInitiateResponse`
- `EpaymentResource::status(string $pidx): EpaymentLookupResponse`
- `EpaymentResource::waitForCompletion(string $pidx, int $timeoutSeconds = 30, int $intervalSeconds = 2): EpaymentLookupResponse`

## Verification

- `CallbackResource::parseReturnQuery(array $query): CallbackPayload`
- `CallbackResource::verify(CallbackPayload $payload, VerificationContext $context, ?IdempotencyStoreInterface $idempotencyStore = null, ?MismatchCounterInterface $mismatchCounter = null): OrderVerificationResult`

## Transactions

- `TransactionResource::all(int $page = 1, int $pageSize = 20): TransactionListResponse`
- `TransactionResource::find(string $idx): TransactionDetailResponse`

## Legacy

- `LegacyPaymentResource::verify(string $token, int $amount): LegacyVerifyResponse`
- `LegacyPaymentResource::status(string $token, int $amount): LegacyStatusResponse`
