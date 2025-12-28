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
                placeholder="{{ __('Search workout plans...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="level_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Levels') }}</option>
                <option value="beginner">{{ __('Beginner') }}</option>
                <option value="intermediate">{{ __('Intermediate') }}</option>
                <option value="advanced">{{ __('Advanced') }}</option>
            </flux:select>
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count workout plan|:count workout plans', $workout_plans->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Workout Plan') }}</th>
                @if($show_branch)
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</th>
                @endif
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Level') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Duration') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Days') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Members') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($workout_plans as $workout_plan)
                <tr wire:key="workout-plan-{{ $workout_plan->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $workout_plan->name }}</div>
                        @if($workout_plan->description)
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $workout_plan->description }}</div>
                        @endif
                    </td>
                    @if($show_branch)
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $workout_plan->branch?->name }}</span>
                    </td>
                    @endif
                    <td class="px-6 py-4">
                        @php
                            $level_colors = [
                                'beginner' => 'emerald',
                                'intermediate' => 'amber',
                                'advanced' => 'red',
                            ];
                        @endphp
                        <flux:badge :color="$level_colors[$workout_plan->level] ?? 'zinc'" size="sm">
                            {{ ucfirst($workout_plan->level) }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4">
                        @if($workout_plan->total_weeks)
                            <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                                <flux:icon name="calendar-days" class="h-4 w-4 text-zinc-400" />
                                {{ trans_choice(':count week|:count weeks', $workout_plan->total_weeks) }}
                            </div>
                        @else
                            <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Not set') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $workout_plan->days_count }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $workout_plan->member_workout_plans_count }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <flux:badge :color="$workout_plan->is_active ? 'emerald' : 'zinc'" size="sm">
                            {{ $workout_plan->is_active ? __('Active') : __('Inactive') }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $workout_plan)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('workout-plans.edit', $workout_plan) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('delete', $workout_plan)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteWorkoutPlan({{ $workout_plan->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this workout plan?') }}"
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
                    <td colspan="{{ $show_branch ? 8 : 7 }}" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="document-text" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No workout plans found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by creating your first workout plan.') }}</p>
                            @can('create', App\Models\WorkoutPlan::class)
                            <flux:button variant="primary" href="{{ route('workout-plans.create') }}" wire:navigate class="mt-4">
                                {{ __('Create Workout Plan') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($workout_plans->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $workout_plans->links() }}
            </div>
        @endif
    </div>
</div>

