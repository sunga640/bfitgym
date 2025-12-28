<div>
    {{-- Period Selector & Filters --}}
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
            @if($period === 'custom')
                <flux:input type="date" wire:model.live="date_from" class="w-40" />
                <flux:input type="date" wire:model.live="date_to" class="w-40" />
            @endif
            <flux:select wire:model.live="revenue_type" class="w-48">
                <option value="">{{ __('All Sources') }}</option>
                @foreach($revenue_types as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ \Carbon\Carbon::parse($date_from)->format('M d, Y') }} - {{ \Carbon\Carbon::parse($date_to)->format('M d, Y') }}
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Today's Revenue --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="calendar" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ money($this->dashboardKPIs['today']) }}</p>
                </div>
            </div>
        </div>

        {{-- This Month --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="banknotes" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('This Month') }}</p>
                    <p class="text-xl font-bold text-zinc-900 dark:text-white">{{ money($this->dashboardKPIs['this_month']) }}</p>
                </div>
            </div>
        </div>

        {{-- Growth --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                @if($this->dashboardKPIs['growth_percentage'] >= 0)
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                        <flux:icon name="arrow-trending-up" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                @else
                    <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30">
                        <flux:icon name="arrow-trending-down" class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                @endif
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Growth') }}</p>
                    <p class="text-xl font-bold {{ $this->dashboardKPIs['growth_percentage'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                        {{ $this->dashboardKPIs['growth_percentage'] >= 0 ? '+' : '' }}{{ $this->dashboardKPIs['growth_percentage'] }}%
                    </p>
                </div>
            </div>
        </div>

        {{-- Period Total --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800 dark:bg-emerald-900/20">
            <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-200 dark:bg-emerald-900/50">
                    <flux:icon name="currency-dollar" class="h-6 w-6 text-emerald-700 dark:text-emerald-300" />
                </div>
                <div>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ __('Period Total') }}</p>
                    <p class="text-xl font-bold text-emerald-800 dark:text-emerald-200">{{ money($this->revenueSummary['total']) }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="mb-6 grid gap-6 lg:grid-cols-3">
        {{-- Revenue by Source --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="mb-4 font-semibold text-zinc-900 dark:text-white">{{ __('Revenue by Source') }}</h3>
            <div class="space-y-4">
                @foreach($this->topSources as $key => $source)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-zinc-600 dark:text-zinc-300">{{ $source['label'] }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ money($source['amount']) }}</span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-zinc-200 dark:bg-zinc-700">
                            @php
                                $bar_colors = [
                                    'emerald' => 'bg-emerald-500',
                                    'blue' => 'bg-blue-500',
                                    'purple' => 'bg-purple-500',
                                    'amber' => 'bg-amber-500',
                                ];
                            @endphp
                            <div
                                class="h-2 rounded-full {{ $bar_colors[$source['color']] ?? 'bg-zinc-500' }}"
                                style="width: {{ $source['percentage'] }}%"
                            ></div>
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $source['percentage'] }}%</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Revenue Summary Cards --}}
        <div class="lg:col-span-2 grid gap-4 sm:grid-cols-2">
            <div class="rounded-xl border border-emerald-200 bg-gradient-to-br from-emerald-500 to-emerald-600 p-6 text-white">
                <flux:icon name="credit-card" class="h-8 w-8 text-emerald-200" />
                <p class="mt-4 text-sm text-emerald-200">{{ __('Memberships') }}</p>
                <p class="text-2xl font-bold">{{ money($this->revenueSummary['membership']) }}</p>
            </div>
            <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-500 to-blue-600 p-6 text-white">
                <flux:icon name="calendar" class="h-8 w-8 text-blue-200" />
                <p class="mt-4 text-sm text-blue-200">{{ __('Classes') }}</p>
                <p class="text-2xl font-bold">{{ money($this->revenueSummary['class_booking']) }}</p>
            </div>
            <div class="rounded-xl border border-purple-200 bg-gradient-to-br from-purple-500 to-purple-600 p-6 text-white">
                <flux:icon name="ticket" class="h-8 w-8 text-purple-200" />
                <p class="mt-4 text-sm text-purple-200">{{ __('Events') }}</p>
                <p class="text-2xl font-bold">{{ money($this->revenueSummary['event']) }}</p>
            </div>
            <div class="rounded-xl border border-amber-200 bg-gradient-to-br from-amber-500 to-amber-600 p-6 text-white">
                <flux:icon name="shopping-cart" class="h-8 w-8 text-amber-200" />
                <p class="mt-4 text-sm text-amber-200">{{ __('POS Sales') }}</p>
                <p class="text-2xl font-bold">{{ money($this->revenueSummary['pos']) }}</p>
            </div>
        </div>
    </div>

    {{-- Transactions Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Recent Transactions') }}</h3>
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search...') }}"
                    class="w-48"
                />
            </div>
        </div>

        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reference') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Payer') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Method') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($transactions as $transaction)
                    <tr wire:key="txn-{{ $transaction->id }}">
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $transaction->paid_at?->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-zinc-600 dark:text-zinc-300">{{ $transaction->reference ?? '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($transaction->payerMember)
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $transaction->payerMember->full_name }}</div>
                            @elseif($transaction->payerInsurer)
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $transaction->payerInsurer->name }}</div>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $type_badges = [
                                    'membership' => ['color' => 'emerald', 'label' => __('Membership')],
                                    'class_booking' => ['color' => 'blue', 'label' => __('Class')],
                                    'event' => ['color' => 'purple', 'label' => __('Event')],
                                    'pos' => ['color' => 'amber', 'label' => __('POS')],
                                ];
                                $badge = $type_badges[$transaction->revenue_type] ?? ['color' => 'zinc', 'label' => ucfirst($transaction->revenue_type)];
                            @endphp
                            <flux:badge :color="$badge['color']" size="sm">{{ $badge['label'] }}</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ ucfirst(str_replace('_', ' ', $transaction->payment_method)) }}
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                            {{ money($transaction->amount, $transaction->currency) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <flux:icon name="banknotes" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No transactions found for this period.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($transactions->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>
</div>

