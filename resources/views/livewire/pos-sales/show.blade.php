<div>
    <div class="mx-auto max-w-3xl">
        {{-- Receipt Header --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-start justify-between border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Receipt') }}</h1>
                    <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $sale->sale_number }}</p>
                </div>
                <div class="text-right">
                    @if($sale->status === 'completed')
                        <flux:badge color="emerald" size="lg">{{ __('Paid') }}</flux:badge>
                    @elseif($sale->status === 'refunded')
                        <flux:badge color="rose" size="lg">{{ __('Refunded') }}</flux:badge>
                    @endif
                </div>
            </div>

            {{-- Sale Info --}}
            <div class="grid gap-4 py-4 sm:grid-cols-2">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Date & Time') }}</p>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ $sale->sale_datetime->format('M d, Y H:i') }}</p>
                </div>
                @if($sale->member)
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Customer') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $sale->member->full_name }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $sale->member->phone }}</p>
                    </div>
                @endif
                @if($sale->paymentTransaction)
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Payment Method') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ ucfirst(str_replace('_', ' ', $sale->paymentTransaction->payment_method)) }}</p>
                    </div>
                @endif
            </div>

            {{-- Items --}}
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <h3 class="mb-3 font-medium text-zinc-900 dark:text-white">{{ __('Items') }}</h3>
                <table class="w-full">
                    <thead>
                        <tr class="text-xs uppercase text-zinc-500 dark:text-zinc-400">
                            <th class="pb-2 text-left">{{ __('Item') }}</th>
                            <th class="pb-2 text-center">{{ __('Qty') }}</th>
                            <th class="pb-2 text-right">{{ __('Price') }}</th>
                            <th class="pb-2 text-right">{{ __('Total') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($sale->items as $item)
                            <tr>
                                <td class="py-2">
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $item->branchProduct?->product?->name ?? 'Unknown' }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $item->branchProduct?->product?->sku ?? '' }}</p>
                                </td>
                                <td class="py-2 text-center text-zinc-600 dark:text-zinc-300">{{ $item->quantity }}</td>
                                <td class="py-2 text-right text-zinc-600 dark:text-zinc-300">{{ money($item->unit_price) }}</td>
                                <td class="py-2 text-right font-medium text-zinc-900 dark:text-white">{{ money($item->total_price) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Totals --}}
            <div class="mt-4 space-y-2 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                    <span class="text-zinc-600 dark:text-zinc-300">{{ money($sale->subtotal) }}</span>
                </div>
                @if($sale->discount_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Discount') }}</span>
                        <span class="text-red-600 dark:text-red-400">-{{ money($sale->discount_amount) }}</span>
                    </div>
                @endif
                @if($sale->tax_amount > 0)
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Tax') }}</span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ money($sale->tax_amount) }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                    <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                    <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money($sale->total_amount) }}</span>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="mt-6 flex justify-center gap-3">
            <flux:button variant="ghost" href="{{ route('pos-sales.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Sales') }}
            </flux:button>
            <flux:button variant="primary" wire:click="printReceipt" icon="printer">
                {{ __('Print Receipt') }}
            </flux:button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-receipt', () => {
            window.print();
        });
    });
</script>
@endpush

