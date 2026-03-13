<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Branch Header --}}
    <div class="mb-6 rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-300">
                    <flux:icon name="building-office-2" class="h-8 w-8" />
                </div>
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $branch->name }}</h1>
                        <flux:badge :color="$branch->status === 'active' ? 'emerald' : 'zinc'" size="sm">
                            {{ ucfirst($branch->status) }}
                        </flux:badge>
                    </div>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Code') }}: <span class="font-mono font-medium">{{ $branch->code }}</span>
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-zinc-600 dark:text-zinc-300">
                        @if($branch->city)
                            <span class="flex items-center gap-1">
                                <flux:icon name="map-pin" class="h-4 w-4" />
                                {{ $branch->city }}{{ $branch->country ? ', ' . $branch->country : '' }}
                            </span>
                        @endif
                        @if($branch->phone)
                            <span class="flex items-center gap-1">
                                <flux:icon name="phone" class="h-4 w-4" />
                                {{ $branch->phone }}
                            </span>
                        @endif
                        @if($branch->email)
                            <span class="flex items-center gap-1">
                                <flux:icon name="envelope" class="h-4 w-4" />
                                {{ $branch->email }}
                            </span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                @if($this->canSwitchToBranch)
                    <flux:button variant="primary" icon="arrow-right-circle" wire:click="switchToBranch">
                        {{ __('Switch to this Branch') }}
                    </flux:button>
                @endif
                @if($this->canEdit)
                    <flux:button variant="ghost" icon="cog-6-tooth" href="{{ route('organization.branches.settings', $branch) }}" wire:navigate>
                        {{ __('Settings') }}
                    </flux:button>
                @endif
                <flux:button variant="ghost" icon="arrow-left" href="{{ route('organization.branches.index') }}" wire:navigate>
                    {{ __('Back') }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="mb-6 flex flex-wrap gap-1 rounded-xl border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-800">
        @foreach(['overview' => 'Overview', 'staff' => 'Staff', 'operations' => 'Operations', 'finance' => 'Finance', 'settings' => 'Settings'] as $tab_key => $tab_label)
            <button
                wire:click="setTab('{{ $tab_key }}')"
                class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition-colors {{ $active_tab === $tab_key ? 'bg-zinc-100 text-zinc-900 dark:bg-zinc-700 dark:text-white' : 'text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white' }}"
            >
                {{ __($tab_label) }}
            </button>
        @endforeach
    </div>

    {{-- Tab Content --}}
    <div>
        {{-- Overview Tab --}}
        @if($active_tab === 'overview')
            @php $overview = $this->overview; @endphp
            <div class="space-y-6">
                {{-- KPI Cards --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/50">
                                <flux:icon name="user-group" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Members') }}</p>
                                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($overview['active_members_count']) }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-zinc-500 dark:text-zinc-400">
                            {{ __(':count new this month', ['count' => $overview['new_members_this_month']]) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/50">
                                <flux:icon name="credit-card" class="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Active Subscriptions') }}</p>
                                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($overview['active_subscriptions_count']) }}</p>
                            </div>
                        </div>
                        <p class="mt-2 text-xs {{ $overview['expiring_soon_count'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-zinc-500 dark:text-zinc-400' }}">
                            {{ __(':count expiring in 7 days', ['count' => $overview['expiring_soon_count']]) }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/50">
                                <flux:icon name="finger-print" class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Attendance Today') }}</p>
                                <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($overview['attendance_today']) }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/50">
                                <flux:icon name="banknotes" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                            </div>
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Revenue (MTD)') }}</p>
                                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ money($overview['membership_revenue_this_month']) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Upcoming Schedule --}}
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Upcoming Schedule (Next 7 Days)') }}</h2>
                    </div>
                    <div class="p-6">
                        @php $schedule = $this->upcomingSchedule; @endphp
                        @if(count($schedule) > 0)
                            <div class="space-y-3">
                                @foreach($schedule as $item)
                                    <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg {{ $item['type'] === 'class' ? 'bg-blue-100 dark:bg-blue-900/50' : 'bg-purple-100 dark:bg-purple-900/50' }}">
                                                <flux:icon name="{{ $item['type'] === 'class' ? 'academic-cap' : 'calendar-days' }}" class="h-4 w-4 {{ $item['type'] === 'class' ? 'text-blue-600 dark:text-blue-400' : 'text-purple-600 dark:text-purple-400' }}" />
                                            </div>
                                            <div>
                                                <p class="font-medium text-zinc-900 dark:text-white">{{ $item['title'] }}</p>
                                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                                    {{ $item['datetime']->format('D, M j') }} at {{ $item['datetime']->format('g:i A') }}
                                                    @if($item['location'])
                                                        · {{ $item['location'] }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <flux:badge :color="$item['type'] === 'class' ? 'blue' : 'purple'" size="sm">
                                            {{ ucfirst($item['type']) }}
                                        </flux:badge>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex flex-col items-center justify-center py-8 text-center">
                                <flux:icon name="calendar" class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                                <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No upcoming events or classes scheduled.') }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Staff Tab --}}
        @if($active_tab === 'staff')
            @php $staff_members = $this->staff; @endphp
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Staff Members') }}</h2>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ trans_choice(':count member|:count members', $staff_members->count()) }}
                        </span>
                    </div>
                </div>
                <div class="p-6">
                    @if($staff_members->count() > 0)
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach($staff_members as $staff)
                                <div class="flex items-center gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 text-sm font-semibold text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                        {{ $staff->initials() }}
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <p class="truncate font-medium text-zinc-900 dark:text-white">{{ $staff->name }}</p>
                                        <p class="truncate text-sm text-zinc-500 dark:text-zinc-400">{{ $staff->email }}</p>
                                        @if($staff->roles->isNotEmpty())
                                            <div class="mt-1 flex flex-wrap gap-1">
                                                @foreach($staff->roles->take(2) as $role)
                                                    <flux:badge color="zinc" size="sm">{{ $role->name }}</flux:badge>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-8 text-center">
                            <flux:icon name="users" class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
                            <p class="mt-3 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No staff members assigned to this branch.') }}</p>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Operations Tab --}}
        @if($active_tab === 'operations')
            @php $ops = $this->operationsSummary; @endphp
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a href="{{ route('locations.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-indigo-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100 dark:bg-indigo-900/50">
                            <flux:icon name="map-pin" class="h-5 w-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Locations') }}</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($ops['locations_count']) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('equipment-allocations.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-indigo-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/50">
                            <flux:icon name="wrench-screwdriver" class="h-5 w-5 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Equipment Allocations') }}</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($ops['equipment_allocations_count']) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('hikvision.devices.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-indigo-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-900/50">
                            <flux:icon name="cpu-chip" class="h-5 w-5 text-cyan-600 dark:text-cyan-400" />
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Access Devices') }}</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($ops['access_devices_count']) }}</p>
                        </div>
                    </div>
                </a>

                <a href="{{ route('class-types.index') }}" wire:navigate class="rounded-xl border border-zinc-200 bg-white p-5 transition-colors hover:border-indigo-300 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-indigo-600">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-pink-100 dark:bg-pink-900/50">
                            <flux:icon name="academic-cap" class="h-5 w-5 text-pink-600 dark:text-pink-400" />
                        </div>
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Class Types') }}</p>
                            <p class="text-2xl font-bold text-zinc-900 dark:text-white">{{ number_format($ops['class_types_count']) }}</p>
                        </div>
                    </div>
                </a>
            </div>
        @endif

        {{-- Finance Tab --}}
        @if($active_tab === 'finance')
            @php $finance = $this->financeSummary; @endphp
            <div class="space-y-6">
                {{-- Revenue Summary --}}
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Membership Revenue') }}</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ money($finance['membership_revenue']) }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('POS Revenue') }}</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ money($finance['pos_revenue']) }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Other Revenue') }}</p>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ money($finance['other_revenue']) }}</p>
                    </div>
                    <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Expenses') }}</p>
                        <p class="mt-1 text-2xl font-bold text-rose-600 dark:text-rose-400">{{ money($finance['total_expenses']) }}</p>
                    </div>
                </div>

                {{-- Net Income Card --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('Net Income') }}
                                <span class="text-xs">({{ $finance['period_from']->format('M j') }} - {{ $finance['period_to']->format('M j, Y') }})</span>
                            </p>
                            <p class="mt-1 text-3xl font-bold {{ $finance['net_income'] >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400' }}">
                                {{ money($finance['net_income']) }}
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Total Revenue') }}</p>
                            <p class="text-xl font-semibold text-zinc-900 dark:text-white">{{ money($finance['total_revenue']) }}</p>
                        </div>
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="ghost" icon="chart-bar" href="{{ route('reports.revenue') }}" wire:navigate>
                        {{ __('View Revenue Report') }}
                    </flux:button>
                    <flux:button variant="ghost" icon="arrow-trending-down" href="{{ route('expenses.index') }}" wire:navigate>
                        {{ __('View Expenses') }}
                    </flux:button>
                    <flux:button variant="ghost" icon="receipt-percent" href="{{ route('pos-sales.index') }}" wire:navigate>
                        {{ __('View POS Sales') }}
                    </flux:button>
                </div>
            </div>
        @endif

        {{-- Settings Tab --}}
        @if($active_tab === 'settings')
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Branch Settings') }}</h2>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('Manage branch configuration, modules, and preferences.') }}
                        </p>
                    </div>
                    @if($this->canEdit)
                        <flux:button variant="primary" icon="cog-6-tooth" href="{{ route('organization.branches.settings', $branch) }}" wire:navigate>
                            {{ __('Edit Settings') }}
                        </flux:button>
                    @endif
                </div>

                @if($branch->setting)
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700/50">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Currency') }}</p>
                            <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">{{ $branch->setting->currency }}</p>
                        </div>
                        <div class="rounded-lg bg-zinc-50 p-4 dark:bg-zinc-700/50">
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Modules') }}</p>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @if($branch->setting->module_pos_enabled)
                                    <flux:badge color="emerald" size="sm">{{ __('POS') }}</flux:badge>
                                @endif
                                @if($branch->setting->module_classes_enabled)
                                    <flux:badge color="emerald" size="sm">{{ __('Classes') }}</flux:badge>
                                @endif
                                @if($branch->setting->module_insurance_enabled)
                                    <flux:badge color="emerald" size="sm">{{ __('Insurance') }}</flux:badge>
                                @endif
                                @if($branch->setting->module_access_control_enabled)
                                    <flux:badge color="emerald" size="sm">{{ __('Access Control') }}</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <p class="mt-4 text-sm text-zinc-500 dark:text-zinc-400">{{ __('No settings configured yet.') }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
