<x-layouts.app :title="__('Edit Expense')" :description="__('Update this expense record.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('expenses.index') }}">{{ __('Expenses') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:expenses.form :expense="$expense" />
</x-layouts.app>

