<x-layouts.app :title="__('Subscriptions')" :description="__('Manage member memberships, billing, and renewals.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Subscriptions') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('subscriptions.create') }}" wire:navigate icon="plus">
            {{ __('New Subscription') }}
        </flux:button>
    </x-slot:actions>

    <livewire:subscriptions.index />
</x-layouts.app>

