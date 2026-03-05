<?php

namespace App\Livewire\Reports;

use App\Models\MemberSubscription;
use App\Models\MembershipPackage;
use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Memberships extends Component
{
    use WithPagination;

    #[Url]
    public string $period = 'month';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $package_filter = '';

    #[Url]
    public string $auto_renew_filter = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        if (blank($this->date_from) || blank($this->date_to)) {
            $this->setDefaultDates();
        }
    }

    public function updatedPeriod(): void
    {
        $this->setDefaultDates();
        $this->resetPage();
    }

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

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'status_filter', 'package_filter', 'auto_renew_filter']);
        $this->period = 'month';
        $this->setDefaultDates();
        $this->resetPage();
    }

    #[Computed]
    public function summaryCards(): array
    {
        [$from, $to] = $this->periodBounds();
        $today = now()->startOfDay();
        $period_query = $this->filteredSubscriptionsQuery();

        return [
            'active_total' => MemberSubscription::query()
                ->where('status', 'active')
                ->whereDate('end_date', '>=', $today)
                ->count(),
            'expiring_soon' => MemberSubscription::query()
                ->where('status', 'active')
                ->whereBetween('end_date', [$today->toDateString(), $today->copy()->addDays(7)->toDateString()])
                ->count(),
            'renewals' => (clone $period_query)
                ->whereNotNull('renewed_from_id')
                ->count(),
            'revenue' => (float) PaymentTransaction::query()
                ->paid()
                ->forRevenueType(PaymentTransaction::REVENUE_TYPE_MEMBERSHIP)
                ->betweenDates($from, $to)
                ->whereHasMorph('payable', [MemberSubscription::class], function (Builder $query) {
                    $this->applySubscriptionFilters($query);
                })
                ->sum('amount'),
        ];
    }

    #[Computed]
    public function periodSnapshot(): array
    {
        $period_query = $this->filteredSubscriptionsQuery();

        return [
            'started' => (clone $period_query)->count(),
            'new_signups' => (clone $period_query)->whereNull('renewed_from_id')->count(),
            'renewals' => (clone $period_query)->whereNotNull('renewed_from_id')->count(),
            'pending' => MemberSubscription::query()->where('status', 'pending')->count(),
            'auto_renew_enabled' => MemberSubscription::query()
                ->where('status', 'active')
                ->where('auto_renew', true)
                ->whereDate('end_date', '>=', now()->startOfDay())
                ->count(),
        ];
    }

    #[Computed]
    public function statusSummary(): array
    {
        $counts = $this->filteredSubscriptionsQuery()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $total = max((int) $counts->sum(), 1);

        return collect([
            'active' => ['label' => __('Active'), 'color' => 'emerald'],
            'pending' => ['label' => __('Pending'), 'color' => 'amber'],
            'expired' => ['label' => __('Expired'), 'color' => 'zinc'],
            'cancelled' => ['label' => __('Cancelled'), 'color' => 'rose'],
        ])->map(function (array $config, string $status) use ($counts, $total) {
            $count = (int) ($counts[$status] ?? 0);

            return [
                'key' => $status,
                'label' => $config['label'],
                'color' => $config['color'],
                'count' => $count,
                'share' => $count > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        })->values()->all();
    }

    #[Computed]
    public function packageHighlights(): array
    {
        $package_rows = $this->filteredSubscriptionsQuery()
            ->select('membership_package_id', DB::raw('COUNT(*) as subscriptions_count'))
            ->groupBy('membership_package_id')
            ->orderByDesc('subscriptions_count')
            ->with('membershipPackage:id,name,price,duration_type,duration_value')
            ->limit(5)
            ->get();

        $total = max((int) $package_rows->sum('subscriptions_count'), 1);

        return $package_rows->map(function (MemberSubscription $subscription) use ($total) {
            $package = $subscription->membershipPackage;
            $count = (int) $subscription->subscriptions_count;

            return [
                'name' => $package?->name ?? __('Deleted package'),
                'duration' => $package?->formatted_duration ?? __('N/A'),
                'subscriptions_count' => $count,
                'share' => $count > 0 ? (int) round(($count / $total) * 100) : 0,
                'price' => (float) ($package?->price ?? 0),
            ];
        })->all();
    }

    #[Computed]
    public function expiringSoonSubscriptions(): array
    {
        $today = now()->startOfDay();

        return MemberSubscription::query()
            ->with([
                'member:id,member_no,first_name,last_name',
                'membershipPackage:id,name',
            ])
            ->where('status', 'active')
            ->whereBetween('end_date', [$today->toDateString(), $today->copy()->addDays(14)->toDateString()])
            ->orderBy('end_date')
            ->limit(6)
            ->get()
            ->map(function (MemberSubscription $subscription) use ($today) {
                return [
                    'id' => $subscription->id,
                    'member_name' => $subscription->member?->full_name ?? __('Unknown member'),
                    'member_no' => $subscription->member?->member_no ?? '-',
                    'package_name' => $subscription->membershipPackage?->name ?? __('Deleted package'),
                    'end_date' => $subscription->end_date?->format('M d, Y') ?? '-',
                    'days_left' => max(0, $today->diffInDays($subscription->end_date)),
                    'auto_renew' => $subscription->auto_renew,
                ];
            })
            ->all();
    }

    public function render(): View
    {
        $subscriptions = $this->filteredSubscriptionsQuery()
            ->with([
                'member:id,member_no,first_name,last_name',
                'membershipPackage:id,name,price,duration_type,duration_value',
                'latestPayment',
            ])
            ->latest('start_date')
            ->paginate(12);

        return view('livewire.reports.memberships', [
            'subscriptions' => $subscriptions,
            'packages' => MembershipPackage::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    protected function filteredSubscriptionsQuery(): Builder
    {
        return $this->applySubscriptionFilters(MemberSubscription::query());
    }

    protected function applySubscriptionFilters(Builder $query): Builder
    {
        [$from, $to] = $this->periodBounds();
        $search = trim($this->search);

        return $query
            ->when($search !== '', function (Builder $builder) use ($search) {
                $builder->where(function (Builder $nested_query) use ($search) {
                    $nested_query
                        ->whereHas('member', function (Builder $member_query) use ($search) {
                            $member_query
                                ->where('first_name', 'like', '%' . $search . '%')
                                ->orWhere('last_name', 'like', '%' . $search . '%')
                                ->orWhere('member_no', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('membershipPackage', function (Builder $package_query) use ($search) {
                            $package_query->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($this->status_filter !== '', fn (Builder $builder) => $builder->where('status', $this->status_filter))
            ->when($this->package_filter !== '', fn (Builder $builder) => $builder->where('membership_package_id', $this->package_filter))
            ->when($this->auto_renew_filter !== '', function (Builder $builder) {
                $builder->where('auto_renew', $this->auto_renew_filter === 'yes');
            })
            ->whereBetween('start_date', [$from->toDateString(), $to->toDateString()]);
    }

    protected function periodBounds(): array
    {
        $from = blank($this->date_from)
            ? now()->startOfMonth()
            : Carbon::parse($this->date_from)->startOfDay();

        $to = blank($this->date_to)
            ? now()->endOfMonth()
            : Carbon::parse($this->date_to)->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    protected function setDefaultDates(): void
    {
        $now = now();

        switch ($this->period) {
            case 'today':
                $this->date_from = $now->format('Y-m-d');
                $this->date_to = $now->format('Y-m-d');
                break;
            case 'week':
                $this->date_from = $now->copy()->startOfWeek()->format('Y-m-d');
                $this->date_to = $now->copy()->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->date_from = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->date_from = $now->copy()->startOfQuarter()->format('Y-m-d');
                $this->date_to = $now->copy()->endOfQuarter()->format('Y-m-d');
                break;
            case 'year':
                $this->date_from = $now->copy()->startOfYear()->format('Y-m-d');
                $this->date_to = $now->copy()->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                break;
            default:
                $this->period = 'month';
                $this->date_from = $now->copy()->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->copy()->endOfMonth()->format('Y-m-d');
                break;
        }
    }
}
