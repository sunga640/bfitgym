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

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</p>
                    <p class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $subscription->member->full_name }}
                    </p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $subscription->member->member_no }}</p>
                </div>
                <flux:badge color="{{ $subscription->auto_renew ? 'emerald' : 'zinc' }}" size="sm">
                    {{ $subscription->auto_renew ? __('Auto renew enabled') : __('Auto renew off') }}
                </flux:badge>
            </div>
            <div class="mt-6 grid gap-4 md:grid-cols-2">
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Start date') }}</p>
                    <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ $subscription->start_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('End date') }}</p>
                    <p class="text-base font-semibold text-zinc-900 dark:text-white">{{ $subscription->end_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                    @php
                        $statusColors = [
                            'active' => 'emerald',
                            'pending' => 'amber',
                            'expired' => 'zinc',
                            'cancelled' => 'rose',
                        ];
                    @endphp
                    <flux:badge :color="$statusColors[$subscription->status] ?? 'zinc'">
                        {{ ucfirst($subscription->status) }}
                    </flux:badge>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</p>
                    <p class="text-base font-semibold text-zinc-900 dark:text-white">
                        {{ $subscription->membershipPackage->name }}
                    </p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $subscription->membershipPackage->formatted_duration }}
                    </p>
                </div>
            </div>
            @if($subscription->notes)
                <div class="mt-6 rounded-xl bg-zinc-50 p-4 text-sm text-zinc-600 dark:bg-zinc-900/50 dark:text-zinc-300">
                    {{ $subscription->notes }}
                </div>
            @endif
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Latest payment') }}</p>
            @if($subscription->latestPayment)
                <p class="mt-2 text-3xl font-semibold text-zinc-900 dark:text-white">
                    {{ money($subscription->latestPayment->amount, $subscription->latestPayment->currency) }}
                </p>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $subscription->latestPayment->payment_method }} • {{ $subscription->latestPayment->paid_at?->format('M d, Y H:i') }}
                </p>
                @if($subscription->latestPayment->reference)
                    <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">Ref: {{ $subscription->latestPayment->reference }}</p>
                @endif
            @else
                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No payment recorded for this cycle.') }}</p>
            @endif
            <div class="mt-6">
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total payments recorded') }}</p>
                <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($payments->count()) }}</p>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Renewal timeline') }}</p>
            <div class="mt-4 space-y-4">
                @forelse($timeline as $entry)
                    <div class="flex gap-3">
                        <div class="flex flex-col items-center">
                            <div class="h-3 w-3 rounded-full {{ $entry->id === $subscription->id ? 'bg-emerald-500' : 'bg-zinc-400' }}"></div>
                            @if(!$loop->last)
                                <div class="mt-1 h-full w-px flex-1 bg-zinc-200 dark:bg-zinc-700"></div>
                            @endif
                        </div>
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $entry->membershipPackage->name }}
                                @if($entry->id === $subscription->id)
                                    <span class="ml-2 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200">{{ __('Current') }}</span>
                                @endif
                            </p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $entry->start_date->format('M d, Y') }} → {{ $entry->end_date->format('M d, Y') }}
                            </p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No renewal history yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-3">
        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 lg:col-span-2">
            <div class="flex items-center justify-between">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Payment history') }}</h3>
            </div>
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Amount') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Method') }}</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reference') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse($payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $payment->paid_at?->format('M d, Y H:i') ?? '—' }}</td>
                                <td class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">{{ money($payment->amount, $payment->currency) }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-300">{{ $payment->payment_method }}</td>
                                <td class="px-4 py-3 text-sm text-zinc-500 dark:text-zinc-400">{{ $payment->reference ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No payments recorded yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-base font-semibold text-zinc-900 dark:text-white">{{ __('Actions') }}</h3>
            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Manage the lifecycle of this subscription.') }}
            </p>
            <div class="mt-4 space-y-4">
                {{-- Status Update --}}
                @if($this->canUpdateStatus)
                    <div>
                        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Update Status') }}</p>
                        <div class="flex flex-wrap gap-2">
                            @foreach(['pending', 'active', 'expired', 'cancelled'] as $status)
                                @if($status !== $subscription->status)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="updateStatus('{{ $status }}')"
                                        wire:confirm="{{ __('Are you sure you want to change the status to :status?', ['status' => ucfirst($status)]) }}"
                                        class="{{ $status === 'cancelled' ? 'text-red-600 hover:text-red-700 dark:text-red-400' : '' }}"
                                    >
                                        @php
                                            $icons = [
                                                'pending' => 'clock',
                                                'active' => 'check-circle',
                                                'expired' => 'x-circle',
                                                'cancelled' => 'x-mark',
                                            ];
                                        @endphp
                                        <flux:icon :name="$icons[$status] ?? 'circle'" class="mr-1 h-4 w-4" />
                                        {{ ucfirst($status) }}
                                    </flux:button>
                                @endif
                            @endforeach
                        </div>
                    </div>
                    <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700"></div>
                @endif

                {{-- Other Actions --}}
                @if($this->canRenew)
                    <flux:button
                        variant="primary"
                        href="{{ route('subscriptions.renew', $subscription) }}"
                        wire:navigate
                        icon="arrow-path"
                        class="w-full justify-center"
                    >
                        {{ __('Renew subscription') }}
                    </flux:button>
                @endif
                <flux:button
                    variant="ghost"
                    href="{{ route('subscriptions.index') }}"
                    wire:navigate
                    class="w-full justify-center"
                >
                    {{ __('Back to list') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>
