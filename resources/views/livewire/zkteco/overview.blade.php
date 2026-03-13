<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div>
        <flux:heading size="xl">{{ __('ZKTeco Integration') }}</flux:heading>
        <flux:subheading>{{ __('Platform-first integration with optional local-agent fallback.') }}</flux:subheading>
    </div>

    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Mode') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ strtoupper($config?->mode ?? 'platform') }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Provider') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $config?->provider ?? 'zkbio_platform' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Health') }}</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ strtoupper($config?->health_status ?? 'unknown') }}</p>
            </div>
        </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Devices') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($device_count) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Identities') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($identity_count) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Events Today') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($log_count_today) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Agent Enrollments') }}</p>
            <p class="mt-1 text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($enrollment_count) }}</p>
        </div>
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <flux:button href="{{ route('zkteco.devices.index') }}" wire:navigate variant="ghost" class="justify-start">
            {{ __('Devices') }}
        </flux:button>
        <flux:button href="{{ route('zkteco.logs.index') }}" wire:navigate variant="ghost" class="justify-start">
            {{ __('Events / Logs') }}
        </flux:button>
        <flux:button href="{{ route('zkteco.settings') }}" wire:navigate variant="ghost" class="justify-start">
            {{ __('Integration Settings') }}
        </flux:button>
    </div>
</div>

