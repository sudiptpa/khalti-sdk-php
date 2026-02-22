<?php

declare(strict_types=1);

namespace Khalti\Tests\Unit;

use Khalti\Config\ClientConfig;
use Khalti\Exception\ApiException;
use Khalti\Exception\AuthenticationException;
use Khalti\Exception\TransportException;
use Khalti\Exception\UnexpectedResponseException;
use Khalti\Exception\ValidationException;
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;
use Khalti\Khalti;
use Khalti\Model\EpaymentInitiateRequest;
use Khalti\Tests\Fakes\FakeTransport;
use Khalti\Transport\TransportInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApiClientErrorMappingTest extends TestCase
{
    public function testMapsUnauthorizedToAuthenticationException(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(401, json_encode(['detail' => 'Invalid key'], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('bad_key'), $transport);

        $this->expectException(AuthenticationException::class);
        $client->payments()->status('pidx-unauthorized');
    }

    public function testMapsValidationErrorsToValidationException(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(400, json_encode(['message' => 'Invalid payload'], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(ValidationException::class);
        $client->payments()->create(new EpaymentInitiateRequest(
            returnUrl: 'https://example.test/return',
            websiteUrl: 'https://example.test',
            amount: 1000,
            purchaseOrderId: 'ord-1',
            purchaseOrderName: 'Order 1'
        ));
    }

    public function testMapsUnknownApiErrorsToApiException(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(500, json_encode(['error_key' => 'server_error'], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(ApiException::class);
        $client->payments()->status('pidx-500');
    }

    public function testThrowsUnexpectedResponseExceptionOnInvalidJson(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, '{invalid-json'));

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(UnexpectedResponseException::class);
        $client->payments()->status('pidx-invalid-json');
    }

    public function testWrapsUnknownTransportExceptions(): void
    {
        $transport = new class () implements TransportInterface {
            public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
            {
                throw new RuntimeException('socket timeout');
            }
        };

        $client = Khalti::client(new ClientConfig('test_key'), $transport);

        $this->expectException(TransportException::class);
        $client->payments()->status('pidx-timeout');
    }

    public function testAddsAuthorizationHeaderWithKeyPrefix(): void
    {
        $transport = new FakeTransport();
        $transport->queue(new HttpResponse(200, json_encode([
            'pidx' => 'pidx-123',
            'status' => 'Pending',
        ], JSON_THROW_ON_ERROR)));

        $client = Khalti::client(new ClientConfig('live_secret_key'), $transport);
        $client->payments()->status('pidx-123');

        $this->assertCount(1, $transport->requests);
        $this->assertSame('Key live_secret_key', $transport->requests[0]->headers['Authorization'] ?? null);
    }
}
