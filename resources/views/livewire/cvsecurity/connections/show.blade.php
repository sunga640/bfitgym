<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $connection->name }}</flux:heading>
            <flux:subheading>{{ __('Branch #:id • CVSecurity + local-agent bridge', ['id' => $connection->branch_id]) }}</flux:subheading>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button variant="ghost" href="{{ route('zkteco.connections.index') }}" wire:navigate>{{ __('Back') }}</flux:button>
            @if($can_manage)
                <flux:button variant="filled" href="{{ route('zkteco.connections.edit', $connection) }}" wire:navigate>{{ __('Edit') }}</flux:button>
            @endif
        </div>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>{{ session('success') }}</flux:callout>
    @endif
    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>{{ session('error') }}</flux:callout>
    @endif

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Pairing') }}</p>
            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ strtoupper($connection->pairing_status) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Status: :status', ['status' => strtoupper($connection->status)]) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Agent') }}</p>
            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ strtoupper($connection->agent_status) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Last heartbeat: :at', ['at' => $connection->last_heartbeat_at?->diffForHumans() ?? __('never')]) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('CVSecurity') }}</p>
            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ strtoupper($connection->cvsecurity_status) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Last tested: :at', ['at' => $connection->last_tested_at?->diffForHumans() ?? __('never')]) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Sync Queue') }}</p>
            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ number_format($connection->pending_sync_items_count) }}</p>
            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Failed: :count', ['count' => number_format($connection->failed_sync_items_count)]) }}</p>
        </div>
    </div>

    @if($can_manage)
        <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:grid-cols-2 lg:grid-cols-4">
            <flux:button variant="primary" wire:click="generatePairingToken">{{ __('Generate Pairing Token') }}</flux:button>
            <flux:button variant="ghost" wire:click="testConnection">{{ __('Test CVSecurity Connection') }}</flux:button>
            <flux:button variant="ghost" wire:click="syncMembersNow">{{ __('Sync Members Now') }}</flux:button>
            <flux:button variant="ghost" wire:click="pullLatestEvents">{{ __('Pull Latest Events') }}</flux:button>
            <flux:button variant="danger" wire:click="disconnect">{{ __('Disconnect') }}</flux:button>
            <flux:button variant="filled" wire:click="disable">{{ __('Disable Integration') }}</flux:button>
        </div>
    @endif

    @if($generated_pairing_token)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800/70 dark:bg-emerald-900/20">
            <flux:heading size="lg">{{ __('Pairing Token (One-Time Display)') }}</flux:heading>
            <p class="mt-2 text-sm text-emerald-800 dark:text-emerald-200">
                {{ __('Use this token in the local-agent setup. Expires at :time', ['time' => $generated_pairing_token_expires_at]) }}
            </p>
            <div class="mt-3 rounded-lg bg-white px-3 py-2 font-mono text-sm text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
                {{ $generated_pairing_token }}
            </div>
        </div>
    @endif

    @if($connection->last_error)
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700 dark:border-rose-800/60 dark:bg-rose-900/20 dark:text-rose-200">
            <strong>{{ __('Last Error:') }}</strong> {{ $connection->last_error }}
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Recent Events') }}</flux:heading>
            </div>
            <div class="max-h-[26rem] overflow-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 text-xs uppercase text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-3 py-2">{{ __('Occurred') }}</th>
                        <th class="px-3 py-2">{{ __('Type') }}</th>
                        <th class="px-3 py-2">{{ __('Member') }}</th>
                        <th class="px-3 py-2">{{ __('Device') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($recent_events as $event)
                        <tr>
                            <td class="px-3 py-2">{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-3 py-2">{{ $event->event_type }}</td>
                            <td class="px-3 py-2">
                                @if($event->member)
                                    {{ $event->member->first_name }} {{ $event->member->last_name }}
                                    <span class="text-xs text-zinc-500">({{ $event->member->member_no }})</span>
                                @else
                                    {{ $event->external_person_id ?: __('Unknown') }}
                                @endif
                            </td>
                            <td class="px-3 py-2">{{ $event->device_id ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-8 text-center text-zinc-500">{{ __('No events yet.') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
                <flux:heading size="lg">{{ __('Activity Log') }}</flux:heading>
            </div>
            <div class="space-y-3 p-4">
                @forelse($connection->activityLogs as $log)
                    <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ $log->event }}</p>
                                <p class="mt-1 text-zinc-600 dark:text-zinc-300">{{ $log->message ?: __('No details') }}</p>
                            </div>
                            <flux:badge size="sm" color="{{ $log->level === 'error' ? 'rose' : ($log->level === 'warning' ? 'amber' : 'zinc') }}">
                                {{ strtoupper($log->level) }}
                            </flux:badge>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">{{ $log->created_at?->diffForHumans() }}</p>
                    </div>
                @empty
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No activity logs yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

