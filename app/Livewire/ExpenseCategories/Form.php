<?php

namespace App\Livewire\ExpenseCategories;

use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?ExpenseCategory $expenseCategory = null;

    public bool $is_editing = false;
    public bool $needs_branch_selection = false;

    public string $name = '';
    public string $description = '';

    public function mount(?ExpenseCategory $expenseCategory = null): void
    {
        $this->expenseCategory = $expenseCategory;
        $this->is_editing = $expenseCategory && $expenseCategory->exists;

        if ($this->is_editing) {
            $this->authorize('update', $expenseCategory);

            $this->name = $expenseCategory->name;
            $this->description = $expenseCategory->description ?? '';
            return;
        }

        $this->authorize('create', ExpenseCategory::class);

        if (!current_branch_id() && !(auth()->user()?->hasRole('super-admin') ?? false)) {
            $this->needs_branch_selection = true;
        }
    }

    public function rules(): array
    {
        $branch_id = $this->resolveTargetBranchId();

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('expense_categories', 'name')
                    ->where(function ($query) use ($branch_id) {
                        if ($branch_id === null) {
                            $query->whereNull('branch_id');
                        } else {
                            $query->where('branch_id', $branch_id);
                        }

                        $query->whereNull('deleted_at');
                    })
                    ->ignore($this->expenseCategory?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function save(): void
    {
        if ($this->needs_branch_selection) {
            session()->flash('error', __('Please select a branch before creating an expense category.'));
            return;
        }

        $validated = $this->validate();

        $name = trim($validated['name']);
        $description = blank($validated['description']) ? null : trim($validated['description']);

        if ($this->is_editing && $this->expenseCategory) {
            $this->authorize('update', $this->expenseCategory);

            $this->expenseCategory->update([
                'name' => $name,
                'description' => $description,
            ]);

            session()->flash('success', __('Expense category updated successfully.'));
        } else {
            $this->authorize('create', ExpenseCategory::class);

            ExpenseCategory::create([
                'branch_id' => $this->resolveTargetBranchId(),
                'name' => $name,
                'description' => $description,
            ]);

            session()->flash('success', __('Expense category created successfully.'));
        }

        $this->redirect(route('expense-categories.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.expense-categories.form');
    }

    protected function resolveTargetBranchId(): ?int
    {
        if ($this->is_editing) {
            return $this->expenseCategory?->branch_id;
        }

        return current_branch_id();
    }
}

