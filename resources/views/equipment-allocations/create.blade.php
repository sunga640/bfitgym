<x-layouts.app.sidebar :title="__('Add Equipment Allocation')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('equipment-allocations.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Add Equipment Allocation') }}</flux:heading>
                <flux:subheading>{{ __('Assign equipment to a location.') }}</flux:subheading>
            </div>
        </div>

        <livewire:equipment-allocations.form />
    </flux:main>
</x-layouts.app.sidebar>

