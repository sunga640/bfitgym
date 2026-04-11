<x-layouts.app :title="__('Edit Expense Category')" :description="__('Update category details.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('expense-categories.index') }}">{{ __('Expense Categories') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $expenseCategory->name }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:expense-categories.form :expenseCategory="$expenseCategory" />
</x-layouts.app>

