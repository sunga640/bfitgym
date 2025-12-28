<x-layouts.app title="{{ __('Stock Adjustments') }}" description="{{ __('Adjust stock levels with tracking.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('branch-products.index') }}">{{ __('Inventory') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Adjustments') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:stock-adjustments.index />
</x-layouts.app>
