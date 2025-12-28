<x-layouts.app :title="__('Create User')" :description="__('Add a new staff member.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('Users') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:users.form />
</x-layouts.app>

