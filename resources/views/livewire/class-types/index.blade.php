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
                placeholder="{{ __('Search class types...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </flux:select>
            <flux:select wire:model.live="booking_fee_filter" class="w-full md:max-w-[180px]">
                <option value="">{{ __('All Booking Fees') }}</option>
                <option value="1">{{ __('Has Booking Fee') }}</option>
                <option value="0">{{ __('No Booking Fee') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count class type|:count class types', $class_types->total()) }}
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Class Type') }}</th>
                @if($show_branch)
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</th>
                @endif
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Capacity') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Booking Fee') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Sessions') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($class_types as $class_type)
                <tr wire:key="class-type-{{ $class_type->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $class_type->name }}</div>
                        @if($class_type->description)
                            <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-1">{{ $class_type->description }}</div>
                        @endif
                    </td>
                    @if($show_branch)
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $class_type->branch?->name }}</span>
                    </td>
                    @endif
                    <td class="px-6 py-4">
                        @if($class_type->capacity)
                            <div class="flex items-center gap-1.5 text-sm text-zinc-700 dark:text-zinc-200">
                                <flux:icon name="users" class="h-4 w-4 text-zinc-400" />
                                {{ $class_type->capacity }}
                            </div>
                        @else
                            <span class="text-sm text-zinc-400 dark:text-zinc-500">{{ __('Unlimited') }}</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($class_type->has_booking_fee)
                            <div class="flex items-center gap-1.5">
                                <flux:badge color="emerald" size="sm">
                                    {{ number_format($class_type->booking_fee, 2) }}
                                </flux:badge>
                            </div>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('Free') }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $class_type->sessions_count }}</span>
                    </td>
                    <td class="px-6 py-4">
                        <flux:badge :color="$class_type->status === 'active' ? 'emerald' : 'zinc'" size="sm">
                            {{ ucfirst($class_type->status) }}
                        </flux:badge>
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            @can('update', $class_type)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('class-types.edit', $class_type) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('delete', $class_type)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteClassType({{ $class_type->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this class type?') }}"
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
                    <td colspan="{{ $show_branch ? 7 : 6 }}" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="rectangle-stack" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No class types found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by creating your first class type.') }}</p>
                            @can('create', App\Models\ClassType::class)
                            <flux:button variant="primary" href="{{ route('class-types.create') }}" wire:navigate class="mt-4">
                                {{ __('Create Class Type') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($class_types->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $class_types->links() }}
            </div>
        @endif
    </div>
</div>

