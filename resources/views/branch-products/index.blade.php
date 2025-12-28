<x-layouts.app title="{{ __('Branch Inventory') }}" description="{{ __('Manage stock levels and pricing for your branch.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('stock-adjustments.index') }}" wire:navigate icon="adjustments-horizontal">
            {{ __('Adjustments') }}
        </flux:button>
        <flux:button variant="ghost" href="{{ route('purchase-orders.index') }}" wire:navigate icon="clipboard-document-list">
            {{ __('Purchase Orders') }}
        </flux:button>
    </x-slot:actions>

    <livewire:branch-products.index />
</x-layouts.app>
