<x-layouts.app :title="__('Add Member')" :description="__('Register a new gym member.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('members.index') }}">{{ __('Members') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Add Member') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>
    <livewire:members.form />
</x-layouts.app>

