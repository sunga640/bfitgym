<div class="h-[calc(100vh-12rem)]">
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid h-full gap-6 lg:grid-cols-3">
        {{-- Products Section --}}
        <div class="lg:col-span-2 flex flex-col">
            {{-- Product Search & Filters --}}
            <div class="mb-4 flex flex-wrap gap-3 items-center">
                <flux:input
                    wire:model.live.debounce.300ms="product_search"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search products...') }}"
                    class="flex-1 min-w-[200px]"
                />
                <flux:select wire:model.live="category_filter" class="w-48">
                    <option value="">{{ __('All Categories') }}</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Products Grid --}}
            <div class="flex-1 overflow-y-auto rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                @if($products->isEmpty())
                    <div class="flex h-full items-center justify-center">
                        <div class="text-center">
                            <flux:icon name="cube" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No products available') }}</p>
                        </div>
                    </div>
                @else
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($products as $product)
                            <button
                                wire:click="addToCart({{ $product->id }})"
                                wire:key="prod-{{ $product->id }}"
                                class="group relative rounded-xl border border-zinc-200 bg-white p-4 text-left transition hover:border-emerald-500 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-emerald-500"
                            >
                                <div class="flex items-start justify-between">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-medium text-zinc-900 dark:text-white truncate">{{ $product->product->name }}</h3>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $product->product->category?->name ?? '-' }}</p>
                                    </div>
                                    <div class="text-right flex-shrink-0 ml-2">
                                        <p class="font-bold text-emerald-600 dark:text-emerald-400">{{ money($product->product->selling_price ?? $product->price) }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Stock: :qty', ['qty' => $product->current_stock]) }}</p>
                                    </div>
                                </div>
                                <div class="absolute inset-0 flex items-center justify-center rounded-xl bg-emerald-500/90 opacity-0 transition group-hover:opacity-100">
                                    <flux:icon name="plus-circle" class="h-10 w-10 text-white" />
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Cart Section --}}
        <div class="flex flex-col rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            {{-- Cart Header --}}
            <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Cart') }}</h2>
                    @if(count($cart) > 0)
                        <flux:button variant="ghost" size="sm" wire:click="clearCart" icon="trash">
                            {{ __('Clear') }}
                        </flux:button>
                    @endif
                </div>

                {{-- Customer Selection --}}
                <div class="mt-3">
                    @if($selected_member)
                        <div class="flex items-center justify-between rounded-lg bg-emerald-50 p-2 dark:bg-emerald-900/20">
                            <div class="flex items-center gap-2">
                                <flux:icon name="user-circle" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                                <div>
                                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ $selected_member->full_name }}</p>
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $selected_member->phone }}</p>
                                </div>
                            </div>
                            <button wire:click="clearMember" class="text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                                <flux:icon name="x-mark" class="h-4 w-4" />
                            </button>
                        </div>
                    @else
                        <div class="relative">
                            <flux:input
                                wire:model.live.debounce.300ms="member_search"
                                type="text"
                                icon="user"
                                placeholder="{{ __('Search member (optional)...') }}"
                                class="w-full"
                            />
                            @if(strlen($member_search) >= 2 && $searched_members->isNotEmpty())
                                <div class="absolute top-full left-0 right-0 z-10 mt-1 max-h-48 overflow-y-auto rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800">
                                    @foreach($searched_members as $member)
                                        <button
                                            wire:click="selectMember({{ $member->id }})"
                                            class="flex w-full items-center gap-3 px-4 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-700"
                                        >
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $member->full_name }}</p>
                                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $member->member_no }} • {{ $member->phone }}</p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Cart Items --}}
            <div class="flex-1 overflow-y-auto p-4">
                @if(count($cart) === 0)
                    <div class="flex h-full items-center justify-center">
                        <div class="text-center">
                            <flux:icon name="shopping-cart" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Cart is empty') }}</p>
                            <p class="text-xs text-zinc-400 dark:text-zinc-500">{{ __('Click on products to add them') }}</p>
                        </div>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($cart as $index => $item)
                            <div wire:key="cart-{{ $index }}" class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-zinc-900 dark:text-white truncate">{{ $item['product_name'] }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ money($item['unit_price']) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="updateCartQuantity({{ $index }}, {{ $item['quantity'] - 1 }})"
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300"
                                    >
                                        <flux:icon name="minus" class="h-4 w-4" />
                                    </button>
                                    <span class="w-8 text-center font-medium text-zinc-900 dark:text-white">{{ $item['quantity'] }}</span>
                                    <button
                                        wire:click="updateCartQuantity({{ $index }}, {{ $item['quantity'] + 1 }})"
                                        class="flex h-7 w-7 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300"
                                        {{ $item['quantity'] >= $item['max_stock'] ? 'disabled' : '' }}
                                    >
                                        <flux:icon name="plus" class="h-4 w-4" />
                                    </button>
                                </div>
                                <div class="w-24 text-right">
                                    <p class="font-semibold text-zinc-900 dark:text-white">{{ money($item['total']) }}</p>
                                </div>
                                <button
                                    wire:click="removeFromCart({{ $index }})"
                                    class="text-zinc-400 hover:text-red-500"
                                >
                                    <flux:icon name="x-mark" class="h-5 w-5" />
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Cart Footer --}}
            <div class="border-t border-zinc-200 p-4 dark:border-zinc-700">
                {{-- Discount --}}
                <div class="mb-3 flex items-center justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Discount') }}</span>
                    <div class="flex items-center gap-2">
                        <span class="text-zinc-600 dark:text-zinc-300">{{ app_currency() }}</span>
                        <input
                            type="number"
                            wire:model.live="discount_amount"
                            min="0"
                            step="0.01"
                            class="w-24 rounded border-zinc-300 bg-transparent text-right text-sm dark:border-zinc-600"
                            placeholder="0.00"
                        />
                    </div>
                </div>

                {{-- Totals --}}
                <div class="space-y-1 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Subtotal') }}</span>
                        <span class="text-zinc-600 dark:text-zinc-300">{{ money($this->subtotal) }}</span>
                    </div>
                    @if($discount_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Discount') }}</span>
                            <span class="text-red-600 dark:text-red-400">-{{ money($discount_amount) }}</span>
                        </div>
                    @endif
                    @if($this->taxAmount > 0)
                        <div class="flex justify-between text-sm">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Tax') }}</span>
                            <span class="text-zinc-600 dark:text-zinc-300">{{ money($this->taxAmount) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <span class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Total') }}</span>
                        <span class="text-lg font-bold text-emerald-600 dark:text-emerald-400">{{ money($this->total) }}</span>
                    </div>
                </div>

                {{-- Checkout Button --}}
                <flux:button
                    variant="primary"
                    wire:click="openCheckout"
                    class="mt-4 w-full py-3 text-base"
                    :disabled="count($cart) === 0"
                    icon="credit-card"
                >
                    {{ __('Checkout') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Checkout Modal --}}
    <flux:modal wire:model="showCheckout" class="max-w-md">
        <form wire:submit.prevent="completeSale">
            <flux:heading size="lg">{{ __('Complete Sale') }}</flux:heading>

            <div class="mt-6 space-y-4">
                {{-- Total Summary --}}
                <div class="rounded-lg bg-emerald-50 p-4 dark:bg-emerald-900/20">
                    <div class="flex items-center justify-between">
                        <span class="text-emerald-700 dark:text-emerald-300">{{ __('Total Amount') }}</span>
                        <span class="text-2xl font-bold text-emerald-800 dark:text-emerald-200">{{ money($this->total) }}</span>
                    </div>
                </div>

                {{-- Payment Method --}}
                <flux:field>
                    <flux:label>{{ __('Payment Method') }}</flux:label>
                    <flux:select wire:model.live="payment_method" required>
                        @foreach($payment_methods as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                </flux:field>

                {{-- Amount Received (for cash) --}}
                @if($payment_method === 'cash')
                    <flux:field>
                        <flux:label>{{ __('Amount Received') }}</flux:label>
                        <flux:input wire:model.live="amount_received" type="number" step="0.01" min="0" required />
                        @if((float)$amount_received >= $this->total)
                            <p class="mt-1 text-sm text-emerald-600 dark:text-emerald-400">
                                {{ __('Change: :amount', ['amount' => money($this->changeAmount)]) }}
                            </p>
                        @endif
                    </flux:field>
                @endif

                {{-- Reference --}}
                @if(in_array($payment_method, ['card', 'mobile_money', 'bank_transfer', 'mpesa', 'tigopesa', 'airtel_money']))
                    <flux:field>
                        <flux:label>{{ __('Reference / Transaction ID') }}</flux:label>
                        <flux:input wire:model="payment_reference" />
                    </flux:field>
                @endif

                {{-- Notes --}}
                <flux:field>
                    <flux:label>{{ __('Notes (Optional)') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="2" />
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeCheckout">{{ __('Cancel') }}</flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled" icon="check">
                    <span wire:loading.remove>{{ __('Complete Sale') }}</span>
                    <span wire:loading>{{ __('Processing...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Success Modal --}}
    <flux:modal wire:model="showSuccess" class="max-w-md">
        <div class="text-center">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                <flux:icon name="check-circle" class="h-10 w-10 text-emerald-600 dark:text-emerald-400" />
            </div>

            <h3 class="mt-4 text-xl font-semibold text-zinc-900 dark:text-white">{{ __('Sale Complete!') }}</h3>

            @if($completed_sale)
                <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Invoice #:number', ['number' => $completed_sale->sale_number]) }}
                </p>

                <div class="mt-4 rounded-lg bg-zinc-50 p-4 text-left dark:bg-zinc-800">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Items') }}</span>
                        <span class="text-zinc-900 dark:text-white">{{ $completed_sale->items->sum('quantity') }}</span>
                    </div>
                    <div class="flex justify-between text-sm mt-1">
                        <span class="text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</span>
                        <span class="font-semibold text-zinc-900 dark:text-white">{{ money($completed_sale->total_amount) }}</span>
                    </div>
                    @if($completed_sale->member)
                        <div class="flex justify-between text-sm mt-1">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Customer') }}</span>
                            <span class="text-zinc-900 dark:text-white">{{ $completed_sale->member->full_name }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <div class="mt-6 flex justify-center gap-3">
                <flux:button variant="ghost" wire:click="closeSuccess">{{ __('New Sale') }}</flux:button>
                <flux:button variant="primary" wire:click="printReceipt" icon="printer">{{ __('Print Receipt') }}</flux:button>
            </div>
        </div>
    </flux:modal>
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

