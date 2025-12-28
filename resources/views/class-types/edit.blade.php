<x-layouts.app.sidebar :title="__('Edit Class Type')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('class-types.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Edit Class Type') }}</flux:heading>
                <flux:subheading>{{ __('Update the class type details.') }}</flux:subheading>
            </div>
        </div>

        <livewire:class-types.form :classType="$classType" />
    </flux:main>
</x-layouts.app.sidebar>

