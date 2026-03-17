<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <flux:button href="{{ route($route_prefix . '.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
        <div>
            <flux:heading size="xl">
                {{ $is_editing ? __('Edit :integration Device', ['integration' => $integration_label]) : __('Add :integration Device', ['integration' => $integration_label]) }}
            </flux:heading>
            <flux:subheading>
                {{ $is_editing ? __('Update device configuration and connection settings.') : __('Configure a new :integration access control device.', ['integration' => $integration_label]) }}
            </flux:subheading>
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

    <form wire:submit="save" class="space-y-6">
        {{-- Branch Selection (for users who can switch branches) --}}
        @if($can_switch_branches)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Branch') }}</h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Select the branch where this device is located.') }}</p>
                </div>

                <flux:field>
                    <flux:label>{{ __('Branch') }} *</flux:label>
                    <flux:select wire:model.live="branch_id" required>
                        <option value="">{{ __('Select branch...') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="branch_id" />
                </flux:field>
            </div>
        @endif

        {{-- Basic Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Basic Information') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Device identification and classification.') }}</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Device Name') }} *</flux:label>
                    <flux:input
                        wire:model="name"
                        placeholder="{{ __('e.g., Main Entrance Terminal') }}"
                        required
                    />
                    <flux:error name="name" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Serial Number') }} *</flux:label>
                    <flux:input
                        wire:model="serial_number"
                        placeholder="{{ __('e.g., DS-K1T808MFWX12345678') }}"
                        required
                    />
                    <flux:error name="serial_number" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Device Model') }} *</flux:label>
                    <flux:select wire:model.live="device_model" required>
                        @foreach($device_models as $model => $label)
                            <option value="{{ $model }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="device_model" />
                    <flux:description>{{ __('Selecting a model will set default capabilities.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Device Type') }} *</flux:label>
                    <flux:select wire:model="device_type" required>
                        @foreach($device_types as $type => $label)
                            <option value="{{ $type }}">{{ $label }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="device_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Location') }}</flux:label>
                    <flux:select wire:model="location_id">
                        <option value="">{{ __('Select location...') }}</option>
                        @foreach($locations as $location)
                            <option value="{{ $location->id }}">{{ $location->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="location_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Status') }} *</flux:label>
                    <flux:select wire:model="status" required>
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                @if($show_provider_selector)
                    <flux:field class="sm:col-span-2">
                        <flux:label>{{ __('Provider') }} *</flux:label>
                        <flux:select wire:model.live="provider" required>
                            @foreach($provider_options as $provider_key => $provider_label)
                                <option value="{{ $provider_key }}">{{ $provider_label }}</option>
                            @endforeach
                        </flux:select>
                        <flux:error name="provider" />
                    </flux:field>
                @endif
            </div>
        </div>

        {{-- Connection Settings --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Connection Settings') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    @if($requires_credentials)
                        {{ __('Network and authentication configuration used by the local agent/device connector.') }}
                    @else
                        {{ __('This provider uses shared local-agent CVAccess settings. No per-device LAN credentials are required.') }}
                    @endif
                </p>
            </div>

            @if($requires_credentials)
                <div class="grid gap-6 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>{{ __('IP Address') }} *</flux:label>
                        <flux:input
                            wire:model="ip_address"
                            type="text"
                            placeholder="{{ __('e.g., 192.168.1.100') }}"
                        />
                        <flux:error name="ip_address" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Port') }} *</flux:label>
                        <flux:input
                            wire:model="port"
                            type="number"
                            min="1"
                            max="65535"
                            placeholder="80"
                            required
                        />
                        <flux:error name="port" />
                        <flux:description>{{ __('Default is 80 for HTTP, 443 for HTTPS.') }}</flux:description>
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Username') }} *</flux:label>
                        <flux:input
                            wire:model="username"
                            type="text"
                            placeholder="admin"
                        />
                        <flux:error name="username" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Password') }} {{ !$is_editing ? '*' : '' }}</flux:label>
                        <flux:input
                            wire:model="password"
                            type="password"
                            placeholder="{{ $is_editing ? __('Leave blank to keep current') : __('Device password') }}"
                            :required="!$is_editing"
                        />
                        <flux:error name="password" />
                        @if($is_editing)
                            <flux:description>{{ __('Leave blank to keep the current password.') }}</flux:description>
                        @endif
                    </flux:field>
                </div>
            @else
                <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-800/40 dark:bg-amber-900/20 dark:text-amber-200">
                    {{ __('Set the shared ZKTeco CVAccess host and credentials on the receptionist PC running local-agent. This device entry only needs name/serial/provider mapping.') }}
                </div>
            @endif
        </div>

        {{-- Device Capabilities --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Device Capabilities') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Authentication methods supported by this device.') }}</p>
            </div>

            <div class="flex flex-wrap gap-6">
                <flux:checkbox wire:model="supports_face_recognition">
                    <flux:label>{{ __('Face Recognition') }}</flux:label>
                    <flux:description>{{ __('Device can identify users by face.') }}</flux:description>
                </flux:checkbox>

                <flux:checkbox wire:model="supports_fingerprint">
                    <flux:label>{{ __('Fingerprint') }}</flux:label>
                    <flux:description>{{ __('Device supports fingerprint scanning.') }}</flux:description>
                </flux:checkbox>

                <flux:checkbox wire:model="supports_card">
                    <flux:label>{{ __('Card / RFID') }}</flux:label>
                    <flux:description>{{ __('Device can read access cards.') }}</flux:description>
                </flux:checkbox>
            </div>
        </div>

        {{-- Sync Settings --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Sync Settings') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Configure automatic access log synchronization.') }}</p>
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:checkbox wire:model="auto_sync_enabled">
                        <flux:label>{{ __('Enable Automatic Sync') }}</flux:label>
                        <flux:description>{{ __('Automatically fetch access logs from this device at regular intervals.') }}</flux:description>
                    </flux:checkbox>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Sync Interval (minutes)') }}</flux:label>
                    <flux:input
                        wire:model="sync_interval_minutes"
                        type="number"
                        min="1"
                        max="1440"
                        :disabled="!$auto_sync_enabled"
                    />
                    <flux:error name="sync_interval_minutes" />
                    <flux:description>{{ __('How often to fetch new access logs (1-1440 minutes).') }}</flux:description>
                </flux:field>
            </div>
        </div>

        {{-- Notes --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="mb-6">
                <h3 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Notes') }}</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Additional information about this device.') }}</p>
            </div>

            <flux:field>
                <flux:textarea
                    wire:model="notes"
                    rows="3"
                    placeholder="{{ __('Add any notes or installation details...') }}"
                />
                <flux:error name="notes" />
            </flux:field>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route($route_prefix . '.index') }}" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">
                    {{ $is_editing ? __('Update Device') : __('Create Device') }}
                </span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
