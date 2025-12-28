@props(['title', 'description' => null, 'icon' => 'document'])

<x-layouts.app :title="$title" :description="$description">
    <x-slot:breadcrumbs>
        <x-breadcrumb-item :current="true">{{ $title }}</x-breadcrumb-item>
    </x-slot:breadcrumbs>

    <div class="flex h-[50vh] w-full flex-col items-center justify-center gap-6 rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-8 dark:border-zinc-700 dark:bg-zinc-900/50">
        <div class="flex h-20 w-20 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
            <flux:icon :name="$icon" class="h-10 w-10 text-zinc-400" />
        </div>
        <div class="text-center">
            <h2 class="text-lg font-medium text-zinc-900 dark:text-white">{{ __('Coming Soon') }}</h2>
            <p class="mt-2 max-w-md text-sm text-zinc-500 dark:text-zinc-400">
                {{ $description ?? __('This feature is under development and will be available soon.') }}
            </p>
        </div>
    </div>
</x-layouts.app>
