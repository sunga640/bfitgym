<x-layouts.app.sidebar :title="__('Create Class Type')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('class-types.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Create Class Type') }}</flux:heading>
                <flux:subheading>{{ __('Add a new class type to your gym offerings.') }}</flux:subheading>
            </div>
        </div>

        <livewire:class-types.form />
    </flux:main>
</x-layouts.app.sidebar>

