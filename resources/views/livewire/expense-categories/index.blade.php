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

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search categories...') }}"
                class="w-full md:max-w-sm"
            />
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count category|:count categories', $categories->total()) }}
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Scope') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Expenses') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($categories as $category)
                    <tr wire:key="expense-category-{{ $category->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $category->name }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($category->branch_id)
                                <flux:badge color="blue" size="sm">{{ $category->branch?->name ?? __('Branch') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ __('Global') }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $category->description ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                            {{ number_format($category->expenses_count) }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                @can('update', $category)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('expense-categories.edit', $category) }}"
                                        wire:navigate
                                        icon="pencil"
                                    >
                                        {{ __('Edit') }}
                                    </flux:button>
                                @endcan
                                @can('delete', $category)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="deleteCategory({{ $category->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this category?') }}"
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
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="folder" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No categories found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($search)
                                        {{ __('Try adjusting your search terms.') }}
                                    @else
                                        {{ __('Create your first expense category to get started.') }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($categories->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>

