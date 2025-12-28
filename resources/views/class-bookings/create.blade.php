<x-layouts.app.sidebar :title="__('Create Booking')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('class-bookings.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ __('Create Booking') }}</flux:heading>
                <flux:subheading>{{ __('Book a member into a class session.') }}</flux:subheading>
            </div>
        </div>

        <livewire:class-bookings.create />
    </flux:main>
</x-layouts.app.sidebar>

