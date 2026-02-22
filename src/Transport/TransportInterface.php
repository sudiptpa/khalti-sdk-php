<?php

declare(strict_types=1);

namespace Khalti\Transport;

use Khalti\Http\HttpRequest;
use Khalti\Http\HttpResponse;

interface TransportInterface
{
    public function send(HttpRequest $request, int $timeoutSeconds): HttpResponse;
}
