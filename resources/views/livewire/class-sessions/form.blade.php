<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-3xl space-y-8">
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

        {{-- Class Type & Location --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Class Details') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Select the class type and location for this session.') }}</p>
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
                        <flux:label>{{ __('Class Type') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="class_type_id">
                            <option value="">{{ __('Select class type') }}</option>
                            @foreach($class_types as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('class_type_id')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

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

                {{-- Class Type Info Card --}}
                @if($selected_class_type)
                    <div class="sm:col-span-2">
                        <div class="rounded-lg border border-blue-200 bg-blue-50/50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                            <div class="flex items-start gap-3">
                                <flux:icon name="information-circle" class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-blue-900 dark:text-blue-100">{{ __('Class Type Info') }}</h4>
                                    <div class="mt-2 flex flex-wrap gap-4 text-sm text-blue-700 dark:text-blue-300">
                                        <span>
                                            <strong>{{ __('Default Capacity:') }}</strong>
                                            {{ $selected_class_type->capacity ?? __('Unlimited') }}
                                        </span>
                                        @if($selected_class_type->has_booking_fee)
                                            <span>
                                                <strong>{{ __('Booking Fee:') }}</strong>
                                                {{ number_format($selected_class_type->booking_fee, 2) }}
                                            </span>
                                        @else
                                            <span class="text-blue-600 dark:text-blue-400">{{ __('No booking fee') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Schedule --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Schedule') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Set when this class session will take place.') }}</p>
            </div>

            <div class="mt-6 space-y-6">
                {{-- Recurring Toggle --}}
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Recurring Session') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable for weekly repeating sessions.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="is_recurring">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-blue-500 peer-focus:ring-2 peer-focus:ring-blue-300 dark:bg-zinc-600"></div>
                    </label>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    @if($is_recurring)
                        <div class="sm:col-span-2">
                            <flux:field>
                                <flux:label>{{ __('Day of Week') }} <span class="text-red-500">*</span></flux:label>
                                <flux:select wire:model.live="day_of_week">
                                    <option value="">{{ __('Select day') }}</option>
                                    @foreach($days as $num => $name)
                                        <option value="{{ $num }}">{{ $name }}</option>
                                    @endforeach
                                </flux:select>
                                @error('day_of_week')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>
                    @else
                        <div class="sm:col-span-2">
                            <flux:field>
                                <flux:label>{{ __('Specific Date') }} <span class="text-red-500">*</span></flux:label>
                                <flux:input type="date" wire:model.live="specific_date" />
                                @error('specific_date')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>
                    @endif

                    <div>
                        <flux:field>
                            <flux:label>{{ __('Start Time') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input type="time" wire:model.live="start_time" />
                            @error('start_time')
                                <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                            @enderror
                        </flux:field>
                    </div>

                    <div>
                        <flux:field>
                            <flux:label>{{ __('End Time') }} <span class="text-red-500">*</span></flux:label>
                            <flux:input type="time" wire:model.live="end_time" />
                            @error('end_time')
                                <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                            @enderror
                        </flux:field>
                    </div>
                </div>
            </div>
        </div>

        {{-- Instructors --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Instructors') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Assign the main instructor and optional assistant staff.') }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div>
                    <flux:field>
                        <flux:label>{{ __('Main Instructor') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="main_instructor_id">
                            <option value="">{{ __('Select instructor') }}</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}">{{ $instructor->name }}</option>
                            @endforeach
                        </flux:select>
                        @error('main_instructor_id')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Status') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model.live="status">
                            <option value="active">{{ __('Active') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                        </flux:select>
                        @error('status')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Assistant Staff') }}</flux:label>
                        <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700 sm:grid-cols-3">
                            @forelse($instructors as $instructor)
                                @if($instructor->id !== $main_instructor_id)
                                    <label class="flex cursor-pointer items-center gap-2 rounded-md p-2 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                        <input
                                            type="checkbox"
                                            wire:model.live="assistant_staff_ids"
                                            value="{{ $instructor->id }}"
                                            class="rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600"
                                        >
                                        <span class="text-sm text-zinc-700 dark:text-zinc-200">{{ $instructor->name }}</span>
                                    </label>
                                @endif
                            @empty
                                <p class="col-span-full text-sm text-zinc-500 dark:text-zinc-400">{{ __('No staff available') }}</p>
                            @endforelse
                        </div>
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Select additional staff to assist with this session.') }}</p>
                        @error('assistant_staff_ids')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Capacity Override --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Capacity') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Override the default capacity for this specific session if needed.') }}</p>
            </div>

            <div class="mt-6">
                <flux:field>
                    <flux:label>{{ __('Capacity Override') }}</flux:label>
                    <flux:input
                        type="number"
                        wire:model.live="capacity_override"
                        min="1"
                        max="500"
                        placeholder="{{ $selected_class_type?->capacity ? __('Default: :capacity', ['capacity' => $selected_class_type->capacity]) : __('Leave empty for unlimited') }}"
                    />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                        {{ __('Leave empty to use the class type default capacity.') }}
                    </p>
                    @error('capacity_override')
                        <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                    @enderror
                </flux:field>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('class-sessions.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $is_editing ? __('Update Session') : __('Create Session') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

