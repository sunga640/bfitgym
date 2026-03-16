<x-layouts.app title="{{ __('Attendance Report') }}" description="{{ __('Unified attendance report across HIKVision and ZKTeco integrations.') }}">
    <x-slot:actions>
        <flux:button variant="ghost" href="{{ route('hikvision.logs.index') }}" wire:navigate icon="finger-print">
            {{ __('HIKVision Logs') }}
        </flux:button>
        <flux:button variant="ghost" href="{{ route('zkteco.logs.index') }}" wire:navigate icon="cpu-chip">
            {{ __('ZKTeco Logs') }}
        </flux:button>
    </x-slot:actions>

    <livewire:reports.attendance />
</x-layouts.app>
