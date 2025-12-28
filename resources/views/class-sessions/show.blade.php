<x-layouts.app.sidebar :title="__('Class Session Details')">
    <flux:main container class="space-y-6">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" href="{{ route('class-sessions.index') }}" wire:navigate icon="arrow-left" size="sm">
                {{ __('Back') }}
            </flux:button>
            <div>
                <flux:heading size="xl">{{ $classSession->classType?->name ?? __('Class Session') }}</flux:heading>
                <flux:subheading>{{ __('Session details and bookings.') }}</flux:subheading>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Session Details --}}
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Session Information') }}</h3>
                    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Class Type') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $classSession->classType?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $classSession->location?->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Schedule') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">
                                @if($classSession->is_recurring)
                                    {{ ['', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$classSession->day_of_week] ?? '' }}
                                @else
                                    {{ $classSession->specific_date?->format('F d, Y') }}
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Time') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">
                                {{ \Carbon\Carbon::parse($classSession->start_time)->format('g:i A') }} - {{ \Carbon\Carbon::parse($classSession->end_time)->format('g:i A') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">
                                {{ $classSession->effective_capacity ?? __('Unlimited') }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                            <dd class="mt-1">
                                <flux:badge :color="$classSession->status === 'active' ? 'emerald' : 'rose'" size="sm">
                                    {{ ucfirst($classSession->status) }}
                                </flux:badge>
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Instructors --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Instructors') }}</h3>
                    <div class="mt-4 space-y-3">
                        <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                <flux:icon name="user" class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $classSession->mainInstructor?->name }}</p>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Main Instructor') }}</p>
                            </div>
                        </div>
                        @foreach($classSession->assistantStaff as $assistant)
                            <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                    <flux:icon name="user" class="h-5 w-5" />
                                </div>
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $assistant->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Assistant') }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Quick Stats --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Booking Stats') }}</h3>
                    @php
                        $confirmed = $classSession->bookings()->whereIn('status', ['pending', 'confirmed'])->count();
                        $attended = $classSession->bookings()->where('status', 'attended')->count();
                        $available = $classSession->effective_capacity ? max(0, $classSession->effective_capacity - $confirmed) : null;
                    @endphp
                    <div class="mt-4 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Confirmed') }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $confirmed }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Attended') }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $attended }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Available') }}</span>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $available ?? '∞' }}</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Actions') }}</h3>
                    <div class="mt-4 space-y-2">
                        @can('update', $classSession)
                        <flux:button variant="ghost" href="{{ route('class-sessions.edit', $classSession) }}" wire:navigate icon="pencil" class="w-full justify-start">
                            {{ __('Edit Session') }}
                        </flux:button>
                        @endcan
                        @can('create', App\Models\ClassBooking::class)
                        <flux:button variant="ghost" href="{{ route('class-bookings.create') }}" wire:navigate icon="plus" class="w-full justify-start">
                            {{ __('Add Booking') }}
                        </flux:button>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </flux:main>
</x-layouts.app.sidebar>

