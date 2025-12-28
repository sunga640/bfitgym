<?php

namespace App\Livewire\Reports;

use App\Services\Revenue\RevenueReportService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Revenue Report', 'description' => 'Comprehensive revenue analysis by source.'])]
#[Title('Revenue Report')]
class Revenue extends Component
{
    use WithPagination;

    #[Url]
    public string $period = 'month';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public string $revenue_type = '';

    #[Url]
    public string $search = '';

    protected RevenueReportService $revenue_service;

    public function boot(RevenueReportService $revenue_service): void
    {
        $this->revenue_service = $revenue_service;
    }

    public function mount(): void
    {
        // Set default dates based on period
        if (empty($this->date_from) || empty($this->date_to)) {
            $this->setDefaultDates();
        }
    }

    public function updatedPeriod(): void
    {
        $this->setDefaultDates();
        $this->resetPage();
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
                $this->date_from = $now->startOfWeek()->format('Y-m-d');
                $this->date_to = $now->endOfWeek()->format('Y-m-d');
                break;
            case 'month':
                $this->date_from = $now->startOfMonth()->format('Y-m-d');
                $this->date_to = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'quarter':
                $this->date_from = $now->startOfQuarter()->format('Y-m-d');
                $this->date_to = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'year':
                $this->date_from = $now->startOfYear()->format('Y-m-d');
                $this->date_to = $now->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                // Keep existing dates
                break;
        }
    }

    #[Computed]
    public function revenueSummary(): array
    {
        $branch_id = current_branch_id();
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        return $this->revenue_service->getRevenueBySource($branch_id, $from, $to);
    }

    #[Computed]
    public function topSources(): array
    {
        $branch_id = current_branch_id();
        $from = Carbon::parse($this->date_from)->startOfDay();
        $to = Carbon::parse($this->date_to)->endOfDay();

        return $this->revenue_service->getTopSourcesSummary($branch_id, $from, $to);
    }

    #[Computed]
    public function chartData(): array
    {
        $branch_id = current_branch_id();
        return $this->revenue_service->getRevenueChartData($branch_id, $this->period === 'year' ? 'year' : 'month');
    }

    #[Computed]
    public function dashboardKPIs(): array
    {
        $branch_id = current_branch_id();
        return $this->revenue_service->getDashboardKPIs($branch_id);
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        // Get transactions with filters
        $transactions = $this->revenue_service->getTransactions($branch_id, [
            'revenue_type' => $this->revenue_type,
            'from_date' => $this->date_from,
            'to_date' => $this->date_to,
            'search' => $this->search,
            'status' => 'paid',
            'per_page' => 15,
        ]);

        $revenue_types = [
            'membership' => 'Memberships',
            'class_booking' => 'Classes',
            'event' => 'Events',
            'pos' => 'POS Sales',
        ];

        return view('livewire.reports.revenue', [
            'transactions' => $transactions,
            'revenue_types' => $revenue_types,
        ]);
    }
}

