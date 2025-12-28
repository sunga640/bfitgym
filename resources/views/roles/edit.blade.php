@php
    $role = \Spatie\Permission\Models\Role::findOrFail(request()->route('role'));
@endphp

<x-layouts.app :title="__('Edit Role')" :description="__('Update role permissions.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('roles.index') }}">{{ __('Roles') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:roles.form :role="$role" />
</x-layouts.app>

