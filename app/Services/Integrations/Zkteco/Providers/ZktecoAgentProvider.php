<?php

namespace App\Services\Integrations\Zkteco\Providers;

use App\Models\AccessControlDevice;
use App\Models\AccessIntegrationConfig;
use App\Services\Integrations\Zkteco\Contracts\ZktecoProvider;

class ZktecoAgentProvider implements ZktecoProvider
{
    public function providerKey(): string
    {
        return AccessControlDevice::PROVIDER_ZKTECO_AGENT;
    }

    public function health(AccessIntegrationConfig $config): array
    {
        return [
            'provider' => $this->providerKey(),
            'status' => $config->health_status ?: AccessIntegrationConfig::HEALTH_UNKNOWN,
            'message' => $config->last_health_message ?: 'Using local multi-provider agent fallback.',
            'checked_at' => $config->last_health_check_at?->toIso8601String(),
        ];
    }

    public function syncStatus(AccessIntegrationConfig $config): array
    {
        return [
            'mode' => $config->mode,
            'provider' => $this->providerKey(),
            'sync_enabled' => (bool) $config->sync_enabled,
            'last_sync_at' => $config->last_sync_at?->toIso8601String(),
            'agent_fallback_enabled' => (bool) $config->agent_fallback_enabled,
        ];
    }

    public function supportsAgentFallback(): bool
    {
        return true;
    }
}

