@php
    $subscription->loadMissing('member', 'membershipPackage');
@endphp

<x-layouts.app :title="$subscription->member->full_name" :description="__('Subscription overview for :member', ['member' => $subscription->member->full_name])">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.index') }}">{{ __('Subscriptions') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $subscription->member->full_name }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    @if($subscription->end_date->isPast() || $subscription->end_date->isToday())
        <x-slot:actions>
            <flux:button variant="ghost" href="{{ route('subscriptions.renew', $subscription) }}" wire:navigate icon="arrow-path">
                {{ __('Renew') }}
            </flux:button>
        </x-slot:actions>
    @endif

    <livewire:subscriptions.show :subscription="$subscription" />
</x-layouts.app>

