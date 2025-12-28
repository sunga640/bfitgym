<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between print:hidden">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('purchase-orders.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Purchase Order') }}</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $purchase_order->order_number }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('purchase-orders.edit', $purchase_order) }}" wire:navigate variant="ghost" icon="pencil">
                {{ __('Edit') }}
            </flux:button>
            <flux:button wire:click="printOrder" variant="primary" icon="printer">
                {{ __('Print') }}
            </flux:button>
        </div>
    </div>

    {{-- Printable Content --}}
    <div class="space-y-6" id="printable-content">
        {{-- Print Header (only visible when printing) --}}
        <div class="hidden print:block mb-8 text-center border-b border-zinc-200 pb-6">
            <h1 class="text-2xl font-bold text-zinc-900">{{ __('Purchase Order') }}</h1>
            <p class="text-lg font-medium text-zinc-700 mt-1">{{ $purchase_order->order_number }}</p>
        </div>

        {{-- Order Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 print:border-zinc-300 print:shadow-none">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4 print:border-zinc-300">
                {{ __('Order Details') }}
            </h2>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Order Number') }}</p>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->order_number }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Order Date') }}</p>
                    <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->order_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                    <span @class([
                        'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium',
                        'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400' => $purchase_order->status === 'draft',
                        'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' => $purchase_order->status === 'ordered',
                        'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' => $purchase_order->status === 'received',
                    ])>
                        {{ ucfirst($purchase_order->status) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Amount') }}</p>
                    <p class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money($purchase_order->total_amount) }}</p>
                </div>
            </div>
        </div>

        {{-- Supplier Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 print:border-zinc-300 print:shadow-none">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4 print:border-zinc-300">
                {{ __('Supplier Information') }}
            </h2>

            @if($purchase_order->supplier)
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Supplier Name') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->supplier->name }}</p>
                    </div>
                    @if($purchase_order->supplier->contact_person)
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Contact Person') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->supplier->contact_person }}</p>
                        </div>
                    @endif
                    @if($purchase_order->supplier->phone)
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->supplier->phone }}</p>
                        </div>
                    @endif
                    @if($purchase_order->supplier->email)
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->supplier->email }}</p>
                        </div>
                    @endif
                    @if($purchase_order->supplier->address)
                        <div class="sm:col-span-2">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Address') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ $purchase_order->supplier->address }}</p>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-zinc-500 dark:text-zinc-400">{{ __('No supplier information available.') }}</p>
            @endif
        </div>

        {{-- Order Items --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 print:border-zinc-300 print:shadow-none">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700 print:border-zinc-300">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Order Items') }}</h2>
            </div>

            @if($purchase_order->items->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700 print:divide-zinc-300">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 print:bg-zinc-100">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Product') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Unit Cost') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800 print:divide-zinc-300">
                            @foreach($purchase_order->items as $index => $item)
                                <tr>
                                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $item->product->name }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-sm font-mono text-zinc-500 dark:text-zinc-400">{{ $item->product->sku }}</td>
                                    <td class="px-6 py-4 text-right text-zinc-900 dark:text-white">{{ number_format($item->quantity) }}</td>
                                    <td class="px-6 py-4 text-right text-zinc-600 dark:text-zinc-300">{{ money($item->unit_cost) }}</td>
                                    <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">{{ money($item->total_cost) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-zinc-50 dark:bg-zinc-900/50 print:bg-zinc-100">
                            <tr>
                                <td colspan="3" class="px-6 py-4"></td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-500 dark:text-zinc-400">{{ __('Total Qty:') }}</td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">{{ number_format($purchase_order->items->sum('quantity')) }}</td>
                                <td class="px-6 py-4"></td>
                            </tr>
                            <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                                <td colspan="4" class="px-6 py-4"></td>
                                <td class="px-6 py-4 text-right text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Grand Total:') }}</td>
                                <td class="px-6 py-4 text-right text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money($purchase_order->total_amount) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <div class="p-12 text-center">
                    <flux:icon name="shopping-cart" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __('No items in this order.') }}</p>
                </div>
            @endif
        </div>

        {{-- Print Footer (only visible when printing) --}}
        <div class="hidden print:block mt-8 pt-6 border-t border-zinc-200 text-center text-sm text-zinc-500">
            <p>{{ __('Generated on :date', ['date' => now()->format('M d, Y H:i')]) }}</p>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('print-order', () => {
            window.print();
        });
    });
</script>
@endpush

@push('styles')
<style>
    @media print {
        /* Hide everything except printable content */
        body * {
            visibility: hidden;
        }
        #printable-content, #printable-content * {
            visibility: visible;
        }
        #printable-content {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
        /* Reset dark mode styles for printing */
        .dark\:text-white { color: #18181b !important; }
        .dark\:text-zinc-300 { color: #52525b !important; }
        .dark\:text-zinc-400 { color: #71717a !important; }
        .dark\:bg-zinc-800 { background-color: #ffffff !important; }
        .dark\:bg-zinc-900\/50 { background-color: #f4f4f5 !important; }
        .dark\:border-zinc-700 { border-color: #e4e4e7 !important; }
        .dark\:divide-zinc-700 > * + * { border-color: #e4e4e7 !important; }
        .dark\:text-emerald-400 { color: #059669 !important; }
    }
</style>
@endpush

