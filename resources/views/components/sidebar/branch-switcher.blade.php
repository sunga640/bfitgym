@php
    $branches = branch_context()->getAccessibleBranches();
    $current_branch = current_branch();
@endphp

<flux:dropdown class="mb-4 w-full">
    <flux:button variant="subtle" class="w-full justify-between">
        <div class="flex items-center gap-2">
            <flux:icon name="building-office" class="h-4 w-4" />
            <span class="truncate">{{ $current_branch?->name ?? __('Select Branch') }}</span>
        </div>
        <flux:icon name="chevron-down" class="h-4 w-4" />
    </flux:button>

    <flux:menu class="w-full">
        <flux:menu.heading>{{ __('Switch Branch') }}</flux:menu.heading>

        @foreach($branches as $branch)
            <flux:menu.item
                :href="request()->fullUrlWithQuery(['switch_branch' => $branch->id])"
                :current="$current_branch?->id === $branch->id"
            >
                <div class="flex items-center gap-2">
                    @if($current_branch?->id === $branch->id)
                        <flux:icon name="check" class="h-4 w-4 text-green-500" />
                    @else
                        <span class="h-4 w-4"></span>
                    @endif
                    <span>{{ $branch->name }}</span>
                    <span class="ml-auto text-xs text-zinc-400">{{ $branch->code }}</span>
                </div>
            </flux:menu.item>
        @endforeach

        @if($current_branch && auth()->user()->hasRole('super-admin'))
            <flux:menu.separator />
            <flux:menu.item :href="request()->fullUrlWithQuery(['clear_branch' => 1])">
                <div class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
                    <flux:icon name="globe-alt" class="h-4 w-4" />
                    <span>{{ __('View All Branches') }}</span>
                </div>
            </flux:menu.item>
        @endif
    </flux:menu>
</flux:dropdown>

