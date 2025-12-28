<x-layouts.app title="{{ __('Point of Sale') }}" description="{{ __('Process sales transactions.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('pos-sales.index') }}" wire:navigate icon="clock">
            {{ __('Sales History') }}
        </flux:button>
    </x-slot:actions>

    <livewire:pos.terminal />
</x-layouts.app>
