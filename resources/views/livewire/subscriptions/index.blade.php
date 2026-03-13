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

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active subscriptions') }}</div>
            <div class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">{{ number_format($stats['active']) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-500/10">
            <div class="text-sm text-amber-700 dark:text-amber-200">{{ __('Expiring in 7 days') }}</div>
            <div class="mt-2 text-3xl font-semibold text-amber-800 dark:text-amber-100">{{ number_format($stats['expiring']) }}</div>
        </div>
        <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 dark:border-blue-500/40 dark:bg-blue-500/10">
            <div class="text-sm text-blue-700 dark:text-blue-200">{{ __('Pending activations') }}</div>
            <div class="mt-2 text-3xl font-semibold text-blue-800 dark:text-blue-100">{{ number_format($stats['pending']) }}</div>
        </div>
    </div>

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search member or number...') }}"
                class="w-full md:max-w-xs"
            />

            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>

            <flux:select wire:model.live="package_filter" class="w-full md:max-w-[220px]">
                <option value="">{{ __('All packages') }}</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="auto_renew_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('Auto renew?') }}</option>
                <option value="yes">{{ __('Yes') }}</option>
                <option value="no">{{ __('No') }}</option>
            </flux:select>
        </div>

        <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters" class="self-start">
            {{ __('Clear filters') }}
        </flux:button>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Member') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Package') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Period') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Status') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Auto renew') }}
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Latest payment') }}
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40" wire:key="subscription-{{ $subscription->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">
                                {{ $subscription->member->full_name }}
                            </div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $subscription->member->member_no }}
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ $subscription->membershipPackage->name }}
                            </div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $subscription->membershipPackage->formatted_duration }}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $subscription->start_date->format('M d, Y') }} — {{ $subscription->end_date->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColors = [
                                    'active' => 'emerald',
                                    'pending' => 'amber',
                                    'expired' => 'zinc',
                                    'cancelled' => 'rose',
                                ];
                                $status = $subscription->status;
                            @endphp
                            <flux:badge :color="$statusColors[$status] ?? 'zinc'" size="sm">
                                {{ ucfirst($subscription->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">
                            @if($subscription->auto_renew)
                                <flux:badge color="emerald" size="sm">{{ __('Enabled') }}</flux:badge>
                            @else
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Disabled') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            @if($subscription->latestPayment)
                                <div>{{ money($subscription->latestPayment->amount, $subscription->latestPayment->currency) }}</div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ $subscription->latestPayment->payment_method }} • {{ $subscription->latestPayment->paid_at?->format('M d, Y') }}
                                </div>
                            @else
                                <span class="text-zinc-400">{{ __('—') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('subscriptions.show', $subscription) }}"
                                    wire:navigate
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:button>
                                @can('update', $subscription)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('subscriptions.edit', $subscription) }}"
                                        wire:navigate
                                    >
                                        {{ __('Edit') }}
                                    </flux:button>
                                @endcan
                                @if($subscription->end_date->isPast() || $subscription->end_date->isToday())
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('subscriptions.renew', $subscription) }}"
                                        wire:navigate
                                        icon="arrow-path"
                                    >
                                        {{ __('Renew') }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="clipboard-document-list" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <p class="mt-4 text-base font-medium text-zinc-700 dark:text-white">{{ __('No subscriptions found') }}</p>
                                <p class="mt-1 text-sm">
                                    @if($search || $status_filter || $package_filter || $auto_renew_filter)
                                        {{ __('Try adjusting the filters above.') }}
                                    @else
                                        {{ __('Create your first subscription to get started.') }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($subscriptions->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $subscriptions->links() }}
            </div>
        @endif
    </div>
</div>
