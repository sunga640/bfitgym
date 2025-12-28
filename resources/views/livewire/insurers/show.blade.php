<div>
    <div class="mx-auto max-w-3xl rounded-xl border border-zinc-200 bg-white p-8 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-start justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $insurer->name }}</h1>
                <div class="mt-2">
                    <flux:badge :color="$insurer->status === 'active' ? 'emerald' : 'zinc'" size="sm">
                        {{ ucfirst($insurer->status) }}
                    </flux:badge>
                </div>
            </div>
            <div class="flex gap-2">
                @can('manage insurers')
                <flux:button variant="ghost" href="{{ route('insurers.edit', $insurer) }}" wire:navigate icon="pencil">
                    {{ __('Edit') }}
                </flux:button>
                @endcan
            </div>
        </div>

        <div class="mt-8 grid gap-6 sm:grid-cols-2">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Contact Person') }}</p>
                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurer->contact_person ?: '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Phone') }}</p>
                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurer->phone ?: '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Email') }}</p>
                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurer->email ?: '—' }}</p>
            </div>
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Policies') }}</p>
                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurer->member_insurances_count }}</p>
            </div>
            <div class="sm:col-span-2">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Address') }}</p>
                <p class="font-medium text-zinc-900 dark:text-white">{{ $insurer->address ?: '—' }}</p>
            </div>
        </div>

        <div class="mt-8 flex gap-2 border-t border-zinc-200 pt-6 dark:border-zinc-700">
            <flux:button variant="ghost" href="{{ route('insurers.index') }}" wire:navigate>
                {{ __('Back to list') }}
            </flux:button>
        </div>
    </div>
</div>

