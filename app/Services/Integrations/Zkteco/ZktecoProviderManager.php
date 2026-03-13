<?php

namespace App\Services\Integrations\Zkteco;

use App\Models\AccessControlDevice;
use App\Models\AccessIntegrationConfig;
use App\Services\Integrations\Zkteco\Contracts\ZktecoProvider;
use App\Services\Integrations\Zkteco\Providers\ZkbioPlatformProvider;
use App\Services\Integrations\Zkteco\Providers\ZktecoAgentProvider;

class ZktecoProviderManager
{
    public function resolve(AccessIntegrationConfig $config): ZktecoProvider
    {
        return match ($config->provider) {
            AccessControlDevice::PROVIDER_ZKBIO_PLATFORM => app(ZkbioPlatformProvider::class),
            AccessControlDevice::PROVIDER_ZKTECO_AGENT => app(ZktecoAgentProvider::class),
            default => $this->resolveFallback($config),
        };
    }

    public function resolveForBranch(int $branch_id): ZktecoProvider
    {
        $config = AccessIntegrationConfig::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
            ->first();

        if (!$config) {
            return app(ZkbioPlatformProvider::class);
        }

        return $this->resolve($config);
    }

    public function providerOptions(): array
    {
        return [
            AccessControlDevice::PROVIDER_ZKBIO_PLATFORM => 'ZKBio Platform (Preferred)',
            AccessControlDevice::PROVIDER_ZKTECO_AGENT => 'Local Agent (Fallback)',
        ];
    }

    private function resolveFallback(AccessIntegrationConfig $config): ZktecoProvider
    {
        if ($config->mode === AccessIntegrationConfig::MODE_AGENT) {
            return app(ZktecoAgentProvider::class);
        }

        return app(ZkbioPlatformProvider::class);
    }
}

