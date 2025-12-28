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

    {{-- Equipment Usage Summary by Location --}}
    @if($location_summary->count() > 0)
        <div class="mb-6">
            <h3 class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Equipment by Location') }}</h3>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach($location_summary as $summary)
                    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                                <flux:icon name="map-pin" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $summary->location?->name }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ trans_choice(':count item|:count items', $summary->total_items) }}
                                    ({{ $summary->total_quantity }} {{ __('total qty') }})
                                </p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center md:flex-wrap">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search equipment or asset tag...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="location_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Locations') }}</option>
                @foreach($locations as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="equipment_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Equipment') }}</option>
                @foreach($equipment_list as $equipment)
                    <option value="{{ $equipment->id }}">{{ $equipment->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count allocation|:count allocations', $allocations->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Equipment') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Asset Tag') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Quantity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($allocations as $allocation)
                <tr wire:key="allocation-{{ $allocation->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $allocation->equipment?->name }}</div>
                        @if($allocation->equipment?->brand || $allocation->equipment?->type)
                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @if($allocation->equipment->brand)
                                    {{ $allocation->equipment->brand }}
                                @endif
                                @if($allocation->equipment->type)
                                    · {{ $allocation->equipment->type }}
                                @endif
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $allocation->location?->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        @if($allocation->asset_tag)
                            <code class="rounded bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ $allocation->asset_tag }}
                            </code>
                        @else
                            <span class="text-zinc-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $allocation->quantity }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <button
                            wire:click="toggleStatus({{ $allocation->id }})"
                            class="focus:outline-none"
                            title="{{ $allocation->is_active ? __('Click to deactivate') : __('Click to activate') }}"
                        >
                            <flux:badge :color="$allocation->is_active ? 'emerald' : 'zinc'" size="sm">
                                {{ $allocation->is_active ? __('Active') : __('Inactive') }}
                            </flux:badge>
                        </button>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $allocation)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('equipment-allocations.edit', $allocation) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('delete', $allocation)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteAllocation({{ $allocation->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this allocation?') }}"
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
                            <flux:icon name="squares-plus" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No equipment allocations found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Start by allocating equipment to locations.') }}</p>
                            @can('create', App\Models\EquipmentAllocation::class)
                            <flux:button variant="primary" href="{{ route('equipment-allocations.create') }}" wire:navigate class="mt-4">
                                {{ __('Add Allocation') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($allocations->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $allocations->links() }}
            </div>
        @endif
    </div>
</div>

