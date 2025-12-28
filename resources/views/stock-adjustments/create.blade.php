<x-layouts.app title="{{ __('New Stock Adjustment') }}" description="{{ __('Adjust stock levels with tracking.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('branch-products.index') }}">{{ __('Inventory') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('stock-adjustments.index') }}">{{ __('Adjustments') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:stock-adjustments.index />
</x-layouts.app>

