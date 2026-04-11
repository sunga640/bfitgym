<x-layouts.app :title="__('Create Registration')" :description="__('Register a member or visitor for an event.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('event-registrations.index') }}">{{ __('Event Registrations') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:event-registrations.form />
</x-layouts.app>

