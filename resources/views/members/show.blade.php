<x-layouts.app :title="__('Member Details')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('members.index') }}">{{ __('Members') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ __('View Member') }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>
    <livewire:members.show :member="$member" />
</x-layouts.app>

