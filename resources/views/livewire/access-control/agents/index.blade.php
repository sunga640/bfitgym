<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Access Control Agents') }}</flux:heading>
            <flux:subheading>{{ __('Local agents (branch PCs) that execute LAN device commands.') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('access-control.enrollments.index') }}" wire:navigate icon="key">
                {{ __('Enrollment Codes') }}
            </flux:button>
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

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Agent') }}</th>
                        <th class="px-4 py-3">{{ __('OS / Version') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Last Seen') }}</th>
                        <th class="px-4 py-3">{{ __('Last Error') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($agents as $agent)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div>
                                    <a href="{{ route('access-control.agents.show', $agent) }}" wire:navigate class="font-medium text-zinc-900 hover:underline dark:text-white">
                                        {{ $agent->name }}
                                    </a>
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                        <code class="rounded bg-zinc-100 px-2 py-1 text-[11px] dark:bg-zinc-700">{{ $agent->uuid }}</code>
                                    </p>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-zinc-700 dark:text-zinc-300">
                                    {{ strtoupper($agent->os) }}
                                    @if($agent->app_version)
                                        <span class="text-zinc-400">•</span> {{ $agent->app_version }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($agent->status === 'active')
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                        {{ __('Active') }}
                                    </span>
                                    @if($agent->isLastSeenStale($stale_minutes))
                                        <span class="ml-2 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" title="{{ __('Agent has not checked in recently') }}">
                                            <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                                            {{ __('Agent Offline') }}
                                        </span>
                                    @endif
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-zinc-400"></span>
                                        {{ __('Revoked') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($agent->last_seen_at)
                                    <span class="text-zinc-600 dark:text-zinc-400" title="{{ $agent->last_seen_at->format('Y-m-d H:i:s') }}">
                                        {{ $agent->last_seen_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">{{ __('Never') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($agent->last_error)
                                    <span class="text-xs text-zinc-600 dark:text-zinc-300">{{ \Illuminate\Support\Str::limit($agent->last_error, 120) }}</span>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <flux:button
                                        href="{{ route('access-control.agents.show', $agent) }}"
                                        wire:navigate
                                        variant="ghost"
                                        size="sm"
                                        icon="eye"
                                        title="{{ __('View') }}"
                                    />
                                    @if($agent->status === 'active')
                                        <flux:button
                                            wire:click="confirmRevoke({{ $agent->id }})"
                                            variant="ghost"
                                            size="sm"
                                            icon="no-symbol"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            title="{{ __('Revoke') }}"
                                        />
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.cpu-chip class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('No agents yet') }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Generate an enrollment code and register the local agent.') }}</p>
                                    </div>
                                    <flux:button href="{{ route('access-control.enrollments.index') }}" wire:navigate icon="key" size="sm">
                                        {{ __('Generate Enrollment Code') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($agents->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $agents->links() }}
            </div>
        @endif
    </div>

    <flux:modal wire:model="show_revoke_modal" class="max-w-md">
        <div class="space-y-4">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/30">
                    <flux:icon.no-symbol class="h-5 w-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Revoke Agent') }}</flux:heading>
                    <flux:subheading>{{ __('This agent will no longer be able to authenticate.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Are you sure you want to revoke this agent?') }}
            </p>

            <div class="flex justify-end gap-3">
                <flux:button wire:click="closeModal" variant="ghost">{{ __('Cancel') }}</flux:button>
                <flux:button wire:click="revoke" variant="danger">{{ __('Revoke') }}</flux:button>
            </div>
        </div>
    </flux:modal>
</div>
