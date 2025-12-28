<?php

namespace App\Livewire\Subscriptions;

use App\Models\MemberSubscription;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Show extends Component
{
    public MemberSubscription $subscription;

    public function mount(MemberSubscription $subscription): void
    {
        $this->authorize('view', $subscription);

        $this->subscription = $subscription->load([
            'member',
            'membershipPackage',
            'renewedFrom.membershipPackage',
            'renewals.membershipPackage',
        ]);
    }

    public function updateStatus(string $new_status): void
    {
        $this->authorize('update', $this->subscription);

        $valid_statuses = ['pending', 'active', 'expired', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            session()->flash('error', __('Invalid status.'));
            return;
        }

        $old_status = $this->subscription->status;

        $this->subscription->update(['status' => $new_status]);

        Log::info('Subscription status updated', [
            'subscription_id' => $this->subscription->id,
            'member_id' => $this->subscription->member_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', __('Subscription status updated to :status.', ['status' => ucfirst($new_status)]));
    }

    public function getPaymentHistoryProperty()
    {
        return $this->subscription->paymentTransactions()->latest('paid_at')->get();
    }

    public function getRenewalTimelineProperty(): Collection
    {
        $timeline = collect();

        $ancestor = $this->subscription->renewedFrom;
        while ($ancestor) {
            $timeline->prepend($ancestor);
            $ancestor = $ancestor->renewedFrom;
        }

        $timeline->push($this->subscription);

        $descendants = $this->subscription->renewals()->orderBy('start_date')->get();

        return $timeline->merge($descendants);
    }

    public function getCanRenewProperty(): bool
    {
        // Only allow renewal if the subscription end date has passed or is past today
        $is_past_end_date = $this->subscription->end_date->isPast() || $this->subscription->end_date->isToday();

        return $is_past_end_date && (auth()->user()?->can('renew', $this->subscription) ?? false);
    }

    public function getCanUpdateStatusProperty(): bool
    {
        return auth()->user()?->can('update', $this->subscription) ?? false;
    }

    public function render(): View
    {
        return view('livewire.subscriptions.show', [
            'payments' => $this->payment_history,
            'timeline' => $this->renewal_timeline,
        ]);
    }
}
