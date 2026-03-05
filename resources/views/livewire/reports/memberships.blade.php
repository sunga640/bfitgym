<div>
    <div class="mb-6 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 xl:flex-row xl:items-end xl:justify-between">
        <div class="flex flex-1 flex-wrap gap-4">
            <flux:select wire:model.live="period" class="w-full sm:w-40">
                <option value="today">{{ __('Today') }}</option>
                <option value="week">{{ __('This Week') }}</option>
                <option value="month">{{ __('This Month') }}</option>
                <option value="quarter">{{ __('This Quarter') }}</option>
                <option value="year">{{ __('This Year') }}</option>
                <option value="custom">{{ __('Custom') }}</option>
            </flux:select>

            @if($period === 'custom')
                <flux:input type="date" wire:model.live="date_from" class="w-full sm:w-40" />
                <flux:input type="date" wire:model.live="date_to" class="w-full sm:w-40" />
            @endif

            <flux:select wire:model.live="status_filter" class="w-full sm:w-44">
                <option value="">{{ __('All statuses') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="expired">{{ __('Expired') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>

            <flux:select wire:model.live="package_filter" class="w-full sm:w-52">
                <option value="">{{ __('All packages') }}</option>
                @foreach($packages as $package)
                    <option value="{{ $package->id }}">{{ $package->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="auto_renew_filter" class="w-full sm:w-40">
                <option value="">{{ __('Auto renew') }}</option>
                <option value="yes">{{ __('Enabled') }}</option>
                <option value="no">{{ __('Disabled') }}</option>
            </flux:select>
        </div>

        <div class="flex w-full flex-col gap-3 xl:w-auto xl:min-w-[320px] xl:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search member, number, or package...') }}"
                class="w-full xl:w-80"
            />

            <div class="flex flex-wrap items-center gap-3 text-sm text-zinc-500 dark:text-zinc-400">
                <span>{{ \Carbon\Carbon::parse($date_from)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($date_to)->format('M d, Y') }}</span>
                <flux:button variant="ghost" icon="x-mark" wire:click="clearFilters">
                    {{ __('Reset') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="users" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active subscriptions') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->summaryCards['active_total']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/40 dark:bg-amber-500/10">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-200 dark:bg-amber-900/40">
                    <flux:icon name="clock" class="h-6 w-6 text-amber-700 dark:text-amber-300" />
                </div>
                <div>
                    <p class="text-sm text-amber-700 dark:text-amber-200">{{ __('Expiring in 7 days') }}</p>
                    <p class="text-xl font-bold text-amber-800 dark:text-amber-100">{{ number_format($this->summaryCards['expiring_soon']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 dark:border-blue-500/40 dark:bg-blue-500/10">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-200 dark:bg-blue-900/40">
                    <flux:icon name="arrow-path" class="h-6 w-6 text-blue-700 dark:text-blue-300" />
                </div>
                <div>
                    <p class="text-sm text-blue-700 dark:text-blue-200">{{ __('Renewals in period') }}</p>
                    <p class="text-xl font-bold text-blue-800 dark:text-blue-100">{{ number_format($this->summaryCards['renewals']) }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-500 to-teal-600 p-5 text-white">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/15">
                    <flux:icon name="banknotes" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <p class="text-sm text-emerald-100">{{ __('Membership revenue') }}</p>
                    <p class="text-xl font-bold">{{ money($this->summaryCards['revenue']) }}</p>
                    <p class="mt-1 text-xs text-emerald-100">
                        {{ __(':count subscriptions started in this period', ['count' => number_format($this->periodSnapshot['started'])]) }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="mb-6 grid gap-6 xl:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Status mix') }}</h3>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($this->periodSnapshot['started']) }} {{ __('records') }}</span>
            </div>

            <div class="space-y-4">
                @php
                    $barColors = [
                        'emerald' => 'bg-emerald-500',
                        'amber' => 'bg-amber-500',
                        'rose' => 'bg-rose-500',
                        'zinc' => 'bg-zinc-500',
                    ];
                @endphp

                @foreach($this->statusSummary as $status)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $status['label'] }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($status['count']) }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div
                                class="h-2 rounded-full {{ $barColors[$status['color']] ?? 'bg-zinc-500' }}"
                                style="width: {{ $status['count'] > 0 ? max($status['share'], 6) : 0 }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $status['share'] }}%</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Top packages') }}</h3>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Selected period') }}</span>
            </div>

            <div class="space-y-4">
                @forelse($this->packageHighlights as $package)
                    <div>
                        <div class="mb-1 flex items-center justify-between gap-4 text-sm">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $package['name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $package['duration'] }}</p>
                            </div>
                            <span class="text-right font-medium text-zinc-700 dark:text-zinc-200">{{ number_format($package['subscriptions_count']) }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            <div class="h-2 rounded-full bg-sky-500" style="width: {{ max($package['share'], 8) }}%"></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>{{ $package['share'] }}%</span>
                            <span>{{ money($package['price']) }}</span>
                        </div>
                    </div>
                @empty
                    <div class="flex min-h-48 flex-col items-center justify-center text-center text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="credit-card" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-4 text-sm">{{ __('No package activity for this period.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Renewal watchlist') }}</h3>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Next 14 days') }}</span>
            </div>

            <div class="space-y-3">
                @forelse($this->expiringSoonSubscriptions as $subscription)
                    <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $subscription['member_name'] }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $subscription['member_no'] }} &middot; {{ $subscription['package_name'] }}</p>
                            </div>
                            @if($subscription['auto_renew'])
                                <flux:badge color="emerald" size="sm">{{ __('Auto') }}</flux:badge>
                            @endif
                        </div>
                        <div class="mt-3 flex items-center justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ $subscription['end_date'] }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">
                                @if($subscription['days_left'] === 0)
                                    {{ __('Due today') }}
                                @elseif($subscription['days_left'] === 1)
                                    {{ __('1 day left') }}
                                @else
                                    {{ __(':days days left', ['days' => $subscription['days_left']]) }}
                                @endif
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="flex min-h-48 flex-col items-center justify-center text-center text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="shield-check" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-4 text-sm">{{ __('Nothing is expiring in the next two weeks.') }}</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-6 rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
                <h4 class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Period snapshot') }}</h4>
                <dl class="mt-3 space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Started') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($this->periodSnapshot['started']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('New sign-ups') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($this->periodSnapshot['new_signups']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Pending activations') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($this->periodSnapshot['pending']) }}</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-zinc-500 dark:text-zinc-400">{{ __('Auto renew active') }}</dt>
                        <dd class="font-medium text-zinc-900 dark:text-white">{{ number_format($this->periodSnapshot['auto_renew_enabled']) }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Subscription activity') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Subscriptions that started in the selected period.') }}</p>
                </div>
                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ number_format($subscriptions->total()) }} {{ __('results') }}</span>
            </div>
        </div>

        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Cycle') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Renewal') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @php
                    $statusColors = [
                        'active' => 'emerald',
                        'pending' => 'amber',
                        'expired' => 'zinc',
                        'cancelled' => 'rose',
                    ];
                @endphp

                @forelse($subscriptions as $subscription)
                    <tr class="hover:bg-zinc-50/60 dark:hover:bg-zinc-900/40" wire:key="membership-report-{{ $subscription->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $subscription->member?->full_name ?? __('Unknown member') }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $subscription->member?->member_no ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-zinc-900 dark:text-white">{{ $subscription->membershipPackage?->name ?? __('Deleted package') }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $subscription->membershipPackage?->formatted_duration ?? __('N/A') }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            <div>{{ $subscription->start_date->format('M d, Y') }} - {{ $subscription->end_date->format('M d, Y') }}</div>
                            <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                @if($subscription->status === 'active' && $subscription->end_date->isFuture())
                                    {{ __('Ends in :days days', ['days' => now()->startOfDay()->diffInDays($subscription->end_date)]) }}
                                @elseif($subscription->end_date->isToday())
                                    {{ __('Ends today') }}
                                @elseif($subscription->end_date->isPast())
                                    {{ __('Ended :date', ['date' => $subscription->end_date->format('M d, Y')]) }}
                                @else
                                    {{ __('Starts soon') }}
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge :color="$statusColors[$subscription->status] ?? 'zinc'" size="sm">
                                {{ ucfirst($subscription->status) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($subscription->renewed_from_id)
                                    <flux:badge color="blue" size="sm">{{ __('Renewal') }}</flux:badge>
                                @else
                                    <flux:badge color="purple" size="sm">{{ __('New') }}</flux:badge>
                                @endif

                                @if($subscription->auto_renew)
                                    <flux:badge color="emerald" size="sm">{{ __('Auto renew') }}</flux:badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            @if($subscription->latestPayment)
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ money($subscription->latestPayment->amount, $subscription->latestPayment->currency) }}
                                </div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">
                                    {{ ucfirst(str_replace('_', ' ', $subscription->latestPayment->payment_method)) }} &middot; {{ $subscription->latestPayment->paid_at?->format('M d, Y') }}
                                </div>
                            @else
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ money($subscription->membershipPackage?->price ?? 0) }}
                                </div>
                                <div class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('No payment record') }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('subscriptions.show', $subscription) }}"
                                wire:navigate
                                icon="eye"
                            >
                                {{ __('View') }}
                            </flux:button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center text-zinc-500 dark:text-zinc-400">
                                <flux:icon name="chart-pie" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <p class="mt-4 text-base font-medium text-zinc-700 dark:text-white">{{ __('No subscriptions found') }}</p>
                                <p class="mt-1 text-sm">{{ __('Try adjusting the selected period or filters.') }}</p>
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
