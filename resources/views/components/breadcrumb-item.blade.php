@props(['href' => null, 'current' => false])

<li class="flex items-center">
    <flux:icon name="chevron-right" class="mx-2 h-4 w-4 text-zinc-400" />
    @if($href && !$current)
        <a href="{{ $href }}" class="text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200" wire:navigate>
            {{ $slot }}
        </a>
    @else
        <span class="font-medium text-zinc-900 dark:text-white" @if($current) aria-current="page" @endif>
            {{ $slot }}
        </span>
    @endif
</li>

