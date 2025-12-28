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
                placeholder="{{ __('Search adjustments...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="type_filter" class="w-full md:max-w-[160px]">
                <option value="">{{ __('All Types') }}</option>
                <option value="increase">{{ __('Increase') }}</option>
                <option value="decrease">{{ __('Decrease') }}</option>
            </flux:select>
        </div>
        <div class="flex items-center gap-3">
            <flux:button variant="ghost" href="{{ route('branch-products.index') }}" wire:navigate icon="arrow-left">
                {{ __('Back to Inventory') }}
            </flux:button>
            <flux:button variant="primary" wire:click="openAdjustModal" icon="plus">
                {{ __('New Adjustment') }}
            </flux:button>
        </div>
    </div>

    {{-- Adjustments Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Product') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Reason') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('By') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($adjustments as $adjustment)
                    <tr wire:key="adj-{{ $adjustment->id }}">
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $adjustment->created_at->format('M d, Y H:i') }}
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $adjustment->branchProduct->product->name }}</div>
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 font-mono">{{ $adjustment->branchProduct->product->sku }}</div>
                        </td>
                        <td class="px-6 py-4">
                            @if($adjustment->adjustment_type === 'increase')
                                <flux:badge color="emerald" size="sm">
                                    <flux:icon name="arrow-trending-up" class="h-3 w-3 mr-1" />
                                    {{ __('Increase') }}
                                </flux:badge>
                            @else
                                <flux:badge color="rose" size="sm">
                                    <flux:icon name="arrow-trending-down" class="h-3 w-3 mr-1" />
                                    {{ __('Decrease') }}
                                </flux:badge>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-medium {{ $adjustment->adjustment_type === 'increase' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ $adjustment->adjustment_type === 'increase' ? '+' : '-' }}{{ $adjustment->quantity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300 max-w-xs truncate">
                            {{ $adjustment->reason }}
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $adjustment->createdBy?->name ?? '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <flux:icon name="adjustments-horizontal" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No adjustments found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Stock adjustments will appear here.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($adjustments->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $adjustments->links() }}
            </div>
        @endif
    </div>

    {{-- Adjustment Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit.prevent="saveAdjustment">
            <flux:heading size="lg">{{ __('Stock Adjustment') }}</flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Product') }}</flux:label>
                    <flux:select wire:model="branch_product_id" required>
                        <option value="">{{ __('Select a product') }}</option>
                        @foreach($branch_products as $id => $label)
                            <option value="{{ $id }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    @error('branch_product_id')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Adjustment Type') }}</flux:label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model="adjustment_type" value="increase" class="text-emerald-600 focus:ring-emerald-500" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Increase') }}</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" wire:model="adjustment_type" value="decrease" class="text-rose-600 focus:ring-rose-500" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ __('Decrease') }}</span>
                        </label>
                    </div>
                    @error('adjustment_type')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Quantity') }}</flux:label>
                    <flux:input wire:model="quantity" type="number" min="1" required />
                    @error('quantity')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Reason') }}</flux:label>
                    <flux:textarea wire:model="reason" rows="2" required placeholder="{{ __('e.g., Damaged goods, Inventory count correction, etc.') }}" />
                    @error('reason')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeModal">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('Save Adjustment') }}</span>
                    <span wire:loading>{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

