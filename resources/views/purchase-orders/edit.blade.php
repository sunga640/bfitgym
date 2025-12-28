<x-layouts.app title="{{ __('Edit Purchase Order') }}" description="{{ __('Update purchase order details.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('purchase-orders.index') }}">{{ __('Purchase Orders') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:purchase-orders.form :purchaseOrder="$purchaseOrder" />
</x-layouts.app>

