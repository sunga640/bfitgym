<x-layouts.app.sidebar :title="__('Equipment Allocations')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Equipment Allocations') }}</flux:heading>
                <flux:subheading>{{ __('Track equipment assignments to locations.') }}</flux:subheading>
            </div>
            @can('create', App\Models\EquipmentAllocation::class)
            <flux:button variant="primary" href="{{ route('equipment-allocations.create') }}" wire:navigate icon="plus">
                {{ __('Add Allocation') }}
            </flux:button>
            @endcan
        </div>

        <livewire:equipment-allocations.index />
    </flux:main>
</x-layouts.app.sidebar>
