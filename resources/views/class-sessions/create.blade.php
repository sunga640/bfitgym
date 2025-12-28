<x-layouts.app.sidebar :title="__('Schedule Class Session')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('class-sessions.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Schedule Class Session') }}</flux:heading>
                <flux:subheading>{{ __('Create a new class session schedule.') }}</flux:subheading>
            </div>
        </div>

        <livewire:class-sessions.form />
    </flux:main>
</x-layouts.app.sidebar>

