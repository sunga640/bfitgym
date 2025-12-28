@php
    $membershipPackage = \App\Models\MembershipPackage::findOrFail(request()->route('membershipPackage'));
@endphp

<x-layouts.app :title="__('Edit Membership Package')" :description="__('Update package details and pricing.')">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item href="{{ route('dashboard') }}">{{ __('Dashboard') }}</x-breadcrumb-item>
        <x-breadcrumb-item href="{{ route('membership-packages.index') }}">{{ __('Membership Packages') }}</x-breadcrumb-item>
        <x-breadcrumb-item :current="true">{{ $membershipPackage->name }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <livewire:membership-packages.form :membershipPackage="$membershipPackage" />
</x-layouts.app>
