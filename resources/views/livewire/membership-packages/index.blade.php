<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="h-5 w-5" />
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    @if(session('warning'))
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="h-5 w-5" />
                <span>{{ session('warning') }}</span>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            <div class="flex items-center gap-2">
                <flux:icon name="x-circle" class="h-5 w-5" />
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="mb-6 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex flex-1 flex-wrap items-center gap-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search packages...') }}"
                icon="magnifying-glass"
                class="max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="max-w-[160px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </flux:select>
            <flux:select wire:model.live="duration_type_filter" class="max-w-[160px]">
                <option value="">{{ __('All Durations') }}</option>
                @foreach($duration_types as $type)
                    <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                @endforeach
            </flux:select>
            @if($search || $status_filter || $duration_type_filter)
                <flux:button variant="ghost" size="sm" wire:click="clearFilters" icon="x-mark">
                    {{ __('Clear') }}
                </flux:button>
            @endif
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count package|:count packages', $packages->total()) }}
        </div>
    </div>

    {{-- Packages Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
        @forelse($packages as $package)
            <div wire:key="package-{{ $package->id }}" class="group relative overflow-hidden rounded-xl border border-zinc-200 bg-white transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                {{-- Status Badge --}}
                <div class="absolute right-3 top-3">
                    <flux:badge
                        :color="$package->status === 'active' ? 'green' : 'zinc'"
                        size="sm"
                    >
                        {{ ucfirst($package->status) }}
                    </flux:badge>
                </div>

                {{-- Package Info --}}
                <div class="p-5">
                    <h3 class="pr-16 text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $package->name }}
                    </h3>

                    @if($package->description)
                        <p class="mt-2 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $package->description }}
                        </p>
                    @endif

                    {{-- Price --}}
                    <div class="mt-4">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ number_format($package->price, 0) }}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ current_branch()?->currency ?? 'TZS' }}
                        </span>
                    </div>

                    {{-- Duration --}}
                    <div class="mt-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <flux:icon name="calendar" class="h-4 w-4 text-zinc-400" />
                        <span>{{ $package->formatted_duration }}</span>
                    </div>

                    {{-- Features & Stats --}}
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        @if($package->is_renewable)
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                <flux:icon name="arrow-path" class="h-3 w-3" />
                                {{ __('Renewable') }}
                            </span>
                        @endif
                        @if($package->active_subscriptions_count > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-50 px-2 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-300">
                                <flux:icon name="users" class="h-3 w-3" />
                                {{ trans_choice(':count active|:count active', $package->active_subscriptions_count) }}
                            </span>
                        @elseif($package->subscriptions_count > 0)
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                <flux:icon name="users" class="h-3 w-3" />
                                {{ trans_choice(':count subscriber|:count subscribers', $package->subscriptions_count) }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                <flux:icon name="users" class="h-3 w-3" />
                                {{ __('No subscribers') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between border-t border-zinc-100 px-5 py-3 dark:border-zinc-700">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        wire:click="toggleStatus({{ $package->id }})"
                        wire:loading.attr="disabled"
                        wire:target="toggleStatus({{ $package->id }})"
                        icon="{{ $package->status === 'active' ? 'pause' : 'play' }}"
                    >
                        <span wire:loading.remove wire:target="toggleStatus({{ $package->id }})">
                            {{ $package->status === 'active' ? __('Deactivate') : __('Activate') }}
                        </span>
                        <span wire:loading wire:target="toggleStatus({{ $package->id }})">
                            {{ __('Updating...') }}
                        </span>
                    </flux:button>

                    <div class="flex items-center gap-1">
                        <flux:button
                            variant="ghost"
                            size="sm"
                            href="{{ route('membership-packages.edit', $package) }}"
                            wire:navigate
                            icon="pencil"
                        >
                            {{ __('Edit') }}
                        </flux:button>
                        @if($package->subscriptions_count === 0)
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deletePackage({{ $package->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this package? This action cannot be undone.') }}"
                                wire:loading.attr="disabled"
                                wire:target="deletePackage({{ $package->id }})"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                <span wire:loading.remove wire:target="deletePackage({{ $package->id }})">
                                    {{ __('Delete') }}
                                </span>
                                <span wire:loading wire:target="deletePackage({{ $package->id }})">
                                    {{ __('Deleting...') }}
                                </span>
                            </flux:button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                <flux:icon name="credit-card" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">
                    {{ __('No membership packages found') }}
                </h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    @if($search || $status_filter || $duration_type_filter)
                        {{ __('Try adjusting your search or filters.') }}
                    @else
                        {{ __('Create your first membership package to get started.') }}
                    @endif
                </p>
                @if($search || $status_filter || $duration_type_filter)
                    <flux:button variant="ghost" wire:click="clearFilters" icon="x-mark" class="mt-4">
                        {{ __('Clear Filters') }}
                    </flux:button>
                @else
                    <flux:button variant="primary" href="{{ route('membership-packages.create') }}" wire:navigate icon="plus" class="mt-4">
                        {{ __('Create Package') }}
                    </flux:button>
                @endif
            </div>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if($packages->hasPages())
        <div class="mt-6">
            {{ $packages->links() }}
        </div>
    @endif
</div>
