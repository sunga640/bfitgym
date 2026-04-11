<x-layouts.app title="{{ __('Expense Report') }}" description="{{ __('Analyze expense trends and spending categories.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('expenses.index') }}" wire:navigate icon="arrow-trending-down">
            {{ __('Expenses') }}
        </flux:button>
        <flux:button variant="ghost" href="{{ route('expense-categories.index') }}" wire:navigate icon="folder">
            {{ __('Categories') }}
        </flux:button>
    </x-slot:actions>

    <livewire:reports.expenses />
</x-layouts.app>

