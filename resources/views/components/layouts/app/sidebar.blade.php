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

            .side-nav {
                scrollbar-width: thin;
            }

            .side-group {
                border-radius: 0.85rem;
            }

            .side-group summary {
                list-style: none;
            }

            .side-group summary::-webkit-details-marker {
                display: none;
            }

            .side-parent {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                padding: 0.6rem 0.75rem;
                border-radius: 0.75rem;
                color: rgb(82 82 91);
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .dark .side-parent {
                color: rgb(212 212 216);
            }

            .side-parent:hover {
                background: rgba(255, 255, 255, 0.6);
                color: rgb(39 39 42);
            }

            .dark .side-parent:hover {
                background: rgba(39, 39, 42, 0.65);
                color: rgb(244 244 245);
            }

            .side-parent-active {
                background: rgba(249, 115, 22, 0.14);
                color: rgb(194 65 12);
            }

            .dark .side-parent-active {
                background: rgba(249, 115, 22, 0.22);
                color: rgb(254 215 170);
            }

            .side-parent-left {
                display: inline-flex;
                align-items: center;
                gap: 0.6rem;
                min-width: 0;
            }

            .side-parent-label {
                font-size: 0.86rem;
                font-weight: 600;
                line-height: 1.2rem;
            }

            .side-icon {
                width: 1rem;
                text-align: center;
            }

            .side-chevron {
                font-size: 0.75rem;
                transition: transform 0.2s ease;
            }

            .side-group[open] .side-chevron {
                transform: rotate(180deg);
            }

            .side-children {
                margin-left: 1.75rem;
                margin-top: 0.2rem;
                margin-bottom: 0.35rem;
                display: grid;
                gap: 0.2rem;
                border-left: 1px solid rgba(161, 161, 170, 0.35);
                padding-left: 0.65rem;
            }

            .dark .side-children {
                border-left-color: rgba(82, 82, 91, 0.7);
            }

            .side-child {
                display: block;
                border-radius: 0.6rem;
                padding: 0.42rem 0.55rem;
                color: rgb(82 82 91);
                font-size: 0.82rem;
                font-weight: 500;
                line-height: 1.2rem;
                transition: all 0.2s ease;
            }

            .dark .side-child {
                color: rgb(212 212 216);
            }

            .side-child:hover {
                background: rgba(255, 255, 255, 0.5);
                color: rgb(39 39 42);
            }

            .dark .side-child:hover {
                background: rgba(39, 39, 42, 0.55);
                color: rgb(244 244 245);
            }

            .side-child-active {
                background: rgba(249, 115, 22, 0.14);
                color: rgb(194 65 12);
            }

            .dark .side-child-active {
                background: rgba(249, 115, 22, 0.22);
                color: rgb(254 215 170);
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

                <nav class="side-nav flex-1 overflow-y-auto -mx-2 px-2">
    <div class="space-y-1.5">
        <details class="side-group" @if(request()->routeIs('dashboard', 'calendar.*')) open @endif>
            <summary class="side-parent {{ request()->routeIs('dashboard', 'calendar.*') ? 'side-parent-active' : '' }}">
                <span class="side-parent-left">
                    <i class="fa-sharp-duotone fa-solid fa-gauge-high side-icon" aria-hidden="true"></i>
                    <span class="side-parent-label">{{ __('Overview') }}</span>
                </span>
                <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
            </summary>
            <div class="side-children">
                <a href="{{ route('dashboard') }}" class="side-child {{ request()->routeIs('dashboard') ? 'side-child-active' : '' }}" wire:navigate>
                    {{ __('Dashboard') }}
                </a>
                <a href="{{ route('calendar.index') }}" class="side-child {{ request()->routeIs('calendar.*') ? 'side-child-active' : '' }}" wire:navigate>
                    {{ __('Calendar') }}
                </a>
            </div>
        </details>

        @canany(['view branches', 'view users', 'assign roles'])
            <details class="side-group" @if(request()->routeIs('branches.*', 'organization.branches.*', 'users.*', 'roles.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('branches.*', 'organization.branches.*', 'users.*', 'roles.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-buildings side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Organization') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view branches')
                        <a href="{{ route('organization.branches.index') }}" class="side-child {{ request()->routeIs('organization.branches.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Branches') }}</a>
                    @endcan
                    @can('view users')
                        <a href="{{ route('users.index') }}" class="side-child {{ request()->routeIs('users.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Staff / Users') }}</a>
                    @endcan
                    @can('assign roles')
                        <a href="{{ route('roles.index') }}" class="side-child {{ request()->routeIs('roles.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Roles & Permissions') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['view members', 'view insurers'])
            <details class="side-group" @if(request()->routeIs('members.*', 'insurers.*', 'member-insurances.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('members.*', 'insurers.*', 'member-insurances.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-users side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Members') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view members')
                        <a href="{{ route('members.index') }}" class="side-child {{ request()->routeIs('members.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Members') }}</a>
                    @endcan
                    @can('view insurers')
                        <a href="{{ route('insurers.index') }}" class="side-child {{ request()->routeIs('insurers.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Insurers') }}</a>
                        <a href="{{ route('member-insurances.index') }}" class="side-child {{ request()->routeIs('member-insurances.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Member Policies') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['view membership-packages', 'view subscriptions'])
            <details class="side-group" @if(request()->routeIs('membership-packages.*', 'subscriptions.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('membership-packages.*', 'subscriptions.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-id-card side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Memberships') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view membership-packages')
                        <a href="{{ route('membership-packages.index') }}" class="side-child {{ request()->routeIs('membership-packages.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Packages') }}</a>
                    @endcan
                    @can('view subscriptions')
                        <a href="{{ route('subscriptions.index') }}" class="side-child {{ request()->routeIs('subscriptions.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Subscriptions') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['view classes', 'view class bookings'])
            <details class="side-group" @if(request()->routeIs('class-types.*', 'class-sessions.*', 'class-bookings.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('class-types.*', 'class-sessions.*', 'class-bookings.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-dumbbell side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Classes') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view classes')
                        <a href="{{ route('class-types.index') }}" class="side-child {{ request()->routeIs('class-types.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Class Types') }}</a>
                        <a href="{{ route('class-sessions.index') }}" class="side-child {{ request()->routeIs('class-sessions.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Sessions / Schedule') }}</a>
                    @endcan
                    @can('view class bookings')
                        <a href="{{ route('class-bookings.index') }}" class="side-child {{ request()->routeIs('class-bookings.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Bookings') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @can('view workout plans')
            <details class="side-group" @if(request()->routeIs('exercises.*', 'workout-plans.*', 'member-workout-plans.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('exercises.*', 'workout-plans.*', 'member-workout-plans.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-fire side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Training') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('exercises.index') }}" class="side-child {{ request()->routeIs('exercises.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Exercises') }}</a>
                    <a href="{{ route('workout-plans.index') }}" class="side-child {{ request()->routeIs('workout-plans.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Workout Plans') }}</a>
                    @can('assign workout plans')
                        <a href="{{ route('member-workout-plans.index') }}" class="side-child {{ request()->routeIs('member-workout-plans.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Assigned Plans') }}</a>
                    @endcan
                </div>
            </details>
        @endcan

        @can('view events')
            <details class="side-group" @if(request()->routeIs('events.*', 'event-registrations.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('events.*', 'event-registrations.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-calendar-check side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Events') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('events.index') }}" class="side-child {{ request()->routeIs('events.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Events') }}</a>
                    @can('manage event registrations')
                        <a href="{{ route('event-registrations.index') }}" class="side-child {{ request()->routeIs('event-registrations.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Registrations') }}</a>
                    @endcan
                </div>
            </details>
        @endcan

        @canany(['view pos', 'view products', 'view inventory', 'view stock adjustments', 'view purchase orders'])
            <details class="side-group" @if(request()->routeIs('pos.*', 'pos-sales.*', 'products.*', 'product-categories.*', 'branch-products.*', 'stock-adjustments.*', 'suppliers.*', 'purchase-orders.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('pos.*', 'pos-sales.*', 'products.*', 'product-categories.*', 'branch-products.*', 'stock-adjustments.*', 'suppliers.*', 'purchase-orders.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-cart-shopping side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('POS & Inventory') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view pos')
                        <a href="{{ route('pos.index') }}" class="side-child {{ request()->routeIs('pos.index') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Point of Sale') }}</a>
                        <a href="{{ route('pos-sales.index') }}" class="side-child {{ request()->routeIs('pos-sales.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Sales History') }}</a>
                    @endcan
                    @can('view products')
                        <a href="{{ route('products.index') }}" class="side-child {{ request()->routeIs('products.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Products') }}</a>
                        <a href="{{ route('product-categories.index') }}" class="side-child {{ request()->routeIs('product-categories.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Categories') }}</a>
                    @endcan
                    @can('view inventory')
                        <a href="{{ route('branch-products.index') }}" class="side-child {{ request()->routeIs('branch-products.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Stock Levels') }}</a>
                    @endcan
                    @can('view stock adjustments')
                        <a href="{{ route('stock-adjustments.index') }}" class="side-child {{ request()->routeIs('stock-adjustments.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Stock Adjustments') }}</a>
                    @endcan
                    @can('view purchase orders')
                        <a href="{{ route('suppliers.index') }}" class="side-child {{ request()->routeIs('suppliers.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Suppliers') }}</a>
                        <a href="{{ route('purchase-orders.index') }}" class="side-child {{ request()->routeIs('purchase-orders.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Purchase Orders') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @canany(['view hikvision', 'manage hikvision', 'view attendance', 'view access devices', 'manage access devices', 'manage access identities'])
            <details class="side-group" @if(request()->routeIs('hikvision.*', 'attendance.*', 'access-control.*', 'access-identities.*', 'access-devices.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('hikvision.*', 'attendance.*', 'access-control.*', 'access-identities.*', 'access-devices.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-fingerprint side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('HIKVision') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @canany(['view hikvision', 'manage hikvision', 'view attendance', 'view access devices', 'manage access devices'])
                        <a href="{{ route('hikvision.overview') }}" class="side-child {{ request()->routeIs('hikvision.overview') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Overview') }}</a>
                        <a href="{{ route('hikvision.logs.index') }}" class="side-child {{ request()->routeIs('hikvision.logs.*', 'attendance.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Logs') }}</a>
                        <a href="{{ route('hikvision.devices.index') }}" class="side-child {{ request()->routeIs('hikvision.devices.*', 'access-control.devices.*', 'access-devices.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Devices') }}</a>
                        <a href="{{ route('hikvision.agents.index') }}" class="side-child {{ request()->routeIs('hikvision.agents.*', 'access-control.agents.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Agents') }}</a>
                        <a href="{{ route('hikvision.enrollments.index') }}" class="side-child {{ request()->routeIs('hikvision.enrollments.*', 'access-control.enrollments.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Enrollments') }}</a>
                    @endcanany
                    @canany(['manage access identities', 'manage hikvision', 'manage access devices'])
                        <a href="{{ route('hikvision.identities.index') }}" class="side-child {{ request()->routeIs('hikvision.identities.*', 'access-identities.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Identities') }}</a>
                    @endcanany
                </div>
            </details>
        @endcanany

        @canany(['view zkteco', 'manage zkteco', 'manage zkteco settings'])
            <details class="side-group" @if(request()->routeIs('zkteco.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('zkteco.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-microchip side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('ZKTeco') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('zkteco.overview') }}" class="side-child {{ request()->routeIs('zkteco.connections.*', 'zkteco.overview') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Integrations') }}</a>
                    <a href="{{ route('zkteco.events.index') }}" class="side-child {{ request()->routeIs('zkteco.events.*', 'zkteco.logs.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Events') }}</a>
                    @canany(['view zkteco', 'manage zkteco'])
                        <a href="{{ route('zkteco.connections.create') }}" class="side-child {{ request()->routeIs('zkteco.connections.create') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Add Integration') }}</a>
                    @endcanany
                </div>
            </details>
        @endcanany

        @can('view equipment')
            <details class="side-group" @if(request()->routeIs('locations.*', 'equipment.*', 'equipment-allocations.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('locations.*', 'equipment.*', 'equipment-allocations.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-warehouse side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Facilities') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('locations.index') }}" class="side-child {{ request()->routeIs('locations.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Locations') }}</a>
                    <a href="{{ route('equipment.index') }}" class="side-child {{ request()->routeIs('equipment.index', 'equipment.create', 'equipment.edit') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Equipment') }}</a>
                    @can('view equipment allocations')
                        <a href="{{ route('equipment-allocations.index') }}" class="side-child {{ request()->routeIs('equipment-allocations.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Allocations') }}</a>
                    @endcan
                </div>
            </details>
        @endcan

        @can('view payments')
            <details class="side-group" @if(request()->routeIs('payments.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('payments.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-wallet side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Finances') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('payments.index') }}" class="side-child {{ request()->routeIs('payments.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Payments') }}</a>
                </div>
            </details>
        @endcan

        @can('view expenses')
            <details class="side-group" @if(request()->routeIs('expenses.*', 'expense-categories.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('expenses.*', 'expense-categories.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-receipt side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Expenses') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    <a href="{{ route('expenses.index') }}" class="side-child {{ request()->routeIs('expenses.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Expenses') }}</a>
                    <a href="{{ route('expense-categories.index') }}" class="side-child {{ request()->routeIs('expense-categories.*') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Expense Categories') }}</a>
                </div>
            </details>
        @endcan

        @canany(['view reports', 'view financial reports', 'view attendance reports', 'view membership reports', 'view insurance reports', 'view pos reports', 'view expense reports'])
            <details class="side-group" @if(request()->routeIs('reports.*')) open @endif>
                <summary class="side-parent {{ request()->routeIs('reports.*') ? 'side-parent-active' : '' }}">
                    <span class="side-parent-left">
                        <i class="fa-sharp-duotone fa-solid fa-chart-line side-icon" aria-hidden="true"></i>
                        <span class="side-parent-label">{{ __('Reports') }}</span>
                    </span>
                    <i class="fa-sharp-duotone fa-solid fa-chevron-down side-chevron" aria-hidden="true"></i>
                </summary>
                <div class="side-children">
                    @can('view financial reports')
                        <a href="{{ route('reports.revenue') }}" class="side-child {{ request()->routeIs('reports.revenue') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Revenue') }}</a>
                    @endcan
                    @can('view expense reports')
                        <a href="{{ route('reports.expenses') }}" class="side-child {{ request()->routeIs('reports.expenses') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Expenses') }}</a>
                    @endcan
                    @can('view membership reports')
                        <a href="{{ route('reports.memberships') }}" class="side-child {{ request()->routeIs('reports.memberships') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Memberships') }}</a>
                    @endcan
                    @can('view attendance reports')
                        <a href="{{ route('reports.attendance') }}" class="side-child {{ request()->routeIs('reports.attendance') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Attendance') }}</a>
                    @endcan
                    @can('view insurance reports')
                        <a href="{{ route('reports.insurance') }}" class="side-child {{ request()->routeIs('reports.insurance') ? 'side-child-active' : '' }}" wire:navigate>{{ __('Insurance') }}</a>
                    @endcan
                    @can('view pos reports')
                        <a href="{{ route('reports.pos') }}" class="side-child {{ request()->routeIs('reports.pos') ? 'side-child-active' : '' }}" wire:navigate>{{ __('POS / Sales') }}</a>
                    @endcan
                </div>
            </details>
        @endcanany

        @can('view settings')
            <a href="{{ route('settings.general') }}" class="side-parent {{ request()->routeIs('settings.*') ? 'side-parent-active' : '' }} mt-2" wire:navigate>
                <span class="side-parent-left">
                    <i class="fa-sharp-duotone fa-solid fa-gear side-icon" aria-hidden="true"></i>
                    <span class="side-parent-label">{{ __('Settings') }}</span>
                </span>
            </a>
        @endcan
    </div>
</nav>
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

