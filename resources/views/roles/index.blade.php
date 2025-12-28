<x-layouts.app :title="__('Roles & Permissions')" :description="__('Manage roles and their permissions.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Roles') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('roles.create') }}" wire:navigate icon="plus">
            {{ __('Create Role') }}
        </flux:button>
    </x-slot:actions>

    <livewire:roles.index />
</x-layouts.app>
