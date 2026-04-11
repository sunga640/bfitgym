<div>
    @if($needs_branch_selection)
        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-900/20">
                <flux:icon name="building-office" class="mx-auto h-12 w-12 text-amber-500" />
                <h3 class="mt-4 text-lg font-semibold text-amber-800 dark:text-amber-200">
                    {{ __('Branch Selection Required') }}
                </h3>
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    {{ __('Please select a branch from the sidebar before creating an event.') }}
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <flux:button variant="ghost" href="{{ route('events.index') }}" wire:navigate>
                        {{ __('Go Back') }}
                    </flux:button>
                    <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                        {{ __('Go to Dashboard') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="save" class="mx-auto max-w-4xl">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $is_editing ? __('Edit Event') : __('Create Event') }}
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Set schedule, capacity, and payment requirements for this event.') }}
                    </p>
                </div>

                <div class="space-y-6 p-6">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field class="sm:col-span-2">
                            <flux:label required>{{ __('Event Title') }}</flux:label>
                            <flux:input wire:model="title" type="text" maxlength="200" required />
                            <flux:error name="title" />
                        </flux:field>

                        <flux:field>
                            <flux:label required>{{ __('Type') }}</flux:label>
                            <flux:select wire:model="type" required>
                                <option value="public">{{ __('Public') }}</option>
                                <option value="paid">{{ __('Paid') }}</option>
                                <option value="internal">{{ __('Internal') }}</option>
                            </flux:select>
                            <flux:error name="type" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Location') }}</flux:label>
                            <flux:input wire:model="location" type="text" maxlength="255" />
                            <flux:error name="location" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model="description" rows="4" maxlength="4000" />
                        <flux:error name="description" />
                    </flux:field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label required>{{ __('Start Date') }}</flux:label>
                            <flux:input wire:model="start_date" type="date" required />
                            <flux:error name="start_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label required>{{ __('Start Time') }}</flux:label>
                            <flux:input wire:model="start_time" type="time" required />
                            <flux:error name="start_time" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('End Date') }}</flux:label>
                            <flux:input wire:model="end_date" type="date" />
                            <flux:error name="end_date" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('End Time') }}</flux:label>
                            <flux:input wire:model="end_time" type="time" />
                            <flux:error name="end_time" />
                        </flux:field>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <div class="flex items-start gap-3">
                                <flux:checkbox wire:model.live="payment_required" id="payment_required" />
                                <div>
                                    <flux:label for="payment_required" class="cursor-pointer">
                                        {{ __('Payment Required') }}
                                    </flux:label>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('Require registrants to pay before confirmation.') }}
                                    </p>
                                </div>
                            </div>
                            <flux:error name="payment_required" />
                        </flux:field>

                        <flux:field>
                            <flux:label :required="$payment_required">{{ __('Price') }}</flux:label>
                            <flux:input
                                wire:key="event-price-{{ $payment_required ? 'enabled' : 'disabled' }}"
                                wire:model="price"
                                type="number"
                                step="0.01"
                                min="0.01"
                                placeholder="0.00"
                                :disabled="!$payment_required"
                                :required="$payment_required"
                            />
                            <flux:error name="price" />
                            <flux:description>
                                {{ $payment_required ? __('Price is required when payment is enabled.') : __('Enable payment to set a price.') }}
                            </flux:description>
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Capacity') }}</flux:label>
                            <flux:input wire:model="capacity" type="number" min="1" placeholder="{{ __('Unlimited') }}" />
                            <flux:error name="capacity" />
                        </flux:field>

                        <flux:field>
                            <div class="flex items-start gap-3">
                                <flux:checkbox wire:model="allow_non_members" id="allow_non_members" />
                                <div>
                                    <flux:label for="allow_non_members" class="cursor-pointer">
                                        {{ __('Allow Non-members') }}
                                    </flux:label>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ __('Visitors can register using the public event page.') }}
                                    </p>
                                </div>
                            </div>
                            <flux:error name="allow_non_members" />
                        </flux:field>
                    </div>

                    <flux:field>
                        <flux:label required>{{ __('Status') }}</flux:label>
                        <flux:radio.group wire:model="status">
                            <flux:radio value="scheduled">
                                <flux:label>{{ __('Scheduled') }}</flux:label>
                            </flux:radio>
                            <flux:radio value="completed">
                                <flux:label>{{ __('Completed') }}</flux:label>
                            </flux:radio>
                            <flux:radio value="cancelled">
                                <flux:label>{{ __('Cancelled') }}</flux:label>
                            </flux:radio>
                        </flux:radio.group>
                        <flux:error name="status" />
                    </flux:field>
                </div>

                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:button variant="ghost" href="{{ route('events.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                        {{ $is_editing ? __('Update Event') : __('Save Event') }}
                    </flux:button>
                </div>
            </div>
        </form>
    @endif
</div>
