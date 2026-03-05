<x-layouts.app title="{{ __('Membership Report') }}" description="{{ __('Track membership trends, renewals, and revenue performance.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('subscriptions.index') }}" wire:navigate icon="credit-card">
            {{ __('Subscriptions') }}
        </flux:button>
        <flux:button variant="ghost" href="{{ route('reports.revenue') }}" wire:navigate icon="chart-bar">
            {{ __('Revenue') }}
        </flux:button>
    </x-slot:actions>

    <livewire:reports.memberships />
</x-layouts.app>
