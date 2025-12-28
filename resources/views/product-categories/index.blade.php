<x-layouts.app title="{{ __('Product Categories') }}" description="{{ __('Manage product categories for your inventory.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('products.index') }}" wire:navigate icon="cube">
            {{ __('Products') }}
        </flux:button>
    </x-slot:actions>

    <livewire:product-categories.index />
</x-layouts.app>
