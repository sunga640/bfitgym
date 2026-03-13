<div>
    @can('view dashboard analytics')
        {{-- Welcome Header --}}
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                    Good {{ now()->format('H') < 12 ? 'Morning' : (now()->format('H') < 17 ? 'Afternoon' : 'Evening') }}, {{ auth()->user()->first_name ?? explode(' ', auth()->user()->name)[0] }} 👋
                </h1>
                <p class="mt-1 text-zinc-500 dark:text-zinc-400">{{ __('Here\'s what\'s happening with your gym today') }}</p>
            </div>
            
            {{-- Search Bar (Desktop) --}}
            <div class="hidden lg:block">
                <div class="relative">
                    <flux:icon name="magnifying-glass" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" />
                    <input 
                        type="text" 
                        placeholder="{{ __('Search members, classes...') }}" 
                        class="w-72 rounded-xl border-0 bg-white/70 py-2.5 pl-10 pr-4 text-sm text-zinc-900 shadow-sm ring-1 ring-zinc-200/50 backdrop-blur-xl transition placeholder:text-zinc-400 focus:ring-2 focus:ring-orange-500/50 dark:bg-zinc-800/70 dark:text-white dark:ring-zinc-700/50 dark:placeholder:text-zinc-500"
                    />
                </div>
            </div>
        </div>

        {{-- Stats Grid --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- Active Members --}}
            <div class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-5 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-emerald-100 opacity-50 blur-2xl transition group-hover:opacity-70 dark:bg-emerald-900/30"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Active Members') }}</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->activeMembersCount) }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-900/30">
                        <flux:icon name="user-group" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                    </div>
                </div>
            </div>

            {{-- Today's Check-ins --}}
            <div class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-5 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-blue-100 opacity-50 blur-2xl transition group-hover:opacity-70 dark:bg-blue-900/30"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Today\'s Check-ins') }}</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->todayCheckins) }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 dark:bg-blue-900/30">
                        <flux:icon name="finger-print" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                </div>
            </div>

            {{-- Revenue (Month) --}}
            <div class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-5 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-amber-100 opacity-50 blur-2xl transition group-hover:opacity-70 dark:bg-amber-900/30"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Revenue (Month)') }}</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ money($this->monthlyRevenue) }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 dark:bg-amber-900/30">
                        <flux:icon name="banknotes" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                    </div>
                </div>
            </div>

            {{-- Expiring Subscriptions --}}
            <div class="group relative overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-5 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="absolute -right-4 -top-4 h-24 w-24 rounded-full bg-rose-100 opacity-50 blur-2xl transition group-hover:opacity-70 dark:bg-rose-900/30"></div>
                <div class="relative flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Expiring Soon') }}</p>
                        <p class="mt-1 text-3xl font-bold text-zinc-900 dark:text-white">{{ number_format($this->expiringSoonCount) }}</p>
                    </div>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-900/30">
                        <flux:icon name="exclamation-triangle" class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        <div class="mb-6 grid gap-6 lg:grid-cols-2">
            {{-- Attendance Chart --}}
            <div class="rounded-2xl border border-white/20 bg-white/70 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="flex items-center justify-between border-b border-zinc-200/50 px-6 py-4 dark:border-zinc-700/50">
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Attendance') }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total: :count check-ins', ['count' => number_format($this->attendanceChartData['total'])]) }}</p>
                    </div>
                    <div>
                        <flux:select wire:model.live="attendance_month" size="sm" class="!bg-white/50 dark:!bg-zinc-800/50">
                            @foreach($this->availableMonths as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="p-6">
                    <div wire:ignore>
                        <div 
                            x-data="attendanceChart()"
                            x-init="init(@js($this->attendanceChartData))"
                            @attendance-chart-update.window="updateChart($event.detail)"
                            x-ref="chartContainer"
                            class="h-64"
                        ></div>
                    </div>
                </div>
            </div>

            {{-- Revenue Chart --}}
            <div class="rounded-2xl border border-white/20 bg-white/70 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70">
                <div class="flex items-center justify-between border-b border-zinc-200/50 px-6 py-4 dark:border-zinc-700/50">
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Revenue') }}</h2>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total: :amount', ['amount' => money($this->revenueChartData['total'])]) }}</p>
                    </div>
                    <div>
                        <flux:select wire:model.live="revenue_month" size="sm" class="!bg-white/50 dark:!bg-zinc-800/50">
                            @foreach($this->availableMonths as $value => $label)
                                <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </div>
                <div class="p-6">
                    <div wire:ignore>
                        <div 
                            x-data="revenueChart()"
                            x-init="init(@js($this->revenueChartData), '{{ app_currency() }}')"
                            @revenue-chart-update.window="updateChart($event.detail.data, $event.detail.currency)"
                            x-ref="chartContainer"
                            class="h-64"
                        ></div>
                    </div>

                    {{-- Revenue by Type --}}
                    @if(!empty($this->revenueChartData['by_type']))
                        <div class="mt-4 grid grid-cols-2 gap-3 border-t border-zinc-200/50 pt-4 dark:border-zinc-700/50 sm:grid-cols-4">
                            @php
                                $type_colors = [
                                    'membership' => 'emerald',
                                    'class_booking' => 'blue',
                                    'event' => 'purple',
                                    'pos' => 'amber',
                                ];
                                $type_labels = [
                                    'membership' => __('Membership'),
                                    'class_booking' => __('Classes'),
                                    'event' => __('Events'),
                                    'pos' => __('POS'),
                                ];
                            @endphp
                            @foreach($this->revenueChartData['by_type'] as $type => $amount)
                                <div class="text-center">
                                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $type_labels[$type] ?? ucfirst($type) }}</p>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ money($amount) }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Main Content Grid --}}
        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Recent Activity --}}
            <div class="lg:col-span-2">
                <div class="rounded-2xl border border-white/20 bg-white/70 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="border-b border-zinc-200/50 px-6 py-4 dark:border-zinc-700/50">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Recent Activity') }}</h2>
                    </div>
                    <div class="p-6">
                        @if($this->recentActivities->isEmpty())
                            <div class="flex flex-col items-center justify-center py-12 text-center">
                                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                                    <flux:icon name="clock" class="h-8 w-8 text-zinc-300 dark:text-zinc-600" />
                                </div>
                                <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No recent activity to display.') }}</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($this->recentActivities as $activity)
                                    <div class="flex items-start gap-4 rounded-xl bg-zinc-50/50 p-3 transition hover:bg-zinc-100/50 dark:bg-zinc-900/30 dark:hover:bg-zinc-900/50">
                                        @php
                                            $color_classes = [
                                                'blue' => 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400',
                                                'emerald' => 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400',
                                                'amber' => 'bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400',
                                            ];
                                        @endphp
                                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl {{ $color_classes[$activity['color']] ?? $color_classes['blue'] }}">
                                            <flux:icon :name="$activity['icon']" class="h-5 w-5" />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <p class="truncate text-sm font-medium text-zinc-900 dark:text-white">{{ $activity['title'] }}</p>
                                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $activity['description'] }}</p>
                                        </div>
                                        <p class="whitespace-nowrap text-xs text-zinc-400 dark:text-zinc-500">
                                            {{ $activity['timestamp']?->diffForHumans() ?? '-' }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-6">
                {{-- Today's POS Sales --}}
                <div class="rounded-2xl border border-white/20 bg-white/70 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="border-b border-zinc-200/50 px-6 py-4 dark:border-zinc-700/50">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Today\'s POS Sales') }}</h2>
                    </div>
                    <div class="p-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ money($this->todayPosSales['total']) }}</p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ trans_choice(':count sale|:count sales', $this->todayPosSales['count']) }}</p>
                            </div>
                            @can('view pos')
                                <flux:button variant="primary" size="sm" href="{{ route('pos.index') }}" wire:navigate icon="shopping-cart" class="!bg-gradient-to-r !from-orange-500 !to-rose-500 !border-0">
                                    {{ __('New Sale') }}
                                </flux:button>
                            @endcan
                        </div>
                    </div>
                </div>

                {{-- Quick Actions Card --}}
                <div class="rounded-2xl border border-white/20 bg-white/70 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="border-b border-zinc-200/50 px-6 py-4 dark:border-zinc-700/50">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Quick Actions') }}</h2>
                    </div>
                    <div class="space-y-1 p-3">
                        @can('create members')
                        <flux:button variant="ghost" class="w-full justify-start rounded-xl" href="{{ route('members.create') }}" wire:navigate icon="user-plus">
                            {{ __('Add New Member') }}
                        </flux:button>
                        @endcan

                        @can('create subscriptions')
                        <flux:button variant="ghost" class="w-full justify-start rounded-xl" href="{{ route('subscriptions.create') }}" wire:navigate icon="credit-card">
                            {{ __('New Subscription') }}
                        </flux:button>
                        @endcan

                        @can('create class bookings')
                        <flux:button variant="ghost" class="w-full justify-start rounded-xl" href="{{ route('class-bookings.create') }}" wire:navigate icon="ticket">
                            {{ __('Book a Class') }}
                        </flux:button>
                        @endcan

                        @can('view pos')
                        <flux:button variant="ghost" class="w-full justify-start rounded-xl" href="{{ route('pos.index') }}" wire:navigate icon="shopping-cart">
                            {{ __('Point of Sale') }}
                        </flux:button>
                        @endcan
                    </div>
                </div>

                {{-- Inventory Alerts --}}
                @if($this->inventoryAlerts['low_stock_count'] > 0 || $this->inventoryAlerts['out_of_stock_count'] > 0)
                    <div class="overflow-hidden rounded-2xl border border-amber-200/50 bg-gradient-to-br from-amber-50/90 to-orange-50/90 shadow-xl shadow-amber-900/5 backdrop-blur-xl dark:border-amber-900/30 dark:from-amber-900/20 dark:to-orange-900/20">
                        <div class="border-b border-amber-200/50 px-6 py-4 dark:border-amber-900/30">
                            <h2 class="font-semibold text-amber-900 dark:text-amber-300">{{ __('Inventory Alerts') }}</h2>
                        </div>
                        <div class="space-y-3 p-4">
                            @if($this->inventoryAlerts['out_of_stock_count'] > 0)
                                <div class="flex items-center gap-3 rounded-xl bg-red-100/50 p-3 dark:bg-red-900/20">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30">
                                        <flux:icon name="exclamation-circle" class="h-4 w-4 text-red-600 dark:text-red-400" />
                                    </div>
                                    <p class="text-sm text-red-800 dark:text-red-200">
                                        {{ trans_choice(':count product out of stock|:count products out of stock', $this->inventoryAlerts['out_of_stock_count']) }}
                                    </p>
                                </div>
                            @endif
                            @if($this->inventoryAlerts['low_stock_count'] > 0)
                                <div class="flex items-center gap-3 rounded-xl bg-amber-100/50 p-3 dark:bg-amber-900/20">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30">
                                        <flux:icon name="exclamation-triangle" class="h-4 w-4 text-amber-600 dark:text-amber-400" />
                                    </div>
                                    <p class="text-sm text-amber-800 dark:text-amber-200">
                                        {{ trans_choice(':count product low on stock|:count products low on stock', $this->inventoryAlerts['low_stock_count']) }}
                                    </p>
                                </div>
                            @endif
                            @can('view inventory')
                                <flux:button variant="ghost" size="sm" class="mt-2 w-full justify-center text-amber-700 hover:text-amber-900 dark:text-amber-300" href="{{ route('branch-products.index') }}" wire:navigate>
                                    {{ __('View Inventory') }} →
                                </flux:button>
                            @endcan
                        </div>
                    </div>
                @endif

                {{-- Expiring Soon --}}
                @if($this->expiringSoonCount > 0)
                    <div class="overflow-hidden rounded-2xl border border-rose-200/50 bg-gradient-to-br from-rose-50/90 to-pink-50/90 shadow-xl shadow-rose-900/5 backdrop-blur-xl dark:border-rose-900/30 dark:from-rose-900/20 dark:to-pink-900/20">
                        <div class="border-b border-rose-200/50 px-6 py-4 dark:border-rose-900/30">
                            <h2 class="font-semibold text-rose-900 dark:text-rose-300">{{ __('Attention Needed') }}</h2>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center gap-3 rounded-xl bg-rose-100/50 p-3 dark:bg-rose-900/20">
                                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 dark:bg-rose-900/30">
                                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-rose-600 dark:text-rose-400" />
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-rose-900 dark:text-rose-200">
                                        {{ trans_choice(':count subscription expiring soon|:count subscriptions expiring soon', $this->expiringSoonCount, ['count' => $this->expiringSoonCount]) }}
                                    </p>
                                    @can('view subscriptions')
                                        <flux:button variant="ghost" size="sm" class="mt-1 !p-0 text-rose-700 hover:text-rose-900 dark:text-rose-300" href="{{ route('subscriptions.index', ['status_filter' => 'active']) }}" wire:navigate>
                                            {{ __('View subscriptions') }} →
                                        </flux:button>
                                    @endcan
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Limited Dashboard View for users without analytics permission --}}
        <div class="mb-8">
            <div class="overflow-hidden rounded-2xl border border-white/20 bg-gradient-to-br from-white/80 to-orange-50/50 p-8 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:from-zinc-800/80 dark:to-zinc-900/50">
                <div class="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-start sm:text-left">
                    <div class="flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-400 to-rose-500 shadow-lg shadow-orange-500/25">
                        <flux:icon name="hand-raised" class="h-10 w-10 text-white" />
                    </div>
                    <div class="flex-1">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ __('Welcome back, :name!', ['name' => auth()->user()->name]) }}
                        </h1>
                        <p class="mt-2 text-zinc-600 dark:text-zinc-400">
                            {{ __('You\'re logged in successfully. Use the navigation menu to access the areas you need.') }}
                        </p>
                        @if(auth()->user()->branch)
                            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-500">
                                {{ __('Current branch: :branch', ['branch' => auth()->user()->branch->name]) }}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Actions for Limited Users --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @can('view members')
                <a href="{{ route('members.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100 transition group-hover:bg-emerald-200 dark:bg-emerald-900/30 dark:group-hover:bg-emerald-900/50">
                            <flux:icon name="user-group" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Members') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View and manage members') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('view classes')
                <a href="{{ route('class-sessions.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 transition group-hover:bg-blue-200 dark:bg-blue-900/30 dark:group-hover:bg-blue-900/50">
                            <flux:icon name="clock" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Class Sessions') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View and manage classes') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('view workout plans')
                <a href="{{ route('workout-plans.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-100 transition group-hover:bg-purple-200 dark:bg-purple-900/30 dark:group-hover:bg-purple-900/50">
                            <flux:icon name="document-text" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Workout Plans') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View and manage workout plans') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @can('view pos')
                <a href="{{ route('pos.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100 transition group-hover:bg-amber-200 dark:bg-amber-900/30 dark:group-hover:bg-amber-900/50">
                            <flux:icon name="shopping-cart" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Point of Sale') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Process sales transactions') }}</p>
                        </div>
                    </div>
                </a>
            @endcan

            @canany(['view hikvision', 'manage hikvision', 'view attendance', 'view access devices', 'manage access devices'])
                <a href="{{ route('hikvision.logs.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-cyan-100 transition group-hover:bg-cyan-200 dark:bg-cyan-900/30 dark:group-hover:bg-cyan-900/50">
                            <flux:icon name="finger-print" class="h-6 w-6 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('HIKVision') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('View integration logs') }}</p>
                        </div>
                    </div>
                </a>
            @endcanany

            @can('view subscriptions')
                <a href="{{ route('subscriptions.index') }}" wire:navigate class="group overflow-hidden rounded-2xl border border-white/20 bg-white/70 p-6 shadow-xl shadow-zinc-900/5 backdrop-blur-xl transition hover:shadow-lg dark:border-zinc-700/50 dark:bg-zinc-800/70">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-rose-100 transition group-hover:bg-rose-200 dark:bg-rose-900/30 dark:group-hover:bg-rose-900/50">
                            <flux:icon name="arrow-path" class="h-6 w-6 text-rose-600 dark:text-rose-400" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-zinc-900 dark:text-white">{{ __('Subscriptions') }}</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Manage member subscriptions') }}</p>
                        </div>
                    </div>
                </a>
            @endcan
        </div>
    @endcan
</div>

@can('view dashboard analytics')
@assets
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.45.1/dist/apexcharts.min.js"></script>
@endassets

@script
<script>
    Alpine.data('attendanceChart', () => ({
        chart: null,
        chartData: null,
        
        init(data) {
            this.chartData = data;
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        
        updateChart(data) {
            this.chartData = data;
            if (this.chart) {
                this.chart.updateOptions({
                    xaxis: { categories: data.labels }
                });
                this.chart.updateSeries([{ data: data.data }]);
            } else {
                this.renderChart();
            }
        },
        
        renderChart() {
            if (!this.chartData || !this.$refs.chartContainer) return;
            
            const isDark = document.documentElement.classList.contains('dark');
            
            const options = {
                series: [{
                    name: 'Check-ins',
                    data: this.chartData.data
                }],
                chart: {
                    type: 'area',
                    height: 256,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    background: 'transparent',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 500,
                    }
                },
                colors: ['#f97316'],
                fill: {
                    type: 'gradient',
                    gradient: {
                        shadeIntensity: 1,
                        opacityFrom: 0.4,
                        opacityTo: 0.05,
                        stops: [0, 90, 100]
                    }
                },
                stroke: {
                    curve: 'smooth',
                    width: 3
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: this.chartData.labels,
                    labels: {
                        style: {
                            colors: isDark ? '#a1a1aa' : '#71717a',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#a1a1aa' : '#71717a',
                            fontSize: '11px'
                        },
                        formatter: (val) => Math.round(val)
                    }
                },
                grid: {
                    borderColor: isDark ? '#27272a' : '#f4f4f5',
                    strokeDashArray: 4,
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: (val) => val + ' check-ins'
                    }
                }
            };

            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new ApexCharts(this.$refs.chartContainer, options);
            this.chart.render();
        }
    }));

    Alpine.data('revenueChart', () => ({
        chart: null,
        chartData: null,
        currency: 'TZS',
        
        init(data, currency) {
            this.chartData = data;
            this.currency = currency || 'TZS';
            this.$nextTick(() => {
                this.renderChart();
            });
        },
        
        updateChart(data, currency) {
            this.chartData = data;
            this.currency = currency || this.currency;
            if (this.chart) {
                this.chart.updateOptions({
                    xaxis: { categories: data.labels }
                });
                this.chart.updateSeries([{ data: data.data }]);
            } else {
                this.renderChart();
            }
        },
        
        renderChart() {
            if (!this.chartData || !this.$refs.chartContainer) return;
            
            const isDark = document.documentElement.classList.contains('dark');
            const currency = this.currency;
            
            const options = {
                series: [{
                    name: 'Revenue',
                    data: this.chartData.data
                }],
                chart: {
                    type: 'bar',
                    height: 256,
                    fontFamily: 'inherit',
                    toolbar: { show: false },
                    background: 'transparent',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 500,
                    }
                },
                colors: ['#f97316'],
                plotOptions: {
                    bar: {
                        borderRadius: 6,
                        columnWidth: '50%',
                    }
                },
                dataLabels: { enabled: false },
                xaxis: {
                    categories: this.chartData.labels,
                    labels: {
                        style: {
                            colors: isDark ? '#a1a1aa' : '#71717a',
                            fontSize: '11px'
                        }
                    },
                    axisBorder: { show: false },
                    axisTicks: { show: false },
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: isDark ? '#a1a1aa' : '#71717a',
                            fontSize: '11px'
                        },
                        formatter: (val) => this.formatMoney(val)
                    }
                },
                grid: {
                    borderColor: isDark ? '#27272a' : '#f4f4f5',
                    strokeDashArray: 4,
                },
                tooltip: {
                    theme: isDark ? 'dark' : 'light',
                    y: {
                        formatter: (val) => currency + ' ' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    }
                }
            };

            if (this.chart) {
                this.chart.destroy();
            }

            this.chart = new ApexCharts(this.$refs.chartContainer, options);
            this.chart.render();
        },
        
        formatMoney(val) {
            if (val >= 1000000) {
                return (val / 1000000).toFixed(1) + 'M';
            } else if (val >= 1000) {
                return (val / 1000).toFixed(0) + 'K';
            }
            return val.toFixed(0);
        }
    }));
</script>
@endscript
@endcan
