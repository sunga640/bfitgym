<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('ZKTeco Settings') }}</flux:heading>
            <flux:subheading>{{ __('Connect FitHub to the branch ZKBio server and map turnstile devices.') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('zkteco.overview') }}" wire:navigate variant="ghost">
            {{ __('Back to Overview') }}
        </flux:button>
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

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Connection Status') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ strtoupper($health['status']) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Successful Test') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_test_success_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Personnel Sync') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_personnel_sync_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Event Sync') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_event_sync_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Online Devices') }}</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ number_format($health['online_devices_count']) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Mapped Devices') }}</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ number_format($health['mapped_devices_count']) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Biometric Pending') }}</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">{{ number_format($health['pending_biometrics_count']) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Connection') }}</p>
                <p class="mt-1 font-semibold text-zinc-900 dark:text-white">
                    {{ $connection ? __('Configured') : __('Not Configured') }}
                </p>
            </div>
        </div>

        @if($health['last_error'])
            <p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ $health['last_error'] }}
            </p>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Connection Details') }}</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Enter the server URL or IP manually. Browser-side LAN discovery is not reliable for server-to-server integration.') }}
            </p>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('ZKBio Base URL or Host') }}</flux:label>
                    <flux:input wire:model="base_url" placeholder="https://zkbio.example.com or 192.168.1.20" />
                    <flux:error name="base_url" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Port (Optional)') }}</flux:label>
                    <flux:input wire:model="port" type="number" placeholder="443" />
                    <flux:error name="port" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Timeout (Seconds)') }}</flux:label>
                    <flux:input wire:model="timeout_seconds" type="number" min="1" max="120" />
                    <flux:error name="timeout_seconds" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Username (Optional)') }}</flux:label>
                    <flux:input wire:model="username" />
                    <flux:error name="username" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password (Optional)') }}</flux:label>
                    <flux:input wire:model="password" type="password" placeholder="{{ $has_saved_password ? 'Saved (leave blank to keep)' : '' }}" />
                    <flux:error name="password" />
                    @if($has_saved_password)
                        <label class="mt-2 inline-flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <input type="checkbox" wire:model="clear_password" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>{{ __('Clear stored password') }}</span>
                        </label>
                    @endif
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('API Key / Secret (Optional)') }}</flux:label>
                    <flux:input wire:model="api_key" type="password" placeholder="{{ $has_saved_api_key ? 'Saved (leave blank to keep)' : '' }}" />
                    <flux:error name="api_key" />
                    @if($has_saved_api_key)
                        <label class="mt-2 inline-flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                            <input type="checkbox" wire:model="clear_api_key" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>{{ __('Clear stored API key') }}</span>
                        </label>
                    @endif
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="ssl_enabled" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>{{ __('Use SSL / HTTPS') }}</span>
                        </label>

                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="allow_self_signed" class="rounded border-zinc-300 dark:border-zinc-600">
                            <span>{{ __('Allow Self-Signed Certificates') }}</span>
                        </label>
                    </div>
                </flux:field>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <flux:button type="submit" variant="primary">{{ __('Save Settings') }}</flux:button>
                <flux:button type="button" wire:click="testConnection" variant="ghost">{{ __('Test Connection') }}</flux:button>
                <flux:button type="button" wire:click="fetchDevices" variant="ghost">{{ __('Fetch Devices') }}</flux:button>
                <flux:button type="button" wire:click="syncPersonnel" variant="ghost">{{ __('Sync Personnel') }}</flux:button>
                <flux:button type="button" wire:click="syncLogs" variant="ghost">{{ __('Sync Logs') }}</flux:button>
                <flux:button type="button" wire:click="disconnect" variant="danger">{{ __('Disconnect') }}</flux:button>
            </div>
        </div>
    </form>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Branch & Device Mapping') }}</h3>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Select the branch and mapped turnstile/door/lane devices.') }}</p>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Branch') }}</flux:label>
                <flux:select wire:model="selected_branch_id">
                    <option value="">{{ __('Select Branch') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </flux:select>
                <flux:error name="selected_branch_id" />
            </flux:field>
        </div>

        <div class="mt-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
            <p class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Available Devices') }}</p>

            @if($devices->isEmpty())
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No devices loaded yet. Run "Fetch Devices" after a successful connection test.') }}</p>
            @else
                <div class="grid gap-2">
                    @foreach($devices as $device)
                        <label class="inline-flex items-center gap-3 rounded-md border border-zinc-200 px-3 py-2 text-sm dark:border-zinc-700">
                            <input type="checkbox" value="{{ $device->id }}" wire:model="selected_device_ids" class="rounded border-zinc-300 dark:border-zinc-600" @disabled(!$device->is_assignable)>
                            <span class="font-medium text-zinc-900 dark:text-white">{{ $device->remote_name ?: $device->remote_device_id }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">({{ $device->remote_type ?: 'device' }})</span>
                            <span class="{{ $device->is_online ? 'text-emerald-600 dark:text-emerald-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                                {{ $device->is_online ? __('Online') : __('Offline') }}
                            </span>
                            @if(!$device->is_assignable)
                                <span class="text-amber-600 dark:text-amber-400">{{ __('Not assignable') }}</span>
                            @endif
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="mt-4">
            <flux:button type="button" wire:click="saveBranchMapping" variant="primary">
                {{ __('Save Device Mapping') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Recent Sync Runs') }}</p>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse($recent_runs as $run)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ str($run->run_type)->replace('_', ' ')->title() }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $run->started_at?->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ strtoupper($run->status) }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ __('ok: :ok | failed: :failed', ['ok' => $run->records_success, 'failed' => $run->records_failed]) }}</p>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No sync runs recorded yet.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>
