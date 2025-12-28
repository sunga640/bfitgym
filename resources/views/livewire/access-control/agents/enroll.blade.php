<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Agent Enrollment') }}</flux:heading>
            <flux:subheading>{{ __('Generate one-time enrollment codes (expires in 30 minutes).') }}</flux:subheading>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('access-control.agents.index') }}" wire:navigate variant="ghost" icon="arrow-left">
                {{ __('Back to Agents') }}
            </flux:button>
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

    <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('Use this code in the Local Agent to register and obtain its view-once token.') }}
            </div>
            <flux:button wire:click="generateEnrollmentCode" icon="key">
                {{ __('Generate Enrollment Code') }}
            </flux:button>
        </div>

        @if($enrollment_code)
            <div class="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50"
                 x-data="{ copied: false }">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-xs uppercase text-zinc-500 dark:text-zinc-400">{{ __('Enrollment Code') }}</div>
                        <div class="mt-1 font-mono text-sm text-zinc-900 dark:text-white">
                            <code class="rounded bg-white px-2 py-1 dark:bg-zinc-800">{{ $enrollment_code }}</code>
                        </div>
                        <div class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __('Expires at') }}:
                            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ \Illuminate\Support\Carbon::parse($expires_at)->format('Y-m-d H:i:s') }}</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <flux:button
                            variant="ghost"
                            icon="clipboard"
                            x-on:click="
                                navigator.clipboard.writeText(@js($enrollment_code));
                                copied = true;
                                setTimeout(() => copied = false, 1200);
                            "
                        >
                            <span x-show="!copied">{{ __('Copy') }}</span>
                            <span x-show="copied">{{ __('Copied') }}</span>
                        </flux:button>
                    </div>
                </div>

                <div class="mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Security note: the agent token is shown only once during registration and is never displayed in the admin UI.') }}
                </div>
            </div>
        @endif
    </div>
</div>
