<?php

namespace App\Livewire\Expenses;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Expense $expense = null;

    public bool $is_editing = false;
    public bool $needs_branch_selection = false;

    public ?int $expense_category_id = null;
    public string $amount = '';
    public string $currency = '';
    public string $expense_date = '';
    public string $description = '';
    public string $reference = '';

    public function mount(?Expense $expense = null): void
    {
        $this->expense = $expense;
        $this->is_editing = $expense && $expense->exists;

        if ($this->is_editing) {
            $this->authorize('update', $expense);

            $this->expense_category_id = $expense->expense_category_id;
            $this->amount = (string) $expense->amount;
            $this->currency = strtoupper($expense->currency);
            $this->expense_date = $expense->expense_date?->format('Y-m-d') ?? now()->format('Y-m-d');
            $this->description = $expense->description ?? '';
            $this->reference = $expense->reference ?? '';
            return;
        }

        $this->authorize('create', Expense::class);

        if (!current_branch_id()) {
            $this->needs_branch_selection = true;
            return;
        }

        $this->currency = app_currency();
        $this->expense_date = now()->format('Y-m-d');
    }

    public function rules(): array
    {
        return [
            'expense_category_id' => ['nullable', Rule::exists('expense_categories', 'id')],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:2000'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function save(): void
    {
        if ($this->needs_branch_selection) {
            session()->flash('error', __('Please select a branch before creating an expense.'));
            return;
        }

        $validated = $this->validate();

        if (!$this->isCategoryAllowedForBranch($validated['expense_category_id'] ?? null)) {
            $this->addError('expense_category_id', __('The selected category is not available in this branch.'));
            return;
        }

        $payload = [
            'expense_category_id' => $validated['expense_category_id'] ?: null,
            'amount' => $validated['amount'],
            'currency' => strtoupper(trim($validated['currency'])),
            'expense_date' => $validated['expense_date'],
            'description' => blank($validated['description']) ? null : trim($validated['description']),
            'reference' => blank($validated['reference']) ? null : trim($validated['reference']),
        ];

        if ($this->is_editing && $this->expense) {
            $this->authorize('update', $this->expense);

            $this->expense->update($payload);
            session()->flash('success', __('Expense updated successfully.'));
        } else {
            $this->authorize('create', Expense::class);

            Expense::create([
                ...$payload,
                'branch_id' => current_branch_id(),
            ]);

            session()->flash('success', __('Expense created successfully.'));
        }

        $this->redirect(route('expenses.index'), navigate: true);
    }

    public function render(): View
    {
        $branch_id = current_branch_id();
        $current_category_id = $this->expense?->expense_category_id;

        $categories_query = ExpenseCategory::query();

        if ($branch_id !== null) {
            $categories_query->forBranch($branch_id);
        } else {
            $categories_query->global();
        }

        $categories = $categories_query
            ->orderBy('name')
            ->get(['id', 'name', 'branch_id']);

        if ($this->is_editing && $current_category_id && !$categories->contains('id', $current_category_id)) {
            $current_category = ExpenseCategory::query()->find($current_category_id, ['id', 'name', 'branch_id']);

            if ($current_category) {
                $categories->prepend($current_category);
            }
        }

        return view('livewire.expenses.form', [
            'categories' => $categories,
        ]);
    }

    protected function isCategoryAllowedForBranch(?int $category_id): bool
    {
        if (!$category_id) {
            return true;
        }

        $category = ExpenseCategory::query()->find($category_id);
        if (!$category) {
            return false;
        }

        if ($category->branch_id === null) {
            return true;
        }

        $branch_id = $this->is_editing ? $this->expense?->branch_id : current_branch_id();

        return $branch_id !== null && (int) $category->branch_id === (int) $branch_id;
    }
}
