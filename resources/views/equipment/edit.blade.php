<x-layouts.app.sidebar :title="__('Edit Equipment')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('equipment.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Edit Equipment') }}</flux:heading>
                <flux:subheading>{{ __('Update the equipment details.') }}</flux:subheading>
            </div>
        </div>

        <livewire:equipment.form :equipment="$equipment" />
    </flux:main>
</x-layouts.app.sidebar>

