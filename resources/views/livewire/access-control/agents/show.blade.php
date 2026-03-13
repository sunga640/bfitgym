<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <flux:button href="{{ route($route_base . '.agents.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
            <div>
                <div class="flex items-center gap-3">
                    <flux:heading size="xl">{{ $agent->name }}</flux:heading>
                    @if($agent->status === 'active')
                        @if($agent->is_online)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-500"></span>
                                {{ __('Online') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                {{ __('Offline') }}
                            </span>
                        @endif
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                            {{ __('Revoked') }}
                        </span>
                    @endif
                </div>
                <flux:subheading>
                    {{ $integration_label }} • <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $agent->uuid }}</code>
                </flux:subheading>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($agent->status === 'active')
                <flux:button wire:click="confirmRevoke" variant="ghost" icon="no-symbol" class="text-red-600">
                    {{ __('Revoke') }}
                </flux:button>
            @endif
            <flux:button wire:click="confirmDelete" variant="danger" icon="trash">
                {{ __('Delete') }}
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
        {{-- Agent Info --}}
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Agent Information') }}</h3>

                <dl class="space-y-4">
                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Operating System') }}</dt>
                        <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ strtoupper($agent->os ?? 'Unknown') }}</dd>
                    </div>

                    @if($agent->app_version)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('App Version') }}</dt>
                            <dd class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $agent->app_version }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last Seen') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">
                            @if($agent->last_seen_at)
                                <span title="{{ $agent->last_seen_at->format('Y-m-d H:i:s') }}">
                                    {{ $agent->last_seen_at->diffForHumans() }}
                                </span>
                            @else
                                <span class="text-zinc-400">{{ __('Never') }}</span>
                            @endif
                        </dd>
                    </div>

                    @if($agent->last_ip)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last IP') }}</dt>
                            <dd class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">{{ $agent->last_ip }}</dd>
                        </div>
                    @endif

                    @if($agent->last_error)
                        <div>
                            <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Last Error') }}</dt>
                            <dd class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $agent->last_error }}</dd>
                        </div>
                    @endif

                    <div>
                        <dt class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Created') }}</dt>
                        <dd class="mt-1 text-zinc-900 dark:text-white">{{ $agent->created_at->format('M d, Y H:i') }}</dd>
                    </div>
                </dl>

                {{-- Command Stats --}}
                <div class="mt-6 border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <h4 class="mb-3 text-sm font-medium text-zinc-900 dark:text-white">{{ __('Command Stats (Recent 50)') }}</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900/50">
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $command_stats['done'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Completed') }}</p>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-3 text-center dark:bg-zinc-900/50">
                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ $command_stats['failed'] }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Failed') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Devices & Commands --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Assigned Devices --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Assigned Devices') }}</h3>
                    <flux:button wire:click="openAssignModal" size="sm" icon="plus">
                        {{ __('Assign') }}
                    </flux:button>
                </div>

                @if($agent->devices->count() > 0)
                    <div class="space-y-2">
                        @foreach($agent->devices as $device)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-zinc-200 dark:bg-zinc-700">
                                        <flux:icon.computer-desktop class="h-4 w-4 text-zinc-600 dark:text-zinc-300" />
                                    </div>
                                    <div>
                                        <a href="{{ route($route_base . '.devices.show', $device) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                            {{ $device->name }}
                                        </a>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $device->serial_number }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($device->connection_status === 'online')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ __('Online') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ __('Offline') }}
                                        </span>
                                    @endif
                                    <flux:button wire:click="confirmUnassignDevice({{ $device->id }})" variant="ghost" size="sm" icon="x-mark" class="text-red-600" />
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex flex-col items-center gap-3 py-8 text-center">
                        <flux:icon.computer-desktop class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <div>
                            <p class="font-medium text-zinc-900 dark:text-white">{{ __('No devices assigned') }}</p>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Assign devices for this agent to manage.') }}</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Enrollment History --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-4 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Enrollment History') }}</h3>

                @if($enrollments->count() > 0)
                    <div class="space-y-2">
                        @foreach($enrollments as $enrollment)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        {{ $enrollment->label ?? __('Enrollment #:id', ['id' => $enrollment->id]) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ __('Created by :name', ['name' => $enrollment->createdBy?->name ?? __('Unknown')]) }}
                                        • {{ $enrollment->created_at->format('M d, Y H:i') }}
                                    </p>
                                </div>
                                <div>
                                    @if($enrollment->computed_status === 'used')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                            {{ __('Used') }}
                                        </span>
                                    @elseif($enrollment->computed_status === 'expired')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                            {{ __('Expired') }}
                                        </span>
                                    @elseif($enrollment->computed_status === 'revoked')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                            {{ __('Revoked') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                            {{ __('Active') }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-8 text-center text-zinc-500 dark:text-zinc-400">
                        {{ __('No enrollment history.') }}
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
                                    <th class="pb-2">{{ __('Device') }}</th>
                                    <th class="pb-2">{{ __('Type') }}</th>
                                    <th class="pb-2">{{ __('Status') }}</th>
                                    <th class="pb-2">{{ __('Created') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @foreach($recent_commands->take(20) as $command)
                                    <tr>
                                        <td class="py-2 text-zinc-900 dark:text-white">
                                            {{ $command->device?->name ?? '—' }}
                                        </td>
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

    {{-- Assign Devices Modal --}}
    <flux:modal wire:model="show_assign_modal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-700">
                    <flux:icon.computer-desktop class="h-5 w-5 text-zinc-700 dark:text-zinc-200" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Assign Devices') }}</flux:heading>
                    <flux:subheading>{{ __('Select devices for this agent to manage.') }}</flux:subheading>
                </div>
            </div>

            <div class="max-h-64 space-y-2 overflow-y-auto">
                @foreach($available_devices as $device)
                    <label class="flex items-center justify-between rounded-lg border border-zinc-200 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900/50">
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                value="{{ $device->id }}"
                                wire:model="selected_device_ids"
                            />
                            <span class="text-zinc-900 dark:text-white">{{ $device->name }}</span>
                        </div>
                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $device->serial_number }}</span>
                    </label>
                @endforeach
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeAssignModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="saveDeviceAssignments">{{ __('Save') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Unassign Device Modal --}}
    <flux:modal wire:model="show_unassign_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.x-mark class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Unassign Device') }}</flux:heading>
                    <flux:subheading>{{ __('This agent will no longer manage this device.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to unassign this device?') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeUnassignModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="unassignDevice" variant="danger">{{ __('Unassign') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Revoke Token Modal --}}
    <flux:modal wire:model="show_revoke_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.no-symbol class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Revoke Agent Token') }}</flux:heading>
                    <flux:subheading>{{ __('The agent will be unable to authenticate.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to revoke this agent\'s token? This action cannot be undone. The agent will need to be re-enrolled.') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeRevokeModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="revokeToken" variant="danger">{{ __('Revoke Token') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Delete Agent Modal --}}
    <flux:modal wire:model="show_delete_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.trash class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Delete Agent') }}</flux:heading>
                    <flux:subheading>{{ __('Permanently remove this agent.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to delete this agent? All device assignments will be removed. This action cannot be undone.') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeDeleteModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="deleteAgent" variant="danger">{{ __('Delete') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
