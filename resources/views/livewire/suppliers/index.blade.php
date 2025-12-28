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

    {{-- Header --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search suppliers...') }}"
                class="w-full md:max-w-xs"
            />
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count supplier|:count suppliers', $suppliers->total()) }}
            </span>
            <flux:button variant="primary" wire:click="openCreateModal" icon="plus">
                {{ __('Add Supplier') }}
            </flux:button>
        </div>
    </div>

    {{-- Suppliers Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Supplier') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact Person') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Orders') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($suppliers as $supplier)
                    <tr wire:key="supplier-{{ $supplier->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $supplier->name }}</div>
                            @if($supplier->address)
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $supplier->address }}</p>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            {{ $supplier->contact_person ?? '-' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($supplier->phone)
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $supplier->phone }}</div>
                            @endif
                            @if($supplier->email)
                                <div class="text-xs text-zinc-400">{{ $supplier->email }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ $supplier->purchase_orders_count }}</span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button variant="ghost" size="sm" wire:click="openEditModal({{ $supplier->id }})" icon="pencil">
                                    {{ __('Edit') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="delete({{ $supplier->id }})"
                                    wire:confirm="{{ __('Are you sure you want to delete this supplier?') }}"
                                    icon="trash"
                                    class="text-red-600 hover:text-red-700 dark:text-red-400"
                                >
                                    {{ __('Delete') }}
                                </flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <flux:icon name="building-storefront" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No suppliers found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first supplier.') }}</p>
                            <flux:button variant="primary" wire:click="openCreateModal" class="mt-4" icon="plus">
                                {{ __('Add Supplier') }}
                            </flux:button>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($suppliers->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit.prevent="save">
            <flux:heading size="lg">
                {{ $editing_id ? __('Edit Supplier') : __('Add Supplier') }}
            </flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Supplier Name') }}</flux:label>
                    <flux:input wire:model="name" required autofocus />
                    @error('name')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Contact Person') }}</flux:label>
                    <flux:input wire:model="contact_person" />
                    @error('contact_person')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('Phone') }}</flux:label>
                        <flux:input wire:model="phone" type="tel" placeholder="+255" />
                        @error('phone')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:input wire:model="email" type="email" />
                        @error('email')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>{{ __('Address') }}</flux:label>
                    <flux:textarea wire:model="address" rows="2" />
                    @error('address')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="closeModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editing_id ? __('Update') : __('Create') }}</span>
                    <span wire:loading>{{ __('Saving...') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>
</div>

