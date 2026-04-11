<?php

namespace App\Livewire\ExpenseCategories;

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

    public function mount(): void
    {
        $this->authorize('viewAny', ExpenseCategory::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function deleteCategory(int $category_id): void
    {
        try {
            $category = ExpenseCategory::query()->findOrFail($category_id);

            $this->authorize('delete', $category);

            $expense_count = Expense::query()
                ->withoutBranchScope()
                ->where('expense_category_id', $category->id)
                ->count();

            if ($expense_count > 0) {
                session()->flash('error', __('Cannot delete this category because it has :count expense record(s).', [
                    'count' => $expense_count,
                ]));
                return;
            }

            DB::beginTransaction();
            $category->delete();
            DB::commit();

            session()->flash('success', __('Expense category deleted successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException) {
            session()->flash('error', __('You do not have permission to delete this category.'));
        } catch (ModelNotFoundException) {
            session()->flash('error', __('Expense category not found.'));
        } catch (\Throwable $throwable) {
            DB::rollBack();

            Log::error('Failed to delete expense category', [
                'category_id' => $category_id,
                'user_id' => auth()->id(),
                'error' => $throwable->getMessage(),
            ]);

            session()->flash('error', __('Failed to delete expense category. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = current_branch_id();

        $categories = ExpenseCategory::query()
            ->with('branch:id,name')
            ->withCount('expenses')
            ->when($branch_id !== null, function (Builder $query) use ($branch_id) {
                $query->forBranch($branch_id);
            })
            ->when($branch_id === null, function (Builder $query) {
                $query->global();
            })
            ->when($this->search !== '', function (Builder $query) {
                $query->where(function (Builder $nested_query) {
                    $nested_query
                        ->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('description', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.expense-categories.index', [
            'categories' => $categories,
        ]);
    }
}
