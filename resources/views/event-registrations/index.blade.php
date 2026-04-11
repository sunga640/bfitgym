<x-layouts.app :title="__('Event Registrations')" :description="__('Manage attendee registrations across events.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Event Registrations') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('create', \App\Models\EventRegistration::class)
            <flux:button variant="primary" href="{{ route('event-registrations.create') }}" wire:navigate icon="plus">
                {{ __('Add Registration') }}
            </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:event-registrations.index />
</x-layouts.app>
