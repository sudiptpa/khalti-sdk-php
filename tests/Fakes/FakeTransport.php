<?php

declare(strict_types=1);

namespace Khalti\Tests\Fakes;

use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Transport\TransportInterface;
use RuntimeException;
use Throwable;

final class FakeTransport implements TransportInterface
{
    /** @var array<int,HttpResponse|Throwable> */
    private array $queue = [];

    /** @var array<int,HttpRequest> */
    public array $requests = [];

    public function queue(HttpResponse $response): void
    {
        $this->queue[] = $response;
    }

    public function queueThrowable(Throwable $throwable): void
    {
        $this->queue[] = $throwable;
    }

    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        $this->requests[] = $request;

        if ($this->queue === []) {
            throw new RuntimeException('No queued fake response.');
        }

        $next = array_shift($this->queue);
        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }
}
