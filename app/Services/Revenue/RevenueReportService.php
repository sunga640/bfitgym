<?php

namespace App\Services\Revenue;

use App\Models\PaymentTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RevenueReportService
{
    /**
     * Get revenue breakdown by source for a period.
     */
    public function getRevenueBySource(?int $branch_id, Carbon $from, Carbon $to): array
    {
        $query = PaymentTransaction::paid()
            ->excludeDeletedMembershipSubscriptions()
            ->betweenDates($from, $to);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        $revenue = $query->select('revenue_type', DB::raw('SUM(amount) as total'))
            ->groupBy('revenue_type')
            ->pluck('total', 'revenue_type')
            ->toArray();

        return [
            'membership' => (float) ($revenue['membership'] ?? 0),
            'class_booking' => (float) ($revenue['class_booking'] ?? 0),
            'event' => (float) ($revenue['event'] ?? 0),
            'pos' => (float) ($revenue['pos'] ?? 0),
            'total' => array_sum($revenue),
        ];
    }

    /**
     * Get daily revenue for a period.
     */
    public function getDailyRevenue(?int $branch_id, Carbon $from, Carbon $to): Collection
    {
        $query = PaymentTransaction::paid()
            ->excludeDeletedMembershipSubscriptions()
            ->betweenDates($from, $to);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->select(
            DB::raw('DATE(paid_at) as date'),
            'revenue_type',
            DB::raw('SUM(amount) as total')
        )
            ->groupBy('date', 'revenue_type')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get monthly revenue for a year.
     */
    public function getMonthlyRevenue(?int $branch_id, int $year): Collection
    {
        $query = PaymentTransaction::paid()
            ->excludeDeletedMembershipSubscriptions()
            ->whereYear('paid_at', $year);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return $query->select(
            DB::raw('MONTH(paid_at) as month'),
            'revenue_type',
            DB::raw('SUM(amount) as total')
        )
            ->groupBy('month', 'revenue_type')
            ->orderBy('month')
            ->get();
    }

    /**
     * Get revenue summary for dashboard KPIs.
     */
    public function getDashboardKPIs(?int $branch_id): array
    {
        $today = today();
        $start_of_month = $today->copy()->startOfMonth();
        $start_of_last_month = $today->copy()->subMonth()->startOfMonth();
        $end_of_last_month = $today->copy()->subMonth()->endOfMonth();

        // Today's revenue
        $today_revenue = $this->getRevenueForPeriod($branch_id, $today, $today);

        // This month's revenue
        $month_revenue = $this->getRevenueForPeriod($branch_id, $start_of_month, $today);

        // Last month's revenue (for comparison)
        $last_month_revenue = $this->getRevenueForPeriod($branch_id, $start_of_last_month, $end_of_last_month);

        // Calculate growth percentage
        $growth_percentage = $last_month_revenue > 0
            ? (($month_revenue - $last_month_revenue) / $last_month_revenue) * 100
            : 0;

        // Revenue by source this month
        $revenue_by_source = $this->getRevenueBySource($branch_id, $start_of_month, $today);

        return [
            'today' => $today_revenue,
            'this_month' => $month_revenue,
            'last_month' => $last_month_revenue,
            'growth_percentage' => round($growth_percentage, 1),
            'by_source' => $revenue_by_source,
        ];
    }

    /**
     * Get total revenue for a period.
     */
    public function getRevenueForPeriod(?int $branch_id, Carbon $from, Carbon $to): float
    {
        $query = PaymentTransaction::paid()
            ->excludeDeletedMembershipSubscriptions()
            ->betweenDates($from, $to);

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        return (float) $query->sum('amount');
    }

    /**
     * Get payment transactions with filters.
     */
    public function getTransactions(?int $branch_id, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = PaymentTransaction::with(['payerMember', 'payerInsurer', 'payable'])
            ->excludeDeletedMembershipSubscriptions();

        if ($branch_id) {
            $query->where('branch_id', $branch_id);
        }

        if (!empty($filters['revenue_type'])) {
            $query->where('revenue_type', $filters['revenue_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('paid_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('paid_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('payerMember', function ($q) use ($search) {
                        $q->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->latest('paid_at')
            ->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get revenue chart data.
     */
    public function getRevenueChartData(?int $branch_id, string $period = 'month'): array
    {
        $labels = [];
        $datasets = [
            'membership' => [],
            'class_booking' => [],
            'event' => [],
            'pos' => [],
        ];

        if ($period === 'month') {
            // Daily data for current month
            $start = today()->startOfMonth();
            $end = today();

            $daily_data = $this->getDailyRevenue($branch_id, $start, $end);

            // Build labels for all days
            $current = $start->copy();
            while ($current <= $end) {
                $date_str = $current->format('Y-m-d');
                $labels[] = $current->format('d');

                foreach (array_keys($datasets) as $type) {
                    $day_data = $daily_data->where('date', $date_str)->where('revenue_type', $type)->first();
                    $datasets[$type][] = $day_data ? (float) $day_data->total : 0;
                }

                $current->addDay();
            }
        } else {
            // Monthly data for current year
            $year = now()->year;
            $monthly_data = $this->getMonthlyRevenue($branch_id, $year);

            for ($month = 1; $month <= 12; $month++) {
                $labels[] = Carbon::create($year, $month, 1)->format('M');

                foreach (array_keys($datasets) as $type) {
                    $month_data = $monthly_data->where('month', $month)->where('revenue_type', $type)->first();
                    $datasets[$type][] = $month_data ? (float) $month_data->total : 0;
                }
            }
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Get top revenue sources summary.
     */
    public function getTopSourcesSummary(?int $branch_id, Carbon $from, Carbon $to): array
    {
        $revenue = $this->getRevenueBySource($branch_id, $from, $to);
        $total = $revenue['total'];

        $sources = [
            'membership' => [
                'label' => 'Memberships',
                'amount' => $revenue['membership'],
                'percentage' => $total > 0 ? round(($revenue['membership'] / $total) * 100, 1) : 0,
                'color' => 'emerald',
            ],
            'class_booking' => [
                'label' => 'Classes',
                'amount' => $revenue['class_booking'],
                'percentage' => $total > 0 ? round(($revenue['class_booking'] / $total) * 100, 1) : 0,
                'color' => 'blue',
            ],
            'event' => [
                'label' => 'Events',
                'amount' => $revenue['event'],
                'percentage' => $total > 0 ? round(($revenue['event'] / $total) * 100, 1) : 0,
                'color' => 'purple',
            ],
            'pos' => [
                'label' => 'POS Sales',
                'amount' => $revenue['pos'],
                'percentage' => $total > 0 ? round(($revenue['pos'] / $total) * 100, 1) : 0,
                'color' => 'amber',
            ],
        ];

        // Sort by amount descending
        uasort($sources, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return $sources;
    }
}
