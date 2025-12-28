<x-layouts.app.sidebar :title="__('Workout Plans')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Workout Plans') }}</flux:heading>
                <flux:subheading>{{ __('Create and manage workout plan templates for your members.') }}</flux:subheading>
            </div>
            @can('create', App\Models\WorkoutPlan::class)
            <flux:button variant="primary" href="{{ route('workout-plans.create') }}" wire:navigate icon="plus">
                {{ __('Add Workout Plan') }}
            </flux:button>
            @endcan
        </div>

        <livewire:workout-plans.index />
    </flux:main>
</x-layouts.app.sidebar>
