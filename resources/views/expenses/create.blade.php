<x-layouts.app :title="__('Record Expense')" :description="__('Capture a new expense entry for this branch.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('expenses.index') }}">{{ __('Expenses') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:expenses.form />
</x-layouts.app>

