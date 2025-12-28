<x-layouts.app :title="__('Members')" :description="__('Manage gym members and their profiles.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item :current="true">{{ __('Members') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>
    <x-slot:actions>
        @can('export members')
        <flux:button variant="ghost" icon="arrow-down-tray">
            {{ __('Export') }}
        </flux:button>
        @endcan
        @can('create members')
        <flux:button variant="primary" href="{{ route('members.create') }}" wire:navigate icon="plus">
            {{ __('Add Member') }}
        </flux:button>
        @endcan
    </x-slot:actions>
    <livewire:members.index />
</x-layouts.app>
