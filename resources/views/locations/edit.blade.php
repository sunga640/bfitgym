<x-layouts.app.sidebar :title="__('Edit Location')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('locations.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Edit Location') }}</flux:heading>
                <flux:subheading>{{ __('Update the location details.') }}</flux:subheading>
            </div>
        </div>

        <livewire:locations.form :location="$location" />
    </flux:main>
</x-layouts.app.sidebar>

