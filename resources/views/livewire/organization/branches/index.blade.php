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

    {{-- Page Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Branches') }}</h1>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Manage your organization\'s branches and locationswdds.') }}
            </p>
        </div>
        @can('create', \App\Models\Branch::class)
        <flux:button variant="primary" icon="plus" href="{{ route('branches.create') }}" wire:navigate>
            {{ __('Add Branch') }}
        </flux:button>
        @endcan
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by name, code, or city...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count branch|:count branches', $branches->total()) }}
        </div>
    </div>

    {{-- Branches Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Branch') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Location') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Members') }}
                    </th>
                    <th class="px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Subscriptions') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Revenue (MTD)') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($branches as $branch)
                    @php
                        $branch_metrics = $metrics[$branch->id] ?? [
                            'active_members_count' => 0,
                            'active_subscriptions_count' => 0,
                            'membership_revenue_this_month' => 0,
                        ];
                    @endphp
                    <tr wire:key="branch-{{ $branch->id }}">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                                    <flux:icon name="building-office-2" class="h-5 w-5" />
                                </div>
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $branch->name }}</div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $branch->code }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-zinc-900 dark:text-white">{{ $branch->city ?: '-' }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $branch->phone ?: '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge :color="$branch->status === 'active' ? 'emerald' : 'zinc'" size="sm">
                                {{ ucfirst($branch->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ number_format($branch_metrics['active_members_count']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ number_format($branch_metrics['active_subscriptions_count']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">
                                {{ money($branch_metrics['membership_revenue_this_month']) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('organization.branches.show', $branch) }}"
                                    wire:navigate
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:button>
                                @can('update', $branch)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('organization.branches.settings', $branch) }}"
                                    wire:navigate
                                    icon="cog-6-tooth"
                                >
                                    {{ __('Settings') }}
                                </flux:button>
                                @endcan
                                @can('manageStatus', $branch)
                                    @if($branch->status === 'active')
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="confirmDeactivate({{ $branch->id }})"
                                            icon="pause"
                                            class="text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                        >
                                            {{ __('Deactivate') }}
                                        </flux:button>
                                    @else
                                        <flux:button
                                            variant="ghost"
                                            size="sm"
                                            wire:click="confirmActivate({{ $branch->id }})"
                                            icon="play"
                                            class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                        >
                                            {{ __('Activate') }}
                                        </flux:button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="building-office-2" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No branches found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first branch.') }}</p>
                                @can('create', \App\Models\Branch::class)
                                <flux:button variant="primary" icon="plus" href="{{ route('branches.create') }}" wire:navigate class="mt-4">
                                    {{ __('Add Branch') }}
                                </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($branches->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $branches->links() }}
            </div>
        @endif
    </div>

    {{-- Deactivate Modal --}}
    <flux:modal wire:model="show_deactivate_modal" class="max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Deactivate Branch') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Are you sure you want to deactivate this branch? Users will not be able to switch to it.') }}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deactivateBranch">
                    {{ __('Deactivate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Activate Modal --}}
    <flux:modal wire:model="show_activate_modal" class="max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                    <flux:icon name="check-circle" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Activate Branch') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Are you sure you want to activate this branch? Users will be able to switch to it.') }}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="activateBranch">
                    {{ __('Activate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

