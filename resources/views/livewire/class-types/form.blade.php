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
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Class Type Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the basic details for this class type.') }}</p>
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
                        <flux:input type="text" wire:model.live="name" placeholder="{{ __('e.g., Yoga, Zumba, HIIT') }}" required />
                        @error('name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model.live="description" rows="3" placeholder="{{ __('Describe the class type, what participants can expect, etc.') }}" />
                        @error('description')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Capacity') }}</flux:label>
                        <flux:input type="number" wire:model.live="capacity" min="1" max="1000" placeholder="{{ __('e.g., 20') }}" />
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Leave empty for unlimited capacity.') }}</p>
                        @error('capacity')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Status') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="status">
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                        </flux:select>
                        @error('status')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Booking Fee Section --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Booking Fee') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Configure if this class type requires a booking fee.') }}</p>
            </div>

            <div class="mt-6">
                {{-- Has Booking Fee Toggle --}}
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Requires Booking Fee') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable if participants need to pay a fee to book this class.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="has_booking_fee">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-300 dark:bg-zinc-600"></div>
                    </label>
                </div>
                @error('has_booking_fee')
                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                @enderror

                {{-- Booking Fee Amount (shown when has_booking_fee is true) --}}
                @if($has_booking_fee)
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <flux:field>
                            <flux:label>{{ __('Booking Fee Amount') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input 
                                type="number" 
                                wire:model.live="booking_fee" 
                                min="0" 
                                step="0.01" 
                                placeholder="{{ __('e.g., 5000.00') }}"
                            />
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('The fee charged when a member books this class.') }}</p>
                            @error('booking_fee')
                                <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                            @enderror
                        </flux:field>
                    </div>
                @endif
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('class-types.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $is_editing ? __('Update Class Type') : __('Create Class Type') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

