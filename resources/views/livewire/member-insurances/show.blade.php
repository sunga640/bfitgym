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
                {{-- Policy Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Policy Information') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Policy Number') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->policy_number ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Insurer') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->insurer?->name }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Coverage Type') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ ucfirst($insurance->coverage_type ?? '—') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                                <flux:badge :color="$insurance->status === 'active' ? 'emerald' : ($insurance->status === 'expired' ? 'amber' : 'zinc')">
                                    {{ ucfirst($insurance->status) }}
                                </flux:badge>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Start Date') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->start_date?->format('F d, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('End Date') }}</p>
                                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->end_date?->format('F d, Y') }}</p>
                            </div>
                        </div>
                        @if($insurance->notes)
                        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Notes') }}</p>
                            <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-200">{{ $insurance->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Member Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Member Information') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-start gap-6">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 text-xl font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                {{ strtoupper(substr($insurance->member->first_name, 0, 1) . substr($insurance->member->last_name, 0, 1)) }}
                            </div>
                            <div class="flex-1">
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ $insurance->member->full_name }}</h3>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member') }} #{{ $insurance->member->member_no }}</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->member->phone }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->member->email ?: '—' }}</p>
                                    </div>
                                    @if($insurance->member->branch)
                                    <div>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</p>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ $insurance->member->branch->name }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Fingerprint Access --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Fingerprint Access') }}</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        @if($has_fingerprint_registered)
                            {{-- Registered - Show status --}}
                            @if($has_fingerprint_access)
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                                    <div class="flex-shrink-0">
                                        <flux:icon name="finger-print" class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ __('Active') }}</p>
                                        <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                            {{ __('Employee No: :id', ['id' => $access_identity?->device_user_id ?? '-']) }}
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="flex items-center gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                                    <div class="flex-shrink-0">
                                        <flux:icon name="finger-print" class="h-8 w-8 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('Disabled') }}</p>
                                        <p class="text-xs text-amber-600 dark:text-amber-400">
                                            {{ __('Employee No: :id', ['id' => $access_identity?->device_user_id ?? '-']) }}
                                        </p>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-2">
                                @if($has_fingerprint_access)
                                    <flux:button variant="filled" color="amber" class="flex-1" icon="pause" wire:click="disableFingerprint" wire:confirm="{{ __('Disable fingerprint access for this member?') }}">
                                        {{ __('Disable') }}
                                    </flux:button>
                                @else
                                    <flux:button variant="primary" class="flex-1" icon="play" wire:click="enableFingerprint">
                                        {{ __('Enable') }}
                                    </flux:button>
                                @endif
                                <flux:button variant="danger" icon="trash" wire:click="removeFingerprint" wire:confirm="{{ __('Permanently remove fingerprint access? This will delete the record.') }}" />
                            </div>
                        @else
                            {{-- Not registered --}}
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-zinc-100 dark:bg-zinc-700/50">
                                <div class="flex-shrink-0">
                                    <flux:icon name="finger-print" class="h-8 w-8 text-zinc-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Not Registered') }}</p>
                                    <p class="text-xs text-zinc-500">{{ __('Add fingerprint for access control') }}</p>
                                </div>
                            </div>
                            @if($insurance->status === 'active')
                            <flux:button variant="primary" class="w-full" icon="finger-print" wire:click="addFingerprint">
                                {{ __('Add Fingerprint') }}
                            </flux:button>
                            @else
                            <p class="text-xs text-center text-zinc-500">{{ __('Policy must be active to add fingerprint') }}</p>
                            @endif
                        @endif
                    </div>
                </div>

                {{-- Validity Status --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Validity') }}</h2>
                    </div>
                    <div class="p-4 space-y-4">
                        @php
                            $is_expired = $insurance->end_date && $insurance->end_date->isPast();
                            $days_remaining = $insurance->end_date ? now()->diffInDays($insurance->end_date, false) : null;
                        @endphp
                        
                        @if($is_expired)
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20">
                                <flux:icon name="exclamation-circle" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                <div>
                                    <p class="text-sm font-medium text-red-800 dark:text-red-200">{{ __('Expired') }}</p>
                                    <p class="text-xs text-red-600 dark:text-red-400">{{ abs($days_remaining) }} {{ __('days ago') }}</p>
                                </div>
                            </div>
                        @elseif($days_remaining !== null && $days_remaining <= 30)
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-amber-50 dark:bg-amber-900/20">
                                <flux:icon name="exclamation-triangle" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                                <div>
                                    <p class="text-sm font-medium text-amber-800 dark:text-amber-200">{{ __('Expiring Soon') }}</p>
                                    <p class="text-xs text-amber-600 dark:text-amber-400">{{ $days_remaining }} {{ __('days remaining') }}</p>
                                </div>
                            </div>
                        @else
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-emerald-50 dark:bg-emerald-900/20">
                                <flux:icon name="check-circle" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                                <div>
                                    <p class="text-sm font-medium text-emerald-800 dark:text-emerald-200">{{ __('Valid') }}</p>
                                    @if($days_remaining !== null)
                                    <p class="text-xs text-emerald-600 dark:text-emerald-400">{{ $days_remaining }} {{ __('days remaining') }}</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Quick Actions') }}</h2>
                    </div>
                    <div class="space-y-2 p-4">
                        <flux:button variant="ghost" class="w-full justify-start" icon="pencil" href="{{ route('member-insurances.edit', $insurance) }}" wire:navigate>
                            {{ __('Edit Policy') }}
                        </flux:button>
                        <flux:button variant="ghost" class="w-full justify-start" icon="user" href="{{ route('members.show', $insurance->member) }}" wire:navigate>
                            {{ __('View Member') }}
                        </flux:button>
                    </div>
                </div>

                {{-- Back Button --}}
                <flux:button variant="ghost" href="{{ route('member-insurances.index') }}" wire:navigate class="w-full" icon="arrow-left">
                    {{ __('Back to Policies') }}
                </flux:button>
            </div>
        </div>
    </div>
</div>

