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
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center md:flex-wrap">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search sessions...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="class_type_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Class Types') }}</option>
                @foreach($class_types as $type)
                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="day_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Days') }}</option>
                @foreach($days as $num => $name)
                    <option value="{{ $num }}">{{ $name }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="cancelled">{{ __('Cancelled') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count session|:count sessions', $sessions->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Class') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Schedule') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Instructor') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($sessions as $session)
                <tr wire:key="session-{{ $session->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $session->classType?->name }}</div>
                        @if($session->classType?->has_booking_fee)
                            <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                {{ __('Fee:') }} {{ number_format($session->classType->booking_fee, 2) }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($session->is_recurring)
                            <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                                <flux:icon name="arrow-path" class="h-4 w-4 text-blue-500" />
                                {{ $days[$session->day_of_week] ?? '-' }}
                            </div>
                        @else
                            <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                                <flux:icon name="calendar" class="h-4 w-4 text-purple-500" />
                                {{ $session->specific_date?->format('M d, Y') }}
                            </div>
                        @endif
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($session->end_time)->format('g:i A') }}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-1.5">
                            <flux:icon name="map-pin" class="h-4 w-4 text-zinc-400" />
                            <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $session->location?->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-zinc-900 dark:text-white">{{ $session->mainInstructor?->name }}</div>
                        @if($session->assistantStaff->count() > 0)
                            <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                +{{ $session->assistantStaff->count() }} {{ __('assistant(s)') }}
                            </div>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $session->bookings_count }}</span>
                            <span class="text-sm text-zinc-400">/</span>
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $session->effective_capacity ?? '∞' }}
                            </span>
                        </div>
                        @if($session->available_spots !== null)
                            @if($session->available_spots > 0)
                                <div class="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                    {{ trans_choice(':count spot left|:count spots left', $session->available_spots) }}
                                </div>
                            @else
                                <div class="mt-1 text-xs text-red-600 dark:text-red-400">
                                    {{ __('Full') }}
                                </div>
                            @endif
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <flux:badge :color="$session->status === 'active' ? 'emerald' : 'rose'" size="sm">
                            {{ ucfirst($session->status) }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('class-sessions.show', $session) }}"
                                wire:navigate
                                icon="eye"
                            >
                                {{ __('View') }}
                            </flux:button>
                            @can('update', $session)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('class-sessions.edit', $session) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @if($session->status === 'active')
                                @can('update', $session)
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    wire:click="cancelSession({{ $session->id }})"
                                    wire:confirm="{{ __('Are you sure you want to cancel this session?') }}"
                                    icon="x-circle"
                                    class="text-amber-600 hover:text-amber-700 dark:text-amber-400"
                                >
                                    {{ __('Cancel') }}
                                </flux:button>
                                @endcan
                            @endif
                            @can('delete', $session)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteSession({{ $session->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this session?') }}"
                                icon="trash"
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
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="clock" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No class sessions found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by scheduling your first class session.') }}</p>
                            @can('create', App\Models\ClassSession::class)
                            <flux:button variant="primary" href="{{ route('class-sessions.create') }}" wire:navigate class="mt-4">
                                {{ __('Schedule Session') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($sessions->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $sessions->links() }}
            </div>
        @endif
    </div>
</div>

