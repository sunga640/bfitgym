<div>
    @if($needs_branch_selection)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-900/20">
                <flux:icon name="building-office" class="mx-auto h-12 w-12 text-amber-500" />
                <h3 class="mt-4 text-lg font-semibold text-amber-800 dark:text-amber-200">
                    {{ __('Branch Selection Required') }}
                </h3>
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    {{ __('Please select a branch from the sidebar before creating an expense.') }}
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <flux:button variant="ghost" href="{{ route('expenses.index') }}" wire:navigate>
                        {{ __('Go Back') }}
                    </flux:button>
                    <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                        {{ __('Go to Dashboard') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="save" class="mx-auto max-w-3xl">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $is_editing ? __('Edit Expense') : __('Record Expense') }}
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Capture the amount, date, and category for this expense entry.') }}
                    </p>
                </div>

                <div class="space-y-6 p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Category') }}</flux:label>
                            <flux:select wire:model="expense_category_id">
                                <option value="">{{ __('Uncategorized') }}</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="expense_category_id" />
                        </flux:field>

                        <flux:field>
                            <flux:label required>{{ __('Expense Date') }}</flux:label>
                            <flux:input type="date" wire:model="expense_date" required />
                            <flux:error name="expense_date" />
                        </flux:field>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label required>{{ __('Amount') }}</flux:label>
                            <flux:input
                                type="number"
                                step="0.01"
                                min="0.01"
                                wire:model="amount"
                                placeholder="0.00"
                                required
                            />
                            <flux:error name="amount" />
                        </flux:field>

                        <flux:field>
                            <flux:label required>{{ __('Currency') }}</flux:label>
                            <flux:input
                                type="text"
                                wire:model="currency"
                                maxlength="3"
                                placeholder="TZS"
                                required
                            />
                            <flux:error name="currency" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Reference') }}</flux:label>
                        <flux:input
                            type="text"
                            wire:model="reference"
                            placeholder="{{ __('Receipt number, invoice ref, etc.') }}"
                        />
                        <flux:error name="reference" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea
                            wire:model="description"
                            rows="4"
                            placeholder="{{ __('Optional details about this expense.') }}"
                        />
                        <flux:error name="description" />
                    </flux:field>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:button variant="ghost" href="{{ route('expenses.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                        {{ $is_editing ? __('Update Expense') : __('Save Expense') }}
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</div>

