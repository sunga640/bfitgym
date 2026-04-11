<x-layouts.app :title="__('Events')" :description="__('Create and manage events across your branch.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Events') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('create', \App\Models\Event::class)
            <flux:button variant="primary" href="{{ route('events.create') }}" wire:navigate icon="plus">
                {{ __('New Event') }}
            </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:events.index />
</x-layouts.app>
