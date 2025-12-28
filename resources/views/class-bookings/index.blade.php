<x-layouts.app.sidebar :title="__('Class Bookings')">
    <flux:main container class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Class Bookings') }}</flux:heading>
                <flux:subheading>{{ __('View and manage class bookings.') }}</flux:subheading>
            </div>
            @can('create', App\Models\ClassBooking::class)
            <flux:button variant="primary" href="{{ route('class-bookings.create') }}" wire:navigate icon="plus">
                {{ __('Create Booking') }}
            </flux:button>
            @endcan
        </div>

        <livewire:class-bookings.index />
    </flux:main>
</x-layouts.app.sidebar>
