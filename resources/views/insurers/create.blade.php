<x-layouts.app :title="__('Add Insurer')" :description="__('Register a new insurance provider.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('insurers.index') }}">{{ __('Insurers') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Add Insurer') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:insurers.form />
</x-layouts.app>

