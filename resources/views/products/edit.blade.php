<x-layouts.app title="{{ __('Edit Product') }}" description="{{ __('Update product details.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('products.index') }}">{{ __('Products') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Edit') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:products.form :product="$product" />
</x-layouts.app>

