<x-layouts.app title="{{ __('Create Purchase Order') }}" description="{{ __('Create a new purchase order from suppliers.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('purchase-orders.index') }}">{{ __('Purchase Orders') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:purchase-orders.form />
</x-layouts.app>

