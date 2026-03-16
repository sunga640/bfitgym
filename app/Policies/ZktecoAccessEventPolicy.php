<?php

namespace App\Policies;

use App\Models\AccessControlDevice;
use App\Models\User;
use App\Models\ZktecoAccessEvent;
use App\Support\Integrations\IntegrationPermission;

class ZktecoAccessEventPolicy extends BranchScopedPolicy
{
    public function viewAny(User $user): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->hasCurrentBranchAccess($user);
    }

    public function view(User $user, ZktecoAccessEvent $event): bool
    {
        return IntegrationPermission::canView($user, AccessControlDevice::INTEGRATION_ZKTECO)
            && $this->belongsToUserBranch($user, $event);
    }
}

