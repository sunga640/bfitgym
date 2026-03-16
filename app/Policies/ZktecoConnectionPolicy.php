<?php

namespace App\Policies;

use App\Models\AccessControlDevice;
use App\Models\User;
use App\Models\ZktecoConnection;
use App\Support\Integrations\IntegrationPermission;

class ZktecoConnectionPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, ZktecoConnection $connection): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $connection);
    }

    public function create(User $user): bool
    {
        return IntegrationPermission::canManageSettings($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->hasCurrentBranchAccess($user);
    }

    public function update(User $user, ZktecoConnection $connection): bool
    {
        return IntegrationPermission::canManageSettings($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $connection);
    }

    public function disconnect(User $user, ZktecoConnection $connection): bool
    {
        return $this->update($user, $connection);
    }

    public function test(User $user, ZktecoConnection $connection): bool
    {
        return $this->update($user, $connection);
    }

    public function sync(User $user, ZktecoConnection $connection): bool
    {
        return IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $connection);
    }
}

