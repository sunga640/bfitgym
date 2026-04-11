<x-layouts.app :title="__('Expenses')" :description="__('Track, review, and manage branch expenses.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Expenses') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('create', \App\Models\Expense::class)
            <flux:button variant="primary" href="{{ route('expenses.create') }}" wire:navigate icon="plus">
                {{ __('New Expense') }}
            </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:expenses.index />
</x-layouts.app>
