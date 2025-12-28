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

    {{-- Summary Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Sales') }}</p>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($totals['count']) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
            <p class="text-sm text-emerald-700 dark:text-emerald-400">{{ __('Total Revenue') }}</p>
            <p class="text-2xl font-semibold text-emerald-800 dark:text-emerald-300">{{ money($totals['revenue']) }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-sm text-red-700 dark:text-red-400">{{ __('Total Refunded') }}</p>
            <p class="text-2xl font-semibold text-red-800 dark:text-red-300">{{ money($totals['refunded']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search sales...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="refunded">{{ __('Refunded') }}</option>
            </flux:select>
            <flux:input
                wire:model.live="date_from"
                type="date"
                class="w-full md:max-w-[150px]"
            />
            <flux:input
                wire:model.live="date_to"
                type="date"
                class="w-full md:max-w-[150px]"
            />
        </div>
        <flux:button variant="primary" href="{{ route('pos.index') }}" wire:navigate icon="shopping-cart">
            {{ __('New Sale') }}
        </flux:button>
    </div>

    {{-- Sales Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Invoice #') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Customer') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Items') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($sales as $sale)
                    <tr wire:key="sale-{{ $sale->id }}">
                        <td class="px-6 py-4">
                            <a href="{{ route('pos-sales.show', $sale) }}" wire:navigate class="font-mono text-sm font-medium text-emerald-600 hover:text-emerald-700 dark:text-emerald-400">
                                {{ $sale->sale_number }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $sale->sale_datetime->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            @if($sale->member)
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $sale->member->full_name }}</div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $sale->member->phone }}</div>
                            @else
                                <span class="text-sm text-zinc-400">{{ __('Walk-in') }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $sale->items->sum('quantity') }}
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                            {{ money($sale->total_amount) }}
                        </td>
                        <td class="px-6 py-4">
                            @if($sale->status === 'completed')
                                <flux:badge color="emerald" size="sm">{{ __('Completed') }}</flux:badge>
                            @elseif($sale->status === 'refunded')
                                <flux:badge color="rose" size="sm">{{ __('Refunded') }}</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">{{ ucfirst($sale->status) }}</flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('pos-sales.show', $sale) }}"
                                    wire:navigate
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:button>
                                @if($sale->status === 'completed')
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="refundSale({{ $sale->id }})"
                                        wire:confirm="{{ __('Are you sure you want to refund this sale? Stock will be restored.') }}"
                                        icon="arrow-uturn-left"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        {{ __('Refund') }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <flux:icon name="receipt-percent" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No sales found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sales will appear here after checkout.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($sales->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $sales->links() }}
            </div>
        @endif
    </div>
</div>

