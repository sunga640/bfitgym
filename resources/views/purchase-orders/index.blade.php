<x-layouts.app title="{{ __('Purchase Orders') }}" description="{{ __('Manage purchase orders and receiving.') }}">
    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('purchase-orders.create') }}" wire:navigate icon="plus">
            {{ __('New Order') }}
        </flux:button>
    </x-slot:actions>

    <livewire:purchase-orders.index />
</x-layouts.app>
