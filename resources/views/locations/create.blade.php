<x-layouts.app.sidebar :title="__('Create Location')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('locations.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Create Location') }}</flux:heading>
                <flux:subheading>{{ __('Add a new location or area to your gym.') }}</flux:subheading>
            </div>
        </div>

        <livewire:locations.form />
    </flux:main>
</x-layouts.app.sidebar>

