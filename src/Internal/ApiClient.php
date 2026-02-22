<?php

declare(strict_types=1);

namespace Khalti\Internal;

use JsonException;
use Khalti\Config\ClientConfig;
use Khalti\Exception\ApiException;
use Khalti\Exception\AuthenticationException;
use Khalti\Exception\TransportException;
use Khalti\Exception\UnexpectedResponseException;
use Khalti\Exception\ValidationException;
use Khalti\Http\HttpRequest;
use Khalti\Transport\TransportInterface;
use Throwable;

final class ApiClient
{
    public function __construct(
        private readonly ClientConfig $config,
        private readonly TransportInterface $transport
    ) {
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    public function post(string $path, array $payload): array
    {
        return $this->send('POST', $path, $payload);
    }

    /**
     * @param array<string,mixed> $query
     *
     * @return array<string,mixed>
     */
    public function get(string $path, array $query = []): array
    {
        return $this->send('GET', $path, $query);
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function send(string $method, string $path, array $payload): array
    {
        $payload = $this->normalizeRequest($path, $payload);

        $url = $this->config->resolvedBaseUrl().'/'.ltrim($path, '/');
        $body = '';

        if ($method === 'GET' && $payload !== []) {
            $url .= '?'.http_build_query($payload);
        } elseif ($method !== 'GET') {
            try {
                $body = json_encode($payload, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new UnexpectedResponseException('Failed to encode request payload.');
            }
        }

        $request = new HttpRequest(
            method: $method,
            url: $url,
            headers: [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Key '.$this->config->secretKey,
                'User-Agent' => 'sudiptpa/khalti-sdk-php',
            ],
            body: $body
        );

        $attempt = 0;

        while (true) {
            try {
                $response = $this->transport->send($request, $this->config->timeoutSeconds);
            } catch (TransportException $exception) {
                if ($this->shouldRetryTransport($attempt)) {
                    $this->backoff($attempt);
                    $attempt++;
                    continue;
                }

                throw $exception;
            } catch (Throwable $exception) {
                if ($this->shouldRetryTransport($attempt)) {
                    $this->backoff($attempt);
                    $attempt++;
                    continue;
                }

                throw new TransportException('Transport request failed before receiving response.', 0, $exception);
            }

            if ($response->statusCode >= 200 && $response->statusCode < 300) {
                $decoded = $this->decode($response->body);

                return $this->normalizeResponse($path, $decoded);
            }

            if ($this->shouldRetryHttpStatus($response->statusCode, $attempt)) {
                $this->backoff($attempt);
                $attempt++;
                continue;
            }

            $decoded = $this->tryDecode($response->body);
            $decoded = $this->normalizeResponse($path, $decoded);
            $message = $this->extractErrorMessage($decoded) ?? 'Khalti API request failed.';
            $context = ['response' => $decoded];

            if ($response->statusCode === 401 || $response->statusCode === 403) {
                throw new AuthenticationException($message, $response->statusCode, $context);
            }

            if ($response->statusCode === 400 || $response->statusCode === 422) {
                throw new ValidationException($message, $response->statusCode, $context);
            }

            throw new ApiException($message, $response->statusCode, $context);
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function decode(string $body): array
    {
        if (trim($body) === '') {
            throw new UnexpectedResponseException('Empty JSON response received from Khalti API.');
        }

        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new UnexpectedResponseException('Invalid JSON response received from Khalti API.');
        }

        if (!is_array($decoded)) {
            throw new UnexpectedResponseException('Unexpected response shape received from Khalti API.');
        }

        return $decoded;
    }

    /**
     * @return array<string,mixed>
     */
    private function tryDecode(string $body): array
    {
        if (trim($body) === '') {
            return [];
        }

        try {
            $decoded = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string,mixed> $decoded
     */
    private function extractErrorMessage(array $decoded): ?string
    {
        if (isset($decoded['detail']) && is_string($decoded['detail'])) {
            return $decoded['detail'];
        }

        if (isset($decoded['message']) && is_string($decoded['message'])) {
            return $decoded['message'];
        }

        if (isset($decoded['error_key']) && is_string($decoded['error_key'])) {
            return $decoded['error_key'];
        }

        return null;
    }

    private function shouldRetryTransport(int $attempt): bool
    {
        return $attempt < $this->config->maxRetries;
    }

    private function shouldRetryHttpStatus(int $statusCode, int $attempt): bool
    {
        if ($attempt >= $this->config->maxRetries) {
            return false;
        }

        return in_array($statusCode, $this->config->retryHttpStatusCodes, true);
    }

    private function backoff(int $attempt): void
    {
        if ($this->config->retryBackoffMs === 0) {
            return;
        }

        $delayMs = $this->config->retryBackoffMs * (2 ** $attempt);
        $delayMs = min($delayMs, $this->config->retryMaxBackoffMs);

        usleep($delayMs * 1000);
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function normalizeRequest(string $path, array $payload): array
    {
        $normalized = $payload;

        foreach ($this->config->requestNormalizers as $normalizer) {
            $normalized = $normalizer->normalize($path, $normalized);
        }

        return $normalized;
    }

    /**
     * @param array<string,mixed> $payload
     *
     * @return array<string,mixed>
     */
    private function normalizeResponse(string $path, array $payload): array
    {
        $normalized = $payload;

        foreach ($this->config->responseNormalizers as $normalizer) {
            $normalized = $normalizer->normalize($path, $normalized);
        }

        return $normalized;
    }
}
