<?php

declare(strict_types=1);

namespace Khalti\Resource;

use Khalti\Contracts\IdempotencyStoreInterface;
use Khalti\Contracts\MismatchCounterInterface;
use Khalti\Enum\OrderVerificationStatus;
use Khalti\Enum\PaymentStatus;
use Khalti\Model\CallbackPayload;
use Khalti\Model\OrderVerificationResult;
use Khalti\Support\NullMismatchCounter;
use Khalti\Verification\VerificationContext;

final class CallbackResource
{
    public function __construct(
        private readonly EpaymentResource $epayment
    ) {
    }

    /**
     * @param array<string,mixed> $query
     */
    public function parseReturnQuery(array $query): CallbackPayload
    {
        return CallbackPayload::fromReturnQuery($query);
    }

    public function verify(
        CallbackPayload $payload,
        VerificationContext $context,
        ?IdempotencyStoreInterface $idempotencyStore = null,
        ?MismatchCounterInterface $mismatchCounter = null
    ): OrderVerificationResult {
        $mismatchCounter ??= new NullMismatchCounter();

        if ($payload->pidx !== $context->pidx) {
            $mismatchCounter->increment('pidx_mismatch');

            return new OrderVerificationResult(
                status: OrderVerificationStatus::Failed,
                verified: false,
                fulfillable: false,
                reason: 'Pidx mismatch between callback payload and expected order context.',
                callback: $payload,
                lookup: $this->epayment->status($payload->pidx)
            );
        }

        if ($context->receivedAtUnix !== null && $context->replayWindowSeconds !== null) {
            $age = abs(time() - $context->receivedAtUnix);
            if ($age > $context->replayWindowSeconds) {
                $mismatchCounter->increment('replay_window_exceeded');

                return new OrderVerificationResult(
                    status: OrderVerificationStatus::Failed,
                    verified: false,
                    fulfillable: false,
                    reason: 'Return verification exceeded replay window.',
                    callback: $payload,
                    lookup: $this->epayment->status($payload->pidx),
                    meta: ['age_seconds' => $age]
                );
            }
        }

        $idempotencyKey = $context->resolvedIdempotencyKey();
        if ($idempotencyStore !== null && $idempotencyStore->has($idempotencyKey)) {
            return new OrderVerificationResult(
                status: OrderVerificationStatus::Duplicate,
                verified: true,
                fulfillable: false,
                reason: 'Duplicate return detected for this payment context.',
                callback: $payload,
                lookup: $this->epayment->status($payload->pidx),
                meta: ['idempotency_key' => $idempotencyKey]
            );
        }

        $lookup = $this->epayment->status($payload->pidx);

        if ($lookup->totalAmount !== null && $lookup->totalAmount !== $context->expectedAmount->value) {
            $mismatchCounter->increment('amount_mismatch');

            return new OrderVerificationResult(
                status: OrderVerificationStatus::Failed,
                verified: false,
                fulfillable: false,
                reason: 'Amount mismatch between expected order amount and Khalti lookup response.',
                callback: $payload,
                lookup: $lookup,
                meta: [
                    'expected_amount' => $context->expectedAmount->value,
                    'actual_amount' => $lookup->totalAmount,
                ]
            );
        }

        if ($lookup->status === PaymentStatus::Refunded || $lookup->status === PaymentStatus::PartiallyRefunded) {
            return new OrderVerificationResult(
                status: OrderVerificationStatus::Refunded,
                verified: true,
                fulfillable: false,
                reason: 'Payment is refunded or partially refunded.',
                callback: $payload,
                lookup: $lookup
            );
        }

        if ($lookup->status !== PaymentStatus::Completed) {
            return new OrderVerificationResult(
                status: OrderVerificationStatus::Pending,
                verified: true,
                fulfillable: false,
                reason: 'Payment is not completed yet.',
                callback: $payload,
                lookup: $lookup
            );
        }

        if ($idempotencyStore !== null) {
            $idempotencyStore->put($idempotencyKey, $context->idempotencyTtlSeconds);
        }

        return new OrderVerificationResult(
            status: OrderVerificationStatus::Paid,
            verified: true,
            fulfillable: true,
            reason: 'Payment verified and ready for fulfillment.',
            callback: $payload,
            lookup: $lookup,
            meta: ['idempotency_key' => $idempotencyKey]
        );
    }
}
