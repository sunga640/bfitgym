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

    {{-- Inventory Summary Cards --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Products') }}</p>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($inventory_summary['total_products']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Stock Units') }}</p>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ number_format($inventory_summary['total_stock_units']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Inventory Value') }}</p>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ money($inventory_summary['inventory_value']) }}</p>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <p class="text-sm text-amber-700 dark:text-amber-400">{{ __('Low Stock') }}</p>
            <p class="text-2xl font-semibold text-amber-800 dark:text-amber-300">{{ number_format($inventory_summary['low_stock_count']) }}</p>
        </div>
        <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
            <p class="text-sm text-red-700 dark:text-red-400">{{ __('Out of Stock') }}</p>
            <p class="text-2xl font-semibold text-red-800 dark:text-red-300">{{ number_format($inventory_summary['out_of_stock_count']) }}</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search products...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="category_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="stock_filter" class="w-full md:max-w-[160px]">
                <option value="">{{ __('All Stock') }}</option>
                <option value="in">{{ __('In Stock') }}</option>
                <option value="low">{{ __('Low Stock') }}</option>
                <option value="out">{{ __('Out of Stock') }}</option>
            </flux:select>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" href="{{ route('stock-adjustments.index') }}" wire:navigate icon="adjustments-horizontal">
                {{ __('Adjustments') }}
            </flux:button>
            <flux:button variant="primary" wire:click="openAddModal" icon="plus">
                {{ __('Add Product') }}
            </flux:button>
        </div>
    </div>

    {{-- Products Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Product') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Price') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Stock') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reorder Level') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($branch_products as $bp)
                    <tr wire:key="bp-{{ $bp->id }}" class="{{ $bp->is_low_stock ? 'bg-amber-50/50 dark:bg-amber-900/10' : '' }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $bp->product->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">{{ $bp->product->sku }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($bp->product->category)
                                <flux:badge color="zinc" size="sm">{{ $bp->product->category->name }}</flux:badge>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-zinc-900 dark:text-white">
                            {{ money($bp->price) }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            @if($bp->current_stock <= 0)
                                <flux:badge color="rose" size="sm">{{ $bp->current_stock }}</flux:badge>
                            @elseif($bp->is_low_stock)
                                <flux:badge color="amber" size="sm">{{ $bp->current_stock }}</flux:badge>
                            @else
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $bp->current_stock }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $bp->reorder_level ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="openEditModal({{ $bp->id }})" icon="pencil">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="removeProduct({{ $bp->id }})"
                                    wire:confirm="{{ __('Are you sure you want to remove this product from inventory?') }}"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700 dark:text-red-400"
                                >
                                    {{ __('Remove') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <flux:icon name="archive-box" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No products in inventory') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Add products from the catalog to your branch inventory.') }}</p>
                            <flux:button variant="primary" wire:click="openAddModal" class="mt-4" icon="plus">
                                {{ __('Add Product') }}
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($branch_products->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $branch_products->links() }}
            </div>
        @endif
    </div>

    {{-- Add Product Modal --}}
    <flux:modal wire:model="showAddModal" class="max-w-lg">
        <form wire:submit.prevent="addProduct">
            <flux:heading size="lg">{{ __('Add Product to Inventory') }}</flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Product') }}</flux:label>
                    <flux:select wire:model="selected_product_id" required>
                        <option value="">{{ __('Select a product') }}</option>
                        @foreach($available_products as $product)
                            <option value="{{ $product->id }}">{{ $product->name }} ({{ $product->sku }})</option>
                        @endforeach
                    </flux:select>
                    @error('selected_product_id')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Selling Price') }}</flux:label>
                    <flux:input wire:model="new_price" type="number" step="0.01" min="0" required />
                    @error('new_price')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Initial Stock') }}</flux:label>
                        <flux:input wire:model="new_stock" type="number" min="0" />
                        @error('new_stock')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Reorder Level') }}</flux:label>
                        <flux:input wire:model="new_reorder_level" type="number" min="0" placeholder="{{ __('Optional') }}" />
                        @error('new_reorder_level')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeAddModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Add Product') }}</span>
                    <span wire:loading>{{ __('Adding...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Edit Product Modal --}}
    <flux:modal wire:model="showEditModal" class="max-w-lg">
        <form wire:submit.prevent="updateProduct">
            <flux:heading size="lg">{{ __('Edit Product') }}</flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Selling Price') }}</flux:label>
                    <flux:input wire:model="edit_price" type="number" step="0.01" min="0" required />
                    @error('edit_price')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Reorder Level') }}</flux:label>
                    <flux:input wire:model="edit_reorder_level" type="number" min="0" placeholder="{{ __('Optional') }}" />
                    @error('edit_reorder_level')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeEditModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Update') }}</span>
                    <span wire:loading>{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

