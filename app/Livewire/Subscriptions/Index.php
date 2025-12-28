<?php

namespace App\Livewire\Subscriptions;

use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $package_filter = '';

    #[Url]
    public string $auto_renew_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPackageFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAutoRenewFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status_filter', 'package_filter', 'auto_renew_filter']);
        $this->resetPage();
    }

    public function render(): View
    {
        $subscriptions = MemberSubscription::query()
            ->with(['member', 'membershipPackage', 'latestPayment'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($member_query) {
                    $member_query
                        ->where('first_name', 'like', '%' . $this->search . '%')
                        ->orWhere('last_name', 'like', '%' . $this->search . '%')
                        ->orWhere('member_no', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status_filter, fn ($query) => $query->where('status', $this->status_filter))
            ->when($this->package_filter, fn ($query) => $query->where('membership_package_id', $this->package_filter))
            ->when($this->auto_renew_filter !== '', function ($query) {
                $query->where('auto_renew', $this->auto_renew_filter === 'yes');
            })
            ->latest('start_date')
            ->paginate(12);

        return view('livewire.subscriptions.index', [
            'subscriptions' => $subscriptions,
            'packages' => MembershipPackage::query()->active()->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'active' => MemberSubscription::query()->active()->count(),
                'expiring' => MemberSubscription::query()->expiringSoon(7)->count(),
                'pending' => MemberSubscription::query()->where('status', 'pending')->count(),
            ],
        ]);
    }
}


