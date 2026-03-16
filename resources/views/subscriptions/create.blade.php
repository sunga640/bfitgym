<x-layouts.app :title="__('Create Subscription')" :description="__('Start a new membership cycle for a member.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.index') }}">{{ __('Subscriptions') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:subscriptions.form />
</x-layouts.app>