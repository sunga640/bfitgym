<?php

namespace App\Services\AccessControl;

use App\Models\Member;
use Illuminate\Support\Carbon;

class AccessEligibilityService
{
    /**
     * Public API (cloud source of truth).
     */
    public function isAllowed(Member $member, ?Carbon $as_of = null): bool
    {
        $as_of = ($as_of ?? now())->copy()->startOfDay();

        if ($member->status !== 'active') {
            return false;
        }

        $has_active_subscription = $member->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $as_of->toDateString())
            ->exists();

        if ($has_active_subscription) {
            return true;
        }

        return $member->insurances()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $as_of->toDateString())
            ->where(function ($q) use ($as_of) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $as_of->toDateString());
            })
            ->exists();
    }

    /**
     * Return the furthest "date end" the member is eligible until.
     *
     * - If any active insurance has NULL end_date => NULL (open-ended) wins.
     * - Otherwise choose the max of subscription end_date and insurance end_date.
     *
     * IMPORTANT: This returns a date (startOfDay). Callers convert to endOfDay timestamp when needed.
     */
    public function allowedUntil(Member $member, ?Carbon $as_of = null): ?Carbon
    {
        $as_of = ($as_of ?? now())->copy()->startOfDay();

        if ($member->status !== 'active') {
            return null;
        }

        $subscription_end_date = $member->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $as_of->toDateString())
            ->max('end_date');

        $insurance_query = $member->insurances()
            ->where('status', 'active')
            ->whereDate('start_date', '<=', $as_of->toDateString())
            ->where(function ($q) use ($as_of) {
                $q->whereNull('end_date')
                    ->orWhereDate('end_date', '>=', $as_of->toDateString());
            });

        $has_infinite_insurance = (clone $insurance_query)
            ->whereNull('end_date')
            ->exists();

        if ($has_infinite_insurance) {
            return null;
        }

        $insurance_end_date = $insurance_query->max('end_date');

        if (! $subscription_end_date && ! $insurance_end_date) {
            return null;
        }

        $sub_end = $subscription_end_date ? Carbon::parse($subscription_end_date)->startOfDay() : null;
        $ins_end = $insurance_end_date ? Carbon::parse($insurance_end_date)->startOfDay() : null;

        if ($sub_end && $ins_end) {
            return $sub_end->greaterThan($ins_end) ? $sub_end : $ins_end;
        }

        return $sub_end ?: $ins_end;
    }

    /**
     * Member is allowed if:
     * - members.status = 'active'
     * AND (
     *   (has an active subscription: member_subscriptions.status='active' AND end_date >= today)
     *   OR
     *   (has an active insurance policy:
     *     member_insurances.status='active'
     *     AND start_date <= today
     *     AND (end_date IS NULL OR end_date >= today)
     *   )
     * )
     */
    public function is_member_allowed(Member $member, ?Carbon $date = null): bool
    {
        return $this->isAllowed($member, $date);
    }

    /**
     * allowed_until behavior:
     * - if allowed via insurance and end_date is null -> return null (infinite)
     * - if allowed via subscription -> return subscription end_date (max if multiple)
     * - if both exist -> choose the furthest end_date; if any is null -> null wins
     */
    public function allowed_until(Member $member, ?Carbon $date = null): ?Carbon
    {
        return $this->allowedUntil($member, $date);
    }
}
