<x-layouts.app.sidebar :title="__('Class Sessions')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Class Sessions') }}</flux:heading>
                <flux:subheading>{{ __('Schedule and manage class sessions.') }}</flux:subheading>
            </div>
            @can('create', App\Models\ClassSession::class)
            <flux:button variant="primary" href="{{ route('class-sessions.create') }}" wire:navigate icon="plus">
                {{ __('Schedule Session') }}
            </flux:button>
            @endcan
        </div>

        <livewire:class-sessions.index />
    </flux:main>
</x-layouts.app.sidebar>
