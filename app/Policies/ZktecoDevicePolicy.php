<?php

namespace App\Policies;

use App\Models\AccessControlDevice;
use App\Models\User;
use App\Models\ZktecoDevice;
use App\Support\Integrations\IntegrationPermission;

class ZktecoDevicePolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, ZktecoDevice $device): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $device);
    }

    public function update(User $user, ZktecoDevice $device): bool
    {
        return IntegrationPermission::canManage($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $device);
    }
}

