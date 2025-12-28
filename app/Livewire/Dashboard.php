<?php

namespace App\Livewire;

use App\Models\AccessLog;
use App\Models\BranchProduct;
use App\Models\Member;
use App\Models\MemberSubscription;
use App\Models\PaymentTransaction;
use App\Models\PosSale;
use App\Services\Inventory\InventoryService;
use App\Services\Revenue\RevenueReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Dashboard', 'description' => "Welcome back! Here's an overview of your gym."])]
#[Title('Dashboard')]
class Dashboard extends Component
{
    #[Url]
    public string $attendance_month = '';

    #[Url]
    public string $revenue_month = '';

    protected RevenueReportService $revenue_service;
    protected InventoryService $inventory_service;

    public function boot(RevenueReportService $revenue_service, InventoryService $inventory_service): void
    {
        $this->revenue_service = $revenue_service;
        $this->inventory_service = $inventory_service;
    }

    public function mount(): void
    {
        // Default to current month if not set
        if (empty($this->attendance_month)) {
            $this->attendance_month = now()->format('Y-m');
        }
        if (empty($this->revenue_month)) {
            $this->revenue_month = now()->format('Y-m');
        }
    }

    public function updatedAttendanceMonth(): void
    {
        $this->dispatch('attendance-chart-update', $this->attendanceChartData);
    }

    public function updatedRevenueMonth(): void
    {
        $this->dispatch('revenue-chart-update', [
            'data' => $this->revenueChartData,
            'currency' => app_currency(),
        ]);
    }

    #[Computed]
    public function activeMembersCount(): int
    {
        return Member::active()->count();
    }

    #[Computed]
    public function todayCheckins(): int
    {
        return AccessLog::entries()
            ->whereDate('event_timestamp', today())
            ->count();
    }

    #[Computed]
    public function monthlyRevenue(): float
    {
        return PaymentTransaction::paid()
            ->whereYear('paid_at', now()->year)
            ->whereMonth('paid_at', now()->month)
            ->sum('amount');
    }

    #[Computed]
    public function expiringSoonCount(): int
    {
        return MemberSubscription::expiringSoon(7)->count();
    }

    #[Computed]
    public function revenueKPIs(): array
    {
        $branch_id = current_branch_id();
        return $this->revenue_service->getDashboardKPIs($branch_id);
    }

    #[Computed]
    public function todayPosSales(): array
    {
        $branch_id = current_branch_id();
        $today_sales = PosSale::where('branch_id', $branch_id)
            ->whereDate('sale_datetime', today())
            ->completed()
            ->selectRaw('COUNT(*) as count, SUM(total_amount) as total')
            ->first();

        return [
            'count' => $today_sales->count ?? 0,
            'total' => (float) ($today_sales->total ?? 0),
        ];
    }

    #[Computed]
    public function inventoryAlerts(): array
    {
        $branch_id = current_branch_id();
        return $this->inventory_service->getInventorySummary($branch_id);
    }

    #[Computed]
    public function recentActivities(): \Illuminate\Support\Collection
    {
        // Get recent check-ins
        $checkins = AccessLog::with(['member'])
            ->entries()
            ->latest('event_timestamp')
            ->limit(5)
            ->get()
            ->map(fn($log) => [
                'type' => 'checkin',
                'icon' => 'finger-print',
                'color' => 'blue',
                'title' => $log->member?->full_name ?? __('Unknown Member'),
                'description' => __('Checked in'),
                'timestamp' => $log->event_timestamp,
            ]);

        // Get recent payments
        $payments = PaymentTransaction::with(['payerMember'])
            ->paid()
            ->latest('paid_at')
            ->limit(5)
            ->get()
            ->map(fn($payment) => [
                'type' => 'payment',
                'icon' => 'banknotes',
                'color' => 'emerald',
                'title' => $payment->payerMember?->full_name ?? __('Unknown'),
                'description' => __('Paid :amount', ['amount' => money($payment->amount, $payment->currency)]),
                'timestamp' => $payment->paid_at,
            ]);

        // Get recent subscriptions
        $subscriptions = MemberSubscription::with(['member', 'membershipPackage'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($sub) => [
                'type' => 'subscription',
                'icon' => 'credit-card',
                'color' => 'amber',
                'title' => $sub->member?->full_name ?? __('Unknown Member'),
                'description' => __('Subscribed to :package', ['package' => $sub->membershipPackage?->name ?? 'Package']),
                'timestamp' => $sub->created_at,
            ]);

        // Merge and sort by timestamp
        return collect()
            ->merge($checkins)
            ->merge($payments)
            ->merge($subscriptions)
            ->sortByDesc('timestamp')
            ->take(10)
            ->values();
    }

    #[Computed]
    public function attendanceChartData(): array
    {
        $date = Carbon::createFromFormat('Y-m', $this->attendance_month);
        $start_of_month = $date->copy()->startOfMonth();
        $end_of_month = $date->copy()->endOfMonth();
        $days_in_month = $date->daysInMonth;

        // Get daily attendance counts
        $attendance = AccessLog::entries()
            ->whereBetween('event_timestamp', [$start_of_month, $end_of_month])
            ->select(
                DB::raw('DATE(event_timestamp) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Build chart data with all days
        $labels = [];
        $data = [];
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = $start_of_month->copy()->addDays($day - 1);
            $labels[] = $current_date->format('d');
            $date_key = $current_date->format('Y-m-d');
            $data[] = $attendance[$date_key] ?? 0;
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
        ];
    }

    #[Computed]
    public function revenueChartData(): array
    {
        $date = Carbon::createFromFormat('Y-m', $this->revenue_month);
        $start_of_month = $date->copy()->startOfMonth();
        $end_of_month = $date->copy()->endOfMonth();
        $days_in_month = $date->daysInMonth;

        // Get daily revenue
        $revenue = PaymentTransaction::paid()
            ->whereBetween('paid_at', [$start_of_month, $end_of_month])
            ->select(
                DB::raw('DATE(paid_at) as date'),
                DB::raw('SUM(amount) as total')
            )
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        // Get revenue by type for this month
        $revenue_by_type = PaymentTransaction::paid()
            ->whereBetween('paid_at', [$start_of_month, $end_of_month])
            ->select('revenue_type', DB::raw('SUM(amount) as total'))
            ->groupBy('revenue_type')
            ->pluck('total', 'revenue_type')
            ->toArray();

        // Build chart data with all days
        $labels = [];
        $data = [];
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $current_date = $start_of_month->copy()->addDays($day - 1);
            $labels[] = $current_date->format('d');
            $date_key = $current_date->format('Y-m-d');
            $data[] = (float) ($revenue[$date_key] ?? 0);
        }

        return [
            'labels' => $labels,
            'data' => $data,
            'total' => array_sum($data),
            'by_type' => $revenue_by_type,
        ];
    }

    #[Computed]
    public function availableMonths(): array
    {
        $months = [];
        $current = now();
        
        // Show last 12 months
        for ($i = 0; $i < 12; $i++) {
            $date = $current->copy()->subMonths($i);
            $months[$date->format('Y-m')] = $date->format('F Y');
        }

        return $months;
    }

    public function render(): View
    {
        return view('livewire.dashboard');
    }
}

