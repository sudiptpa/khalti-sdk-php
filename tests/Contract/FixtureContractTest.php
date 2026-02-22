<?php

declare(strict_types=1);

namespace Khalti\Tests\Contract;

use Khalti\Model\EpaymentInitiateResponse;
use Khalti\Model\EpaymentLookupResponse;
use Khalti\Model\TransactionDetailResponse;
use Khalti\Model\TransactionListResponse;
use PHPUnit\Framework\TestCase;

final class FixtureContractTest extends TestCase
{
    public function testEpaymentInitiateFixtureMapsToTypedModel(): void
    {
        $raw = $this->readFixture('epayment_initiate.json');
        $model = EpaymentInitiateResponse::fromArray($raw);

        $this->assertSame('HTYJ5a8M4xkz9N7xYpQw6r', $model->pidx);
        $this->assertNotNull($model->expiresAt);
    }

    public function testEpaymentLookupFixtureMapsToTypedModel(): void
    {
        $raw = $this->readFixture('epayment_lookup_completed.json');
        $model = EpaymentLookupResponse::fromArray($raw);

        $this->assertTrue($model->isCompleted());
        $this->assertSame(1000, $model->totalAmount);
    }

    public function testTransactionFixturesMapToTypedModels(): void
    {
        $list = TransactionListResponse::fromArray($this->readFixture('transaction_list.json'));
        $detail = TransactionDetailResponse::fromArray($this->readFixture('transaction_detail.json'));

        $this->assertSame(2, $list->count);
        $this->assertSame('txn_1', $list->records[0]->idx);
        $this->assertSame('Completed', $list->records[0]->state);
        $this->assertSame('txn_1', $detail->transaction->idx);
        $this->assertSame('k_1', $detail->transaction->transactionId);
    }

    /**
     * @return array<string,mixed>
     */
    private function readFixture(string $filename): array
    {
        $path = __DIR__.'/../Fixtures/khalti/'.$filename;
        $content = file_get_contents($path);
        if ($content === false) {
            $this->fail('Failed to read fixture: '.$filename);
        }

        $decoded = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            $this->fail('Invalid fixture shape: '.$filename);
        }

        return $decoded;
    }
}
