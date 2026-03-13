<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('ZKTeco Integration Settings') }}</flux:heading>
            <flux:subheading>{{ __('Configure platform mode first, then use local agent fallback only when needed.') }}</flux:subheading>
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

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Mode') }}</flux:label>
                    <flux:select wire:model.live="mode">
                        <option value="platform">{{ __('Platform (Preferred)') }}</option>
                        <option value="agent">{{ __('Local Agent (Fallback)') }}</option>
                    </flux:select>
                    <flux:error name="mode" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Provider') }}</flux:label>
                    <flux:select wire:model="provider">
                        @foreach($provider_options as $provider_key => $provider_label)
                            <option value="{{ $provider_key }}">{{ $provider_label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="provider" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <flux:checkbox wire:model="is_enabled">
                            <flux:label>{{ __('Integration Enabled') }}</flux:label>
                        </flux:checkbox>
                        <flux:checkbox wire:model="sync_enabled">
                            <flux:label>{{ __('Sync Enabled') }}</flux:label>
                        </flux:checkbox>
                        <flux:checkbox wire:model="agent_fallback_enabled">
                            <flux:label>{{ __('Allow Agent Fallback') }}</flux:label>
                        </flux:checkbox>
                    </div>
                </flux:field>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('ZKBio Platform Connection') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Only required when platform mode is enabled.') }}</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Platform Base URL') }}</flux:label>
                    <flux:input wire:model="platform_base_url" placeholder="https://zkbio.example.com" />
                    <flux:error name="platform_base_url" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Username') }}</flux:label>
                    <flux:input wire:model="platform_username" />
                    <flux:error name="platform_username" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input wire:model="platform_password" type="password" />
                    <flux:error name="platform_password" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Site / Tenant Code') }}</flux:label>
                    <flux:input wire:model="platform_site_code" />
                    <flux:error name="platform_site_code" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Client ID') }}</flux:label>
                    <flux:input wire:model="platform_client_id" />
                    <flux:error name="platform_client_id" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Client Secret') }}</flux:label>
                    <flux:input wire:model="platform_client_secret" type="password" />
                    <flux:error name="platform_client_secret" />
                </flux:field>
            </div>
        </div>

        @if($provider_meta)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="mb-3 text-lg font-medium text-zinc-900 dark:text-white">{{ __('Provider Health') }}</h3>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</p>
                        <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $provider_meta['provider'] ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                        <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ strtoupper($provider_meta['status'] ?? 'unknown') }}</p>
                    </div>
                    <div>
                        <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Check') }}</p>
                        <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $provider_meta['checked_at'] ?? '-' }}</p>
                    </div>
                </div>
                @if(!empty($provider_meta['message']))
                    <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-300">{{ $provider_meta['message'] }}</p>
                @endif
            </div>
        @endif

        <div class="flex justify-end">
            <flux:button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ __('Save Settings') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
