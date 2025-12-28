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
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Profile Information') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-6">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ strtoupper(substr($member->first_name, 0, 1) . substr($member->last_name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-xl font-semibold text-zinc-900 dark:text-white">{{ $member->full_name }}</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member') }} #{{ $member->member_no }}</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->email ?: '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Gender') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->gender ? ucfirst($member->gender) : '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Date of Birth') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->dob?->format('F d, Y') ?: '—' }}</p>
                                    </div>
                                    @if($member->branch)
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->branch->name }}</p>
                                    </div>
                                    @endif
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member Since') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $member->created_at?->format('F d, Y') }}</p>
                                    </div>
                                </div>
                                @if($member->address)
                                <div class="mt-4">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Address') }}</p>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ $member->address }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                @if($member->notes)
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Notes') }}</h2>
                    </div>
                    <div class="p-6">
                        <p class="text-sm text-zinc-700 dark:text-zinc-200">{{ $member->notes }}</p>
                    </div>
                </div>
                @endif

                {{-- Subscription History --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Subscription History') }}</h2>
                        @if(!$this->hasActiveSubscription)
                            @can('create subscriptions')
                            <flux:button size="sm" variant="primary" icon="plus" wire:click="openSubscriptionModal">
                                {{ __('Add Subscription') }}
                            </flux:button>
                            @endcan
                        @endif
                    </div>
                    <div class="p-6">
                        @if($member->subscriptions->count() > 0)
                            <div class="space-y-3">
                                @foreach($member->subscriptions->sortByDesc('start_date')->take(5) as $subscription)
                                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $subscription->membershipPackage?->name }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ $subscription->start_date?->format('M d, Y') }} → {{ $subscription->end_date?->format('M d, Y') }}
                                        </p>
                                    </div>
                                    <flux:badge :color="$subscription->status === 'active' ? 'emerald' : ($subscription->status === 'expired' ? 'amber' : 'zinc')" size="sm">
                                        {{ ucfirst($subscription->status) }}
                                    </flux:badge>
                                </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon name="credit-card" class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No subscription history.') }}</p>
                                @if(!$this->hasActiveSubscription)
                                    @can('create subscriptions')
                                    <flux:button size="sm" variant="primary" icon="plus" wire:click="openSubscriptionModal" class="mt-4">
                                        {{ __('Add Subscription') }}
                                    </flux:button>
                                    @endcan
                                @endif
                            </div>
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
                        @php
                            $active_subscription = $member->subscriptions->where('status', 'active')->first();
                            $days_remaining = $active_subscription ? (int) now()->startOfDay()->diffInDays($active_subscription->end_date->startOfDay(), false) : 0;
                        @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member Status') }}</span>
                            <flux:badge :color="$member->status === 'active' ? 'emerald' : ($member->status === 'suspended' ? 'rose' : 'zinc')">
                                {{ ucfirst($member->status) }}
                            </flux:badge>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Subscription') }}</span>
                            @if($active_subscription)
                                <flux:badge color="emerald">{{ __('Active') }}</flux:badge>
                            @else
                                <flux:badge color="amber">{{ __('No Active') }}</flux:badge>
                            @endif
                        </div>
                        @if($active_subscription)
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Plan') }}</span>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $active_subscription->membershipPackage->name ?? '-' }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Expires') }}</span>
                                <span class="text-sm font-medium {{ $days_remaining <= 7 && $days_remaining > 0 ? 'text-amber-600 dark:text-amber-400' : ($days_remaining <= 0 ? 'text-rose-600 dark:text-rose-400' : 'text-zinc-900 dark:text-white') }}">
                                    {{ $active_subscription->end_date->format('d M Y') }}
                                    @if($days_remaining > 0)
                                        ({{ $days_remaining }} {{ Str::plural('day', $days_remaining) }})
                                    @elseif($days_remaining === 0)
                                        ({{ __('Today') }})
                                    @else
                                        ({{ __('Expired') }})
                                    @endif
                                </span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Insurance') }}</span>
                            <flux:badge :color="$member->has_insurance ? 'emerald' : 'zinc'">
                                {{ $member->has_insurance ? __('Yes') : __('None') }}
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
                        {{-- Eligibility badge (cloud source of truth) --}}
                        @if($this->eligibility_is_allowed)
                            <div class="flex items-center justify-between rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-200">
                                <span class="font-medium">{{ __('Eligible') }}</span>
                                <span class="text-xs">{{ $this->allowed_until_human }}</span>
                            </div>
                        @else
                            <div class="flex items-center justify-between rounded-lg bg-rose-50 p-3 text-sm text-rose-800 dark:bg-rose-900/20 dark:text-rose-200">
                                <span class="font-medium">{{ __('Not eligible') }}</span>
                                <span class="text-xs">{{ $this->allowed_until_human }}</span>
                            </div>
                        @endif

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
                                        @if($access_identity?->valid_until)
                                            <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                                {{ __('Valid until: :date', ['date' => $access_identity->valid_until->format('d M Y')]) }}
                                            </p>
                                        @endif
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

                            @can('edit members')
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
                                    @if($this->eligibility_is_allowed)
                                        <flux:button
                                            variant="primary"
                                            class="flex-1"
                                            icon="play"
                                            wire:click="enableFingerprint"
                                            :disabled="$this->last_command_is_pending"
                                        >
                                            {{ $this->last_command_is_pending ? __('Pending…') : __('Enable') }}
                                        </flux:button>
                                    @endif
                                @else
                                    @if($member->status === 'active')
                                        <flux:button
                                            variant="filled"
                                            color="amber"
                                            class="flex-1"
                                            icon="pause"
                                            wire:click="disableFingerprint"
                                            wire:confirm="{{ __('Disable device access for this member?') }}"
                                            :disabled="$this->last_command_is_pending"
                                        >
                                            {{ $this->last_command_is_pending ? __('Pending…') : __('Disable') }}
                                        </flux:button>
                                    @endif
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
                            @can('edit members')
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
                                    @if($this->hasActiveSubscription)
                                        <p class="text-xs text-zinc-500">{{ __('Sync user to device for access control') }}</p>
                                    @else
                                        <p class="text-xs text-amber-600 dark:text-amber-400">{{ __('Requires active subscription') }}</p>
                                    @endif
                                </div>
                            </div>
                            @can('edit members')
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
                                    @if(!$this->hasActiveSubscription)
                                        <p class="text-center text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ __('Add a subscription first to enable device access.') }}
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
                        @can('edit members')
                        <flux:button variant="ghost" class="w-full justify-start" icon="pencil" href="{{ route('members.edit', $member) }}" wire:navigate>
                            {{ __('Edit Member') }}
                        </flux:button>
                        @endcan
                        @can('create subscriptions')
                        <flux:button variant="ghost" class="w-full justify-start" icon="credit-card" href="{{ route('subscriptions.create') }}" wire:navigate>
                            {{ __('New Subscription') }}
                        </flux:button>
                        @endcan
                        <flux:button variant="ghost" class="w-full justify-start" icon="ticket" disabled>
                            {{ __('Book Class') }}
                        </flux:button>
                        <flux:button variant="ghost" class="w-full justify-start" icon="document-text" disabled>
                            {{ __('Assign Workout Plan') }}
                        </flux:button>
                    </div>
                </div>

                {{-- Back Button --}}
                <flux:button variant="ghost" href="{{ route('members.index') }}" wire:navigate class="w-full" icon="arrow-left">
                    {{ __('Back to Members') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Add Subscription Modal --}}
    <flux:modal wire:model="show_subscription_modal" class="max-w-2xl">
        <div class="p-6">
            <div class="mb-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ __('Add Subscription for :name', ['name' => $member->full_name]) }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('Select a membership package and enter payment details.') }}
                </p>
            </div>

            @error('subscription_form')
                <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
                    {{ $message }}
                </div>
            @enderror

            <form wire:submit="saveSubscription" class="space-y-6">
                {{-- Package Selection --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Package Details') }}</h3>

                    <div>
                        <label for="modal_package_id" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Membership Package') }} <span class="text-red-500">*</span>
                        </label>
                        <flux:select id="modal_package_id" wire:model.live="membership_package_id">
                            <option value="">{{ __('Select a package') }}</option>
                            @foreach($this->availablePackages as $package)
                                <option value="{{ $package->id }}">
                                    {{ $package->name }} — {{ money($package->price) }} ({{ $package->formatted_duration }})
                                </option>
                            @endforeach
                        </flux:select>
                        @error('membership_package_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Start Date') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input type="date" wire:model.live="start_date" />
                            @error('start_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('End Date') }}
                            </label>
                            <flux:input type="date" wire:model="end_date" disabled />
                            <p class="mt-1 text-xs text-zinc-500">{{ __('Calculated from package duration.') }}</p>
                        </div>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Auto Renew') }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Automatically renew when subscription ends.') }}</p>
                        </div>
                        <label class="inline-flex cursor-pointer items-center">
                            <input type="checkbox" class="peer sr-only" wire:model.live="auto_renew">
                            <div class="peer h-5 w-9 rounded-full bg-zinc-300 shadow-inner transition-all after:absolute after:top-0.5 after:left-0.5 after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:bg-emerald-500 peer-checked:after:translate-x-4 relative"></div>
                        </label>
                    </div>
                </div>

                {{-- Payment Details --}}
                <div class="space-y-4 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Payment Details') }}</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Amount') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input type="number" step="0.01" wire:model.live="amount" />
                            @error('amount') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Currency') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input type="text" wire:model.live="currency" maxlength="3" class="uppercase" />
                            @error('currency') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Payment Method') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:select wire:model.live="payment_method">
                                <option value="cash">{{ __('Cash') }}</option>
                                <option value="card">{{ __('Card') }}</option>
                                <option value="mobile_money">{{ __('Mobile Money') }}</option>
                                <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                            </flux:select>
                            @error('payment_method') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Reference') }}
                            </label>
                            <flux:input type="text" wire:model.live="reference" placeholder="{{ __('Optional') }}" />
                            @error('reference') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Paid At') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input type="datetime-local" wire:model.live="paid_at" />
                            @error('paid_at') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div>
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Notes') }}
                    </label>
                    <textarea
                        wire:model.live="subscription_notes"
                        rows="2"
                        placeholder="{{ __('Optional notes about this subscription') }}"
                        class="w-full rounded-xl border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    ></textarea>
                    @error('subscription_notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>

                {{-- Summary --}}
                @if($this->selectedPackage)
                <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700/50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ $this->selectedPackage->name }}</p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $start_date }} → {{ $end_date ?: '—' }}
                            </p>
                        </div>
                        <p class="text-lg font-semibold text-zinc-900 dark:text-white">
                            {{ $amount !== '' ? money((float) $amount, $currency) : '—' }}
                        </p>
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                    <flux:button type="button" variant="ghost" wire:click="closeSubscriptionModal">
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                        {{ __('Add Subscription') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
