<x-layouts.app title="{{ __('Products') }}" description="{{ __('Manage your product catalog.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('product-categories.index') }}" wire:navigate icon="tag">
            {{ __('Categories') }}
        </flux:button>
        <flux:button variant="primary" href="{{ route('products.create') }}" wire:navigate icon="plus">
            {{ __('Add Product') }}
        </flux:button>
    </x-slot:actions>

    <livewire:products.index />
</x-layouts.app>
