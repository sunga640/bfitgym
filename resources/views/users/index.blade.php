<x-layouts.app :title="__('Users')" :description="__('Manage staff members and their access.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Users') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('users.create') }}" wire:navigate icon="plus">
            {{ __('Add User') }}
        </flux:button>
    </x-slot:actions>

    <livewire:users.index />
</x-layouts.app>
