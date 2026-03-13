@php
    $subscription->loadMissing('member');
@endphp

<x-layouts.app :title="__('Edit Subscription')" :description="__('Update the membership cycle for :member', ['member' => $subscription->member->full_name])">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.index') }}">{{ __('Subscriptions') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.show', $subscription) }}">{{ $subscription->member->full_name }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:subscriptions.form :subscription="$subscription" />
</x-layouts.app>
