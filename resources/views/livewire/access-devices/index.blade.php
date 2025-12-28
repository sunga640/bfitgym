<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Access Control Devices') }}</flux:heading>
            <flux:subheading>{{ __('Manage Hikvision access control devices for your branch.') }}</flux:subheading>
        </div>

        <div class="flex items-center gap-3">
            <flux:button href="{{ route('access-devices.create') }}" wire:navigate icon="plus">
                {{ __('Add Device') }}
            </flux:button>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-zinc-100 dark:bg-zinc-700">
                    <flux:icon.cpu-chip class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $stats['total'] }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Devices') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon.signal class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $stats['online'] }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Online') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                    <flux:icon.signal-slash class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <p class="text-2xl font-semibold text-red-600 dark:text-red-400">{{ $stats['offline'] }}</p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Offline') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search devices...') }}"
                clearable
            />
        </div>

        <div class="flex flex-wrap gap-3">
            <flux:select wire:model.live="status_filter" class="w-40">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
            </flux:select>

            <flux:select wire:model.live="connection_filter" class="w-40">
                <option value="">{{ __('All Connections') }}</option>
                <option value="online">{{ __('Online') }}</option>
                <option value="offline">{{ __('Offline') }}</option>
                <option value="unknown">{{ __('Unknown') }}</option>
            </flux:select>

            @if($locations->count() > 0)
                <flux:select wire:model.live="location_filter" class="w-44">
                    <option value="">{{ __('All Locations') }}</option>
                    @foreach($locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </flux:select>
            @endif
        </div>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Devices Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3">{{ __('Model') }}</th>
                        <th class="px-4 py-3">{{ __('IP Address') }}</th>
                        <th class="px-4 py-3">{{ __('Location') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Connection') }}</th>
                        <th class="px-4 py-3">{{ __('Last Sync') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Commands') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($devices as $device)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $device->name }}</p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $device->serial_number }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $device->device_model }}</span>
                                    <span class="rounded bg-zinc-100 px-1.5 py-0.5 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                        {{ $device->device_type_label }}
                                    </span>
                                </div>
                                <div class="mt-1 flex gap-1">
                                    @if($device->supports_face_recognition)
                                        <span class="rounded bg-blue-100 px-1.5 py-0.5 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">Face</span>
                                    @endif
                                    @if($device->supports_fingerprint)
                                        <span class="rounded bg-purple-100 px-1.5 py-0.5 text-xs text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">FP</span>
                                    @endif
                                    @if($device->supports_card)
                                        <span class="rounded bg-amber-100 px-1.5 py-0.5 text-xs text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">Card</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $device->ip_address }}:{{ $device->port }}</code>
                            </td>
                            <td class="px-4 py-3">
                                @if($device->location)
                                    <span class="text-zinc-700 dark:text-zinc-300">{{ $device->location->name }}</span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="toggleStatus({{ $device->id }})"
                                    wire:loading.attr="disabled"
                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium transition-colors
                                        {{ $device->status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400'
                                            : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-400' }}"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full {{ $device->status === 'active' ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                    {{ $device->status === 'active' ? __('Active') : __('Inactive') }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($device->isHeartbeatStale($stale_minutes))
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" title="{{ __('Last heartbeat is stale') }}">
                                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                        {{ __('Offline') }}
                                    </span>
                                @elseif($device->connection_status === 'online')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                        {{ __('Online') }}
                                    </span>
                                @elseif($device->connection_status === 'offline')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        {{ __('Offline') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                        {{ __('Unknown') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($device->last_sync_at)
                                    <span class="text-zinc-600 dark:text-zinc-400" title="{{ $device->last_sync_at->format('Y-m-d H:i:s') }}">
                                        {{ $device->last_sync_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">{{ __('Never') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-1">
                                    @if(($device->pending_commands_count ?? 0) > 0)
                                        <span class="rounded bg-blue-100 px-2 py-1 text-xs text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ __('Pending') }}: {{ $device->pending_commands_count }}
                                        </span>
                                    @endif
                                    @if(($device->failed_commands_count ?? 0) > 0)
                                        <span class="rounded bg-red-100 px-2 py-1 text-xs text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            {{ __('Failed') }}: {{ $device->failed_commands_count }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        wire:click="openTestModal({{ $device->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="signal"
                                        title="{{ __('Test Connection') }}"
                                    />

                                    <flux:button
                                        wire:click="syncDevice({{ $device->id }})"
                                        wire:loading.attr="disabled"
                                        variant="ghost"
                                        size="sm"
                                        icon="arrow-path"
                                        title="{{ __('Sync Now') }}"
                                    />

                                    <flux:button
                                        href="{{ route('access-devices.edit', $device) }}"
                                        wire:navigate
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        title="{{ __('Edit') }}"
                                    />

                                    <flux:button
                                        wire:click="confirmDelete({{ $device->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="trash"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        title="{{ __('Delete') }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.cpu-chip class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('No devices found') }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Add your first Hikvision access control device to get started.') }}</p>
                                    </div>
                                    <flux:button href="{{ route('access-devices.create') }}" wire:navigate icon="plus" size="sm">
                                        {{ __('Add Device') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($devices->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $devices->links() }}
            </div>
        @endif
    </div>

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.exclamation-triangle class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Delete Device') }}</flux:heading>
                    <flux:subheading>{{ __('This action cannot be undone.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to delete this device? Any configuration and sync history will be permanently removed.') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeModals" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="delete" variant="danger">{{ __('Delete Device') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Test Connection Modal --}}
    <flux:modal wire:model="showTestModal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.signal class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Test Connection') }}</flux:heading>
                    <flux:subheading>{{ __('Verify device connectivity') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('This will attempt to connect to the device and verify its availability.') }}
            </p>

            @if($test_result === 'success')
                <flux:callout variant="success" icon="check-circle">
                    {{ __('Connection successful! Device is online and responding.') }}
                </flux:callout>
            @elseif($test_result === 'error')
                <flux:callout variant="danger" icon="exclamation-circle">
                    {{ session('error') ?? __('Connection failed. Please check device settings.') }}
                </flux:callout>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeModals" variant="ghost">{{ __('Close') }}</flux:button>
                <flux:button
                    wire:click="testConnection"
                    wire:loading.attr="disabled"
                    :disabled="$test_loading"
                >
                    <span wire:loading.remove wire:target="testConnection">{{ __('Test Connection') }}</span>
                    <span wire:loading wire:target="testConnection">{{ __('Testing...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

