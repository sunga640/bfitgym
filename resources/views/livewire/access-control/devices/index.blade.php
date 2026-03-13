<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __(':integration Devices', ['integration' => $integration_label]) }}</flux:heading>
            <flux:subheading>{{ __('Monitor device health and assign agents for :integration integration.', ['integration' => $integration_label]) }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route($route_base . '.agents.index') }}" wire:navigate variant="ghost" icon="users">
                {{ __('Agents') }}
            </flux:button>
            <flux:button href="{{ route($route_prefix . '.create') }}" wire:navigate icon="plus">
                {{ __('Add Device') }}
            </flux:button>
        </div>
    </div>

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

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Connection') }}</th>
                        <th class="px-4 py-3">{{ __('Last Heartbeat') }}</th>
                        <th class="px-4 py-3">{{ __('Last Error') }}</th>
                        <th class="px-4 py-3">{{ __('Primary Agent') }}</th>
                        <th class="px-4 py-3">{{ __('Assigned Agents') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($devices as $device)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div>
                                    <a href="{{ route($route_prefix . '.show', $device) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $device->name }}
                                    </a>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $device->serial_number }}</p>
                                </div>
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
                                @if($device->last_heartbeat_at)
                                    <span class="text-zinc-600 dark:text-zinc-400" title="{{ $device->last_heartbeat_at->format('Y-m-d H:i:s') }}">
                                        {{ $device->last_heartbeat_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">{{ __('Never') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($device->last_error)
                                    <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($device->last_error, 120) }}</span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:select wire:model.defer="primary_agent_ids.{{ $device->id }}" class="w-56">
                                        <option value="">{{ __('None') }}</option>
                                        @foreach($agents as $agent)
                                            <option value="{{ $agent->id }}">
                                                {{ $agent->name }} ({{ $agent->status }})
                                            </option>
                                        @endforeach
                                    </flux:select>
                                    <flux:button wire:click="savePrimaryAgent({{ $device->id }})" size="sm" variant="ghost" icon="check" />
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($device->agents->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($device->agents as $a)
                                            <span class="rounded bg-zinc-100 px-2 py-1 text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                                {{ $a->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="mb-2 flex flex-wrap justify-end gap-1">
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
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        href="{{ route($route_prefix . '.show', $device) }}"
                                        wire:navigate
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"
                                        title="{{ __('View') }}"
                                    />
                                    <flux:button
                                        href="{{ route($route_prefix . '.edit', $device) }}"
                                        wire:navigate
                                        variant="ghost"
                                        size="sm"
                                        icon="pencil"
                                        title="{{ __('Edit') }}"
                                    />
                                    <flux:button
                                        wire:click="openAssignModal({{ $device->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="users"
                                        title="{{ __('Assign Agents') }}"
                                    />
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.cpu-chip class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('No devices found') }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Add devices first, then assign agents.') }}</p>
                                    </div>
                                    <flux:button href="{{ route($route_prefix . '.create') }}" wire:navigate icon="plus" size="sm">
                                        {{ __('Add Device') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <flux:modal wire:model="show_assign_modal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <flux:icon.users class="h-5 w-5 text-zinc-700 dark:text-zinc-200" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Assign Agents') }}</flux:heading>
                    <flux:subheading>{{ __('Choose which agents may manage this device.') }}</flux:subheading>
                </div>
            </div>

            @if($assign_device_id)
                <div class="space-y-2">
                    @foreach($agents as $agent)
                        <label class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                            <div class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                    value="{{ $agent->id }}"
                                    wire:model="assigned_agent_ids.{{ $assign_device_id }}"
                                />
                                <span class="text-zinc-900 dark:text-white">{{ $agent->name }}</span>
                            </div>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ strtoupper($agent->os) }} • {{ $agent->status }}</span>
                        </label>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeAssignModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="saveAssignments">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
