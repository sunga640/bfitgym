<?php

namespace App\Livewire\Subscriptions;

use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Services\Memberships\SubscriptionService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;
use Throwable;

class Form extends Component
{
    public ?MemberSubscription $subscription = null;

    public bool $is_renewal = false;
    public bool $is_editing = false;

    public ?int $member_id = null;
    public ?int $membership_package_id = null;

    public string $start_date;
    public string $end_date = '';
    public bool $auto_renew = false;
    public string $notes = '';

    public string $amount = '';
    public string $currency = '';
    public string $payment_method = 'cash';
    public ?string $reference = '';
    public string $paid_at;

    public function mount(?MemberSubscription $subscription = null, bool $isRenewal = false): void
    {
        $this->subscription = $subscription;
        $this->is_renewal = $isRenewal;
        $this->currency = app_currency();
        $this->paid_at = now()->format('Y-m-d\TH:i');

        if ($subscription && $subscription->exists) {
            $subscription->loadMissing(['membershipPackage', 'latestPayment']);

            if ($isRenewal) {
                $this->authorize('renew', $subscription);

                $this->member_id = $subscription->member_id;
                $this->membership_package_id = $subscription->membership_package_id;
                $this->start_date = $subscription->end_date->copy()->addDay()->format('Y-m-d');
                $this->auto_renew = $subscription->auto_renew;
                $this->amount = (string) $subscription->membershipPackage->price;
            } else {
                $this->authorize('update', $subscription);

                $this->is_editing = true;
                $latest_payment = $subscription->latestPayment;

                $this->member_id = $subscription->member_id;
                $this->membership_package_id = $subscription->membership_package_id;
                $this->start_date = $subscription->start_date->format('Y-m-d');
                $this->auto_renew = $subscription->auto_renew;
                $this->notes = $subscription->notes ?? '';
                $this->amount = $latest_payment ? (string) $latest_payment->amount : (string) $subscription->membershipPackage->price;
                $this->currency = $latest_payment?->currency ?? app_currency();
                $this->payment_method = $latest_payment?->payment_method ?? 'cash';
                $this->reference = $latest_payment?->reference ?? '';
                $this->paid_at = ($latest_payment?->paid_at ?? now())->format('Y-m-d\TH:i');
            }
        } else {
            $this->authorize('create', MemberSubscription::class);
            $this->start_date = now()->format('Y-m-d');
        }

        if (!$this->is_editing) {
            $this->syncAmountFromPackage();
        }
        $this->syncEndDate();
    }

    public function rules(): array
    {
        return [
            'member_id' => ['required', Rule::exists('members', 'id')],
            'membership_package_id' => ['required', Rule::exists('membership_packages', 'id')],
            'start_date' => ['required', 'date', 'after_or_equal:' . now()->subYear()->format('Y-m-d')],
            'auto_renew' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'payment_method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['required', 'date'],
        ];
    }

    public function updatedMembershipPackageId(): void
    {
        $this->syncAmountFromPackage();
        $this->syncEndDate();
    }

    public function updatedStartDate(): void
    {
        $this->syncEndDate();
    }

    public function save(): void
    {
        $validated = $this->validate();

        $package = MembershipPackage::query()->findOrFail($validated['membership_package_id']);

        $payload = [
            'start_date' => $validated['start_date'],
            'auto_renew' => $validated['auto_renew'],
            'notes' => $validated['notes'],
            'amount' => $validated['amount'],
            'currency' => $validated['currency'],
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'],
            'paid_at' => $validated['paid_at'],
        ];

        try {
            /** @var SubscriptionService $service */
            $service = app(SubscriptionService::class);

            if ($this->is_renewal && $this->subscription) {
                $target_subscription = $service->renewSubscription($this->subscription, $package, $payload);
                session()->flash('success', __('Subscription renewed successfully.'));
            } elseif ($this->is_editing && $this->subscription) {
                $target_subscription = $service->updateSubscription($this->subscription, $package, $payload);
                session()->flash('success', __('Subscription updated successfully.'));
            } else {
                $member = Member::query()->findOrFail($validated['member_id']);
                $target_subscription = $service->startSubscription($member, $package, $payload);
                session()->flash('success', __('Subscription created successfully.'));
            }

            $this->redirect(route('subscriptions.show', $target_subscription), navigate: true);
        } catch (RuntimeException $exception) {
            $this->addError('form', $exception->getMessage());
        } catch (Throwable $throwable) {
            report($throwable);
            $this->addError('form', __('Something went wrong while saving the subscription. Please try again.'));
        }
    }

    public function getSelectedPackageProperty(): ?MembershipPackage
    {
        if (!$this->membership_package_id) {
            return null;
        }

        return MembershipPackage::query()->find($this->membership_package_id);
    }

    public function render(): View
    {
        $packages_query = MembershipPackage::query();
        $current_package_id = $this->subscription?->membership_package_id;

        if ($this->is_editing && $current_package_id) {
            $packages_query->where(function ($query) use ($current_package_id) {
                $query->where('status', 'active')
                    ->orWhere('id', $current_package_id);
            });
        } else {
            $packages_query->where('status', 'active');
        }

        return view('livewire.subscriptions.form', [
            'members' => Member::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'member_no']),
            'packages' => $packages_query->orderBy('name')->get(['id', 'name', 'price', 'duration_type', 'duration_value', 'status']),
        ]);
    }

    protected function syncAmountFromPackage(): void
    {
        $package = $this->selectedPackage;

        if ($package) {
            $this->amount = (string) $package->price;
        }
    }

    protected function syncEndDate(): void
    {
        $package = $this->selectedPackage;

        if (!$package || empty($this->start_date)) {
            $this->end_date = '';
            return;
        }

        /** @var SubscriptionService $service */
        $service = app(SubscriptionService::class);

        $start = Carbon::parse($this->start_date)->startOfDay();
        $end = $service->calculateEndDate($start, $package);
        $this->end_date = $end->format('Y-m-d');
    }
}

