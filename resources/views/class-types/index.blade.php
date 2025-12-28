<x-layouts.app.sidebar :title="__('Class Types')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Class Types') }}</flux:heading>
                <flux:subheading>{{ __('Manage the types of classes offered at your gym.') }}</flux:subheading>
            </div>
            @can('create', App\Models\ClassType::class)
            <flux:button variant="primary" href="{{ route('class-types.create') }}" wire:navigate icon="plus">
                {{ __('Add Class Type') }}
            </flux:button>
            @endcan
        </div>

        <livewire:class-types.index />
    </flux:main>
</x-layouts.app.sidebar>
