<x-layouts.app :title="__('Insurers')" :description="__('Manage insurance providers and their details.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item :current="true">{{ __('Insurers') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('manage insurers')
        <flux:button variant="primary" href="{{ route('insurers.create') }}" wire:navigate icon="plus">
            {{ __('Add Insurer') }}
        </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:insurers.index />
</x-layouts.app>
