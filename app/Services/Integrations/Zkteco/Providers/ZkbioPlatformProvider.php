<?php

namespace App\Services\Integrations\Zkteco\Providers;

use App\Models\AccessControlDevice;
use App\Models\AccessIntegrationConfig;
use App\Services\Integrations\Zkteco\Contracts\ZktecoProvider;

class ZkbioPlatformProvider implements ZktecoProvider
{
    public function providerKey(): string
    {
        return AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
    }

    public function health(AccessIntegrationConfig $config): array
    {
        // Platform transport/auth handshake is intentionally deferred to
        // installation-specific integration details.
        return [
            'provider' => $this->providerKey(),
            'status' => $config->health_status ?: AccessIntegrationConfig::HEALTH_UNKNOWN,
            'message' => $config->last_health_message ?: 'ZKBio platform provider scaffold is configured.',
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
            'platform_base_url' => $config->platform_base_url,
            'platform_site_code' => $config->platform_site_code,
        ];
    }

    public function supportsAgentFallback(): bool
    {
        return false;
    }
}

