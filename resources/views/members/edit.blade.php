<x-layouts.app :title="__('Edit Member')" :description="__('Update member information.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('members.index') }}">{{ __('Members') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('Edit Member') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>
    <livewire:members.form :member="$member" />
</x-layouts.app>

