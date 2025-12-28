<div>
    {{-- Flash Messages --}}
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

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search insurers...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count insurer|:count insurers', $insurers->total()) }}
        </div>
    </div>

    {{-- Insurers Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('Name') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('Contact') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('Policies') }}
                </th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('Status') }}
                </th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                    {{ __('Actions') }}
                </th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($insurers as $insurer)
                <tr wire:key="insurer-{{ $insurer->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $insurer->name }}</div>
                        @if($insurer->contact_person)
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $insurer->contact_person }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-zinc-900 dark:text-white">{{ $insurer->phone ?: '—' }}</div>
                        <div class="text-xs text-zinc-400">{{ $insurer->email ?: '—' }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $insurer->member_insurances_count }} {{ __('policies') }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <flux:badge
                            :color="$insurer->status === 'active' ? 'emerald' : 'zinc'"
                            size="sm"
                            class="cursor-pointer"
                            wire:click="toggleStatus({{ $insurer->id }})"
                            title="{{ __('Click to toggle status') }}"
                        >
                            {{ ucfirst($insurer->status) }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('insurers.show', $insurer) }}"
                                wire:navigate
                                icon="eye"
                            >
                                {{ __('View') }}
                            </flux:button>
                            @can('manage insurers')
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('insurers.edit', $insurer) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteInsurer({{ $insurer->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this insurer?') }}"
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
                            <flux:icon name="heart" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No insurers found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                @if($search || $status_filter)
                                    {{ __('Try adjusting your search or filters.') }}
                                @else
                                    {{ __('Get started by adding your first insurer.') }}
                                @endif
                            </p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>

        @if($insurers->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $insurers->links() }}
            </div>
        @endif
    </div>
</div>

