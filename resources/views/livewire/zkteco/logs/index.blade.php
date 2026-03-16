<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('ZKTeco Entry Logs') }}</flux:heading>
            <flux:subheading>{{ __('Imported entry/exit records from ZKBio.') }}</flux:subheading>
        </div>

        @if($can_sync)
            <flux:button variant="primary" wire:click="syncNow">
                {{ __('Sync Logs Now') }}
            </flux:button>
        @endif
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

    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by event/member/device...') }}"
                clearable
            />
        </div>

        <flux:select wire:model.live="direction_filter" class="w-40">
            <option value="">{{ __('All Directions') }}</option>
            <option value="in">{{ __('In') }}</option>
            <option value="out">{{ __('Out') }}</option>
            <option value="unknown">{{ __('Unknown') }}</option>
        </flux:select>

        <flux:select wire:model.live="status_filter" class="w-48">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach($available_statuses as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Time') }}</th>
                        <th class="px-4 py-3">{{ __('Direction') }}</th>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3">{{ __('Member') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Remote Event') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($events as $event)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ $event->occurred_at?->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ strtoupper($event->direction) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ $event->device?->remote_name ?? $event->device?->remote_device_id ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                @if($event->member)
                                    {{ $event->member->first_name }} {{ $event->member->last_name }}
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $event->member->member_no }})</span>
                                @else
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Unmatched') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ $event->event_status ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">
                                    {{ $event->remote_event_id ?? '-' }}
                                </code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No ZKTeco access events found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($events->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $events->links() }}
            </div>
        @endif
    </div>
</div>

