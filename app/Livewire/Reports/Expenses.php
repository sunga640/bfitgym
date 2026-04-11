<?php

namespace App\Livewire\Reports;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Expenses extends Component
{
    use WithPagination;

    #[Url]
    public string $period = 'month';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    #[Url]
    public string $category_filter = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasAnyPermission([
            'view expense reports',
            'view financial reports',
            'view reports',
        ]), 403);

        if ($this->date_from === '' || $this->date_to === '') {
            $this->setDefaultDates();
        }
    }

    public function updatedPeriod(): void
    {
        $this->setDefaultDates();
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

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->period = 'month';
        $this->category_filter = '';
        $this->search = '';
        $this->setDefaultDates();
        $this->resetPage();
    }

    #[Computed]
    public function summary(): array
    {
        $query = $this->buildBaseQuery();

        $total = (float) (clone $query)->sum('amount');
        $count = (int) (clone $query)->count();
        $largest = (float) (clone $query)->max('amount');

        return [
            'total' => $total,
            'count' => $count,
            'average' => $count > 0 ? $total / $count : 0.0,
            'largest' => $largest,
        ];
    }

    #[Computed]
    public function categoryBreakdown(): array
    {
        $rows = (clone $this->buildBaseQuery())
            ->select('expense_category_id', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total_count'))
            ->groupBy('expense_category_id')
            ->orderByDesc('total_amount')
            ->limit(6)
            ->get();

        $category_ids = $rows
            ->pluck('expense_category_id')
            ->filter()
            ->values();

        $names = ExpenseCategory::query()
            ->whereIn('id', $category_ids)
            ->pluck('name', 'id');

        $grand_total = max((float) $rows->sum('total_amount'), 0.01);

        return $rows->map(function ($row) use ($names, $grand_total) {
            $category_name = $row->expense_category_id
                ? ($names[(int) $row->expense_category_id] ?? __('Unknown'))
                : __('Uncategorized');

            return [
                'name' => $category_name,
                'total' => (float) $row->total_amount,
                'count' => (int) $row->total_count,
                'share' => (int) round(((float) $row->total_amount / $grand_total) * 100),
            ];
        })->all();
    }

    public function render(): View
    {
        $expenses = $this->buildBaseQuery()
            ->with('category:id,name')
            ->orderByDesc('expense_date')
            ->paginate(20);

        $branch_id = current_branch_id();

        $categories = ExpenseCategory::query()
            ->when($branch_id !== null, fn (Builder $query) => $query->forBranch($branch_id))
            ->when($branch_id === null, fn (Builder $query) => $query->global())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.reports.expenses', [
            'expenses' => $expenses,
            'categories' => $categories,
        ]);
    }

    protected function buildBaseQuery(): Builder
    {
        return Expense::query()
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $nested_query) {
                    $nested_query
                        ->where('description', 'like', '%' . $this->search . '%')
                        ->orWhere('reference', 'like', '%' . $this->search . '%')
                        ->orWhereHas('category', function (Builder $category_query) {
                            $category_query->where('name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->category_filter !== '', fn (Builder $query) => $query->where('expense_category_id', (int) $this->category_filter))
            ->whereDate('expense_date', '>=', $this->date_from)
            ->whereDate('expense_date', '<=', $this->date_to);
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

