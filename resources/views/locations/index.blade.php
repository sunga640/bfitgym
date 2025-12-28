<x-layouts.app.sidebar :title="__('Locations')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Locations') }}</flux:heading>
                <flux:subheading>{{ __('Manage gym locations and areas within your branch.') }}</flux:subheading>
            </div>
            @can('create', App\Models\Location::class)
            <flux:button variant="primary" href="{{ route('locations.create') }}" wire:navigate icon="plus">
                {{ __('Add Location') }}
            </flux:button>
            @endcan
        </div>

        <livewire:locations.index />
    </flux:main>
</x-layouts.app.sidebar>
