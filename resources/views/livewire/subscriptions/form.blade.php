<div>
    @error('form')
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-300">
            {{ $message }}
        </div>
    @enderror

    @php
        $selectedMember = $member_id ? $members->firstWhere('id', $member_id) : null;
        $selectedPackageModel = $this->selectedPackage;
    @endphp

    <form wire:submit.prevent="save" class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                    {{ __('Member & Package') }}
                </h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label for="member_id" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Member') }}
                        </label>
                        <flux:select
                            id="member_id"
                            wire:model.live="member_id"
                            :disabled="$is_renewal"
                        >
                            <option value="">{{ __('Select member') }}</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">
                                    {{ $member->first_name }} {{ $member->last_name }} ({{ $member->member_no }})
                                </option>
                            @endforeach
                        </flux:select>
                        @error('member_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="package_id" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Package') }}
                        </label>
                        <flux:select id="package_id" wire:model.live="membership_package_id">
                            <option value="">{{ __('Select package') }}</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}">
                                    {{ $package->name }} — {{ money($package->price) }}
                                </option>
                            @endforeach
                        </flux:select>
                        @error('membership_package_id') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Start date') }}
                        </label>
                        <flux:input type="date" wire:model.live="start_date" />
                        @error('start_date') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('End date') }}
                        </label>
                        <flux:input type="date" wire:model="end_date" disabled />
                        <p class="mt-1 text-xs text-zinc-500">{{ __('End date is calculated from the package duration.') }}</p>
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                    <div>
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">{{ __('Auto renew') }}</p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Automatically create a renewal draft when this subscription ends.') }}</p>
                    </div>
                    <label class="inline-flex cursor-pointer items-center">
                        <input type="checkbox" class="peer sr-only" wire:model.live="auto_renew">
                        <div class="peer h-6 w-11 rounded-full bg-zinc-300 shadow-inner transition-all peer-checked:bg-emerald-500"></div>
                    </label>
                </div>

                <div class="mt-6">
                    <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        {{ __('Notes (optional)') }}
                    </label>
                    <textarea
                        wire:model.live="notes"
                        rows="3"
                        class="w-full rounded-xl border border-zinc-200 bg-white p-3 text-sm text-zinc-900 shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                    ></textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <h3 class="text-base font-semibold text-zinc-900 dark:text-white">
                    {{ __('Payment Details') }}
                </h3>
                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Amount') }}
                        </label>
                        <flux:input type="number" step="0.01" wire:model.live="amount" />
                        @error('amount') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Currency') }}
                        </label>
                        <flux:input type="text" wire:model.live="currency" maxlength="3" class="uppercase" />
                        @error('currency') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Payment method') }}
                        </label>
                        <flux:input type="text" wire:model.live="payment_method" />
                        @error('payment_method') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Reference (optional)') }}
                        </label>
                        <flux:input type="text" wire:model.live="reference" />
                        @error('reference') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            {{ __('Paid at') }}
                        </label>
                        <flux:input type="datetime-local" wire:model.live="paid_at" />
                        @error('paid_at') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Member') }}</p>
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">
                            {{ $selectedMember ? $selectedMember->first_name . ' ' . $selectedMember->last_name : __('Select member') }}
                        </p>
                    </div>
                </div>
                <div class="mt-4 border-t border-dashed border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Package') }}</p>
                    <p class="text-base font-semibold text-zinc-900 dark:text-white">
                        {{ $selectedPackageModel?->name ?? __('Select package') }}
                    </p>
                    @if($selectedPackageModel)
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ $selectedPackageModel->formatted_duration }}
                        </p>
                    @endif
                </div>
                <div class="mt-4 border-t border-dashed border-zinc-200 pt-4 dark:border-zinc-700">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Billing summary') }}</p>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ $amount !== '' ? money((float) $amount, $currency) : money(0, $currency) }}
                    </p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Period') }}: {{ $start_date }} → {{ $end_date ?: '—' }}
                    </p>
                </div>
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    @if($is_renewal)
                        {{ __('You are renewing an existing subscription. The new cycle will begin after the current one ends.') }}
                    @else
                        {{ __('Review the details above and save to start the subscription immediately.') }}
                    @endif
                </p>
                <div class="mt-6 flex flex-col gap-2">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled" icon="check">
                        {{ $is_renewal ? __('Renew Subscription') : __('Create Subscription') }}
                    </flux:button>
                    <flux:button
                        variant="ghost"
                        href="{{ route('subscriptions.index') }}"
                        wire:navigate
                    >
                        {{ __('Cancel') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>

