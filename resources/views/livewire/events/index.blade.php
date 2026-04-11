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

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search events...') }}"
                class="w-full md:max-w-xs"
            />

            <flux:select wire:model.live="type_filter" class="w-full md:max-w-[160px]">
                <option value="">{{ __('All Types') }}</option>
                <option value="public">{{ __('Public') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="internal">{{ __('Internal') }}</option>
            </flux:select>

            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[160px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="scheduled">{{ __('Scheduled') }}</option>
                <option value="completed">{{ __('Completed') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>
        </div>

        <div class="flex items-center gap-2">
            <flux:button
                variant="{{ $view_mode === 'list' ? 'primary' : 'ghost' }}"
                wire:click="setViewMode('list')"
                icon="list-bullet"
            >
                {{ __('List') }}
            </flux:button>
            <flux:button
                variant="{{ $view_mode === 'calendar' ? 'primary' : 'ghost' }}"
                wire:click="setViewMode('calendar')"
                icon="calendar-days"
            >
                {{ __('Calendar') }}
            </flux:button>
        </div>
    </div>

    @if($view_mode === 'calendar' && $calendar_data)
        <div class="mb-4 flex items-center justify-between rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-2">
                <flux:button variant="ghost" size="sm" wire:click="previousMonth" icon="chevron-left" />
                <flux:button variant="ghost" size="sm" wire:click="goToToday">{{ __('Today') }}</flux:button>
                <flux:button variant="ghost" size="sm" wire:click="nextMonth" icon="chevron-right" />
            </div>
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">{{ $calendar_data['current_month'] }}</h3>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)
                    <div class="px-2 py-3 text-center text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __($day) }}
                    </div>
                @endforeach
            </div>

            <div class="grid grid-cols-7">
                @foreach($calendar_data['days'] as $index => $day)
                    @php
                        $day_events = $calendar_events[$day['date']] ?? [];
                    @endphp
                    <div class="min-h-[120px] border-b border-r border-zinc-200 p-1.5 dark:border-zinc-700 {{ !$day['is_current_month'] ? 'bg-zinc-50 dark:bg-zinc-900/40' : '' }} {{ ($index + 1) % 7 === 0 ? 'border-r-0' : '' }}">
                        <div class="mb-1 flex items-center justify-between">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium {{ $day['is_today'] ? 'bg-blue-600 text-white' : 'text-zinc-700 dark:text-zinc-300' }}">
                                {{ $day['day'] }}
                            </span>
                        </div>
                        <div class="space-y-1">
                            @foreach(array_slice($day_events, 0, 3) as $event)
                                <button
                                    wire:click="showEventQuickView({{ $event['id'] }})"
                                    class="w-full truncate rounded px-1.5 py-1 text-left text-xs {{ $event['is_paid'] ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-violet-100 text-violet-800 dark:bg-violet-900/30 dark:text-violet-300' }}"
                                >
                                    {{ \Carbon\Carbon::parse($event['start_time'])->format('g:i') }} {{ $event['title'] }}
                                </button>
                            @endforeach
                            @if(count($day_events) > 3)
                                <p class="px-1 text-xs text-zinc-500 dark:text-zinc-400">+{{ count($day_events) - 3 }} {{ __('more') }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if($view_mode === 'list' && $events)
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Event') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Schedule') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Registrations') }}</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse($events as $event)
                        <tr wire:key="event-{{ $event->id }}">
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $event->title }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $event->location ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                <div>{{ $event->start_datetime?->format('M d, Y g:i A') }}</div>
                                @if($event->end_datetime)
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Ends') }}: {{ $event->end_datetime->format('M d, Y g:i A') }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <flux:badge :color="$event->payment_required ? 'amber' : 'zinc'" size="sm">
                                        {{ ucfirst($event->type) }}
                                    </flux:badge>
                                    @if($event->payment_required)
                                        <flux:badge color="amber" size="sm">{{ money((float) $event->price) }}</flux:badge>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-700 dark:text-zinc-200">
                                {{ number_format($event->registrations_count) }}
                                @if($event->capacity)
                                    / {{ number_format($event->capacity) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button variant="ghost" size="sm" href="{{ route('events.show', $event) }}" wire:navigate icon="eye">
                                        {{ __('View') }}
                                    </flux:button>
                                    @can('update', $event)
                                        <flux:button variant="ghost" size="sm" href="{{ route('events.edit', $event) }}" wire:navigate icon="pencil">
                                            {{ __('Edit') }}
                                        </flux:button>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <flux:icon name="calendar-days" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No events found') }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Try adjusting filters or create a new event.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if($events->hasPages())
                <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    {{ $events->links() }}
                </div>
            @endif
        </div>
    @endif

    @if($show_event_modal && !empty($selected_event_data))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-900/50 p-4">
            <div class="w-full max-w-lg rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $selected_event_data['title'] }}</h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ ucfirst($selected_event_data['type']) }} {{ __('event') }}</p>
                    </div>
                    <flux:button variant="ghost" size="sm" wire:click="closeEventModal" icon="x-mark" />
                </div>

                <div class="mt-4 space-y-2 text-sm text-zinc-600 dark:text-zinc-300">
                    <p><span class="font-medium">{{ __('Date') }}:</span> {{ $selected_event_data['start_date'] }}</p>
                    <p><span class="font-medium">{{ __('Time') }}:</span> {{ $selected_event_data['start_time'] }} @if($selected_event_data['end_time']) - {{ $selected_event_data['end_time'] }} @endif</p>
                    <p><span class="font-medium">{{ __('Location') }}:</span> {{ $selected_event_data['location'] ?: '-' }}</p>
                    <p><span class="font-medium">{{ __('Registrations') }}:</span> {{ $selected_event_data['registered'] }} @if($selected_event_data['capacity']) / {{ $selected_event_data['capacity'] }} @endif</p>
                    <p><span class="font-medium">{{ __('Payment') }}:</span> {{ $selected_event_data['payment_required'] ? money((float) $selected_event_data['price']) : __('Not required') }}</p>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <flux:button variant="ghost" wire:click="closeEventModal">{{ __('Close') }}</flux:button>
                    <flux:button variant="primary" href="{{ route('events.show', $selected_event_id) }}" wire:navigate>{{ __('Open Event') }}</flux:button>
                </div>
            </div>
        </div>
    @endif
</div>

