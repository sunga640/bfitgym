<x-layouts.app.sidebar :title="__('Edit Workout Plan')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('workout-plans.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Edit Workout Plan') }}</flux:heading>
                <flux:subheading>{{ __('Update the workout plan details.') }}</flux:subheading>
            </div>
        </div>

        <livewire:workout-plans.form :workoutPlan="$workoutPlan" />
    </flux:main>
</x-layouts.app.sidebar>

