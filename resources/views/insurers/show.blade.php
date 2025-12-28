<x-layouts.app :title="__('Insurer Details')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('insurers.index') }}">{{ __('Insurers') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('View Insurer') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:insurers.show :insurer="$insurer" />
</x-layouts.app>

