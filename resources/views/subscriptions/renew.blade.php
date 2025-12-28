<x-layouts.app :title="__('Renew Subscription')" :description="__('Extend the membership for :member', ['member' => $subscription->member->full_name])">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.index') }}">{{ __('Subscriptions') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.show', $subscription) }}">{{ $subscription->member->full_name }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Renew') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:subscriptions.form :subscription="$subscription" :is-renewal="true" />
</x-layouts.app>

