<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    {{-- Header --}}
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Agent Enrollments') }}</flux:heading>
            <flux:subheading>{{ __('Generate and manage enrollment codes for local agents.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button wire:click="openGenerateModal" icon="plus">
                {{ __('Generate Code') }}
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

    {{-- Filters --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                placeholder="{{ __('Search by label or agent name...') }}"
                icon="magnifying-glass"
            />
        </div>
        <flux:select wire:model.live="status_filter" class="w-40">
            <option value="">{{ __('All Status') }}</option>
            <option value="active">{{ __('Active') }}</option>
            <option value="used">{{ __('Used') }}</option>
            <option value="expired">{{ __('Expired') }}</option>
            <option value="revoked">{{ __('Revoked') }}</option>
        </flux:select>
    </div>

    {{-- Enrollments Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Label / Code') }}</th>
                        <th class="px-4 py-3">{{ __('Agent') }}</th>
                        <th class="px-4 py-3">{{ __('Devices') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Expires') }}</th>
                        <th class="px-4 py-3">{{ __('Created By') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($enrollments as $enrollment)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">
                                        {{ $enrollment->label ?? __('Enrollment #:id', ['id' => $enrollment->id]) }}
                                    </p>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        <code class="rounded bg-zinc-100 px-2 py-0.5 dark:bg-zinc-700">{{ $enrollment->masked_code }}</code>
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                @if($enrollment->agent)
                                    <a href="{{ route('access-control.agents.show', $enrollment->agent) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $enrollment->agent->name }}
                                    </a>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ Str::limit($enrollment->agent->uuid, 16) }}</p>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($enrollment->devices->count() > 0)
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($enrollment->devices->take(3) as $device)
                                            <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                                {{ $device->name }}
                                            </span>
                                        @endforeach
                                        @if($enrollment->devices->count() > 3)
                                            <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                                +{{ $enrollment->devices->count() - 3 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($enrollment->computed_status === 'active')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        {{ __('Active') }}
                                    </span>
                                @elseif($enrollment->computed_status === 'used')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        {{ __('Used') }}
                                    </span>
                                @elseif($enrollment->computed_status === 'expired')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                        {{ __('Expired') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-red-500"></span>
                                        {{ __('Revoked') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($enrollment->expires_at)
                                    <span class="text-zinc-600 dark:text-zinc-400" title="{{ $enrollment->expires_at->format('Y-m-d H:i:s') }}">
                                        {{ $enrollment->time_remaining ?? $enrollment->expires_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-zinc-600 dark:text-zinc-300">
                                    {{ $enrollment->createdBy?->name ?? __('Unknown') }}
                                </div>
                                <div class="text-xs text-zinc-400">
                                    {{ $enrollment->created_at->format('M d, Y H:i') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($enrollment->computed_status === 'active')
                                    <flux:button
                                        wire:click="confirmRevoke({{ $enrollment->id }})"
                                        variant="ghost"
                                        size="sm"
                                        icon="no-symbol"
                                        class="text-red-600 hover:text-red-700 dark:text-red-400"
                                        title="{{ __('Revoke') }}"
                                    />
                                @else
                                    <span class="text-zinc-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.key class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('No enrollments yet') }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Generate an enrollment code to register a new agent.') }}</p>
                                    </div>
                                    <flux:button wire:click="openGenerateModal" icon="plus" size="sm">
                                        {{ __('Generate Code') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($enrollments->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $enrollments->links() }}
            </div>
        @endif
    </div>

    {{-- Generate Enrollment Modal --}}
    <flux:modal wire:model="show_generate_modal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon.key class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Generate Enrollment Code') }}</flux:heading>
                    <flux:subheading>{{ __('Create a one-time code to register a new agent.') }}</flux:subheading>
                </div>
            </div>

            <div class="space-y-4">
                <flux:field>
                    <flux:label>{{ __('Label (Optional)') }}</flux:label>
                    <flux:input
                        wire:model="enrollment_label"
                        placeholder="{{ __('e.g., Branch Office PC') }}"
                    />
                    <flux:description>{{ __('A friendly name to identify this enrollment.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Expires In (Minutes)') }}</flux:label>
                    <flux:input
                        wire:model="expires_in_minutes"
                        type="number"
                        min="5"
                        max="1440"
                    />
                    <flux:description>{{ __('How long the code remains valid (5-1440 minutes).') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Pre-assign Devices') }}</flux:label>
                    <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                        @forelse($available_devices as $device)
                            <label class="flex items-center gap-2">
                                <input
                                    type="checkbox"
                                    class="rounded border-zinc-300 text-zinc-900 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800"
                                    value="{{ $device->id }}"
                                    wire:model="selected_device_ids"
                                />
                                <span class="text-sm text-zinc-900 dark:text-white">{{ $device->name }}</span>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $device->serial_number }}</span>
                            </label>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('No devices available. Add devices first.') }}</p>
                        @endforelse
                    </div>
                    <flux:description>{{ __('These devices will be automatically assigned when the agent registers.') }}</flux:description>
                </flux:field>
            </div>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeGenerateModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="generateEnrollment" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="generateEnrollment">{{ __('Generate') }}</span>
                    <span wire:loading wire:target="generateEnrollment">{{ __('Generating...') }}</span>
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- View Code Modal (View-Once) --}}
    <flux:modal wire:model="show_code_modal" class="max-w-lg">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon.check-circle class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Enrollment Code Generated') }}</flux:heading>
                    <flux:subheading>{{ __('Copy this code now — it will not be shown again.') }}</flux:subheading>
                </div>
            </div>

            <flux:callout variant="warning" icon="exclamation-triangle">
                {{ __('This code is displayed only once. Make sure to copy it before closing this dialog.') }}
            </flux:callout>

            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Enrollment Code') }}</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 break-all rounded-lg border border-zinc-300 bg-zinc-100 px-3 py-2 font-mono text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            {{ $generated_code }}
                        </code>
                        <flux:button
                            type="button"
                            variant="ghost"
                            size="sm"
                            icon="{{ $code_copied ? 'check' : 'clipboard-document' }}"
                            x-data="{}"
                            x-on:click="navigator.clipboard.writeText('{{ $generated_code }}'); $wire.markCodeCopied()"
                            class="{{ $code_copied ? 'text-emerald-600' : '' }}"
                        >
                            {{ $code_copied ? __('Copied!') : __('Copy') }}
                        </flux:button>
                    </div>
                </div>

                @if($generated_agent_uuid)
                    <div>
                        <label class="mb-1 block text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('Agent UUID') }}</label>
                        <code class="block break-all rounded-lg border border-zinc-300 bg-zinc-100 px-3 py-2 font-mono text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white">
                            {{ $generated_agent_uuid }}
                        </code>
                    </div>
                @endif
            </div>

            <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                <h4 class="mb-3 font-medium text-zinc-900 dark:text-white">{{ __('Setup Instructions') }}</h4>
                
                {{-- Cloud URL --}}
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Cloud URL (needed during setup)') }}</label>
                    <div class="flex items-center gap-2">
                        <code class="flex-1 rounded border border-zinc-300 bg-white px-2 py-1 font-mono text-xs text-zinc-700 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">{{ config('app.url') }}</code>
                        <button
                            type="button"
                            class="rounded p-1 text-zinc-400 hover:bg-zinc-200 hover:text-zinc-600 dark:hover:bg-zinc-700 dark:hover:text-zinc-300"
                            x-data="{}"
                            x-on:click="navigator.clipboard.writeText('{{ config('app.url') }}')"
                            title="{{ __('Copy URL') }}"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                        </button>
                    </div>
                </div>

                <h5 class="mb-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">{{ __('On the Branch Computer:') }}</h5>
                <ol class="list-inside list-decimal space-y-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <li>
                        {{ __('Ensure PHP is installed') }}
                        <span class="text-xs text-zinc-500">({{ __('download from windows.php.net') }})</span>
                    </li>
                    <li>
                        {{ __('Open the local-agent folder') }}
                    </li>
                    <li>
                        {{ __('Double-click') }} <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">install-agent.bat</code>
                    </li>
                    <li>
                        {{ __('Enter the Cloud URL and paste the enrollment code when prompted') }}
                    </li>
                    <li>
                        {{ __('Enter the device IP address, username, and password') }}
                    </li>
                    <li>
                        {{ __('Double-click') }} <code class="rounded bg-zinc-200 px-1 py-0.5 text-xs dark:bg-zinc-700">start-agent.bat</code> {{ __('to start') }}
                    </li>
                </ol>

                <div class="mt-3 rounded border border-blue-200 bg-blue-50 p-2 text-xs text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
                    <strong>{{ __('Tip:') }}</strong> {{ __('For automatic startup, run install-service.bat as Administrator.') }}
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button wire:click="closeCodeModal">{{ __('Done') }}</flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Revoke Confirmation Modal --}}
    <flux:modal wire:model="show_revoke_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.no-symbol class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Revoke Enrollment') }}</flux:heading>
                    <flux:subheading>{{ __('This code will no longer be valid.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to revoke this enrollment code? If an agent has not yet registered using this code, it will no longer be able to.') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeRevokeModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="revokeEnrollment" variant="danger">{{ __('Revoke') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
