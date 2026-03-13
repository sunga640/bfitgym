@php
    $subscription->loadMissing('member', 'membershipPackage');
@endphp

<x-layouts.app :title="$subscription->member->full_name" :description="__('Subscription overview for :member', ['member' => $subscription->member->full_name])">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('subscriptions.index') }}">{{ __('Subscriptions') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $subscription->member->full_name }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    @php
        $can_edit = auth()->user()?->can('update', $subscription) ?? false;
        $can_renew = ($subscription->end_date->isPast() || $subscription->end_date->isToday())
            && (auth()->user()?->can('renew', $subscription) ?? false);
    @endphp

    @if($can_edit || $can_renew)
        <x-slot:actions>
            @if($can_edit)
                <flux:button variant="ghost" href="{{ route('subscriptions.edit', $subscription) }}" wire:navigate>
                    {{ __('Edit') }}
                </flux:button>
            @endif
            @if($can_renew)
                <flux:button variant="ghost" href="{{ route('subscriptions.renew', $subscription) }}" wire:navigate icon="arrow-path">
                    {{ __('Renew') }}
                </flux:button>
            @endif
        </x-slot:actions>
    @endif

    <livewire:subscriptions.show :subscription="$subscription" />
</x-layouts.app>
