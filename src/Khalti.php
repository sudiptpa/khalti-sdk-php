<?php

declare(strict_types=1);

namespace Khalti;

use Khalti\Config\ClientConfig;
use Khalti\Transport\CurlTransport;
use Khalti\Transport\TransportInterface;

final class Khalti
{
    public static function client(ClientConfig $config, ?TransportInterface $transport = null): KhaltiClient
    {
        return new KhaltiClient($config, $transport ?? new CurlTransport());
    }
}
