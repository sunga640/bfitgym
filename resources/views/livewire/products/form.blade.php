<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-2xl space-y-8">
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

        {{-- Product Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Product Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the product details.') }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Product Name') }}</flux:label>
                        <flux:input type="text" wire:model.live="name" required />
                        @error('name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('SKU') }}</flux:label>
                        <div class="flex gap-2">
                            <flux:input type="text" wire:model.live="sku" required class="flex-1" />
                            <flux:button type="button" wire:click="generateSku" variant="ghost" size="sm">
                                {{ __('Generate') }}
                            </flux:button>
                        </div>
                        @error('sku')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Category') }}</flux:label>
                        <flux:select wire:model.live="product_category_id">
                            <option value="">{{ __('Select category') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('product_category_id')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model.live="description" rows="3" />
                        @error('description')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Buying Price') }}</flux:label>
                        <flux:input type="number" wire:model.live="buying_price" step="0.01" min="0" placeholder="0.00" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Default cost price for purchases') }}</p>
                        @error('buying_price')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Selling Price') }}</flux:label>
                        <flux:input type="number" wire:model.live="selling_price" step="0.01" min="0" placeholder="0.00" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Default price for POS sales') }}</p>
                        @error('selling_price')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Active') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active products can be added to branch inventory and sold.') }}</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center">
                            <input type="checkbox" class="peer sr-only" wire:model.live="is_active">
                            <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-300 dark:bg-zinc-600"></div>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('products.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $isEditing ? __('Update Product') : __('Create Product') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

