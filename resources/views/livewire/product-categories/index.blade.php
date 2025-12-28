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

    {{-- Header Actions --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end lg:justify-between">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search categories...') }}"
                class="w-full md:max-w-xs"
            />
        </div>
        <div class="flex items-center gap-3">
            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count category|:count categories', $categories->total()) }}
            </span>
            <flux:button variant="primary" wire:click="openCreateModal" icon="plus">
                {{ __('Add Category') }}
            </flux:button>
        </div>
    </div>

    {{-- Categories Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($categories as $category)
            <div wire:key="category-{{ $category->id }}" class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">{{ $category->name }}</h3>
                        @if($category->description)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $category->description }}</p>
                        @endif
                    </div>
                    <flux:dropdown>
                        <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />
                        <flux:menu>
                            <flux:menu.item wire:click="openEditModal({{ $category->id }})" icon="pencil">
                                {{ __('Edit') }}
                            </flux:menu.item>
                            <flux:menu.item
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this category?') }}"
                                icon="trash"
                                variant="danger"
                            >
                                {{ __('Delete') }}
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                </div>
                <div class="mt-4 flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                    <flux:icon name="cube" class="h-4 w-4" />
                    <span>{{ trans_choice(':count product|:count products', $category->products_count) }}</span>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-zinc-200 bg-white p-16 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon name="tag" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No categories found') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by creating your first category.') }}</p>
                <flux:button variant="primary" wire:click="openCreateModal" class="mt-4" icon="plus">
                    {{ __('Add Category') }}
                </flux:button>
            </div>
        @endforelse
    </div>

    @if($categories->hasPages())
        <div class="mt-6">
            {{ $categories->links() }}
        </div>
    @endif

    {{-- Create/Edit Modal --}}
    <flux:modal wire:model="showModal" class="max-w-lg">
        <form wire:submit.prevent="save">
            <flux:heading size="lg">
                {{ $editing_id ? __('Edit Category') : __('Add Category') }}
            </flux:heading>

            <div class="mt-6 space-y-4">
                <flux:field>
                    <flux:label>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" required autofocus />
                    @error('name')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Description') }}</flux:label>
                    <flux:textarea wire:model="description" rows="3" />
                    @error('description')
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

