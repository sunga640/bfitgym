@props(['title' => null, 'description' => null])

<x-layouts.app.sidebar :title="$title">
    <flux:main class="app-content relative h-[calc(100dvh-4rem)] min-h-0 overflow-y-auto overscroll-contain p-4 pt-0 pb-8 lg:h-[calc(100dvh-4.5rem)] lg:p-6 lg:pt-0 lg:pb-8 lg:pl-2">
        {{-- Page Header --}}
        <div class="mb-6">
            {{-- Breadcrumbs (optional) --}}
            @isset($breadcrumbs)
                <nav class="mb-3 text-sm" aria-label="Breadcrumb">
                    <ol class="flex items-center space-x-2">
                        <li>
                            <a href="{{ route('dashboard') }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
                                <flux:icon name="home" class="h-4 w-4" />
                            </a>
                        </li>
                        {{ $breadcrumbs }}
                    </ol>
                </nav>
            @endisset

            {{-- Title Section with optional actions --}}
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if($title)
                        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
                    @endif

                    @if($description)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                    @endif

                    @isset($subtitle)
                        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $subtitle }}</p>
                    @endisset
                </div>

                {{-- Page Actions (optional) --}}
                @isset($actions)
                    <div class="flex flex-shrink-0 items-center gap-2">
                        {{ $actions }}
                    </div>
                @endisset
            </div>

            {{-- Custom Header Content (optional) --}}
            @isset($header)
                <div class="mt-4">
                    {{ $header }}
                </div>
            @endisset
        </div>

        {{-- Filters/Toolbar (optional) --}}
        @isset($toolbar)
            <div class="mb-4 flex flex-col gap-4 rounded-2xl border border-white/20 bg-white/70 p-4 shadow-xl shadow-zinc-900/5 backdrop-blur-xl dark:border-zinc-700/50 dark:bg-zinc-800/70 sm:flex-row sm:items-center sm:justify-between">
                {{ $toolbar }}
            </div>
        @endisset

        {{-- Main Page Content --}}
        <div class="flex-1">
            {{ $slot }}
        </div>

        {{-- Footer (optional) --}}
        @isset($footer)
            <div class="mt-6 border-t border-zinc-200/50 pt-4 dark:border-zinc-700/50">
                {{ $footer }}
            </div>
        @endisset
    </flux:main>
</x-layouts.app.sidebar>
