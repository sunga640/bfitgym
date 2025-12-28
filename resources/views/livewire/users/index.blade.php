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
    <div class="mb-4 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex flex-1 items-center gap-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search users...') }}"
                icon="magnifying-glass"
                class="max-w-xs"
            />
            <flux:select wire:model.live="role_filter" class="max-w-[180px]">
                <option value="">{{ __('All Roles') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}">{{ ucwords(str_replace('-', ' ', $role->name)) }}</option>
                @endforeach
            </flux:select>
            <flux:select wire:model.live="branch_filter" class="max-w-[180px]">
                <option value="">{{ __('All Branches') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count user|:count users', $users->total()) }}
        </div>
    </div>

    {{-- Users Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('User') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Role(s)') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Branch') }}
                    </th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        {{ __('Created') }}
                    </th>
                    <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">{{ __('Actions') }}</span>
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                @forelse($users as $user)
                    <tr wire:key="user-{{ $user->id }}">
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-sm font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                    {{ $user->initials() }}
                                </div>
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ $user->name }}
                                        @if($user->id === auth()->id())
                                            <span class="ml-1 text-xs text-zinc-400">({{ __('You') }})</span>
                                        @endif
                                    </div>
                                    <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->email }}</div>
                                    @if($user->phone)
                                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $user->phone }}</div>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @forelse($user->roles as $role)
                                    <flux:badge
                                        :color="$role->name === 'super-admin' ? 'red' : ($role->name === 'branch-admin' ? 'amber' : 'zinc')"
                                        size="sm"
                                    >
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </flux:badge>
                                @empty
                                    <span class="text-sm text-zinc-400">{{ __('No roles') }}</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-6 py-4">
                            @if($user->branch)
                                <span class="text-sm text-zinc-900 dark:text-white">{{ $user->branch->name }}</span>
                            @else
                                <span class="text-sm text-zinc-400">{{ __('All branches') }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $user->created_at->format('M d, Y') }}
                        </td>
                        <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('users.show', $user) }}"
                                    wire:navigate
                                    icon="eye"
                                >
                                    {{ __('View') }}
                                </flux:button>
                                <flux:button
                                    variant="ghost"
                                    size="sm"
                                    href="{{ route('users.edit', $user) }}"
                                    wire:navigate
                                    icon="pencil"
                                >
                                    {{ __('Edit') }}
                                </flux:button>
                                @if($user->id !== auth()->id())
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        wire:click="deleteUser({{ $user->id }})"
                                        wire:confirm="{{ __('Are you sure you want to delete this user?') }}"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                    >
                                        {{ __('Delete') }}
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <flux:icon name="users" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No users found') }}</h3>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($search || $role_filter || $branch_filter)
                                        {{ __('Try adjusting your search or filters.') }}
                                    @else
                                        {{ __('Get started by creating your first user.') }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($users->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $users->links() }}
            </div>
        @endif
    </div>
</div>

