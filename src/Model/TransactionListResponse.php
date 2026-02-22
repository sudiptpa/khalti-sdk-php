<?php

declare(strict_types=1);

namespace Khalti\Model;

readonly class TransactionListResponse
{
    /**
     * @param list<TransactionRecord> $records
     * @param array<string,mixed> $raw
     */
    public function __construct(
        public int $count,
        public ?string $next,
        public ?string $previous,
        public array $records,
        public array $raw
    ) {
    }

    /**
     * @param array<string,mixed> $raw
     */
    public static function fromArray(array $raw): self
    {
        $records = [];
        $recordData = $raw['records'] ?? [];
        if (is_array($recordData)) {
            foreach ($recordData as $item) {
                if (is_array($item)) {
                    $records[] = TransactionRecord::fromArray($item);
                }
            }
        }

        return new self(
            count: isset($raw['count']) ? (int) $raw['count'] : count($records),
            next: isset($raw['next']) && is_string($raw['next']) ? $raw['next'] : null,
            previous: isset($raw['previous']) && is_string($raw['previous']) ? $raw['previous'] : null,
            records: $records,
            raw: $raw
        );
    }
}
