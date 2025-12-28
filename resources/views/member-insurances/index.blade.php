<x-layouts.app :title="__('Member Policies')" :description="__('Manage member insurance policies.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item :current="true">{{ __('Member Policies') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <x-slot:actions>
        @can('manage insurers')
        <flux:button variant="primary" href="{{ route('members.create') }}" wire:navigate icon="plus">
            {{ __('Add Member with Insurance') }}
        </flux:button>
        @endcan
    </x-slot:actions>

    <livewire:member-insurances.index />
</x-layouts.app>
