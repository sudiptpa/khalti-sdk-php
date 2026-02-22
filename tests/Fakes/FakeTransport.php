<?php

declare(strict_types=1);

namespace Khalti\Tests\Fakes;

use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Transport\TransportInterface;
use RuntimeException;

final class FakeTransport implements TransportInterface
{
    /** @var array<int,HttpResponse> */
    private array $queue = [];

    /** @var array<int,HttpRequest> */
    public array $requests = [];

    public function queue(HttpResponse $response): void
    {
        $this->queue[] = $response;
    }

    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new RuntimeException('No queued fake response.');
        }

        return array_shift($this->queue);
    }
}
