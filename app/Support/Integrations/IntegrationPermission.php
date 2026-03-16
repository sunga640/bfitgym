<?php

namespace App\Support\Integrations;

use App\Models\AccessControlDevice;
use App\Models\User;

class IntegrationPermission
{
    /**
     * @return array<int, string>
     */
    public static function viewPermissions(string $integration_type): array
    {
        return match ($integration_type) {
            AccessControlDevice::INTEGRATION_ZKTECO => [
                'view zkteco',
                'manage zkteco',
                'manage zkteco settings',
            ],
            default => [
                'view hikvision',
                'manage hikvision',
                'view attendance',
                'view access devices',
                'manage access devices',
            ],
        };
    }

    /**
     * @return array<int, string>
     */
    public static function managePermissions(string $integration_type): array
    {
        return match ($integration_type) {
            AccessControlDevice::INTEGRATION_ZKTECO => [
                'manage zkteco',
                'manage zkteco settings',
            ],
            default => [
                'manage hikvision',
                'manage access devices',
            ],
        };
    }

    public static function canView(User $user, string $integration_type): bool
    {
        return $user->hasAnyPermission(self::viewPermissions($integration_type));
    }

    public static function canManage(User $user, string $integration_type): bool
    {
        return $user->hasAnyPermission(self::managePermissions($integration_type));
    }

    public static function canManageSettings(User $user, string $integration_type): bool
    {
        if ($integration_type === AccessControlDevice::INTEGRATION_ZKTECO) {
            return $user->hasAnyPermission([
                'manage zkteco settings',
                'manage zkteco',
            ]);
        }

        return self::canManage($user, $integration_type);
    }
}
