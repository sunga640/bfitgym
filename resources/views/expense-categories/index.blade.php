<x-layouts.app :title="__('Expense Categories')" :description="__('Organize and manage expense categories.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Expense Categories') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('create', \App\Models\ExpenseCategory::class)
            <flux:button variant="primary" href="{{ route('expense-categories.create') }}" wire:navigate icon="plus">
                {{ __('New Category') }}
            </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:expense-categories.index />
</x-layouts.app>
