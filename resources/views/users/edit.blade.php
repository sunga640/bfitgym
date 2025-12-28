@php
    $user = \App\Models\User::findOrFail(request()->route('user'));
@endphp

<x-layouts.app :title="__('Edit User')" :description="__('Update user information and roles.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('User Management') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('Users') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:users.form :user="$user" />
</x-layouts.app>

