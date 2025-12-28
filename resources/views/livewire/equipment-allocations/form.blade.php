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

        {{-- Location & Equipment Selection --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Allocation Details') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Assign equipment to a specific location.') }}</p>
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

                <div>
                    <flux:field>
                        <flux:label>{{ __('Location') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="location_id">
                            <option value="">{{ __('Select location') }}</option>
                            @foreach($locations as $location)
                                <option value="{{ $location->id }}">{{ $location->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('location_id')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Equipment') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="equipment_id">
                            <option value="">{{ __('Select equipment') }}</option>
                            @foreach($equipment_list as $equipment)
                                <option value="{{ $equipment->id }}">
                                    {{ $equipment->name }}
                                    @if($equipment->brand) ({{ $equipment->brand }}) @endif
                                </option>
                            @endforeach
                        </flux:select>
                        @error('equipment_id')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Asset Tag') }}</flux:label>
                        <flux:input
                            type="text"
                            wire:model.live="asset_tag"
                            placeholder="{{ __('e.g., EQ-001') }}"
                        />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Unique identifier for this specific unit.') }}</p>
                        @error('asset_tag')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Quantity') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input
                            type="number"
                            wire:model.live="quantity"
                            min="1"
                            max="1000"
                        />
                        @error('quantity')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Status --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Status') }}</h2>
            </div>

            <div class="mt-6">
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Active') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Equipment is currently in use at this location.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="is_active">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-300 dark:bg-zinc-600"></div>
                    </label>
                </div>
            </div>
        </div>

        {{-- Current Equipment at Location --}}
        @if($location_equipment && count($location_equipment) > 0)
            <div class="rounded-xl border border-blue-200 bg-blue-50/50 p-6 dark:border-blue-800 dark:bg-blue-900/20">
                <div class="flex items-start gap-3">
                    <flux:icon name="information-circle" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                    <div class="flex-1">
                        <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Other Equipment at This Location') }}</h4>
                        <div class="mt-3 space-y-2">
                            @foreach($location_equipment as $existing)
                                <div class="flex items-center justify-between rounded-md bg-white/50 px-3 py-2 dark:bg-zinc-800/50">
                                    <span class="text-sm text-blue-800 dark:text-blue-200">{{ $existing->equipment?->name }}</span>
                                    <span class="text-xs text-blue-600 dark:text-blue-400">{{ __('Qty:') }} {{ $existing->quantity }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('equipment-allocations.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $is_editing ? __('Update Allocation') : __('Create Allocation') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

