<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 lg:flex-row lg:items-end">
        <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search members...') }}"
                class="w-full md:max-w-xs"
            />
            <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('All Status') }}</option>
                <option value="active">{{ __('Active') }}</option>
                <option value="inactive">{{ __('Inactive') }}</option>
                <option value="suspended">{{ __('Suspended') }}</option>
            </flux:select>
            <flux:select wire:model.live="insurance_filter" class="w-full md:max-w-[150px]">
                <option value="">{{ __('Insurance') }}</option>
                <option value="1">{{ __('Has Insurance') }}</option>
                <option value="0">{{ __('No Insurance') }}</option>
            </flux:select>
        </div>
        <div class="text-sm text-zinc-500 dark:text-zinc-400">
            {{ trans_choice(':count member|:count members', $members->total()) }}
        </div>
    </div>
    <div class="space-y-3 md:hidden">
        @forelse($members as $member)
            <div wire:key="member-card-{{ $member->id }}" class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="truncate font-semibold text-zinc-900 dark:text-white">{{ $member->full_name }}</div>
                        <div class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $member->member_no }}</div>
                    </div>
                    <flux:badge :color="$member->status === 'active' ? 'emerald' : ($member->status === 'suspended' ? 'rose' : 'zinc')" size="sm">
                        {{ ucfirst($member->status) }}
                    </flux:badge>
                </div>

                <dl class="mt-4 grid gap-3 text-sm">
                    <div>
                        <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</dt>
                        <dd class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $member->phone }}</dd>
                        @if($member->email)
                            <dd class="break-words text-zinc-500 dark:text-zinc-400">{{ $member->email }}</dd>
                        @endif
                    </div>

                    @if($showBranch)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</dt>
                            <dd class="mt-1 text-zinc-800 dark:text-zinc-100">{{ $member->branch?->name ?? __('Not assigned') }}</dd>
                        </div>
                    @endif

                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Insurance') }}</dt>
                            <dd class="mt-1">
                                @if($member->has_insurance)
                                    <flux:badge color="emerald" size="sm">{{ __('Yes') }}</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">{{ __('No') }}</flux:badge>
                                @endif
                            </dd>
                        </div>
                        <div class="text-right">
                            <dt class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</dt>
                            <dd class="mt-1 text-zinc-700 dark:text-zinc-200">{{ $member->created_at?->format('M d, Y') }}</dd>
                        </div>
                    </div>
                </dl>

                <div class="mt-4 flex flex-wrap justify-end gap-2 border-t border-zinc-100 pt-3 dark:border-zinc-700">
                    <flux:button variant="ghost" size="sm" href="{{ route('members.show', $member) }}" wire:navigate icon="eye">
                        {{ __('View') }}
                    </flux:button>
                    @can('edit members')
                        <flux:button variant="ghost" size="sm" href="{{ route('members.edit', $member) }}" wire:navigate icon="pencil">
                            {{ __('Edit') }}
                        </flux:button>
                    @endcan
                    @can('delete members')
                        <flux:button
                            variant="ghost"
                            size="sm"
                            wire:click="deleteMember({{ $member->id }})"
                            wire:confirm="{{ __('Are you sure you want to delete this member?') }}"
                            icon="trash"
                            class="text-red-600 hover:text-red-700 dark:text-red-400"
                        >
                            {{ __('Delete') }}
                        </flux:button>
                    @endcan
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-800">
                <flux:icon name="user-group" class="mx-auto h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No members found') }}</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first member.') }}</p>
            </div>
        @endforelse

        @if($members->hasPages())
            <div class="rounded-xl border border-zinc-200 bg-white px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800">
                {{ $members->links() }}
            </div>
        @endif
    </div>

    <div class="hidden overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 md:block">
        <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Contact') }}</th>
                @if($showBranch)
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}</th>
                @endif
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Insurance') }}</th>
                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Joined') }}</th>
                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">{{ __('Actions') }}</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
            @forelse($members as $member)
                <tr wire:key="member-{{ $member->id }}">
                    <td class="px-6 py-4">
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $member->full_name }}</div>
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $member->member_no }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-zinc-900 dark:text-white">{{ $member->phone }}</div>
                        <div class="text-xs text-zinc-400">{{ $member->email }}</div>
                    </td>
                    @if($showBranch)
                    <td class="px-6 py-4">
                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $member->branch?->name }}</span>
                    </td>
                    @endif
                    <td class="px-6 py-4">
                        <flux:badge :color="$member->status === 'active' ? 'emerald' : ($member->status === 'suspended' ? 'rose' : 'zinc')" size="sm">{{ ucfirst($member->status) }}</flux:badge>
                    </td>
                    <td class="px-6 py-4">
                        @if($member->has_insurance)
                            <flux:badge color="emerald" size="sm">{{ __('Yes') }}</flux:badge>
                        @else
                            <flux:badge color="zinc" size="sm">{{ __('No') }}</flux:badge>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                        {{ $member->created_at?->format('M d, Y') }}
                    </td>
                    <td class="px-6 py-4 text-right text-sm font-medium">
                        <div class="flex items-center justify-end gap-2">
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('members.show', $member) }}"
                                wire:navigate
                                icon="eye"
                            >
                                {{ __('View') }}
                            </flux:button>
                            @can('edit members')
                            <flux:button
                                variant="ghost"
                                size="sm"
                                href="{{ route('members.edit', $member) }}"
                                wire:navigate
                                icon="pencil"
                            >
                                {{ __('Edit') }}
                            </flux:button>
                            @endcan
                            @can('delete members')
                            <flux:button
                                variant="ghost"
                                size="sm"
                                wire:click="deleteMember({{ $member->id }})"
                                wire:confirm="{{ __('Are you sure you want to delete this member?') }}"
                                icon="trash"
                                class="text-red-600 hover:text-red-700 dark:text-red-400"
                            >
                                {{ __('Delete') }}
                            </flux:button>
                            @endcan
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $showBranch ? 7 : 6 }}" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <flux:icon name="user-group" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                            <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No members found') }}</h3>
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Get started by adding your first member.') }}</p>
                        </div>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
        @if($members->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $members->links() }}
            </div>
        @endif
    </div>
</div>
