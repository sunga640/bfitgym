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
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search orders...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="draft">{{ __('Draft') }}</option>
                <option value="ordered">{{ __('Ordered') }}</option>
                <option value="received">{{ __('Received') }}</option>
            </flux:select>
            <flux:select wire:model.live="supplier_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Suppliers') }}</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <flux:button variant="primary" href="{{ route('purchase-orders.create') }}" wire:navigate icon="plus">
            {{ __('New Order') }}
        </flux:button>
    </div>

    {{-- Orders Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Order #') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Supplier') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Items') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($orders as $order)
                    <tr wire:key="order-{{ $order->id }}">
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm font-medium text-zinc-900 dark:text-white">{{ $order->order_number }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-zinc-900 dark:text-white">{{ $order->supplier?->name ?? '-' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $order->order_date?->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $order->items->count() }}
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                            {{ money($order->total_amount) }}
                        </td>
                        <td class="px-6 py-4">
                            @switch($order->status)
                                @case('draft')
                                    <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                                    @break
                                @case('ordered')
                                    <flux:badge color="blue" size="sm">{{ __('Ordered') }}</flux:badge>
                                    @break
                                @case('received')
                                    <flux:badge color="emerald" size="sm">{{ __('Received') }}</flux:badge>
                                    @break
                            @endswitch
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if($order->status === 'ordered')
                                    <flux:button
                                        variant="primary"
                                        size="sm"
                                        wire:click="receiveOrder({{ $order->id }})"
                                        wire:confirm="{{ __('Mark this order as received and update stock?') }}"
                                        icon="check"
                                    >
                                        {{ __('Receive') }}
                                    </flux:button>
                                @endif
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('purchase-orders.show', $order) }}"
                                    wire:navigate
                                    icon="eye"
                                    title="{{ __('View') }}"
                                />
                                @if($order->status !== 'received')
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        href="{{ route('purchase-orders.edit', $order) }}"
                                        wire:navigate
                                        icon="pencil"
                                        title="{{ __('Edit') }}"
                                    />
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="delete({{ $order->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this order?') }}"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        title="{{ __('Delete') }}"
                                    />
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <flux:icon name="clipboard-document-list" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No purchase orders') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create your first purchase order to order from suppliers.') }}</p>
                            <flux:button variant="primary" href="{{ route('purchase-orders.create') }}" wire:navigate class="mt-4" icon="plus">
                                {{ __('New Order') }}
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($orders->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>

