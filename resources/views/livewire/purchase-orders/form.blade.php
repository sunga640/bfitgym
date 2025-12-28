<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-4xl space-y-6">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Order Details --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Order Details') }}</h2>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <flux:field>
                    <flux:label>{{ __('Supplier') }}</flux:label>
                    <flux:select wire:model.live="supplier_id" required>
                        <option value="">{{ __('Select supplier') }}</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                        @endforeach
                    </flux:select>
                    @error('supplier_id')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Order Date') }}</flux:label>
                    <flux:input type="date" wire:model.live="order_date" required />
                    @error('order_date')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Status') }}</flux:label>
                    <flux:select wire:model.live="status">
                        <option value="draft">{{ __('Draft') }}</option>
                        <option value="ordered">{{ __('Ordered') }}</option>
                    </flux:select>
                    @error('status')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>
            </div>
        </div>

        {{-- Add Item Form --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="font-medium text-zinc-900 dark:text-white mb-4">{{ __('Add Item') }}</h3>

            <div class="flex flex-wrap gap-4 items-end">
                <div class="flex-1 min-w-[200px]">
                    <flux:field>
                        <flux:label>{{ __('Product') }}</flux:label>
                        <flux:select wire:model="add_product_id">
                            <option value="">{{ __('Select product') }}</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                            @endforeach
                        </flux:select>
                    </flux:field>
                </div>
                <div class="w-32">
                    <flux:field>
                        <flux:label>{{ __('Quantity') }}</flux:label>
                        <flux:input type="number" wire:model="add_quantity" min="1" />
                    </flux:field>
                </div>
                <div class="w-40">
                    <flux:field>
                        <flux:label>{{ __('Unit Cost') }}</flux:label>
                        <flux:input type="number" wire:model="add_unit_cost" step="0.01" min="0" placeholder="0.00" />
                    </flux:field>
                </div>
                <flux:button type="button" wire:click="addItem" icon="plus">
                    {{ __('Add') }}
                </flux:button>
            </div>
        </div>

        {{-- Order Items --}}
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h3 class="font-medium text-zinc-900 dark:text-white">{{ __('Order Items') }}</h3>
            </div>

            @if(count($items) > 0)
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Product') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Unit Cost') }}</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                        @foreach($items as $index => $item)
                            <tr wire:key="item-{{ $index }}">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $item['product_name'] }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">{{ $item['product_sku'] }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <input
                                        type="number"
                                        wire:change="updateItemQuantity({{ $index }}, $event.target.value)"
                                        value="{{ $item['quantity'] }}"
                                        min="1"
                                        class="w-20 rounded-lg border-zinc-300 bg-transparent text-right text-sm dark:border-zinc-600"
                                    />
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <input
                                        type="number"
                                        wire:change="updateItemUnitCost({{ $index }}, $event.target.value)"
                                        value="{{ $item['unit_cost'] }}"
                                        step="0.01"
                                        min="0"
                                        class="w-28 rounded-lg border-zinc-300 bg-transparent text-right text-sm dark:border-zinc-600"
                                    />
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                                    {{ money($item['total_cost']) }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="removeItem({{ $index }})"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700"
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-zinc-50 dark:bg-zinc-900/50">
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                                {{ __('Total') }}
                            </td>
                            <td class="px-6 py-4 text-right text-lg font-bold text-zinc-900 dark:text-white">
                                {{ money($order_total) }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            @else
                <div class="p-12 text-center">
                    <flux:icon name="shopping-cart" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                    <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No items added') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Add products to this order.') }}</p>
                </div>
            @endif
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('purchase-orders.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $isEditing ? __('Update Order') : __('Create Order') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

