<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __(':integration Event Logs', ['integration' => $integration_label]) }}</flux:heading>
            <flux:subheading>{{ __('Attendance/access events received from :integration sources.', ['integration' => $integration_label]) }}</flux:subheading>
        </div>
    </div>

    <div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 sm:flex-row sm:items-center">
        <div class="flex-1">
            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search by device or device user ID...') }}"
                clearable
            />
        </div>

        <flux:select wire:model.live="direction_filter" class="w-44">
            <option value="">{{ __('All Directions') }}</option>
            <option value="in">{{ __('In') }}</option>
            <option value="out">{{ __('Out') }}</option>
            <option value="unknown">{{ __('Unknown') }}</option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Time') }}</th>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3">{{ __('Device User ID') }}</th>
                        <th class="px-4 py-3">{{ __('Subject') }}</th>
                        <th class="px-4 py-3">{{ __('Direction') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($logs as $log)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <td class="px-4 py-3">
                                <span class="text-zinc-700 dark:text-zinc-200">{{ $log->event_timestamp?->format('Y-m-d H:i:s') }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-zinc-900 dark:text-white">{{ $log->accessControlDevice?->name ?? '-' }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $log->accessIdentity?->device_user_id ?? '-' }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-zinc-700 dark:text-zinc-200">{{ ucfirst($log->subject_type) }} #{{ $log->subject_id }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ strtoupper($log->direction) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No logs found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
