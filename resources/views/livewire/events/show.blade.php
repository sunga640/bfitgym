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

    <div class="mb-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Date') }}</div>
            <div class="mt-2 text-base font-semibold text-zinc-900 dark:text-white">
                {{ $event->start_datetime?->format('M d, Y g:i A') }}
            </div>
            @if($event->end_datetime)
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Ends') }}: {{ $event->end_datetime->format('M d, Y g:i A') }}
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Attending') }}</div>
            <div class="mt-2 text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ number_format($event->attending_registrations_count) }}
                @if($event->capacity)
                    <span class="text-base text-zinc-500 dark:text-zinc-400">/ {{ number_format($event->capacity) }}</span>
                @endif
            </div>
            @if($event->capacity)
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Remaining') }}: {{ number_format(max(0, $event->capacity - $event->attending_registrations_count)) }}
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</div>
            <div class="mt-2 flex items-center gap-2">
                <flux:badge :color="$event->payment_required ? 'amber' : 'zinc'">
                    {{ $event->payment_required ? __('Required') : __('Not required') }}
                </flux:badge>
                @if($event->payment_required)
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ money((float) $event->price) }}</span>
                @endif
            </div>
            @if($event->payment_required)
                <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Collected') }}: {{ money($payment_total) }}
                </div>
            @endif
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</div>
            <div class="mt-2 flex items-center gap-2">
                @php
                    $statusColor = [
                        'scheduled' => 'blue',
                        'completed' => 'emerald',
                        'cancelled' => 'rose',
                    ][$event->status] ?? 'zinc';
                @endphp
                <flux:badge :color="$statusColor">{{ ucfirst($event->status) }}</flux:badge>
                <flux:badge color="zinc">{{ ucfirst($event->type) }}</flux:badge>
            </div>
            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                {{ $event->allow_non_members ? __('Open to non-members') : __('Members only') }}
            </div>
        </div>
    </div>

    @if($event->description)
        <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('Event Description') }}</h3>
            <p class="mt-2 whitespace-pre-line text-sm text-zinc-600 dark:text-zinc-300">{{ $event->description }}</p>
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search registrations...') }}"
                class="w-full md:max-w-xs"
            />

            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[170px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="confirmed">{{ __('Confirmed') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
                <option value="attended">{{ __('Attended') }}</option>
                <option value="no_show">{{ __('No Show') }}</option>
            </flux:select>
        </div>

        @can('create', \App\Models\EventRegistration::class)
            <flux:button variant="primary" href="{{ route('event-registrations.create', ['event' => $event->id]) }}" wire:navigate icon="user-plus">
                {{ __('Add Registration') }}
            </flux:button>
        @endcan
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Registrant') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Attendance') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Payment') }}</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($registrations as $registration)
                    <tr wire:key="registration-{{ $registration->id }}">
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
                                $regColor = [
                                    'pending' => 'amber',
                                    'confirmed' => 'blue',
                                    'cancelled' => 'zinc',
                                    'attended' => 'emerald',
                                    'no_show' => 'rose',
                                ][$registration->status] ?? 'zinc';
                            @endphp
                            <flux:badge :color="$regColor">{{ ucfirst(str_replace('_', ' ', $registration->status)) }}</flux:badge>
                        </td>
                        <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                            @if($registration->paymentTransaction)
                                <div class="font-medium text-zinc-900 dark:text-white">
                                    {{ money((float) $registration->paymentTransaction->amount, $registration->paymentTransaction->currency) }}
                                </div>
                                <div>
                                    {{ ucfirst($registration->paymentTransaction->status) }}
                                    @if($registration->paymentTransaction->paid_at)
                                        - {{ $registration->paymentTransaction->paid_at->format('M d, Y') }}
                                    @endif
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <div class="flex flex-wrap items-center justify-end gap-2">
                                @can('update', $registration)
                                    @if($registration->status !== 'confirmed')
                                        <flux:button variant="ghost" size="sm" wire:click="updateRegistrationStatus({{ $registration->id }}, 'confirmed')">
                                            {{ __('Confirm') }}
                                        </flux:button>
                                    @endif
                                    @if($registration->status !== 'attended')
                                        <flux:button variant="ghost" size="sm" wire:click="updateRegistrationStatus({{ $registration->id }}, 'attended')">
                                            {{ __('Attend') }}
                                        </flux:button>
                                    @endif
                                    @if($registration->status !== 'cancelled')
                                        <flux:button variant="ghost" size="sm" wire:click="updateRegistrationStatus({{ $registration->id }}, 'cancelled')">
                                            {{ __('Cancel') }}
                                        </flux:button>
                                    @endif
                                    @if($registration->status !== 'no_show')
                                        <flux:button variant="ghost" size="sm" wire:click="updateRegistrationStatus({{ $registration->id }}, 'no_show')">
                                            {{ __('No Show') }}
                                        </flux:button>
                                    @endif
                                @endcan
                                @can('delete', $registration)
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="deleteRegistration({{ $registration->id }})"
                                        wire:confirm="{{ __('Delete this registration?') }}"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="users" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No registrations found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Registrations will appear here once attendees sign up.') }}</p>
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
