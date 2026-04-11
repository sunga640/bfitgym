<x-layouts.app :title="__('Create Expense Category')" :description="__('Define a new category for tracking expenses.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('expense-categories.index') }}">{{ __('Expense Categories') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:expense-categories.form />
</x-layouts.app>

