<div>
    {{-- Header Controls --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        {{-- Navigation --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <button
                    wire:click="previousPeriod"
                    class="flex h-10 w-10 items-center justify-center border-r border-zinc-200 text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700"
                >
                    <flux:icon name="chevron-left" class="h-5 w-5" />
                </button>
                <button
                    wire:click="goToToday"
                    class="flex h-10 items-center justify-center px-4 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-700"
                >
                    {{ __('Today') }}
                </button>
                <button
                    wire:click="nextPeriod"
                    class="flex h-10 w-10 items-center justify-center border-l border-zinc-200 text-zinc-600 transition hover:bg-zinc-50 dark:border-zinc-700 dark:text-zinc-400 dark:hover:bg-zinc-700"
                >
                    <flux:icon name="chevron-right" class="h-5 w-5" />
                </button>
            </div>

            <h2 class="text-xl font-semibold text-zinc-900 dark:text-white">
                {{ $calendar_data['current_month'] }}
            </h2>
        </div>

        {{-- View Mode & Filters --}}
        <div class="flex flex-wrap items-center gap-3">
            {{-- Filter Type --}}
            <flux:select wire:model.live="filter_type" class="w-36">
                <option value="all">{{ __('All Items') }}</option>
                <option value="classes">{{ __('Classes Only') }}</option>
                <option value="events">{{ __('Events Only') }}</option>
            </flux:select>

            {{-- View Mode Toggle --}}
            <div class="flex overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <button
                    wire:click="setViewMode('month')"
                    class="flex h-10 items-center justify-center px-4 text-sm font-medium transition {{ $view_mode === 'month' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                >
                    {{ __('Month') }}
                </button>
                <button
                    wire:click="setViewMode('week')"
                    class="flex h-10 items-center justify-center border-l border-zinc-200 px-4 text-sm font-medium transition dark:border-zinc-700 {{ $view_mode === 'week' ? 'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-700' }}"
                >
                    {{ __('Week') }}
                </button>
            </div>

            {{-- Quick Actions --}}
            <div class="flex gap-2">
                @can('create classes')
                    <flux:button variant="ghost" size="sm" href="{{ route('class-sessions.create') }}" wire:navigate icon="plus">
                        {{ __('Add Class') }}
                    </flux:button>
                @endcan
                @can('create events')
                    <flux:button variant="primary" size="sm" href="{{ route('events.create') }}" wire:navigate icon="calendar-days">
                        {{ __('Add Event') }}
                    </flux:button>
                @endcan
            </div>
        </div>
    </div>

    {{-- Legend --}}
    <div class="mb-4 flex flex-wrap items-center gap-4 text-sm">
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-blue-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Classes') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-violet-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Public Events') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-amber-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Paid Events') }}</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="h-3 w-3 rounded-full bg-slate-500"></span>
            <span class="text-zinc-600 dark:text-zinc-400">{{ __('Internal Events') }}</span>
        </div>
    </div>

    {{-- Calendar Grid --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        {{-- Day Headers --}}
        <div class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900/50">
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day_name)
                <div class="px-2 py-3 text-center text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400 {{ in_array($day_name, ['Sat', 'Sun']) ? 'bg-zinc-100/50 dark:bg-zinc-800/50' : '' }}">
                    {{ __($day_name) }}
                </div>
            @endforeach
        </div>

        {{-- Calendar Days --}}
        <div class="grid grid-cols-7">
            @foreach($calendar_data['days'] as $index => $day)
                @php
                    $items = $calendar_items[$day['date']] ?? [];
                    $is_weekend = in_array($day['day_of_week'], [6, 7]);
                @endphp
                <div
                    class="min-h-[120px] border-b border-r border-zinc-200 p-1.5 transition dark:border-zinc-700 {{ $view_mode === 'week' ? 'min-h-[300px]' : '' }} {{ $is_weekend ? 'bg-zinc-50/50 dark:bg-zinc-900/30' : '' }} {{ !$day['is_current_month'] ? 'bg-zinc-100/50 dark:bg-zinc-900/50' : '' }} {{ ($index + 1) % 7 === 0 ? 'border-r-0' : '' }}"
                >
                    {{-- Date Number --}}
                    <div class="mb-1 flex items-center justify-between">
                        <span class="flex h-7 w-7 items-center justify-center rounded-full text-sm font-medium {{ $day['is_today'] ? 'bg-blue-600 text-white' : ($day['is_current_month'] ? 'text-zinc-900 dark:text-white' : 'text-zinc-400 dark:text-zinc-600') }}">
                            {{ $day['day'] }}
                        </span>
                        @if(count($items) > 3 && $view_mode === 'month')
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">+{{ count($items) - 3 }}</span>
                        @endif
                    </div>

                    {{-- Items --}}
                    <div class="space-y-1">
                        @foreach(array_slice($items, 0, $view_mode === 'week' ? 10 : 3) as $item)
                            @php
                                $color_classes = match($item['color']) {
                                    'emerald' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/60',
                                    'teal' => 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300 hover:bg-teal-200 dark:hover:bg-teal-900/60',
                                    'pink' => 'bg-pink-100 text-pink-800 dark:bg-pink-900/40 dark:text-pink-300 hover:bg-pink-200 dark:hover:bg-pink-900/60',
                                    'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300 hover:bg-orange-200 dark:hover:bg-orange-900/60',
                                    'red' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300 hover:bg-red-200 dark:hover:bg-red-900/60',
                                    'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300 hover:bg-purple-200 dark:hover:bg-purple-900/60',
                                    'amber' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300 hover:bg-amber-200 dark:hover:bg-amber-900/60',
                                    'violet' => 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300 hover:bg-violet-200 dark:hover:bg-violet-900/60',
                                    'slate' => 'bg-slate-200 text-slate-800 dark:bg-slate-700/60 dark:text-slate-300 hover:bg-slate-300 dark:hover:bg-slate-700/80',
                                    default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 hover:bg-blue-200 dark:hover:bg-blue-900/60',
                                };
                            @endphp
                            <button
                                wire:click="{{ $item['type'] === 'class' ? "showClassDetail({$item['id']}, '{$day['date']}')" : "showEventDetail({$item['id']})" }}"
                                class="group flex w-full cursor-pointer items-start gap-1 rounded px-1.5 py-1 text-left text-xs transition {{ $color_classes }}"
                            >
                                <span class="shrink-0 font-medium">
                                    {{ \Carbon\Carbon::parse($item['start_time'])->format('g:i') }}
                                </span>
                                <span class="truncate">{{ $item['name'] }}</span>
                            </button>
                        @endforeach

                        @if(count($items) === 0 && $day['is_current_month'])
                            <div class="py-2 text-center text-xs text-zinc-400 dark:text-zinc-600">
                                {{-- Empty state - no text needed --}}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Upcoming This Week Section --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        {{-- Upcoming Classes --}}
        @if($filter_type !== 'events')
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Classes') }}</h3>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php
                        $upcoming_classes = collect($calendar_items)
                            ->filter(fn($items, $date) => $date >= now()->format('Y-m-d'))
                            ->flatMap(fn($items, $date) => collect($items)->filter(fn($i) => $i['type'] === 'class')->map(fn($i) => array_merge($i, ['date' => $date])))
                            ->sortBy(fn($i) => $i['date'] . ' ' . $i['start_time'])
                            ->take(5);
                    @endphp

                    @forelse($upcoming_classes as $class)
                        <button
                            wire:click="showClassDetail({{ $class['id'] }}, '{{ $class['date'] }}')"
                            class="flex w-full items-center gap-4 px-5 py-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                        >
                            <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                                <span class="text-xs font-medium text-blue-600 dark:text-blue-400">
                                    {{ \Carbon\Carbon::parse($class['date'])->format('D') }}
                                </span>
                                <span class="text-lg font-bold text-blue-700 dark:text-blue-300">
                                    {{ \Carbon\Carbon::parse($class['date'])->format('j') }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $class['name'] }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($class['start_time'])->format('g:i A') }}
                                    @if($class['location'])
                                        · {{ $class['location'] }}
                                    @endif
                                </p>
                            </div>
                            <flux:icon name="chevron-right" class="h-5 w-5 shrink-0 text-zinc-400" />
                        </button>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="clock" class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No upcoming classes') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif

        {{-- Upcoming Events --}}
        @if($filter_type !== 'classes')
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-5 py-4 dark:border-zinc-700">
                    <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Events') }}</h3>
                </div>
                <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @php
                        $upcoming_events = collect($calendar_items)
                            ->filter(fn($items, $date) => $date >= now()->format('Y-m-d'))
                            ->flatMap(fn($items, $date) => collect($items)->filter(fn($i) => $i['type'] === 'event')->map(fn($i) => array_merge($i, ['date' => $date])))
                            ->sortBy(fn($i) => $i['date'] . ' ' . $i['start_time'])
                            ->take(5);
                    @endphp

                    @forelse($upcoming_events as $event)
                        @php
                            $event_bg = match($event['event_type'] ?? 'public') {
                                'paid' => 'bg-amber-100 dark:bg-amber-900/40',
                                'internal' => 'bg-slate-200 dark:bg-slate-700/60',
                                default => 'bg-violet-100 dark:bg-violet-900/40',
                            };
                            $event_text = match($event['event_type'] ?? 'public') {
                                'paid' => 'text-amber-600 dark:text-amber-400',
                                'internal' => 'text-slate-600 dark:text-slate-400',
                                default => 'text-violet-600 dark:text-violet-400',
                            };
                            $event_bold = match($event['event_type'] ?? 'public') {
                                'paid' => 'text-amber-700 dark:text-amber-300',
                                'internal' => 'text-slate-700 dark:text-slate-300',
                                default => 'text-violet-700 dark:text-violet-300',
                            };
                        @endphp
                        <button
                            wire:click="showEventDetail({{ $event['id'] }})"
                            class="flex w-full items-center gap-4 px-5 py-3 text-left transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50"
                        >
                            <div class="flex h-12 w-12 shrink-0 flex-col items-center justify-center rounded-lg {{ $event_bg }}">
                                <span class="text-xs font-medium {{ $event_text }}">
                                    {{ \Carbon\Carbon::parse($event['date'])->format('D') }}
                                </span>
                                <span class="text-lg font-bold {{ $event_bold }}">
                                    {{ \Carbon\Carbon::parse($event['date'])->format('j') }}
                                </span>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $event['name'] }}</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ \Carbon\Carbon::parse($event['start_time'])->format('g:i A') }}
                                    @if($event['location'])
                                        · {{ $event['location'] }}
                                    @endif
                                </p>
                            </div>
                            <flux:icon name="chevron-right" class="h-5 w-5 shrink-0 text-zinc-400" />
                        </button>
                    @empty
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="calendar" class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No upcoming events') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if($show_detail_modal)
        <div
            x-data="{ open: @entangle('show_detail_modal') }"
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto"
            aria-labelledby="modal-title"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex min-h-screen items-end justify-center px-4 pb-20 pt-4 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div
                    x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100"
                    x-transition:leave-end="opacity-0"
                    class="fixed inset-0 bg-zinc-900/75 transition-opacity"
                    wire:click="closeDetailModal"
                ></div>

                <span class="hidden sm:inline-block sm:h-screen sm:align-middle" aria-hidden="true">&#8203;</span>

                {{-- Modal Panel --}}
                <div
                    x-show="open"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    class="relative inline-block w-full transform overflow-hidden rounded-xl bg-white text-left align-bottom shadow-xl transition-all dark:bg-zinc-800 sm:my-8 sm:max-w-lg sm:align-middle"
                >
                    {{-- Modal Header --}}
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                @if($detail_type === 'class')
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                                        <flux:icon name="academic-cap" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $detail_data['name'] ?? '' }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Class Session') }}</p>
                                    </div>
                                @else
                                    @php
                                        $modal_bg = match($detail_data['type'] ?? 'public') {
                                            'paid' => 'bg-amber-100 dark:bg-amber-900/40',
                                            'internal' => 'bg-slate-200 dark:bg-slate-700/60',
                                            default => 'bg-violet-100 dark:bg-violet-900/40',
                                        };
                                        $modal_icon = match($detail_data['type'] ?? 'public') {
                                            'paid' => 'text-amber-600 dark:text-amber-400',
                                            'internal' => 'text-slate-600 dark:text-slate-400',
                                            default => 'text-violet-600 dark:text-violet-400',
                                        };
                                    @endphp
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $modal_bg }}">
                                        <flux:icon name="calendar-days" class="h-5 w-5 {{ $modal_icon }}" />
                                    </div>
                                    <div>
                                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $detail_data['title'] ?? '' }}</h3>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ ucfirst($detail_data['type'] ?? 'public') }} {{ __('Event') }}
                                        </p>
                                    </div>
                                @endif
                            </div>
                            <button
                                wire:click="closeDetailModal"
                                class="rounded-lg p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                            >
                                <flux:icon name="x-mark" class="h-5 w-5" />
                            </button>
                        </div>
                    </div>

                    {{-- Modal Content --}}
                    <div class="px-6 py-5">
                        @if($detail_type === 'class')
                            {{-- Class Details --}}
                            <div class="space-y-4">
                                @if(!empty($detail_data['description']))
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $detail_data['description'] }}</p>
                                @endif

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex items-start gap-3">
                                        <flux:icon name="calendar" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                        <div>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $detail_data['date'] ?? '' }}</p>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $detail_data['start_time'] ?? '' }} - {{ $detail_data['end_time'] ?? '' }}
                                            </p>
                                        </div>
                                    </div>

                                    @if(!empty($detail_data['location']))
                                        <div class="flex items-start gap-3">
                                            <flux:icon name="map-pin" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $detail_data['location'] }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if(!empty($detail_data['instructor']))
                                        <div class="flex items-start gap-3">
                                            <flux:icon name="user" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $detail_data['instructor'] }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Instructor') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex items-start gap-3">
                                        <flux:icon name="users" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                        <div>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                {{ $detail_data['booked'] ?? 0 }} / {{ $detail_data['capacity'] ?? '∞' }}
                                            </p>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Booked') }}</p>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($detail_data['has_booking_fee']) && $detail_data['booking_fee'] > 0)
                                    <div class="rounded-lg bg-emerald-50 px-4 py-3 dark:bg-emerald-900/20">
                                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-300">
                                            {{ __('Booking Fee:') }} {{ money($detail_data['booking_fee']) }}
                                        </p>
                                    </div>
                                @endif

                                @if(!empty($detail_data['assistants']))
                                    <div>
                                        <p class="mb-2 text-xs font-medium uppercase text-zinc-500 dark:text-zinc-400">{{ __('Assistant Instructors') }}</p>
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($detail_data['assistants'] as $assistant)
                                                <span class="rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                                    {{ $assistant }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @else
                            {{-- Event Details --}}
                            <div class="space-y-4">
                                @if(!empty($detail_data['description']))
                                    <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $detail_data['description'] }}</p>
                                @endif

                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div class="flex items-start gap-3">
                                        <flux:icon name="calendar" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                        <div>
                                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $detail_data['date'] ?? '' }}</p>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                                {{ $detail_data['start_time'] ?? '' }}
                                                @if(!empty($detail_data['end_time']))
                                                    - {{ $detail_data['end_time'] }}
                                                @endif
                                            </p>
                                        </div>
                                    </div>

                                    @if(!empty($detail_data['location']))
                                        <div class="flex items-start gap-3">
                                            <flux:icon name="map-pin" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $detail_data['location'] }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if($detail_data['capacity'])
                                        <div class="flex items-start gap-3">
                                            <flux:icon name="users" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                                    {{ $detail_data['registered'] ?? 0 }} / {{ $detail_data['capacity'] }}
                                                </p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Registered') }}</p>
                                            </div>
                                        </div>
                                    @endif

                                    @if(!empty($detail_data['is_paid']) && $detail_data['price'] > 0)
                                        <div class="flex items-start gap-3">
                                            <flux:icon name="banknotes" class="mt-0.5 h-5 w-5 text-zinc-400" />
                                            <div>
                                                <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ money($detail_data['price']) }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Entry Fee') }}</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if(!empty($detail_data['allow_non_members']))
                                    <div class="flex items-center gap-2 text-sm text-emerald-600 dark:text-emerald-400">
                                        <flux:icon name="check-circle" class="h-4 w-4" />
                                        {{ __('Open to non-members') }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>

                    {{-- Modal Footer --}}
                    <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <flux:button variant="ghost" wire:click="closeDetailModal">
                            {{ __('Close') }}
                        </flux:button>
                        @if($detail_type === 'class')
                            @can('view class bookings')
                                <flux:button variant="ghost" href="{{ route('class-sessions.show', $detail_id) }}" wire:navigate icon="eye">
                                    {{ __('View Details') }}
                                </flux:button>
                            @endcan
                            @can('create class bookings')
                                <flux:button variant="primary" href="{{ route('class-bookings.create', ['session' => $detail_id]) }}" wire:navigate icon="ticket">
                                    {{ __('Book Now') }}
                                </flux:button>
                            @endcan
                        @else
                            @can('view events')
                                <flux:button variant="ghost" href="{{ route('events.show', $detail_id) }}" wire:navigate icon="eye">
                                    {{ __('View Details') }}
                                </flux:button>
                            @endcan
                            @can('manage event registrations')
                                <flux:button variant="primary" href="{{ route('event-registrations.create', ['event' => $detail_id]) }}" wire:navigate icon="user-plus">
                                    {{ __('Register') }}
                                </flux:button>
                            @endcan
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

