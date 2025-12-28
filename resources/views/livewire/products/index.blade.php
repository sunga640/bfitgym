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
                placeholder="{{ __('Search products...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="category_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Categories') }}</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[140px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </flux:select>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count product|:count products', $products->total()) }}
            </span>
            <flux:button variant="primary" href="{{ route('products.create') }}" wire:navigate icon="plus">
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
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Buying') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Selling') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($products as $product)
                    <tr wire:key="product-{{ $product->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</div>
                            @if($product->description)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $product->description }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono text-sm text-zinc-600 dark:text-zinc-300">{{ $product->sku }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($product->category)
                                <flux:badge color="zinc" size="sm">{{ $product->category->name }}</flux:badge>
                            @else
                                <span class="text-sm text-zinc-400">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ $product->buying_price ? money($product->buying_price) : '-' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-sm font-medium text-emerald-600 dark:text-emerald-400">{{ $product->selling_price ? money($product->selling_price) : '-' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <button
                                wire:click="toggleStatus({{ $product->id }})"
                                class="inline-flex items-center gap-1.5"
                            >
                                @if($product->is_active)
                                    <flux:badge color="emerald" size="sm">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('products.show', $product) }}"
                                    wire:navigate
                                    icon="eye"
                                    title="{{ __('View') }}"
                                />
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('products.edit', $product) }}"
                                    wire:navigate
                                    icon="pencil"
                                    title="{{ __('Edit') }}"
                                />
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="delete({{ $product->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this product?') }}"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    title="{{ __('Delete') }}"
                                />
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center">
                            <flux:icon name="cube" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No products found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first product.') }}</p>
                            <flux:button variant="primary" href="{{ route('products.create') }}" wire:navigate class="mt-4" icon="plus">
                                {{ __('Add Product') }}
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($products->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

