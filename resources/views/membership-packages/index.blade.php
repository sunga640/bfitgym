<x-layouts.app :title="__('Membership Packages')" :description="__('Manage membership packages and pricing for your gym.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Membership Packages') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('membership-packages.create') }}" wire:navigate icon="plus">
            {{ __('Create Package') }}
        </flux:button>
    </x-slot:actions>

    <livewire:membership-packages.index />
</x-layouts.app>
