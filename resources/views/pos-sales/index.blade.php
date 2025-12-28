<x-layouts.app title="{{ __('POS Sales History') }}" description="{{ __('View and manage completed sales.') }}">
    <x-slot:actions>
        <flux:button variant="primary" href="{{ route('pos.index') }}" wire:navigate icon="shopping-cart">
            {{ __('New Sale') }}
        </flux:button>
    </x-slot:actions>

    <livewire:pos-sales.index />
</x-layouts.app>
