<?php

declare(strict_types=1);

namespace Khalti\Transport;

use Khalti\Exception\TransportException;
use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;

final class CurlTransport implements TransportInterface
{
    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse
    {
        if (!function_exists('curl_init')) {
            throw new TransportException('cURL extension is required for CurlTransport.');
        }

        $handle = curl_init();

        $formattedHeaders = [];
        foreach ($request->headers as $name => $value) {
            $formattedHeaders[] = $name.': '.$value;
        }

        $responseHeaders = [];
        curl_setopt_array($handle, [
            CURLOPT_URL => $request->url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($request->method),
            CURLOPT_HTTPHEADER => $formattedHeaders,
            CURLOPT_POSTFIELDS => $request->body,
            CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $length = strlen($headerLine);
                $header = trim($headerLine);
                if ($header === '' || str_contains($header, 'HTTP/')) {
                    return $length;
                }

                $parts = explode(':', $header, 2);
                if (count($parts) === 2) {
                    $responseHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
                }

                return $length;
            },
        ]);

        $body = curl_exec($handle);
        if ($body === false) {
            $message = curl_error($handle);
            curl_close($handle);
            throw new TransportException('Transport request failed: '.$message);
        }

        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        return new HttpResponse($statusCode, (string) $body, $responseHeaders);
    }
}
