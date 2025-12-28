<?php

namespace App\Observers;

use App\Models\MemberInsurance;
use App\Services\AccessControl\AccessControlCommandService;

class MemberInsuranceObserver
{
    public function saved(MemberInsurance $insurance): void
    {
        if (!$insurance->member) {
            return;
        }

        app(AccessControlCommandService::class)->enqueue_member_sync($insurance->member, 'insurance_saved');
    }
}
