<x-layouts.app :title="__('Staff Details')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('users.index') }}">{{ __('Staff') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('View Staff') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>
    <livewire:users.show :user="$user" />
</x-layouts.app>

