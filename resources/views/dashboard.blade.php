<x-layouts.app :title="__('Dashboard')" :description="__('Welcome back! Here\'s an overview of your gym.')">
    {{-- Stats Grid --}}
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- Active Members --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30">
                    <flux:icon name="user-group" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Members') }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">0</p>
                </div>
            </div>
        </div>

        {{-- Today's Check-ins --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30">
                    <flux:icon name="finger-print" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Today\'s Check-ins') }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">0</p>
                </div>
            </div>
        </div>

        {{-- Revenue (Month) --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                    <flux:icon name="banknotes" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Revenue (Month)') }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">TZS 0</p>
                </div>
            </div>
        </div>

        {{-- Expiring Subscriptions --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                </div>
                <div>
                    <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Expiring Soon') }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">0</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Recent Activity --}}
        <div class="lg:col-span-2">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Recent Activity') }}</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <flux:icon name="clock" class="h-12 w-12 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No recent activity to display.') }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="space-y-6">
            {{-- Quick Actions Card --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Quick Actions') }}</h2>
                </div>
                <div class="space-y-2 p-4">
                    @can('create members')
                    <flux:button variant="ghost" class="w-full justify-start" href="{{ route('members.create') }}" wire:navigate icon="user-plus">
                        {{ __('Add New Member') }}
                    </flux:button>
                    @endcan

                    @can('create subscriptions')
                    <flux:button variant="ghost" class="w-full justify-start" href="{{ route('subscriptions.create') }}" wire:navigate icon="credit-card">
                        {{ __('New Subscription') }}
                    </flux:button>
                    @endcan

                    @can('create class bookings')
                    <flux:button variant="ghost" class="w-full justify-start" href="{{ route('class-bookings.create') }}" wire:navigate icon="ticket">
                        {{ __('Book a Class') }}
                    </flux:button>
                    @endcan

                    @can('view pos')
                    <flux:button variant="ghost" class="w-full justify-start" href="{{ route('pos.index') }}" wire:navigate icon="shopping-cart">
                        {{ __('Point of Sale') }}
                    </flux:button>
                    @endcan
                </div>
            </div>

            {{-- Today's Classes --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Today\'s Classes') }}</h2>
                </div>
                <div class="p-6">
                    <div class="flex flex-col items-center justify-center py-6 text-center">
                        <flux:icon name="calendar" class="h-8 w-8 text-zinc-300 dark:text-zinc-600" />
                        <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No classes scheduled for today.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
