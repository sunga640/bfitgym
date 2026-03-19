<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                {{ $is_editing ? __('Edit CVSecurity Integration') : __('Create CVSecurity Integration') }}
            </flux:heading>
            <flux:subheading>{{ __('Cloud stores configuration, local-agent executes LAN communication to CVSecurity.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" href="{{ route('zkteco.connections.index') }}" wire:navigate>
            {{ __('Back') }}
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

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Step 1: Integration Details') }}</flux:heading>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @if($can_switch_branches)
                    <flux:field>
                        <flux:label>{{ __('Branch') }}</flux:label>
                        <flux:select wire:model="branch_id">
                            <option value="">{{ __('Select branch') }}</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="branch_id" />
                    </flux:field>
                @endif

                <flux:field>
                    <flux:label>{{ __('Integration Name') }}</flux:label>
                    <flux:input wire:model="name" placeholder="{{ __('Main Branch CVSecurity') }}" />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Local-Agent Label') }}</flux:label>
                    <flux:input wire:model="agent_label" placeholder="{{ __('Reception PC Agent') }}" />
                    <flux:error name="agent_label" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Timezone') }}</flux:label>
                    <flux:input wire:model="timezone" placeholder="{{ __('Africa/Nairobi') }}" />
                    <flux:error name="timezone" />
                </flux:field>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('Step 2: CVSecurity Connection') }}</flux:heading>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('CVSecurity Base URL / Host') }}</flux:label>
                    <flux:input wire:model="cv_base_url" placeholder="{{ __('http://192.168.1.20') }}" />
                    <flux:error name="cv_base_url" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Port') }}</flux:label>
                    <flux:input type="number" wire:model="cv_port" placeholder="4370" min="1" max="65535" />
                    <flux:error name="cv_port" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Poll Interval (seconds)') }}</flux:label>
                    <flux:input type="number" wire:model="poll_interval_seconds" min="5" max="3600" />
                    <flux:error name="poll_interval_seconds" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Username') }}</flux:label>
                    <flux:input wire:model="cv_username" autocomplete="off" />
                    <flux:error name="cv_username" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input type="password" wire:model="cv_password" autocomplete="new-password" />
                    <flux:description>{{ __('Leave blank to keep saved password.') }}</flux:description>
                    <flux:error name="cv_password" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('API Token (optional)') }}</flux:label>
                    <flux:input type="password" wire:model="cv_api_token" autocomplete="off" />
                    <flux:description>{{ __('Use this if your CVSecurity instance authenticates by API token.') }}</flux:description>
                    <flux:error name="cv_api_token" />
                </flux:field>

                @if($is_editing)
                    <flux:field>
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="clear_cv_password" class="rounded border-zinc-300 dark:border-zinc-600" />
                            {{ __('Clear saved password') }}
                        </label>
                    </flux:field>
                    <flux:field>
                        <label class="inline-flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-200">
                            <input type="checkbox" wire:model="clear_cv_api_token" class="rounded border-zinc-300 dark:border-zinc-600" />
                            {{ __('Clear saved API token') }}
                        </label>
                    </flux:field>
                @endif
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:field>
                <flux:label>{{ __('Notes') }}</flux:label>
                <flux:textarea wire:model="notes" rows="3" placeholder="{{ __('Optional operator notes...') }}" />
                <flux:error name="notes" />
            </flux:field>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <flux:button type="submit" variant="primary">
                {{ __('Save') }}
            </flux:button>

            @if($is_editing)
                <flux:button type="button" variant="filled" wire:click="generatePairingToken">
                    {{ __('Generate Pairing Token') }}
                </flux:button>
            @endif

            <flux:button type="button" variant="ghost" href="{{ route('zkteco.connections.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
        </div>
    </form>

    @if($generated_pairing_token)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 dark:border-emerald-800/70 dark:bg-emerald-900/20">
            <flux:heading size="lg">{{ __('Pairing Token (Copy Now)') }}</flux:heading>
            <p class="mt-2 text-sm text-emerald-800 dark:text-emerald-200">
                {{ __('Give this token to the local-agent setup wizard. It expires at :time', ['time' => $generated_pairing_token_expires_at]) }}
            </p>
            <div class="mt-3 rounded-lg bg-white px-3 py-2 font-mono text-sm text-zinc-900 dark:bg-zinc-900 dark:text-zinc-100">
                {{ $generated_pairing_token }}
            </div>
        </div>
    @endif
</div>

