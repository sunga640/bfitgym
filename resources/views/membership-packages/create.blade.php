<x-layouts.app :title="__('Create Membership Package')" :description="__('Add a new membership package to your gym.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('membership-packages.index') }}">{{ __('Membership Packages') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:membership-packages.form />
</x-layouts.app>

