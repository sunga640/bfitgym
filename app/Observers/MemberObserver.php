<?php

namespace App\Observers;

use App\Models\Member;
use App\Services\AccessControl\AccessControlCommandService;

class MemberObserver
{
    public function updated(Member $member): void
    {
        if ($member->wasChanged('status')) {
            app(AccessControlCommandService::class)->enqueue_member_sync($member, 'member_status_changed');
        }
    }
}
