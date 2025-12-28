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

    {{-- Search --}}
    <div class="mb-4 flex flex-col gap-4 rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex flex-1 items-center gap-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search roles...') }}"
                icon="magnifying-glass"
                class="max-w-xs"
            />
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count role|:count roles', $roles->count()) }}
        </div>
    </div>

    {{-- Roles Grid --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse($roles as $role)
            <div wire:key="role-{{ $role->id }}" class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="font-semibold text-zinc-900 dark:text-white">
                            {{ ucwords(str_replace('-', ' ', $role->name)) }}
                        </h3>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $role->name }}
                        </p>
                    </div>
                    @if(in_array($role->name, ['super-admin', 'branch-admin']))
                        <flux:badge color="amber" size="sm">{{ __('System') }}</flux:badge>
                    @endif
                </div>

                <div class="mt-4 flex items-center gap-4 text-sm">
                    <div class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="users" class="h-4 w-4" />
                        <span>{{ trans_choice(':count user|:count users', $role->users_count) }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-zinc-500 dark:text-zinc-400">
                        <flux:icon name="key" class="h-4 w-4" />
                        <span>{{ trans_choice(':count permission|:count permissions', $role->permissions_count) }}</span>
                    </div>
                </div>

                <div class="mt-4 flex items-center gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-700">
                    <flux:button
                        variant="ghost"
                        size="sm"
                        href="{{ route('roles.edit', $role) }}"
                        wire:navigate
                        icon="pencil"
                    >
                        {{ __('Edit') }}
                    </flux:button>
                    @unless(in_array($role->name, ['super-admin', 'branch-admin']))
                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="deleteRole({{ $role->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete this role?') }}"
                            icon="trash"
                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            {{ __('Delete') }}
                        </flux:button>
                    @endunless
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-12 text-center dark:border-zinc-700 dark:bg-zinc-900/50">
                <flux:icon name="shield-check" class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No roles found') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create your first role to get started.') }}</p>
                <flux:button variant="primary" href="{{ route('roles.create') }}" wire:navigate icon="plus" class="mt-4">
                    {{ __('Create Role') }}
                </flux:button>
            </div>
        @endforelse
    </div>
</div>

