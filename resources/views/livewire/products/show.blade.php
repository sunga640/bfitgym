<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <flux:button href="{{ route('products.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $product->name }}</h1>
                <p class="text-sm font-mono text-zinc-500 dark:text-zinc-400">{{ $product->sku }}</p>
            </div>
        </div>
        <div class="flex gap-2">
            <flux:button href="{{ route('products.edit', $product) }}" wire:navigate variant="ghost" icon="pencil">
                {{ __('Edit') }}
            </flux:button>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Product Details --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4">
                    {{ __('Product Details') }}
                </h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Product Name') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $product->name }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('SKU') }}</p>
                        <p class="font-mono font-medium text-zinc-900 dark:text-white">{{ $product->sku }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Category') }}</p>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ $product->category?->name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                        @if($product->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                {{ __('Active') }}
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ __('Inactive') }}
                            </span>
                        @endif
                    </div>
                    @if($product->description)
                        <div class="sm:col-span-2">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Description') }}</p>
                            <p class="text-zinc-900 dark:text-white">{{ $product->description }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Branch Stock --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Stock by Branch') }}</h2>
                </div>

                @if($branch_products->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Branch Price') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Current Stock') }}</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reorder Level') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                @foreach($branch_products as $bp)
                                    <tr>
                                        <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ $bp->branch?->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ money($bp->price) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ number_format($bp->current_stock) }}
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ $bp->reorder_level ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            @if($bp->current_stock <= 0)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                                    {{ __('Out of Stock') }}
                                                </span>
                                            @elseif($bp->is_low_stock)
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800 dark:bg-amber-900/30 dark:text-amber-400">
                                                    {{ __('Low Stock') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800 dark:bg-green-900/30 dark:text-green-400">
                                                    {{ __('In Stock') }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-zinc-50 dark:bg-zinc-900/50">
                                <tr>
                                    <td class="px-6 py-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Total') }}</td>
                                    <td class="px-6 py-4"></td>
                                    <td class="px-6 py-4 text-right text-sm font-bold text-zinc-900 dark:text-white">
                                        {{ number_format($branch_products->sum('current_stock')) }}
                                    </td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @else
                    <div class="p-12 text-center">
                        <flux:icon name="building-storefront" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-4 text-zinc-500 dark:text-zinc-400">{{ __('This product is not assigned to any branch yet.') }}</p>
                        <flux:button href="{{ route('branch-products.index') }}" wire:navigate variant="primary" class="mt-4" icon="plus">
                            {{ __('Assign to Branch') }}
                        </flux:button>
                    </div>
                @endif
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Pricing Card --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4">
                    {{ __('Pricing') }}
                </h2>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Buying Price') }}</span>
                        <span class="text-lg font-bold text-zinc-900 dark:text-white">
                            {{ $product->buying_price ? money($product->buying_price) : '-' }}
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Selling Price') }}</span>
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                            {{ $product->selling_price ? money($product->selling_price) : '-' }}
                        </span>
                    </div>

                    @if($profit_amount !== null)
                        <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Profit Amount') }}</span>
                                <span @class([
                                    'font-semibold',
                                    'text-emerald-600 dark:text-emerald-400' => $profit_amount >= 0,
                                    'text-red-600 dark:text-red-400' => $profit_amount < 0,
                                ])>
                                    {{ money($profit_amount) }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Profit Margin') }}</span>
                                <span @class([
                                    'font-semibold',
                                    'text-emerald-600 dark:text-emerald-400' => $profit_margin >= 0,
                                    'text-red-600 dark:text-red-400' => $profit_margin < 0,
                                ])>
                                    {{ number_format($profit_margin, 1) }}%
                                </span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Quick Stats --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4">
                    {{ __('Quick Stats') }}
                </h2>

                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Stock (All Branches)') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ number_format($branch_products->sum('current_stock')) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Branches with Stock') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ $branch_products->where('current_stock', '>', 0)->count() }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Low Stock Alerts') }}</span>
                        <span @class([
                            'font-semibold',
                            'text-zinc-900 dark:text-white' => $branch_products->filter(fn($bp) => $bp->is_low_stock)->count() === 0,
                            'text-amber-600 dark:text-amber-400' => $branch_products->filter(fn($bp) => $bp->is_low_stock)->count() > 0,
                        ])>
                            {{ $branch_products->filter(fn($bp) => $bp->is_low_stock)->count() }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- Meta Info --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white border-b border-zinc-200 dark:border-zinc-700 pb-4 mb-4">
                    {{ __('Information') }}
                </h2>

                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</span>
                        <span class="text-zinc-900 dark:text-white">{{ $product->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Last Updated') }}</span>
                        <span class="text-zinc-900 dark:text-white">{{ $product->updated_at->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

