<x-layouts.app.sidebar :title="__('Create Workout Plan')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('workout-plans.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Create Workout Plan') }}</flux:heading>
                <flux:subheading>{{ __('Add a new workout plan template to your gym.') }}</flux:subheading>
            </div>
        </div>

        <livewire:workout-plans.form />
    </flux:main>
</x-layouts.app.sidebar>

