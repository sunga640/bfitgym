<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route($route_prefix . '.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <flux:heading size="xl">{{ $device->name }}</flux:heading>
                <flux:subheading>{{ $integration_label }} • {{ $device->serial_number }}</flux:subheading>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route($route_prefix . '.edit', $device) }}" wire:navigate icon="pencil" size="sm">
                {{ __('Edit') }}
            </flux:button>
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

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Device Info --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Device Information') }}</h3>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Model') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $device->model_name }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Type') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $device->device_type_label }}</dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($device->status === 'active')
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                    {{ __('Active') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                    {{ __('Inactive') }}
                                </span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Connection') }}</dt>
                        <dd class="mt-1">
                            @if($device->connection_status === 'online')
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
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('IP Address') }}</dt>
                        <dd class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">{{ $device->ip_address }}:{{ $device->port }}</dd>
                    </div>

                    @if($device->location)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Location') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $device->location->name }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last Heartbeat') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">
                            @if($device->last_heartbeat_at)
                                <span title="{{ $device->last_heartbeat_at->format('Y-m-d H:i:s') }}">
                                    {{ $device->last_heartbeat_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-zinc-400">{{ __('Never') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last Sync') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">
                            @if($device->last_sync_at)
                                <span title="{{ $device->last_sync_at->format('Y-m-d H:i:s') }}">
                                    {{ $device->last_sync_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-zinc-400">{{ __('Never') }}</span>
                            @endif
                        </dd>
                    </div>

                    @if($device->last_error)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last Error') }}</dt>
                            <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $device->last_error }}</dd>
                        </div>
                    @endif
                </dl>

                {{-- Capabilities --}}
                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <h4 class="mb-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Capabilities') }}</h4>
                    <div class="flex flex-wrap gap-2">
                        @if($device->supports_face_recognition)
                            <span class="rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ __('Face') }}
                            </span>
                        @endif
                        @if($device->supports_fingerprint)
                            <span class="rounded-full bg-purple-100 px-2 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                                {{ __('Fingerprint') }}
                            </span>
                        @endif
                        @if($device->supports_card)
                            <span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                {{ __('Card') }}
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Primary Agent --}}
                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <h4 class="mb-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Primary Agent') }}</h4>
                    <div class="flex items-center gap-2">
                        <select
                            wire:change="setPrimaryAgent($event.target.value)"
                            class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white"
                        >
                            <option value="">{{ __('None') }}</option>
                            @foreach($available_agents as $agent)
                                <option value="{{ $agent->id }}" @selected($device->access_control_agent_id == $agent->id)>
                                    {{ $agent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>

        {{-- Assigned Agents & Commands --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Assigned Agents --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Assigned Agents') }}</h3>
                    <flux:button wire:click="openAssignModal" size="sm" icon="plus">
                        {{ __('Assign') }}
                    </flux:button>
                </div>

                @if($device->agents->count() > 0)
                    <div class="space-y-2">
                        @foreach($device->agents as $agent)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <flux:icon.cpu-chip class="h-4 w-4 text-zinc-600 dark:text-zinc-300" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $agent->name }}</p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $agent->uuid }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($agent->is_online)
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ __('Online') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ __('Offline') }}
                                        </span>
                                    @endif
                                    <flux:button wire:click="confirmUnassign({{ $agent->id }})" variant="ghost" size="sm" icon="x-mark" class="text-red-600" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center gap-3 py-8 text-center">
                        <flux:icon.users class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ __('No agents assigned') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Assign agents to manage this device.') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Recent Commands --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Recent Commands') }}</h3>

                @if($recent_commands->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-zinc-200 text-xs uppercase text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
                                <tr>
                                    <th class="pb-2">{{ __('Type') }}</th>
                                    <th class="pb-2">{{ __('Status') }}</th>
                                    <th class="pb-2">{{ __('Agent') }}</th>
                                    <th class="pb-2">{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($recent_commands as $command)
                                    <tr>
                                        <td class="py-2">
                                            <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $command->type }}</code>
                                        </td>
                                        <td class="py-2">
                                            @if($command->status === 'done')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                                    {{ __('Done') }}
                                                </span>
                                            @elseif($command->status === 'failed')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                                    {{ __('Failed') }}
                                                </span>
                                            @elseif($command->status === 'processing')
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                                    {{ __('Processing') }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                                    {{ ucfirst($command->status) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-zinc-600 dark:text-zinc-300">
                                            {{ $command->claimedByAgent?->name ?? '—' }}
                                        </td>
                                        <td class="py-2 text-zinc-500 dark:text-zinc-400">
                                            {{ $command->created_at->diffForHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        {{ __('No commands yet.') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Assign Agents Modal --}}
    <flux:modal wire:model="show_assign_modal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <flux:icon.users class="h-5 w-5 text-zinc-700 dark:text-zinc-200" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Assign Agents') }}</flux:heading>
                    <flux:subheading>{{ __('Select agents to manage this device.') }}</flux:subheading>
                </div>
            </div>

            <div class="max-h-64 space-y-2 overflow-y-auto">
                @foreach($available_agents as $agent)
                    <label class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                value="{{ $agent->id }}"
                                wire:model="selected_agent_ids"
                            />
                            <span class="text-zinc-900 dark:text-white">{{ $agent->name }}</span>
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $agent->is_online ? __('Online') : __('Offline') }}
                        </span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeAssignModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="saveAssignments">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Unassign Confirmation Modal --}}
    <flux:modal wire:model="show_unassign_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.x-mark class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Unassign Agent') }}</flux:heading>
                    <flux:subheading>{{ __('This agent will no longer manage this device.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to unassign this agent from the device?') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeUnassignModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="unassignAgent" variant="danger">{{ __('Unassign') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
