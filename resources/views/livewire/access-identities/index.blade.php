<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __(':integration Identities', ['integration' => $integration_label]) }}</flux:heading>
            <flux:subheading>{{ __('Map members and staff to device user IDs / cards.') }}</flux:subheading>
        </div>
        @if($can_manage)
            <flux:button href="{{ route($route_prefix . '.create') }}" wire:navigate icon="plus">
                {{ __('Add Identity') }}
            </flux:button>
        @endif
    </div>

    <div class="flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search device user ID or card number...') }}"
                clearable
            />
        </div>

        <div class="flex flex-wrap gap-3">
            <flux:select wire:model.live="subject_filter" class="w-40">
                <option value="">{{ __('All Subjects') }}</option>
                <option value="member">{{ __('Members') }}</option>
                <option value="staff">{{ __('Staff') }}</option>
            </flux:select>

            <flux:select wire:model.live="status_filter" class="w-36">
                <option value="">{{ __('All Status') }}</option>
                <option value="1">{{ __('Active') }}</option>
                <option value="0">{{ __('Inactive') }}</option>
            </flux:select>
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
                        <th class="px-4 py-3">{{ __('Subject') }}</th>
                        <th class="px-4 py-3">{{ __('Device User ID') }}</th>
                        <th class="px-4 py-3">{{ __('Card Number') }}</th>
                        <th class="px-4 py-3 text-center">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($identities as $identity)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    @if($identity->subject_type === 'member' && $identity->member)
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $identity->member->full_name ?? $identity->member->first_name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</span>
                                    @elseif($identity->subject_type === 'staff' && $identity->staff)
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $identity->staff->name }}</span>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Staff') }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $identity->device_user_id }}</code>
                            </td>
                            <td class="px-4 py-3">
                                @if($identity->card_number)
                                    <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $identity->card_number }}</code>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($can_manage)
                                    <button
                                        wire:click="toggleStatus({{ $identity->id }})"
                                        class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium transition-colors
                                            {{ $identity->is_active
                                                ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400'
                                                : 'bg-zinc-100 text-zinc-600 hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-400' }}"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full {{ $identity->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                        {{ $identity->is_active ? __('Active') : __('Inactive') }}
                                    </button>
                                @else
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $identity->is_active ? 'bg-emerald-500' : 'bg-zinc-400' }}"></span>
                                        {{ $identity->is_active ? __('Active') : __('Inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($can_manage)
                                    <div class="flex items-center justify-end gap-1">
                                        <flux:button
                                            href="{{ route($route_prefix . '.edit', $identity) }}"
                                            wire:navigate
                                            variant="ghost"
                                            size="sm"
                                            icon="pencil"
                                            title="{{ __('Edit') }}"
                                        />
                                        <flux:button
                                            wire:click="delete({{ $identity->id }})"
                                            variant="ghost"
                                            size="sm"
                                            icon="trash"
                                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                                            title="{{ __('Delete') }}"
                                        />
                                    </div>
                                @else
                                    <span class="text-zinc-400 dark:text-zinc-500">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center gap-3">
                                    <flux:icon.identification class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <div>
                                        <p class="font-medium text-zinc-900 dark:text-white">{{ __('No identities found') }}</p>
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Create an identity to allow device access.') }}</p>
                                    </div>
                                    @if($can_manage)
                                        <flux:button href="{{ route($route_prefix . '.create') }}" wire:navigate icon="plus" size="sm">
                                            {{ __('Add Identity') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($identities->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $identities->links() }}
            </div>
        @endif
    </div>
</div>
