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
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search equipment...') }}"
                class="w-full md:max-w-xs"
            />
            @if($types->isNotEmpty())
                <flux:select wire:model.live="type_filter" class="w-full md:max-w-[180px]">
                    <option value="">{{ __('All Types') }}</option>
                    @foreach($types as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </flux:select>
            @endif
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count equipment item|:count equipment items', $equipment->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Equipment') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Brand') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Model') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Allocations') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($equipment as $item)
                <tr wire:key="equipment-{{ $item->id }}">
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                                <flux:icon name="wrench-screwdriver" class="h-5 w-5 text-zinc-500 dark:text-zinc-400" />
                            </div>
                            <div>
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $item->name }}</div>
                                @if($item->description)
                                    <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $item->description }}</div>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($item->type)
                            <flux:badge color="zinc" size="sm">{{ $item->type }}</flux:badge>
                        @else
                            <span class="text-sm text-zinc-400 dark:text-zinc-500">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $item->brand ?? '—' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $item->model ?? '—' }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                            <flux:icon name="squares-plus" class="h-4 w-4 text-zinc-400" />
                            {{ $item->allocations_count }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $item)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('equipment.edit', $item) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('delete', $item)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteEquipment({{ $item->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this equipment? This action cannot be undone.') }}"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="wrench-screwdriver" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No equipment found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first equipment item.') }}</p>
                            @can('create', App\Models\Equipment::class)
                            <flux:button variant="primary" href="{{ route('equipment.create') }}" wire:navigate class="mt-4">
                                {{ __('Add Equipment') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($equipment->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $equipment->links() }}
            </div>
        @endif
    </div>
</div>

