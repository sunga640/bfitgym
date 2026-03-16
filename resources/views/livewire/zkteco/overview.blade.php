<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('ZKTeco Integration') }}</flux:heading>
            <flux:subheading>{{ __('FitHub -> ZKBio server -> turnstile devices') }}</flux:subheading>
        </div>

        <div class="flex gap-2">
            <flux:button href="{{ route('zkteco.settings') }}" wire:navigate variant="primary">
                {{ __('Manage Connection') }}
            </flux:button>
            <flux:button href="{{ route('zkteco.logs.index') }}" wire:navigate variant="ghost">
                {{ __('View Events') }}
            </flux:button>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ strtoupper($health['status']) }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Test') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_test_success_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Personnel Sync') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_personnel_sync_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Last Event Sync') }}</p>
                <p class="mt-1 font-medium text-zinc-900 dark:text-white">{{ $health['last_event_sync_at']?->format('Y-m-d H:i') ?? '-' }}</p>
            </div>
        </div>

        @if($health['last_error'])
            <p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-sm text-rose-700 dark:bg-rose-900/20 dark:text-rose-300">
                {{ $health['last_error'] }}
            </p>
        @endif
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Online Devices') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($health['online_devices_count']) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mapped Devices') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($mapped_devices) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Events Today') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($event_count_today) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Biometric Pending') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($pending_biometrics) }}</p>
        </div>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 px-5 py-3 dark:border-zinc-700">
            <p class="font-medium text-zinc-900 dark:text-white">{{ __('Recent Sync Runs') }}</p>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            @forelse($recent_runs as $run)
                <div class="flex items-center justify-between px-5 py-3 text-sm">
                    <div>
                        <p class="font-medium text-zinc-900 dark:text-white">{{ str($run->run_type)->replace('_', ' ')->title() }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ $run->started_at?->format('Y-m-d H:i:s') }}</p>
                    </div>
                    <div class="text-right">
                        <p class="font-medium text-zinc-900 dark:text-white">{{ strtoupper($run->status) }}</p>
                        <p class="text-zinc-500 dark:text-zinc-400">{{ __('ok: :ok | failed: :failed', ['ok' => $run->records_success, 'failed' => $run->records_failed]) }}</p>
                    </div>
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                    {{ __('No ZKTeco sync runs yet.') }}
                </div>
            @endforelse
        </div>
    </div>
</div>

