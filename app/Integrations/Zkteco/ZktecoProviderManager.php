<?php

namespace App\Integrations\Zkteco;

use App\Integrations\Zkteco\Contracts\ZktecoProvider;
use App\Integrations\Zkteco\Providers\ZkbioApiProvider;
use App\Models\ZktecoConnection;

class ZktecoProviderManager
{
    public function resolve(ZktecoConnection $connection): ZktecoProvider
    {
        return match ($connection->provider) {
            ZktecoConnection::PROVIDER_ZKBIO_API => app(ZkbioApiProvider::class),
            default => app(ZkbioApiProvider::class),
        };
    }
}

