<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search member...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="confirmed">{{ __('Confirmed') }}</option>
                <option value="attended">{{ __('Attended') }}</option>
                <option value="no_show">{{ __('No Show') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>
            <flux:select wire:model.live="class_type_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Class Types') }}</option>
                @foreach($class_types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count booking|:count bookings', $bookings->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Class Session') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Booked At') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Fee') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($bookings as $booking)
                <tr wire:key="booking-{{ $booking->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $booking->member?->full_name }}</div>
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $booking->member?->member_no }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $booking->classSession?->classType?->name }}</div>
                        <div class="mt-1 flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <flux:icon name="map-pin" class="h-3 w-3" />
                            {{ $booking->classSession?->location?->name }}
                        </div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            @if($booking->classSession?->is_recurring)
                                {{ ['', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$booking->classSession->day_of_week] ?? '' }}
                            @else
                                {{ $booking->classSession?->specific_date?->format('M d, Y') }}
                            @endif
                            @ {{ $booking->classSession?->start_time?->format('g:i A') }}
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $booking->booked_at?->format('M d, Y g:i A') }}
                    </td>
                    <td class="px-6 py-4">
                        @if($booking->booking_fee_amount)
                            <div class="flex items-center gap-1">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ number_format($booking->booking_fee_amount, 2) }}</span>
                                @if($booking->paymentTransaction)
                                    <flux:badge color="emerald" size="sm">{{ __('Paid') }}</flux:badge>
                                @endif
                            </div>
                        @else
                            <span class="text-zinc-400">{{ __('Free') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @php
                            $status_colors = [
                                'pending' => 'amber',
                                'confirmed' => 'blue',
                                'attended' => 'emerald',
                                'no_show' => 'rose',
                                'cancelled' => 'zinc',
                            ];
                        @endphp
                        <flux:badge :color="$status_colors[$booking->status] ?? 'zinc'" size="sm">
                            {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @if($booking->status === 'pending')
                                @can('update', $booking)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="confirmBooking({{ $booking->id }})"
                                    icon="check-circle"
                                    class="text-emerald-600 hover:text-emerald-700 dark:text-emerald-400"
                                >
                                    {{ __('Confirm') }}
                                </flux:button>
                                @endcan
                            @endif

                            @if(in_array($booking->status, ['pending', 'confirmed']))
                                @can('update', $booking)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="markAttended({{ $booking->id }})"
                                    icon="user-check"
                                    class="text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                >
                                    {{ __('Attended') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="markNoShow({{ $booking->id }})"
                                    icon="user-minus"
                                    class="text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                >
                                    {{ __('No Show') }}
                                </flux:button>
                                @endcan

                                @can('cancel', $booking)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="cancelBooking({{ $booking->id }})"
                                    wire:confirm="{{ __('Are you sure you want to cancel this booking?') }}"
                                    icon="x-circle"
                                    class="text-red-600 hover:text-red-700 dark:text-red-400"
                                >
                                    {{ __('Cancel') }}
                                </flux:button>
                                @endcan
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="ticket" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No bookings found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Start booking members into class sessions.') }}</p>
                            @can('create', App\Models\ClassBooking::class)
                            <flux:button variant="primary" href="{{ route('class-bookings.create') }}" wire:navigate class="mt-4">
                                {{ __('Create Booking') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($bookings->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</div>

