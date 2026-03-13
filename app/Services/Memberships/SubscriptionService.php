<?php

namespace App\Services\Memberships;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Services\AccessControl\AccessControlService;
use App\Services\Payments\PaymentService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SubscriptionService
{
    public function __construct(
        protected PaymentService $payment_service,
        protected AccessControlService $access_control_service,
    ) {
    }

    /**
     * Start a brand-new subscription for a member.
     * This will deactivate any existing active subscription.
     */
    public function startSubscription(Member $member, MembershipPackage $package, array $payload): MemberSubscription
    {
        $this->guardActivePackage($package);

        return DB::transaction(function () use ($member, $package, $payload) {
            // Deactivate any existing active subscriptions (member can only have one active at a time)
            $this->deactivateExistingSubscriptions($member);

            $start_date = Carbon::parse($payload['start_date'])->startOfDay();
            $end_date = $this->calculateEndDate($start_date, $package);
            $status = $start_date->isFuture() ? 'pending' : 'active';

            $subscription = MemberSubscription::create([
                'branch_id' => $member->branch_id,
                'member_id' => $member->id,
                'membership_package_id' => $package->id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
                'auto_renew' => (bool) ($payload['auto_renew'] ?? false),
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->payment_service->recordMembershipPayment($member, $subscription, $payload);

            Log::info('Member subscription created', [
                'subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'branch_id' => $member->branch_id,
                'package_id' => $package->id,
                'user_id' => auth()->id(),
            ]);

            // Note: Fingerprint enrollment is now a manual action triggered by the user
            // from the member show page using the "Add Fingerprint" button.
            // If member already has fingerprint registered and it's active, update validity.
            $this->updateFingerprintValidityIfActive($member);

            return $subscription->fresh(['member', 'membershipPackage', 'latestPayment']);
        });
    }

    /**
     * Renew an existing subscription (optionally changing the package).
     * This will deactivate the current subscription.
     */
    public function renewSubscription(MemberSubscription $current_subscription, MembershipPackage $package, array $payload): MemberSubscription
    {
        $this->guardActivePackage($package);

        $member = $current_subscription->member()->firstOrFail();

        return DB::transaction(function () use ($current_subscription, $package, $payload, $member) {
            // Deactivate existing subscriptions (including the one being renewed)
            $this->deactivateExistingSubscriptions($member);

            $requested_start = Carbon::parse($payload['start_date'])->startOfDay();
            $start_date = $requested_start->gt($current_subscription->end_date)
                ? $requested_start
                : $current_subscription->end_date->copy()->addDay();

            $end_date = $this->calculateEndDate($start_date, $package);
            $status = $start_date->isFuture() ? 'pending' : 'active';

            $subscription = MemberSubscription::create([
                'branch_id' => $member->branch_id,
                'member_id' => $member->id,
                'membership_package_id' => $package->id,
                'renewed_from_id' => $current_subscription->id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
                'auto_renew' => (bool) ($payload['auto_renew'] ?? $current_subscription->auto_renew),
                'notes' => $payload['notes'] ?? null,
            ]);

            $this->payment_service->recordMembershipPayment($member, $subscription, $payload);

            Log::info('Member subscription renewed', [
                'previous_subscription_id' => $current_subscription->id,
                'new_subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'branch_id' => $member->branch_id,
                'package_id' => $package->id,
                'user_id' => auth()->id(),
            ]);

            // Note: Fingerprint enrollment is now a manual action triggered by the user
            // from the member show page using the "Add Fingerprint" button.
            // If member already has fingerprint registered and it's active, update validity.
            $this->updateFingerprintValidityIfActive($member);

            return $subscription->fresh(['member', 'membershipPackage', 'latestPayment']);
        });
    }

    /**
     * Update an existing subscription cycle and its latest payment details.
     */
    public function updateSubscription(MemberSubscription $subscription, MembershipPackage $package, array $payload): MemberSubscription
    {
        $member = $subscription->member()->firstOrFail();
        $is_changing_package = $subscription->membership_package_id !== $package->id;

        if ($is_changing_package) {
            $this->guardActivePackage($package);
        }

        return DB::transaction(function () use ($subscription, $package, $payload, $member) {
            $start_date = Carbon::parse($payload['start_date'])->startOfDay();
            $end_date = $this->calculateEndDate($start_date, $package);

            $status = $subscription->status;
            if (in_array($subscription->status, ['pending', 'active'], true)) {
                $status = $start_date->isFuture() ? 'pending' : 'active';
            }

            $subscription->update([
                'membership_package_id' => $package->id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'status' => $status,
                'auto_renew' => (bool) ($payload['auto_renew'] ?? false),
                'notes' => $payload['notes'] ?? null,
            ]);

            $payment_data = [
                'amount' => $payload['amount'],
                'currency' => strtoupper($payload['currency']),
                'payment_method' => $payload['payment_method'],
                'reference' => $payload['reference'] ?? null,
                'paid_at' => Carbon::parse($payload['paid_at']),
                'notes' => $payload['notes'] ?? null,
            ];

            $latest_payment = $subscription->paymentTransactions()->latest('paid_at')->first();

            if ($latest_payment) {
                $latest_payment->update($payment_data);
            } else {
                $this->payment_service->recordMembershipPayment($member, $subscription, $payload);
            }

            Log::info('Member subscription updated', [
                'subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'branch_id' => $member->branch_id,
                'package_id' => $package->id,
                'user_id' => auth()->id(),
            ]);

            $this->updateFingerprintValidityIfActive($member);

            return $subscription->fresh(['member', 'membershipPackage', 'latestPayment']);
        });
    }

    /**
     * Calculate the subscription end date based on the package duration.
     */
    public function calculateEndDate(Carbon $start_date, MembershipPackage $package): Carbon
    {
        $duration_value = max(1, (int) $package->duration_value);

        $end_date = match ($package->duration_type) {
            'days' => $start_date->copy()->addDays($duration_value),
            'weeks' => $start_date->copy()->addWeeks($duration_value),
            'months' => $start_date->copy()->addMonthsNoOverflow($duration_value),
            'years' => $start_date->copy()->addYearsNoOverflow($duration_value),
            default => $start_date->copy()->addMonthsNoOverflow(1),
        };

        // Subtract one day so a 1-month package starting Jan 1 ends Jan 31
        return $end_date->subDay();
    }

    /**
     * Deactivate all existing active/pending subscriptions for a member.
     * Ensures only one active subscription at a time.
     */
    protected function deactivateExistingSubscriptions(Member $member): void
    {
        $existing_subscriptions = MemberSubscription::query()
            ->where('member_id', $member->id)
            ->whereIn('status', ['active', 'pending'])
            ->get();

        foreach ($existing_subscriptions as $subscription) {
            // Mark as expired if end_date is past, otherwise cancelled
            $new_status = $subscription->end_date && $subscription->end_date->isPast() 
                ? 'expired' 
                : 'cancelled';
            
            $subscription->update(['status' => $new_status]);

            Log::info('Existing subscription deactivated', [
                'subscription_id' => $subscription->id,
                'member_id' => $member->id,
                'old_status' => $subscription->getOriginal('status'),
                'new_status' => $new_status,
            ]);
        }
    }

    protected function guardActivePackage(MembershipPackage $package): void
    {
        if (!$package->isActive()) {
            throw new RuntimeException(__('The selected package is inactive.'));
        }
    }

    /**
     * Update fingerprint validity if member already has an active fingerprint.
     * This is called after subscription changes to sync validity dates.
     * 
     * Note: This does NOT auto-enable disabled fingerprints. Fingerprint enrollment
     * must be triggered manually from the member show page.
     */
    protected function updateFingerprintValidityIfActive(Member $member): void
    {
        $identity = $this->access_control_service->getMemberIdentity($member);

        if (!$identity) {
            // Member has no fingerprint registered, nothing to do
            return;
        }

        if (!$identity->is_active) {
            // Fingerprint is disabled - do not auto-enable
            // User must manually enable from the member show page
            Log::info('Member has disabled fingerprint, skipping auto-enable after subscription', [
                'member_id' => $member->id,
                'device_user_id' => $identity->device_user_id,
            ]);
            return;
        }

        // Already active - update the validity dates on device to new subscription end date
        $result = $this->access_control_service->updateMemberValidity($member);
        
        if ($result['success']) {
            Log::info('Member fingerprint validity updated after subscription', [
                'member_id' => $member->id,
                'device_user_id' => $identity->device_user_id,
                'result' => $result['message'],
            ]);
        } else {
            Log::warning('Failed to update fingerprint validity after subscription', [
                'member_id' => $member->id,
                'error' => $result['message'] ?? 'Unknown error',
            ]);
        }
    }
}

