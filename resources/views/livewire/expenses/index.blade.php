<div>
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Filtered Total') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">{{ money($summary['period_total']) }}</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 dark:border-rose-500/40 dark:bg-rose-500/10">
            <div class="text-sm text-rose-700 dark:text-rose-200">{{ __('This Month') }}</div>
            <div class="mt-2 text-2xl font-semibold text-rose-800 dark:text-rose-100">{{ money($summary['month_total']) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-500/10">
            <div class="text-sm text-amber-700 dark:text-amber-200">{{ __('Average Expense') }}</div>
            <div class="mt-2 text-2xl font-semibold text-amber-800 dark:text-amber-100">{{ money($summary['average_expense']) }}</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/40 dark:bg-blue-500/10">
            <div class="text-sm text-blue-700 dark:text-blue-200">{{ __('Entries') }}</div>
            <div class="mt-2 text-2xl font-semibold text-blue-800 dark:text-blue-100">{{ number_format($summary['expenses_count']) }}</div>
        </div>
    </div>

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-1 flex-col gap-4 lg:flex-row lg:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search reference, description, or category...') }}"
                class="w-full lg:max-w-sm"
            />

            <flux:select wire:model.live="category_filter" class="w-full lg:max-w-[240px]">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <flux:input type="date" wire:model.live="date_from" class="w-full lg:max-w-[170px]" />
            <flux:input type="date" wire:model.live="date_to" class="w-full lg:max-w-[170px]" />
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                {{ __('Clear filters') }}
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reference') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($expenses as $expense)
                    <tr wire:key="expense-{{ $expense->id }}">
                        <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                            {{ $expense->expense_date?->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($expense->category)
                                <flux:badge color="zinc" size="sm">{{ $expense->category->name }}</flux:badge>
                            @else
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Uncategorized') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $expense->reference ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $expense->description ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-zinc-900 dark:text-white">
                            {{ money((float) $expense->amount, $expense->currency) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @can('update', $expense)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('expenses.edit', $expense) }}"
                                        wire:navigate
                                        icon="pencil"
                                    >
                                        {{ __('Edit') }}
                                    </flux:button>
                                @endcan
                                @can('delete', $expense)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="deleteExpense({{ $expense->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this expense?') }}"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="arrow-trending-down" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No expenses found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Try adjusting filters or add a new expense record.') }}
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($expenses->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>
</div>

