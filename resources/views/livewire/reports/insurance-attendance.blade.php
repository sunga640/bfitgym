<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Insurance Attendance') }}</flux:heading>
            <flux:subheading>{{ __('Attendance summaries for insured members.') }}</flux:subheading>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-4">
            <flux:field>
                <flux:label>{{ __('From') }}</flux:label>
                <flux:input type="date" wire:model.live="from" />
            </flux:field>
            <flux:field>
                <flux:label>{{ __('To') }}</flux:label>
                <flux:input type="date" wire:model.live="to" />
            </flux:field>
            <flux:field class="sm:col-span-2">
                <flux:label>{{ __('Insurer') }}</flux:label>
                <flux:select wire:model.live="insurer_id">
                    <option value="">{{ __('All insurers') }}</option>
                    @foreach($insurers as $insurer)
                        <option value="{{ $insurer->id }}">{{ $insurer->name }}</option>
                    @endforeach
                </flux:select>
            </flux:field>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <flux:heading size="lg" class="mb-4">{{ __('Summary by Insurer') }}</flux:heading>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Insurer') }}</th>
                        <th class="px-4 py-3">{{ __('Visits') }}</th>
                        <th class="px-4 py-3">{{ __('Unique Members') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($summary as $row)
                        <tr>
                            <td class="px-4 py-3">{{ $row->insurer->name ?? __('Unknown') }}</td>
                            <td class="px-4 py-3">{{ $row->visits }}</td>
                            <td class="px-4 py-3">{{ $row->unique_members }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">
                                {{ __('No attendance data for this period.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($insurer_id && count($members) > 0)
        <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <flux:heading size="lg" class="mb-4">{{ __('Member Visits') }}</flux:heading>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-zinc-200 bg-zinc-50 text-xs uppercase text-zinc-600 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Member') }}</th>
                            <th class="px-4 py-3">{{ __('Visits') }}</th>
                            <th class="px-4 py-3">{{ __('First Visit') }}</th>
                            <th class="px-4 py-3">{{ __('Last Visit') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($members as $memberRow)
                            <tr>
                                <td class="px-4 py-3">{{ $memberRow->member->full_name ?? $memberRow->member->first_name }}</td>
                                <td class="px-4 py-3">{{ $memberRow->visits }}</td>
                                <td class="px-4 py-3">{{ \Carbon\Carbon::parse($memberRow->first_visit)->toDateString() }}</td>
                                <td class="px-4 py-3">{{ \Carbon\Carbon::parse($memberRow->last_visit)->toDateString() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

