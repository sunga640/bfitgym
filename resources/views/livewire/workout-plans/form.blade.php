<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-2xl space-y-8">
        @if(session('success'))
            <div class="rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Basic Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Workout Plan Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the basic details for this workout plan.') }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @if($branches->count())
                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>{{ __('Branch') }}</flux:label>
                            <flux:select wire:model.live="branch_id">
                                <option value="">{{ __('Select branch') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                @endforeach
                            </flux:select>
                            @error('branch_id')
                                <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                            @enderror
                        </flux:field>
                    </div>
                @endif

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Name') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" wire:model.live="name" placeholder="{{ __('e.g., Full Body Strength, Weight Loss Program') }}" required />
                        @error('name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Level') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="level">
                            @foreach($levels as $level_value => $level_label)
                                <option value="{{ $level_value }}">{{ $level_label }}</option>
                            @endforeach
                        </flux:select>
                        @error('level')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Duration (Weeks)') }}</flux:label>
                        <flux:input type="number" wire:model.live="total_weeks" min="1" max="52" placeholder="{{ __('e.g., 12') }}" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Leave empty if duration is flexible.') }}</p>
                        @error('total_weeks')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model.live="description" rows="4" placeholder="{{ __('Describe the workout plan goals, target audience, and what participants can expect...') }}" />
                        @error('description')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Status Section --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Status') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Control the visibility and availability of this workout plan.') }}</p>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Active') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable to make this workout plan available for assignment to members.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="is_active">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-300 dark:bg-zinc-600"></div>
                    </label>
                </div>
                @error('is_active')
                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('workout-plans.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $is_editing ? __('Update Workout Plan') : __('Create Workout Plan') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

