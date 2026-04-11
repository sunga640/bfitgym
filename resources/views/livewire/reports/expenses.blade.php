<div>
    <div class="mb-6 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-wrap gap-4">
            <flux:select wire:model.live="period" class="w-40">
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('This Week') }}</option>
                <option value="month">{{ __('This Month') }}</option>
                <option value="quarter">{{ __('This Quarter') }}</option>
                <option value="year">{{ __('This Year') }}</option>
                <option value="custom">{{ __('Custom') }}</option>
            </flux:select>

            <flux:input type="date" wire:model.live="date_from" class="w-40" />
            <flux:input type="date" wire:model.live="date_to" class="w-40" />

            <flux:select wire:model.live="category_filter" class="w-56">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search description or reference...') }}"
                class="w-72"
            />
        </div>

        <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
            {{ __('Reset') }}
        </flux:button>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</p>
            <p class="mt-2 text-2xl font-bold text-zinc-900 dark:text-white">{{ money($this->summary['total']) }}</p>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-5 dark:border-rose-500/40 dark:bg-rose-500/10">
            <p class="text-sm text-rose-700 dark:text-rose-200">{{ __('Largest Expense') }}</p>
            <p class="mt-2 text-2xl font-bold text-rose-800 dark:text-rose-100">{{ money($this->summary['largest']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/40 dark:bg-amber-500/10">
            <p class="text-sm text-amber-700 dark:text-amber-200">{{ __('Average Expense') }}</p>
            <p class="mt-2 text-2xl font-bold text-amber-800 dark:text-amber-100">{{ money($this->summary['average']) }}</p>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-500/40 dark:bg-blue-500/10">
            <p class="text-sm text-blue-700 dark:text-blue-200">{{ __('Entries') }}</p>
            <p class="mt-2 text-2xl font-bold text-blue-800 dark:text-blue-100">{{ number_format($this->summary['count']) }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">{{ __('Category Breakdown') }}</h3>
        @if(empty($this->categoryBreakdown))
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No expense data available for this period.') }}</p>
        @else
            <div class="space-y-4">
                @foreach($this->categoryBreakdown as $row)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $row['name'] }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ money($row['total']) }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-2 rounded-full bg-rose-500"
                                style="width: {{ max(2, $row['share']) }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice(':count entry|:count entries', $row['count']) }} - {{ $row['share'] }}%
                        </p>
                    </div>
                @endforeach
            </div>
        @endif
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
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($expenses as $expense)
                    <tr wire:key="expense-report-{{ $expense->id }}">
                        <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-200">{{ $expense->expense_date?->format('M d, Y') }}</td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $expense->category?->name ?? __('Uncategorized') }}</td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $expense->reference ?: '-' }}</td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $expense->description ?: '-' }}</td>
                        <td class="px-6 py-4 text-right font-semibold text-zinc-900 dark:text-white">{{ money((float) $expense->amount, $expense->currency) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No expenses found for the selected filters.') }}
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

