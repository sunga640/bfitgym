<x-layouts.app title="{{ __('Sale Receipt') }}" description="{{ __('View sale details and print receipt.') }}">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('pos-sales.index') }}">{{ __('Sales') }}</x-breadcrumb-item>
        <x-breadcrumb-item>{{ $posSale->sale_number }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:pos-sales.show :posSale="$posSale" />
</x-layouts.app>

