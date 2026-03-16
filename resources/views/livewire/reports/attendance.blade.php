<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="grid gap-4 sm:grid-cols-3 lg:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Events') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($summary['total']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('HIKVision Events') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($summary['hikvision']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('ZKTeco Events') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($summary['zkteco']) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-5">
            <flux:input type="date" wire:model.live="date_from" />
            <flux:input type="date" wire:model.live="date_to" />

            @if($can_switch_branches)
                <flux:select wire:model.live="branch_id">
                    <option value="">{{ __('All Branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch['id'] }}">{{ $branch['name'] }}</option>
                    @endforeach
                </flux:select>
            @endif

            <flux:select wire:model.live="integration_type">
                <option value="">{{ __('All Integrations') }}</option>
                <option value="hikvision">{{ __('HIKVision') }}</option>
                <option value="zkteco">{{ __('ZKTeco') }}</option>
            </flux:select>

            <flux:select wire:model.live="provider">
                @foreach($provider_options as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="device">
                <option value="">{{ __('All Devices') }}</option>
                @foreach($device_options as $option)
                    <option value="{{ $option['key'] }}">{{ $option['label'] }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="direction">
                <option value="">{{ __('All Directions') }}</option>
                <option value="in">{{ __('In') }}</option>
                <option value="out">{{ __('Out') }}</option>
                <option value="unknown">{{ __('Unknown') }}</option>
            </flux:select>

            <flux:input
                wire:model.live.debounce.300ms="search"
                icon="magnifying-glass"
                placeholder="{{ __('Search member/device/event...') }}"
                clearable
            />

            <div class="flex items-center">
                <flux:button variant="ghost" wire:click="clearFilters" class="w-full">
                    {{ __('Reset Filters') }}
                </flux:button>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Time') }}</th>
                        <th class="px-4 py-3">{{ __('Integration') }}</th>
                        <th class="px-4 py-3">{{ __('Provider') }}</th>
                        <th class="px-4 py-3">{{ __('Device') }}</th>
                        <th class="px-4 py-3">{{ __('Person') }}</th>
                        <th class="px-4 py-3">{{ __('Device User') }}</th>
                        <th class="px-4 py-3">{{ __('Direction') }}</th>
                        <th class="px-4 py-3">{{ __('Event Ref') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($events as $event)
                        @php
                            $subject_name = trim((string) ($event->subject_first_name ?? '') . ' ' . (string) ($event->subject_last_name ?? ''));
                            if ($subject_name === '') {
                                $subject_name = __('Unknown');
                            }
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/40">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ \Illuminate\Support\Carbon::parse($event->event_timestamp)->format('Y-m-d H:i:s') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ strtoupper((string) $event->integration_type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                {{ (string) $event->provider }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                <div>{{ $event->source_device_name ?? '-' }}</div>
                                @if(!empty($event->source_agent_name))
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event->source_agent_name }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-200">
                                <div>{{ $subject_name }}</div>
                                @if(!empty($event->subject_code))
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $event->subject_code }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $event->device_user_id ?: '-' }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                                    {{ strtoupper((string) $event->direction) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <code class="rounded bg-zinc-100 px-2 py-1 text-xs dark:bg-zinc-700">{{ $event->event_uid ?: '-' }}</code>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No attendance events found for the selected filters.') }}
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
