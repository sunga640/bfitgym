@props(['title' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => $title])
        <style>
            /* Custom scrollbar styling */
            ::-webkit-scrollbar {
                width: 6px;
                height: 6px;
            }
            ::-webkit-scrollbar-track {
                background: transparent;
            }
            ::-webkit-scrollbar-thumb {
                background: rgba(161, 161, 170, 0.3);
                border-radius: 3px;
            }
            ::-webkit-scrollbar-thumb:hover {
                background: rgba(161, 161, 170, 0.5);
            }
            .dark ::-webkit-scrollbar-thumb {
                background: rgba(63, 63, 70, 0.5);
            }
            .dark ::-webkit-scrollbar-thumb:hover {
                background: rgba(63, 63, 70, 0.7);
            }

            /* Floating glass panel styles */
            .glass-panel {
                background: rgba(255, 255, 255, 0.72);
                backdrop-filter: blur(20px);
                -webkit-backdrop-filter: blur(20px);
                border-radius: 1.25rem;
                border: 1px solid rgba(255, 255, 255, 0.25);
                box-shadow: 
                    0 25px 50px -12px rgba(0, 0, 0, 0.08),
                    0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            }
            
            .dark .glass-panel {
                background: rgba(24, 24, 27, 0.78);
                border: 1px solid rgba(63, 63, 70, 0.5);
                box-shadow: 
                    0 25px 50px -12px rgba(0, 0, 0, 0.35),
                    0 0 0 1px rgba(255, 255, 255, 0.04) inset;
            }

            /* Override Flux sidebar defaults */
            .floating-sidebar {
                background: transparent !important;
                border: none !important;
                padding: 1rem !important;
                padding-right: 0.5rem !important;
            }
            
            .floating-sidebar > .sidebar-inner {
                height: calc(100vh - 2rem) !important;
            }

            /* Override Flux header defaults */
            .floating-header {
                background: transparent !important;
                border: none !important;
            }
            
            .floating-header > div,
            .floating-header > .container {
                width: 100% !important;
                max-width: 100% !important;
            }
        </style>
    </head>
    <body class="min-h-screen overflow-x-hidden">
        {{-- Full-page unified background --}}
        <div class="fixed inset-0 -z-10">
            {{-- Base gradient --}}
            <div class="absolute inset-0 bg-gradient-to-br from-amber-100 via-orange-50 to-rose-100 dark:from-zinc-950 dark:via-zinc-900 dark:to-zinc-950"></div>
            
            {{-- Decorative gradient orbs --}}
            <div class="absolute -left-40 -top-40 h-[500px] w-[500px] rounded-full bg-orange-300/40 blur-[100px] dark:bg-orange-500/20"></div>
            <div class="absolute -bottom-40 -right-40 h-[500px] w-[500px] rounded-full bg-rose-300/40 blur-[100px] dark:bg-rose-500/20"></div>
            <div class="absolute left-1/3 top-1/2 h-[400px] w-[400px] -translate-y-1/2 rounded-full bg-amber-200/30 blur-[100px] dark:bg-amber-500/10"></div>
            <div class="absolute bottom-1/4 right-1/3 h-[300px] w-[300px] rounded-full bg-pink-200/30 blur-[80px] dark:bg-pink-500/10"></div>
            
            {{-- Subtle noise texture overlay --}}
            <div class="absolute inset-0 opacity-[0.02] dark:opacity-[0.04]" style="background-image: url('data:image/svg+xml,%3Csvg viewBox=%220 0 256 256%22 xmlns=%22http://www.w3.org/2000/svg%22%3E%3Cfilter id=%22noise%22%3E%3CfeTurbulence type=%22fractalNoise%22 baseFrequency=%220.9%22 numOctaves=%224%22 stitchTiles=%22stitch%22/%3E%3C/filter%3E%3Crect width=%22100%25%22 height=%22100%25%22 filter=%22url(%23noise)%22/%3E%3C/svg%3E');"></div>
        </div>

        {{-- Floating Sidebar --}}
        <flux:sidebar sticky stashable class="floating-sidebar lg:w-[17rem] bg-transparent! border-none!">
            <div class="sidebar-inner glass-panel flex h-full flex-col overflow-hidden p-4">
                <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

                <a href="{{ route('dashboard') }}" class="mb-4 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                    <x-app-logo />
                </a>

                {{-- Branch Switcher --}}
                @auth
                    @if(auth()->user()->hasRole('super-admin') || auth()->user()->can('switch branches'))
                        <x-sidebar.branch-switcher />
                    @elseif(auth()->user()->branch)
                        <div class="mb-4 rounded-xl bg-gradient-to-r from-orange-500/10 to-rose-500/10 px-3 py-2.5 text-xs dark:from-orange-500/20 dark:to-rose-500/20">
                            <span class="text-zinc-500 dark:text-zinc-400">{{ __('Branch') }}:</span>
                            <span class="ml-1 font-medium text-zinc-700 dark:text-zinc-200">{{ auth()->user()->branch->name }}</span>
                        </div>
                    @endif
                @endauth

                <flux:navlist variant="outline" class="flex-1 overflow-y-auto -mx-2">
                    {{-- Overview --}}
                    <flux:navlist.group :heading="__('Overview')" class="grid">
                        <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                            {{ __('Dashboard') }}
                        </flux:navlist.item>
                        <flux:navlist.item icon="calendar" :href="route('calendar.index')" :current="request()->routeIs('calendar.*')" wire:navigate>
                            {{ __('Calendar') }}
                        </flux:navlist.item>
                    </flux:navlist.group>

                    {{-- Organization --}}
                    @canany(['view branches', 'view users', 'assign roles'])
                        <flux:navlist.group :heading="__('Organization')" class="grid" expandable :expanded="request()->routeIs('branches.*', 'organization.branches.*', 'users.*', 'roles.*')">
                            @can('view branches')
                                <flux:navlist.item icon="building-office-2" :href="route('organization.branches.index')" :current="request()->routeIs('organization.branches.*')" wire:navigate>
                                    {{ __('Branches') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view users')
                                <flux:navlist.item icon="users" :href="route('users.index')" :current="request()->routeIs('users.*')" wire:navigate>
                                    {{ __('Staff / Users') }}
                                </flux:navlist.item>
                            @endcan
                            @can('assign roles')
                                <flux:navlist.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                                    {{ __('Roles & Permissions') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Members --}}
                    @canany(['view members', 'view insurers'])
                        <flux:navlist.group :heading="__('Members')" class="grid" expandable :expanded="request()->routeIs('members.*', 'insurers.*', 'member-insurances.*')">
                            @can('view members')
                                <flux:navlist.item icon="user-group" :href="route('members.index')" :current="request()->routeIs('members.*')" wire:navigate>
                                    {{ __('Members') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view insurers')
                                <flux:navlist.item icon="heart" :href="route('insurers.index')" :current="request()->routeIs('insurers.*')" wire:navigate>
                                    {{ __('Insurers') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="clipboard-document-list" :href="route('member-insurances.index')" :current="request()->routeIs('member-insurances.*')" wire:navigate>
                                    {{ __('Member Policies') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Memberships --}}
                    @canany(['view membership-packages', 'view subscriptions'])
                        <flux:navlist.group :heading="__('Memberships')" class="grid" expandable :expanded="request()->routeIs('membership-packages.*', 'subscriptions.*')">
                            @can('view membership-packages')
                                <flux:navlist.item icon="credit-card" :href="route('membership-packages.index')" :current="request()->routeIs('membership-packages.*')" wire:navigate>
                                    {{ __('Packages') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view subscriptions')
                                <flux:navlist.item icon="arrow-path" :href="route('subscriptions.index')" :current="request()->routeIs('subscriptions.*')" wire:navigate>
                                    {{ __('Subscriptions') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Classes --}}
                    @canany(['view classes', 'view class bookings'])
                        <flux:navlist.group :heading="__('Classes')" class="grid" expandable :expanded="request()->routeIs('class-types.*', 'class-sessions.*', 'class-bookings.*')">
                            @can('view classes')
                                <flux:navlist.item icon="rectangle-stack" :href="route('class-types.index')" :current="request()->routeIs('class-types.*')" wire:navigate>
                                    {{ __('Class Types') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="clock" :href="route('class-sessions.index')" :current="request()->routeIs('class-sessions.*')" wire:navigate>
                                    {{ __('Sessions / Schedule') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view class bookings')
                                <flux:navlist.item icon="ticket" :href="route('class-bookings.index')" :current="request()->routeIs('class-bookings.*')" wire:navigate>
                                    {{ __('Bookings') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Training --}}
                    @can('view workout plans')
                        <flux:navlist.group :heading="__('Training')" class="grid" expandable :expanded="request()->routeIs('exercises.*', 'workout-plans.*', 'member-workout-plans.*')">
                            <flux:navlist.item icon="fire" :href="route('exercises.index')" :current="request()->routeIs('exercises.*')" wire:navigate>
                                {{ __('Exercises') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="document-text" :href="route('workout-plans.index')" :current="request()->routeIs('workout-plans.*')" wire:navigate>
                                {{ __('Workout Plans') }}
                            </flux:navlist.item>
                            @can('assign workout plans')
                                <flux:navlist.item icon="clipboard-document-check" :href="route('member-workout-plans.index')" :current="request()->routeIs('member-workout-plans.*')" wire:navigate>
                                    {{ __('Assigned Plans') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcan

                    {{-- Events --}}
                    @can('view events')
                        <flux:navlist.group :heading="__('Events')" class="grid" expandable :expanded="request()->routeIs('events.*', 'event-registrations.*')">
                            <flux:navlist.item icon="calendar-days" :href="route('events.index')" :current="request()->routeIs('events.*')" wire:navigate>
                                {{ __('Events') }}
                            </flux:navlist.item>
                            @can('manage event registrations')
                                <flux:navlist.item icon="user-plus" :href="route('event-registrations.index')" :current="request()->routeIs('event-registrations.*')" wire:navigate>
                                    {{ __('Registrations') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcan

                    {{-- POS & Inventory --}}
                    @canany(['view pos', 'view products', 'view inventory', 'view stock adjustments', 'view purchase orders'])
                        <flux:navlist.group :heading="__('POS & Inventory')" class="grid" expandable :expanded="request()->routeIs('pos.*', 'pos-sales.*', 'products.*', 'product-categories.*', 'branch-products.*', 'stock-adjustments.*', 'suppliers.*', 'purchase-orders.*')">
                            @can('view pos')
                                <flux:navlist.item icon="shopping-cart" :href="route('pos.index')" :current="request()->routeIs('pos.index')" wire:navigate>
                                    {{ __('Point of Sale') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="receipt-percent" :href="route('pos-sales.index')" :current="request()->routeIs('pos-sales.*')" wire:navigate>
                                    {{ __('Sales History') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view products')
                                <flux:navlist.item icon="cube" :href="route('products.index')" :current="request()->routeIs('products.*')" wire:navigate>
                                    {{ __('Products') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="tag" :href="route('product-categories.index')" :current="request()->routeIs('product-categories.*')" wire:navigate>
                                    {{ __('Categories') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view inventory')
                                <flux:navlist.item icon="archive-box" :href="route('branch-products.index')" :current="request()->routeIs('branch-products.*')" wire:navigate>
                                    {{ __('Stock Levels') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view stock adjustments')
                                <flux:navlist.item icon="adjustments-horizontal" :href="route('stock-adjustments.index')" :current="request()->routeIs('stock-adjustments.*')" wire:navigate>
                                    {{ __('Stock Adjustments') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view purchase orders')
                                <flux:navlist.item icon="truck" :href="route('suppliers.index')" :current="request()->routeIs('suppliers.*')" wire:navigate>
                                    {{ __('Suppliers') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="clipboard-document" :href="route('purchase-orders.index')" :current="request()->routeIs('purchase-orders.*')" wire:navigate>
                                    {{ __('Purchase Orders') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Attendance --}}
                    @canany(['view attendance', 'view access devices', 'manage access identities'])
                        <flux:navlist.group :heading="__('Attendance')" class="grid" expandable :expanded="request()->routeIs('attendance.*', 'access-devices.*', 'access-identities.*', 'access-control.*')">
                            @can('view attendance')
                                <flux:navlist.item icon="finger-print" :href="route('attendance.index')" :current="request()->routeIs('attendance.*')" wire:navigate>
                                    {{ __('Attendance Log') }}
                                </flux:navlist.item>
                            @endcan
                            @canany(['view access devices', 'manage access devices'])
                                <flux:navlist.item icon="cpu-chip" :href="route('access-control.devices.index')" :current="request()->routeIs('access-control.devices.*')" wire:navigate>
                                    {{ __('Devices') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="users" :href="route('access-control.agents.index')" :current="request()->routeIs('access-control.agents.*')" wire:navigate>
                                    {{ __('Agents') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="key" :href="route('access-control.enrollments.index')" :current="request()->routeIs('access-control.enrollments.*')" wire:navigate>
                                    {{ __('Enrollments') }}
                                </flux:navlist.item>
                            @endcanany
                            @can('manage access identities')
                                <flux:navlist.item icon="identification" :href="route('access-identities.index')" :current="request()->routeIs('access-identities.*')" wire:navigate>
                                    {{ __('Identities') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Facilities --}}
                    @can('view equipment')
                        <flux:navlist.group :heading="__('Facilities')" class="grid" expandable :expanded="request()->routeIs('locations.*', 'equipment.*', 'equipment-allocations.*')">
                            <flux:navlist.item icon="map-pin" :href="route('locations.index')" :current="request()->routeIs('locations.*')" wire:navigate>
                                {{ __('Locations') }}
                            </flux:navlist.item>
                            <flux:navlist.item icon="wrench-screwdriver" :href="route('equipment.index')" :current="request()->routeIs('equipment.index', 'equipment.create', 'equipment.edit')" wire:navigate>
                                {{ __('Equipment') }}
                            </flux:navlist.item>
                            @can('view equipment allocations')
                                <flux:navlist.item icon="squares-plus" :href="route('equipment-allocations.index')" :current="request()->routeIs('equipment-allocations.*')" wire:navigate>
                                    {{ __('Allocations') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcan

                    {{-- Finances --}}
                    @canany(['view payments', 'view expenses'])
                        <flux:navlist.group :heading="__('Finances')" class="grid" expandable :expanded="request()->routeIs('payments.*', 'expenses.*', 'expense-categories.*')">
                            @can('view payments')
                                <flux:navlist.item icon="banknotes" :href="route('payments.index')" :current="request()->routeIs('payments.*')" wire:navigate>
                                    {{ __('Payments') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view expenses')
                                <flux:navlist.item icon="arrow-trending-down" :href="route('expenses.index')" :current="request()->routeIs('expenses.*')" wire:navigate>
                                    {{ __('Expenses') }}
                                </flux:navlist.item>
                                <flux:navlist.item icon="folder" :href="route('expense-categories.index')" :current="request()->routeIs('expense-categories.*')" wire:navigate>
                                    {{ __('Expense Categories') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Reports --}}
                    @canany(['view reports', 'view financial reports', 'view attendance reports', 'view membership reports', 'view insurance reports', 'view pos reports'])
                        <flux:navlist.group :heading="__('Reports')" class="grid" expandable :expanded="request()->routeIs('reports.*')">
                            @can('view financial reports')
                                <flux:navlist.item icon="chart-bar" :href="route('reports.revenue')" :current="request()->routeIs('reports.revenue')" wire:navigate>
                                    {{ __('Revenue') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view membership reports')
                                <flux:navlist.item icon="chart-pie" :href="route('reports.memberships')" :current="request()->routeIs('reports.memberships')" wire:navigate>
                                    {{ __('Memberships') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view attendance reports')
                                <flux:navlist.item icon="presentation-chart-line" :href="route('reports.attendance')" :current="request()->routeIs('reports.attendance')" wire:navigate>
                                    {{ __('Attendance') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view insurance reports')
                                <flux:navlist.item icon="document-chart-bar" :href="route('reports.insurance')" :current="request()->routeIs('reports.insurance')" wire:navigate>
                                    {{ __('Insurance') }}
                                </flux:navlist.item>
                            @endcan
                            @can('view pos reports')
                                <flux:navlist.item icon="shopping-bag" :href="route('reports.pos')" :current="request()->routeIs('reports.pos')" wire:navigate>
                                    {{ __('POS / Sales') }}
                                </flux:navlist.item>
                            @endcan
                        </flux:navlist.group>
                    @endcanany

                    {{-- Settings --}}
                    @can('view settings')
                        <flux:navlist.group class="mt-4 grid">
                            <flux:navlist.item icon="cog-6-tooth" :href="route('settings.general')" :current="request()->routeIs('settings.*')" wire:navigate>
                                {{ __('Settings') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    @endcan
                </flux:navlist>
            </div>
        </flux:sidebar>

        {{-- Floating Top Navigation Bar --}}
        <flux:header class="floating-header bg-transparent! border-none!" container>
            <div class="glass-panel mr-4 mt-4 ml-1 flex flex-1 items-center px-4 py-2.5">
                {{-- Mobile Sidebar Toggle --}}
                <flux:sidebar.toggle class="lg:hidden mr-2" icon="bars-2" inset="left" />

                <div class="flex-1"></div>

                {{-- Right Side Actions --}}
                <flux:navbar class="space-x-0.5 py-0! rtl:space-x-reverse">
                    {{-- Notifications --}}
                    <flux:tooltip :content="__('Notifications')" position="bottom">
                        <flux:navbar.item class="relative !h-10 [&>div>svg]:size-5" icon="bell" href="#" :label="__('Notifications')">
                            <span class="absolute right-1.5 top-1.5 flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-orange-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-orange-500"></span>
                            </span>
                        </flux:navbar.item>
                    </flux:tooltip>
                </flux:navbar>

                {{-- User Dropdown Menu --}}
                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        class="cursor-pointer"
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu class="w-[220px]">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-gradient-to-br from-orange-400 to-rose-500 text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="user" wire:navigate>{{ __('Profile') }}</flux:menu.item>
                            <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </flux:header>

        {{ $slot }}

        @fluxScripts
        @stack('scripts')
    </body>
</html>