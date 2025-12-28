<div class="flex h-full w-full flex-1 flex-col gap-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Member Policies') }}</flux:heading>
            <flux:subheading>{{ __('Manage insurance policies for members.') }}</flux:subheading>
        </div>
        <flux:button href="{{ route('member-insurances.create') }}" wire:navigate icon="plus">
            {{ __('Add Policy') }}
        </flux:button>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Filters --}}
    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-end">
            <div class="flex flex-1 flex-col gap-4 md:flex-row md:items-center">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    icon="magnifying-glass"
                    placeholder="{{ __('Search member, insurer, policy...') }}"
                    class="w-full md:max-w-xs"
                    clearable
                />
                <flux:select wire:model.live="insurer_filter" class="w-full md:max-w-[180px]">
                    <option value="">{{ __('All Insurers') }}</option>
                    @foreach($insurers as $insurer)
                        <option value="{{ $insurer->id }}">{{ $insurer->name }}</option>
                    @endforeach
                </flux:select>
                <flux:select wire:model.live="status_filter" class="w-full md:max-w-[150px]">
                    <option value="">{{ __('All Status') }}</option>
                    <option value="active">{{ __('Active') }}</option>
                    <option value="expired">{{ __('Expired') }}</option>
                    <option value="cancelled">{{ __('Cancelled') }}</option>
                </flux:select>
            </div>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ trans_choice(':count policy|:count policies', $insurances->total()) }}
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Member') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Insurer') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Policy #') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Coverage') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Period') }}
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Status') }}
                        </th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            {{ __('Actions') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse($insurances as $insurance)
                        <tr wire:key="insurance-{{ $insurance->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-zinc-900 dark:text-white">{{ $insurance->member->full_name ?? $insurance->member->first_name }}</div>
                                <div class="text-sm text-zinc-500 dark:text-zinc-400">{{ $insurance->member->member_no }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-white">{{ $insurance->insurer->name ?? '—' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $insurance->policy_number ?: '—' }}</code>
                            </td>
                            <td class="px-6 py-4 text-sm text-zinc-600 dark:text-zinc-300">
                                {{ $insurance->coverage_type ?: '—' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-zinc-900 dark:text-white">
                                    {{ $insurance->start_date?->format('M d, Y') }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    @if($insurance->end_date)
                                        → {{ $insurance->end_date->format('M d, Y') }}
                                    @else
                                        → {{ __('Indefinite') }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'active' => 'emerald',
                                        'expired' => 'amber',
                                        'cancelled' => 'rose',
                                    ];
                                @endphp
                                <flux:badge :color="$statusColors[$insurance->status] ?? 'zinc'" size="sm">
                                    {{ ucfirst($insurance->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('member-insurances.show', $insurance) }}" wire:navigate variant="ghost" size="sm" icon="eye" title="{{ __('View') }}" />
                                    <flux:button href="{{ route('member-insurances.edit', $insurance) }}" wire:navigate variant="ghost" size="sm" icon="pencil" title="{{ __('Edit') }}" />

                                    @if($canManage)
                                        <flux:dropdown>
                                            <flux:button variant="ghost" size="sm" icon="ellipsis-vertical" />

                                            <flux:menu>
                                                <flux:menu.heading>{{ __('Change Status') }}</flux:menu.heading>

                                                @foreach(['active', 'expired', 'cancelled'] as $status)
                                                    @if($status !== $insurance->status)
                                                        <flux:menu.item
                                                            wire:click="updateStatus({{ $insurance->id }}, '{{ $status }}')"
                                                            wire:confirm="{{ __('Change status to :status?', ['status' => ucfirst($status)]) }}"
                                                            class="{{ $status === 'cancelled' ? 'text-red-600 dark:text-red-400' : '' }}"
                                                        >
                                                            @if($status === 'active')
                                                                <flux:icon name="check-circle" class="mr-2 h-4 w-4 text-emerald-500" />
                                                            @elseif($status === 'expired')
                                                                <flux:icon name="clock" class="mr-2 h-4 w-4 text-amber-500" />
                                                            @else
                                                                <flux:icon name="x-circle" class="mr-2 h-4 w-4 text-red-500" />
                                                            @endif
                                                            {{ __('Mark as :status', ['status' => ucfirst($status)]) }}
                                                        </flux:menu.item>
                                                    @endif
                                                @endforeach

                                                <flux:menu.separator />

                                                <flux:menu.item
                                                    wire:click="deleteInsurance({{ $insurance->id }})"
                                                    wire:confirm="{{ __('Are you sure you want to delete this insurance record?') }}"
                                                    class="text-red-600 dark:text-red-400"
                                                >
                                                    <flux:icon name="trash" class="mr-2 h-4 w-4" />
                                                    {{ __('Delete') }}
                                                </flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <flux:icon name="clipboard-document-list" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                                    <h3 class="mt-4 text-sm font-medium text-zinc-900 dark:text-white">{{ __('No insurance policies found') }}</h3>
                                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                        @if($search || $status_filter || $insurer_filter)
                                            {{ __('Try adjusting your search or filters.') }}
                                        @else
                                            {{ __('Insurance policies will appear here when members are assigned insurance.') }}
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($insurances->hasPages())
            <div class="border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                {{ $insurances->links() }}
            </div>
        @endif
    </div>
</div>
