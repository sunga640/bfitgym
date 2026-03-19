<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('CVSecurity Events') }}</flux:heading>
            <flux:subheading>{{ __('Recent access and attendance events collected through local agents.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" href="{{ route('zkteco.connections.index') }}" wire:navigate>
            {{ __('Back To Integrations') }}
        </flux:button>
    </div>

    <div class="grid gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:grid-cols-4">
        <div class="sm:col-span-2">
            <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="{{ __('Search member/device/event') }}" clearable />
        </div>
        <flux:select wire:model.live="connection_id">
            <option value="">{{ __('All Integrations') }}</option>
            @foreach($connections as $connection)
                <option value="{{ $connection->id }}">{{ $connection->name }}</option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="event_type">
            <option value="">{{ __('All Event Types') }}</option>
            @foreach($event_types as $type)
                <option value="{{ $type }}">{{ $type }}</option>
            @endforeach
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Occurred At') }}</th>
                        <th class="px-4 py-3">{{ __('Integration') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Member') }}</th>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3">{{ __('External ID') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($events as $event)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                            <td class="px-4 py-3">{{ $event->occurred_at?->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3">{{ $event->connection?->name ?: '-' }}</td>
                            <td class="px-4 py-3">{{ $event->event_type }}</td>
                            <td class="px-4 py-3">
                                @if($event->member)
                                    {{ $event->member->first_name }} {{ $event->member->last_name }}
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">({{ $event->member->member_no }})</span>
                                @else
                                    {{ $event->external_person_id ?: __('Unknown') }}
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $event->device_id ?: '-' }}</td>
                            <td class="px-4 py-3"><code>{{ $event->external_event_id ?: '-' }}</code></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-zinc-500 dark:text-zinc-400">{{ __('No CVSecurity events found.') }}</td>
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

