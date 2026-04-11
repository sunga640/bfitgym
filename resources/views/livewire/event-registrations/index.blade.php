<div>
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

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-1 flex-col gap-4 lg:flex-row lg:items-end">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search registrant, member, or event...') }}"
                class="w-full lg:max-w-sm"
            />

            <flux:select wire:model.live="event_filter" class="w-full lg:max-w-[280px]">
                <option value="">{{ __('All Events') }}</option>
                @foreach($events as $event)
                    <option value="{{ $event->id }}">{{ $event->title }} ({{ $event->start_datetime?->format('M d, Y') }})</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="status_filter" class="w-full lg:max-w-[170px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="confirmed">{{ __('Confirmed') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
                <option value="attended">{{ __('Attended') }}</option>
                <option value="no_show">{{ __('No Show') }}</option>
            </flux:select>
        </div>

        <div class="flex items-center justify-end">
            <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark">
                {{ __('Clear filters') }}
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Registrant') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Attendance') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($registrations as $registration)
                    <tr wire:key="event-registration-{{ $registration->id }}">
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $registration->event?->title ?? '-' }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $registration->event?->start_datetime?->format('M d, Y g:i A') }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-zinc-900 dark:text-white">{{ $registration->registrant_name }}</div>
                            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                                @if($registration->member)
                                    {{ __('Member') }}: {{ $registration->member->member_no }}
                                @elseif($registration->email)
                                    {{ $registration->email }}
                                @elseif($registration->phone)
                                    {{ $registration->phone }}
                                @else
                                    -
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <flux:badge :color="$registration->will_attend ? 'emerald' : 'zinc'">
                                {{ $registration->will_attend ? __('Will attend') : __('Not attending') }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $statusColor = [
                                    'pending' => 'amber',
                                    'confirmed' => 'blue',
                                    'cancelled' => 'zinc',
                                    'attended' => 'emerald',
                                    'no_show' => 'rose',
                                ][$registration->status] ?? 'zinc';
                            @endphp
                            <flux:badge :color="$statusColor">
                                {{ ucfirst(str_replace('_', ' ', $registration->status)) }}
                            </flux:badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            @if($registration->paymentTransaction)
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ money((float) $registration->paymentTransaction->amount, $registration->paymentTransaction->currency) }}
                                </div>
                                <div>{{ ucfirst($registration->paymentTransaction->status) }}</div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            @if($registration->event)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('events.show', $registration->event) }}"
                                    wire:navigate
                                    icon="eye"
                                >
                                    {{ __('View Event') }}
                                </flux:button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="user-plus" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No registrations found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting filters or add a new registration.') }}</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($registrations->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $registrations->links() }}
            </div>
        @endif
    </div>
</div>
