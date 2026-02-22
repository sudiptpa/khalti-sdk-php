<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Khalti;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

final class TransactionResourceTest extends TestCase
{
    public function testAllAndFindAliasesCallExpectedEndpoints(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'count' => 1,
            'records' => [
                ['idx' => 'txn-2', 'amount' => 2200],
            ],
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'idx' => 'txn-2',
            'amount' => 2200,
            'status' => 'Completed',
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $list = $client->transactions()->all(page: 1, pageSize: 5);
        $detail = $client->transactions()->find('txn-2');

        $this->assertSame(1, $list->raw['count'] ?? null);
        $this->assertSame('txn-2', $detail->raw['idx'] ?? null);
    }

    public function testAllAndFindCallExpectedEndpoints(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'count' => 1,
            'records' => [
                ['idx' => 'txn-1', 'amount' => 1000],
            ],
        ], JSON_THROW_ON_ERROR)));
        $transport->queue(new HttpResponse(200, json_encode([
            'idx' => 'txn-1',
            'amount' => 1000,
            'status' => 'Completed',
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $list = $client->transactions()->all(page: 2, pageSize: 10);
        $detail = $client->transactions()->find('txn-1');

        $this->assertSame(1, $list->raw['count'] ?? null);
        $this->assertSame('txn-1', $detail->raw['idx'] ?? null);

        $this->assertCount(2, $transport->requests);
        $this->assertStringContainsString('/payment/list/', $transport->requests[0]->url);
        $this->assertStringContainsString('page=2', $transport->requests[0]->url);
        $this->assertStringContainsString('page_size=10', $transport->requests[0]->url);
        $this->assertStringContainsString('/payment/detail/', $transport->requests[1]->url);
        $this->assertStringContainsString('idx=txn-1', $transport->requests[1]->url);
    }
}
