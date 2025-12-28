<?php

namespace App\Observers;

use App\Models\MemberSubscription;
use Illuminate\Support\Facades\Log;

class MemberSubscriptionObserver
{
    /**
     * Handle the subscription "saved" event.
     *
     * NOTE: We intentionally do NOT auto-sync members to access control devices here.
     * Fingerprint enrollment must be triggered manually from the member detail page
     * using the "Enroll Fingerprint" button. This ensures:
     * 
     * 1. Members are properly enrolled on the physical device with actual fingerprint capture
     * 2. UI accurately reflects device state (not showing "active" when device doesn't know the user)
     * 3. Staff has control over when enrollment happens
     * 
     * If member already has an enrolled fingerprint, the AccessControlService will
     * handle updating validity when needed.
     */
    public function saved(MemberSubscription $subscription): void
    {
        if (!$subscription->member) {
            return;
        }

        Log::info('Member subscription saved (fingerprint sync disabled - use manual enrollment)', [
            'subscription_id' => $subscription->id,
            'member_id' => $subscription->member_id,
            'status' => $subscription->status,
        ]);
    }
}
