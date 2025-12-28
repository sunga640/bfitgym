<x-layouts.app.sidebar :title="__('Add Equipment')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('equipment.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Add Equipment') }}</flux:heading>
                <flux:subheading>{{ __('Add a new equipment item to the catalog.') }}</flux:subheading>
            </div>
        </div>

        <livewire:equipment.form />
    </flux:main>
</x-layouts.app.sidebar>

