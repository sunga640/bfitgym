<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category_filter = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public function mount(): void
    {
        $this->authorize('viewAny', Expense::class);

        if ($this->date_from === '' || $this->date_to === '') {
            $this->date_from = now()->startOfMonth()->format('Y-m-d');
            $this->date_to = now()->endOfMonth()->format('Y-m-d');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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
        $this->search = '';
        $this->category_filter = '';
        $this->date_from = now()->startOfMonth()->format('Y-m-d');
        $this->date_to = now()->endOfMonth()->format('Y-m-d');
        $this->resetPage();
    }

    public function deleteExpense(int $expense_id): void
    {
        try {
            $expense = Expense::query()->findOrFail($expense_id);

            $this->authorize('delete', $expense);

            DB::beginTransaction();
            $expense->delete();
            DB::commit();

            session()->flash('success', __('Expense deleted successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            session()->flash('error', __('You do not have permission to delete this expense.'));
        } catch (ModelNotFoundException) {
            session()->flash('error', __('Expense not found.'));
        } catch (\Throwable $throwable) {
            DB::rollBack();

            Log::error('Failed to delete expense', [
                'expense_id' => $expense_id,
                'user_id' => auth()->id(),
                'error' => $throwable->getMessage(),
            ]);

            session()->flash('error', __('Failed to delete expense. Please try again.'));
        }
    }

    public function render(): View
    {
        $base_query = Expense::query()
            ->with(['category:id,name'])
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
            ->when($this->category_filter !== '', function (Builder $query) {
                $query->where('expense_category_id', (int) $this->category_filter);
            })
            ->when($this->date_from !== '', fn (Builder $query) => $query->whereDate('expense_date', '>=', $this->date_from))
            ->when($this->date_to !== '', fn (Builder $query) => $query->whereDate('expense_date', '<=', $this->date_to));

        $expenses = (clone $base_query)
            ->orderByDesc('expense_date')
            ->paginate(12);

        $period_total = (float) (clone $base_query)->sum('amount');
        $expenses_count = (int) (clone $base_query)->count();
        $average_expense = $expenses_count > 0 ? $period_total / $expenses_count : 0.0;

        $month_total = (float) Expense::query()
            ->whereBetween('expense_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        $branch_id = current_branch_id();

        $categories = ExpenseCategory::query()
            ->when($branch_id !== null, fn (Builder $query) => $query->forBranch($branch_id))
            ->when($branch_id === null, fn (Builder $query) => $query->global())
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('livewire.expenses.index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'summary' => [
                'period_total' => $period_total,
                'month_total' => $month_total,
                'average_expense' => $average_expense,
                'expenses_count' => $expenses_count,
            ],
        ]);
    }
}

