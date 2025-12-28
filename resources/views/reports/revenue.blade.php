<x-layouts.app title="{{ __('Revenue Report') }}" description="{{ __('Comprehensive revenue analysis by source.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('reports.memberships') }}" wire:navigate icon="credit-card">
            {{ __('Memberships') }}
        </flux:button>
        <flux:button variant="ghost" href="{{ route('reports.pos') }}" wire:navigate icon="shopping-cart">
            {{ __('POS Report') }}
        </flux:button>
    </x-slot:actions>

    <livewire:reports.revenue />
</x-layouts.app>
