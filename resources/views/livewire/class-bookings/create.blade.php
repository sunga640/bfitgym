<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-4xl space-y-8">
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

        <div class="grid gap-8 lg:grid-cols-2">
            {{-- Select Class Session --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Select Class Session') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Choose which session to book.') }}</p>
                </div>

                <div class="mt-4">
                    <flux:input
                        wire:model.live.debounce.300ms="session_search"
                        type="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search by class type or location...') }}"
                        class="w-full"
                    />
                </div>

                <div class="mt-4 max-h-[400px] space-y-2 overflow-y-auto">
                    @forelse($sessions as $session)
                        <label
                            class="block cursor-pointer rounded-lg border p-4 transition-colors
                                {{ $class_session_id == $session->id
                                    ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                    : 'border-zinc-200 hover:border-blue-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-blue-600 dark:hover:bg-zinc-700/50' }}
                                {{ !$session->is_available ? 'opacity-50' : '' }}"
                        >
                            <div class="flex items-start gap-3">
                                <input
                                    type="radio"
                                    wire:model.live="class_session_id"
                                    value="{{ $session->id }}"
                                    class="mt-1"
                                    {{ !$session->is_available ? 'disabled' : '' }}
                                >
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <span class="font-medium text-zinc-900 dark:text-white">{{ $session->classType?->name }}</span>
                                        @if($session->classType?->has_booking_fee)
                                            <flux:badge color="emerald" size="sm">
                                                {{ number_format($session->classType->booking_fee, 2) }}
                                            </flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('Free') }}</flux:badge>
                                        @endif
                                    </div>
                                    <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span class="flex items-center gap-1">
                                            <flux:icon name="map-pin" class="h-3 w-3" />
                                            {{ $session->location?->name }}
                                        </span>
                                        <span class="flex items-center gap-1">
                                            @if($session->is_recurring)
                                                <flux:icon name="arrow-path" class="h-3 w-3" />
                                                {{ $days[$session->day_of_week] ?? '' }}
                                            @else
                                                <flux:icon name="calendar" class="h-3 w-3" />
                                                {{ $session->specific_date?->format('M d') }}
                                            @endif
                                        </span>
                                        <span class="flex items-center gap-1">
                                            <flux:icon name="clock" class="h-3 w-3" />
                                            {{ \Carbon\Carbon::parse($session->start_time)->format('g:i A') }}
                                        </span>
                                    </div>
                                    <div class="mt-2 flex items-center gap-2">
                                        <span class="text-xs font-medium {{ $session->is_available ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                            @if($session->available_spots === null)
                                                {{ __('Unlimited spots') }}
                                            @elseif($session->available_spots > 0)
                                                {{ trans_choice(':count spot available|:count spots available', $session->available_spots) }}
                                            @else
                                                {{ __('Fully booked') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No available sessions found.') }}
                        </div>
                    @endforelse
                </div>

                @error('class_session_id')
                    <div class="mt-2 text-xs text-red-500">{{ $message }}</div>
                @enderror
            </div>

            {{-- Select Member --}}
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Select Member') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Choose which member to book.') }}</p>
                </div>

                <div class="mt-4">
                    <flux:input
                        wire:model.live.debounce.300ms="member_search"
                        type="search"
                        icon="magnifying-glass"
                        placeholder="{{ __('Search by name, member no, or phone...') }}"
                        class="w-full"
                    />
                </div>

                <div class="mt-4 max-h-[400px] space-y-2 overflow-y-auto">
                    @forelse($members as $member)
                        <label
                            class="block cursor-pointer rounded-lg border p-4 transition-colors
                                {{ $member_id == $member->id
                                    ? 'border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20'
                                    : 'border-zinc-200 hover:border-blue-300 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:border-blue-600 dark:hover:bg-zinc-700/50' }}"
                        >
                            <div class="flex items-center gap-3">
                                <input
                                    type="radio"
                                    wire:model.live="member_id"
                                    value="{{ $member->id }}"
                                >
                                <div class="flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-white">{{ $member->full_name }}</div>
                                    <div class="mt-1 flex gap-4 text-xs text-zinc-500 dark:text-zinc-400">
                                        <span>{{ $member->member_no }}</span>
                                        <span>{{ $member->phone }}</span>
                                    </div>
                                </div>
                            </div>
                        </label>
                    @empty
                        <div class="py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                            {{ __('No members found. Try searching.') }}
                        </div>
                    @endforelse
                </div>

                @error('member_id')
                    <div class="mt-2 text-xs text-red-500">{{ $message }}</div>
                @enderror
            </div>
        </div>

        {{-- Warning if member already booked --}}
        @if($has_existing_booking)
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
                <div class="flex items-center gap-3">
                    <flux:icon name="exclamation-triangle" class="h-5 w-5 text-amber-600 dark:text-amber-400" />
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        {{ __('This member already has a booking for this session.') }}
                    </p>
                </div>
            </div>
        @endif

        {{-- Payment Details (if class has fee) --}}
        @if($selected_session && $selected_session->classType?->has_booking_fee)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Payment Details') }}</h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('This class requires a booking fee.') }}</p>
                </div>

                <div class="mt-6">
                    {{-- Fee Summary --}}
                    <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-800 dark:bg-emerald-900/20">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-emerald-900 dark:text-emerald-100">{{ __('Booking Fee') }}</span>
                            <span class="text-lg font-bold text-emerald-700 dark:text-emerald-300">
                                {{ number_format($selected_session->classType->booking_fee, 2) }} {{ $currency }}
                            </span>
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Payment Method') }} <span class="text-red-500">*</span></flux:label>
                                <flux:select wire:model.live="payment_method">
                                    <option value="cash">{{ __('Cash') }}</option>
                                    <option value="mobile_money">{{ __('Mobile Money') }}</option>
                                    <option value="card">{{ __('Card') }}</option>
                                    <option value="bank_transfer">{{ __('Bank Transfer') }}</option>
                                </flux:select>
                                @error('payment_method')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>

                        <div>
                            <flux:field>
                                <flux:label>{{ __('Currency') }} <span class="text-red-500">*</span></flux:label>
                                <flux:select wire:model.live="currency">
                                    <option value="TZS">TZS</option>
                                    <option value="USD">USD</option>
                                    <option value="KES">KES</option>
                                </flux:select>
                                @error('currency')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>

                        <div class="sm:col-span-2">
                            <flux:field>
                                <flux:label>{{ __('Reference') }}</flux:label>
                                <flux:input
                                    type="text"
                                    wire:model.live="reference"
                                    placeholder="{{ __('Transaction reference (optional)') }}"
                                />
                                @error('reference')
                                    <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                                @enderror
                            </flux:field>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('class-bookings.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button
                variant="primary"
                type="submit"
                wire:loading.attr="disabled"
                :disabled="$has_existing_booking"
            >
                <span wire:loading.remove>{{ __('Create Booking') }}</span>
                <span wire:loading>{{ __('Processing...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

