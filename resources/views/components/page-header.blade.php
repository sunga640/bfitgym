@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $title }}</h1>
        @if($description)
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    {{-- Actions slot for buttons --}}
    @isset($actions)
        <div class="mt-4 flex flex-shrink-0 gap-2 sm:mt-0">
            {{ $actions }}
        </div>
    @endisset
</div>

