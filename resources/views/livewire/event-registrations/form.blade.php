<div>
    <form wire:submit="save" class="mx-auto max-w-3xl">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Create Event Registration') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Register a member or visitor for an event.') }}</p>
            </div>

            <div class="space-y-6 p-6">
                <flux:field>
                    <flux:label required>{{ __('Event') }}</flux:label>
                    <flux:select wire:model="event_id" required>
                        <option value="">{{ __('Select Event') }}</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->title }} ({{ $event->start_datetime?->format('M d, Y g:i A') }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="event_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Member (Optional)') }}</flux:label>
                    <flux:select wire:model.live="member_id">
                        <option value="">{{ __('Visitor / Non-member') }}</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name }} ({{ $member->member_no }})</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="member_id" />
                </flux:field>

                @if(!$member_id)
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label>{{ __('Full Name') }}</flux:label>
                            <flux:input wire:model="full_name" type="text" maxlength="150" />
                            <flux:error name="full_name" />
                        </flux:field>

                        <flux:field>
                            <flux:label>{{ __('Phone') }}</flux:label>
                            <flux:input wire:model="phone" type="text" maxlength="50" />
                            <flux:error name="phone" />
                        </flux:field>

                        <flux:field class="sm:col-span-2">
                            <flux:label>{{ __('Email') }}</flux:label>
                            <flux:input wire:model="email" type="email" maxlength="100" />
                            <flux:error name="email" />
                        </flux:field>
                    </div>
                @endif

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <div class="flex items-start gap-3">
                            <flux:checkbox wire:model="will_attend" id="will_attend" />
                            <div>
                                <flux:label for="will_attend" class="cursor-pointer">{{ __('Will Attend') }}</flux:label>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Uncheck to register as not attending.') }}</p>
                            </div>
                        </div>
                        <flux:error name="will_attend" />
                    </flux:field>

                    <flux:field>
                        <flux:label required>{{ __('Status') }}</flux:label>
                        <flux:select wire:model="status" required>
                            <option value="pending">{{ __('Pending') }}</option>
                            <option value="confirmed">{{ __('Confirmed') }}</option>
                            <option value="cancelled">{{ __('Cancelled') }}</option>
                            <option value="attended">{{ __('Attended') }}</option>
                            <option value="no_show">{{ __('No Show') }}</option>
                        </flux:select>
                        <flux:error name="status" />
                    </flux:field>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:button variant="ghost" href="{{ route('event-registrations.index') }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    {{ __('Create Registration') }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
