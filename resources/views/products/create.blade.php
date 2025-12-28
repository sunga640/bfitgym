<x-layouts.app title="{{ __('Create Product') }}" description="{{ __('Add a new product to your catalog.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('products.index') }}">{{ __('Products') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ __('Create') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:products.form />
</x-layouts.app>

