<x-layouts.app :title="__('Create Role')" :description="__('Define a new role with specific permissions.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('roles.index') }}">{{ __('Roles') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:roles.form />
</x-layouts.app>

