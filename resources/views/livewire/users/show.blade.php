<div>
    <div class="mx-auto max-w-4xl">
        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mb-6 rounded-lg bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/20 dark:text-red-200">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Main Info --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Profile Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Staff Information') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-6">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ $user->initials() }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $user->name }}</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Staff ID') }}: STAFF-{{ $user->id }}</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $user->email }}</p>
                                    </div>
                                    @if($user->phone)
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $user->phone }}</p>
                                    </div>
                                    @endif
                                    @if($user->branch)
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $user->branch->name }}</p>
                                    </div>
                                    @endif
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member Since') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $user->created_at?->format('F d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Roles Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Roles & Permissions') }}</h2>
                    </div>
                    <div class="p-6">
                        @if($user->roles->count() > 0)
                            <div class="flex flex-wrap gap-2">
                                @foreach($user->roles as $role)
                                    <flux:badge
                                        :color="$role->name === 'super-admin' ? 'red' : ($role->name === 'branch-admin' ? 'amber' : 'zinc')"
                                        size="sm"
                                    >
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </flux:badge>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No roles assigned.') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Status Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Status') }}</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Account Status') }}</span>
                            <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</span>
                            @if($user->branch)
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">{{ $user->branch->name }}</span>
                            @else
                                <flux:badge color="amber">{{ __('Not Assigned') }}</flux:badge>
                            @endif
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Two-Factor Auth') }}</span>
                            <flux:badge :color="$user->two_factor_confirmed_at ? 'emerald' : 'zinc'">
                                {{ $user->two_factor_confirmed_at ? __('Enabled') : __('Disabled') }}
                            </flux:badge>
                        </div>
                    </div>
                </div>

                {{-- Device Access --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Device Access') }}</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        {{-- Staff access type badge --}}
                        <div class="flex items-center justify-between rounded-lg bg-blue-50 p-3 text-sm text-blue-800 dark:bg-blue-900/20 dark:text-blue-200">
                            <span class="font-medium">{{ __('Staff Access') }}</span>
                            <span class="text-xs">{{ __('Long-term validity') }}</span>
                        </div>

                        {{-- Last sync command hint --}}
                        @if($this->last_access_set_validity_command)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 text-xs text-zinc-600 dark:border-zinc-700 dark:text-zinc-300">
                                <div class="space-y-0.5">
                                    <div class="font-medium text-zinc-800 dark:text-zinc-100">
                                        {{ __('Last device command') }}:
                                        <span class="uppercase">{{ $this->last_command_action ?? '—' }}</span>
                                    </div>
                                    <div>
                                        {{ __('Status') }}: <span class="font-medium">{{ $this->last_command_status ?? '—' }}</span>
                                    </div>
                                </div>
                                @if($this->last_command_is_pending)
                                    <span class="rounded-md bg-amber-100 px-2 py-1 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                                        {{ __('Pending…') }}
                                    </span>
                                @endif
                            </div>
                        @endif

                        {{-- Last sync error --}}
                        @if($access_identity?->last_sync_error)
                            <div class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800 dark:bg-rose-900/20 dark:text-rose-200">
                                <span class="font-medium">{{ __('Last sync error') }}:</span>
                                {{ Str::limit($access_identity->last_sync_error, 100) }}
                            </div>
                        @endif

                        @if($has_fingerprint_registered)
                            {{-- Synced to device - Show status --}}
                            @if($has_fingerprint_access)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                                    <div class="flex-shrink-0">
                                        <flux:icon name="check-badge" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ __('Synced & Active') }}</p>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                            {{ __('Employee No: :id', ['id' => $access_identity?->device_user_id ?? '-']) }}
                                        </p>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                            {{ __('Long-term access (staff)') }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                                    <div class="flex-shrink-0">
                                        <flux:icon name="pause-circle" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('Synced but Disabled') }}</p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">
                                            {{ __('Employee No: :id', ['id' => $access_identity?->device_user_id ?? '-']) }}
                                        </p>
                                        @if($access_identity?->disabled_at)
                                            <p class="text-xs text-amber-600 dark:text-amber-400">
                                                {{ __('Disabled at: :date', ['date' => $access_identity->disabled_at->format('d M Y H:i')]) }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @can('edit users')
                            <div class="flex gap-2">
                                @if($this->last_command_failed)
                                    <div class="flex-1 rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
                                        {{ __('Last device command failed. You can retry.') }}
                                    </div>
                                @endif

                                @php
                                    $ui_is_disabled = $this->disabled_requested_recently || !$has_fingerprint_access;
                                @endphp

                                {{-- Primary action based on heuristic state --}}
                                @if($ui_is_disabled)
                                    <flux:button
                                        variant="primary"
                                        class="flex-1"
                                        icon="play"
                                        wire:click="enableFingerprint"
                                        :disabled="$this->last_command_is_pending"
                                    >
                                        {{ $this->last_command_is_pending ? __('Pending…') : __('Enable') }}
                                    </flux:button>
                                @else
                                    <flux:button
                                        variant="filled"
                                        color="amber"
                                        class="flex-1"
                                        icon="pause"
                                        wire:click="disableFingerprint"
                                        wire:confirm="{{ __('Disable device access for this staff member?') }}"
                                        :disabled="$this->last_command_is_pending"
                                    >
                                        {{ $this->last_command_is_pending ? __('Pending…') : __('Disable') }}
                                    </flux:button>
                                @endif

                                <flux:button variant="danger" icon="trash" wire:click="removeFingerprint" wire:confirm="{{ __('Permanently remove device access? This will delete the record.') }}" />
                            </div>
                            @endcan

                        @elseif($this->hasPendingSync)
                            {{-- Pending sync - waiting for command to complete --}}
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20">
                                <div class="flex-shrink-0">
                                    <flux:icon name="arrow-path" class="h-8 w-8 text-blue-600 dark:text-blue-400 animate-spin" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-200">{{ __('Sync Pending') }}</p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">
                                        {{ __('Employee No: :id', ['id' => $access_identity?->device_user_id ?? '-']) }}
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">
                                        {{ __('Syncing user to device...') }}
                                    </p>
                                </div>
                            </div>
                            @can('edit users')
                            <div class="flex gap-2">
                                <flux:button variant="ghost" class="flex-1" icon="arrow-path" wire:click="$refresh">
                                    {{ __('Refresh Status') }}
                                </flux:button>
                                <flux:button variant="danger" icon="trash" wire:click="removeFingerprint" wire:confirm="{{ __('Cancel sync and remove access record?') }}" />
                            </div>
                            @endcan

                        @else
                            {{-- Not synced to device --}}
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-100 dark:bg-zinc-700/50">
                                <div class="flex-shrink-0">
                                    <flux:icon name="user-plus" class="h-8 w-8 text-zinc-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Not Synced') }}</p>
                                    @if($user->branch_id)
                                        <p class="text-xs text-zinc-500">{{ __('Sync user to device for access control') }}</p>
                                    @else
                                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('Requires branch assignment') }}</p>
                                    @endif
                                </div>
                            </div>
                            @can('edit users')
                                @if($this->canSyncToDevice)
                                    <flux:button variant="primary" class="w-full" icon="arrow-up-tray" wire:click="syncUserToDevice">
                                        {{ __('Sync User to Device') }}
                                    </flux:button>
                                    <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Add fingerprint later via device web dashboard.') }}
                                    </p>
                                @else
                                    <flux:button variant="ghost" class="w-full" icon="arrow-up-tray" disabled>
                                        {{ __('Sync User to Device') }}
                                    </flux:button>
                                    @if(!$user->branch_id)
                                        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('Assign a branch first to enable device access.') }}
                                        </p>
                                    @endif
                                @endif
                            @endcan
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Quick Actions') }}</h2>
                    </div>
                    <div class="space-y-2 p-4">
                        @can('edit users')
                        <flux:button variant="ghost" class="w-full justify-start" icon="pencil" href="{{ route('users.edit', $user) }}" wire:navigate>
                            {{ __('Edit User') }}
                        </flux:button>
                        @endcan
                    </div>
                </div>

                {{-- Back Button --}}
                <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate class="w-full" icon="arrow-left">
                    {{ __('Back to Users') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>

