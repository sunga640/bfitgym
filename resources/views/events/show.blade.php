<x-layouts.app :title="$event->title" :description="__('Event details and registrations.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('events.index') }}">{{ __('Events') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $event->title }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('update', $event)
            <flux:button variant="ghost" href="{{ route('events.edit', $event) }}" wire:navigate icon="pencil">
                {{ __('Edit Event') }}
            </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:events.show :event="$event" />
</x-layouts.app>

