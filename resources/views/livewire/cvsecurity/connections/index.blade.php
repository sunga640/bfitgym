<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('ZKTeco CVSecurity Integrations') }}</flux:heading>
            <flux:subheading>{{ __('Connect each branch to a local agent and CVSecurity environment.') }}</flux:subheading>
        </div>

        @if($can_manage)
            <flux:button variant="primary" href="{{ route('zkteco.connections.create') }}" wire:navigate>
                {{ __('New Integration') }}
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
                placeholder="{{ __('Search integrations...') }}"
                clearable
            />
        </div>

        <flux:select wire:model.live="status" class="w-48">
            <option value="">{{ __('All Statuses') }}</option>
            @foreach($statuses as $status_option)
                <option value="{{ $status_option }}">{{ strtoupper($status_option) }}</option>
            @endforeach
        </flux:select>
    </div>

    @if($connections->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-12 text-center dark:border-zinc-600 dark:bg-zinc-800">
            <flux:heading size="lg">{{ __('No CVSecurity Integration Yet') }}</flux:heading>
            <flux:subheading class="mt-2">{{ __('Create the first integration, generate a pairing token, then pair a local agent on the branch LAN.') }}</flux:subheading>
            @if($can_manage)
                <div class="mt-6">
                    <flux:button variant="primary" href="{{ route('zkteco.connections.create') }}" wire:navigate>
                        {{ __('Create Integration') }}
                    </flux:button>
                </div>
            @endif
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($connections as $connection)
                <a href="{{ route('zkteco.connections.show', $connection) }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition hover:border-zinc-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $connection->name }}</p>
                            <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $connection->cv_base_url ?: __('No CV host set') }}</p>
                        </div>
                        <flux:badge size="sm" color="{{ $connection->status === 'connected' ? 'emerald' : ($connection->status === 'error' ? 'rose' : 'zinc') }}">
                            {{ strtoupper($connection->status) }}
                        </flux:badge>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs">
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Agent') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ strtoupper($connection->agent_status) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('CVSecurity') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ strtoupper($connection->cvsecurity_status) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Pending Sync') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($connection->pending_sync_items_count) }}</p>
                        </div>
                        <div>
                            <p class="text-zinc-500 dark:text-zinc-400">{{ __('Events') }}</p>
                            <p class="font-medium text-zinc-900 dark:text-zinc-100">{{ number_format($connection->recent_events_count) }}</p>
                        </div>
                    </div>

                    <div class="mt-4 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Last heartbeat: :value', ['value' => $connection->last_heartbeat_at?->diffForHumans() ?? __('never')]) }}
                    </div>
                </a>
            @endforeach
        </div>

        <div>
            {{ $connections->links() }}
        </div>
    @endif
</div>

