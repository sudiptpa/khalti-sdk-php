<?php

declare(strict_types=1);

namespace Khalti\Resource;

use Khalti\Internal\ApiClient;
use Khalti\Model\TransactionDetailResponse;
use Khalti\Model\TransactionListResponse;

final class TransactionResource
{
    public function __construct(
        private readonly ApiClient $apiClient
    ) {
    }

    public function all(int $page = 1, int $pageSize = 20): TransactionListResponse
    {
        $raw = $this->apiClient->get('/payment/list/', [
            'page' => $page,
            'page_size' => $pageSize,
        ]);

        return TransactionListResponse::fromArray($raw);
    }

    public function find(string $idx): TransactionDetailResponse
    {
        $raw = $this->apiClient->get('/payment/detail/', ['idx' => $idx]);

        return TransactionDetailResponse::fromArray($raw);
    }
}
