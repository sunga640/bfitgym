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

        {{-- Member Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Member Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the member\'s personal and contact details.') }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                @if($branches->count())
                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>{{ __('Branch') }}</flux:label>
                            <flux:select wire:model="branch_id">
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
                        <flux:label>{{ __('First Name') }}</flux:label>
                        <flux:input type="text" wire:model.blur="first_name" required />
                        @error('first_name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Last Name') }}</flux:label>
                        <flux:input type="text" wire:model.blur="last_name" required />
                        @error('last_name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Phone') }}</flux:label>
                        <flux:input type="tel" wire:model.blur="phone" required placeholder="+255" />
                        @error('phone')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Email') }}</flux:label>
                        <flux:input type="email" wire:model.blur="email" />
                        @error('email')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Gender') }} <span class="text-red-500">*</span></flux:label>
                        <flux:select wire:model="gender" required>
                            <option value="">{{ __('Select gender') }}</option>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                            <option value="other">{{ __('Other') }}</option>
                        </flux:select>
                        @error('gender')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Date of Birth') }}</flux:label>
                        <flux:input type="date" wire:model.blur="dob" />
                        @error('dob')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Address') }}</flux:label>
                        <flux:textarea wire:model.blur="address" rows="2" />
                        @error('address')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status">
                            <option value="active">{{ __('Active') }}</option>
                            <option value="inactive">{{ __('Inactive') }}</option>
                            <option value="suspended">{{ __('Suspended') }}</option>
                        </flux:select>
                        @error('status')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Notes') }}</flux:label>
                        <flux:textarea wire:model.blur="notes" rows="3" placeholder="{{ __('Health conditions, special requirements, etc.') }}" />
                        @error('notes')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Insurance Section --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Insurance Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Configure member\'s insurance coverage if applicable.') }}</p>
            </div>

            <div class="mt-6">
                {{-- Has Insurance Toggle --}}
                <div class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Has Insurance') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable if this member has insurance coverage.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="has_insurance">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500 peer-focus:ring-2 peer-focus:ring-emerald-300 dark:bg-zinc-600"></div>
                    </label>
                </div>
                @error('has_insurance')
                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                @enderror

                {{-- Insurance Details (shown when has_insurance is true) --}}
                @if($has_insurance)
                    <div class="mt-6 grid gap-4 rounded-lg border border-emerald-200 bg-emerald-50/50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <flux:field>
                                <flux:label>{{ __('Insurer') }} <span class="text-red-500">*</span></flux:label>
                                <flux:select wire:model="insurer_id">
                                    <option value="">{{ __('Select insurer') }}</option>
                                    @foreach($insurers as $insurer)
                                        <option value="{{ $insurer->id }}">{{ $insurer->name }}</option>
                                    @endforeach
                                </flux:select>
                                @error('insurer_id')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Start Date') }} <span class="text-red-500">*</span></flux:label>
                                <flux:input type="date" wire:model.blur="insurance_start_date" />
                                @error('insurance_start_date')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('End Date') }}</flux:label>
                                <flux:input type="date" wire:model.blur="insurance_end_date" />
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Leave empty for indefinite coverage.') }}</p>
                                @error('insurance_end_date')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>

                        @if($insurers->isEmpty())
                            <div class="sm:col-span-2">
                                <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-700 dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-400">
                                    <flux:icon name="exclamation-triangle" class="mr-2 inline h-4 w-4" />
                                    {{ __('No active insurers found. Please add an insurer first.') }}
                                    <a href="{{ route('insurers.create') }}" class="ml-1 font-medium underline" wire:navigate>{{ __('Add Insurer') }}</a>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('members.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $isEditing ? __('Update Member') : __('Create Member') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
