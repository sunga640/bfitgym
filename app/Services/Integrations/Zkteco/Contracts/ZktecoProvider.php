<?php

namespace App\Services\Integrations\Zkteco\Contracts;

use App\Models\AccessIntegrationConfig;

interface ZktecoProvider
{
    public function providerKey(): string;

    /**
     * Lightweight health probe metadata for admin UI.
     *
     * @return array<string, mixed>
     */
    public function health(AccessIntegrationConfig $config): array;

    /**
     * Last known sync/capability metadata for admin UI.
     *
     * @return array<string, mixed>
     */
    public function syncStatus(AccessIntegrationConfig $config): array;

    public function supportsAgentFallback(): bool;
}

