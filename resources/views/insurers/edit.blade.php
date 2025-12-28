<x-layouts.app :title="__('Edit Insurer')" :description="__('Update insurance provider information.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('insurers.index') }}">{{ __('Insurers') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit Insurer') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:insurers.form :insurer="$insurer" />
</x-layouts.app>

