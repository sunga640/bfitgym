<x-layouts.app.sidebar :title="__('Equipment')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Equipment') }}</flux:heading>
                <flux:subheading>{{ __('Manage the gym equipment catalog.') }}</flux:subheading>
            </div>
            @can('create', App\Models\Equipment::class)
            <flux:button variant="primary" href="{{ route('equipment.create') }}" wire:navigate icon="plus">
                {{ __('Add Equipment') }}
            </flux:button>
            @endcan
        </div>

        <livewire:equipment.index />
    </flux:main>
</x-layouts.app.sidebar>
