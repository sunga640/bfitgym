<x-layouts.app :title="__('Edit Event')" :description="__('Update event details, schedule, and payment settings.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('events.index') }}">{{ __('Events') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $event->title }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:events.form :event="$event" />
</x-layouts.app>

