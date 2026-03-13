<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route($route_prefix . '.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
        <div>
            <flux:heading size="xl">
                {{ $isEditing ? __('Edit :integration Identity', ['integration' => $integration_label]) : __('Add :integration Identity', ['integration' => $integration_label]) }}
            </flux:heading>
            <flux:subheading>
                {{ __('Link a member or staff to a device user ID and card number.') }}
            </flux:subheading>
        </div>
    </div>

    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="space-y-6">
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="grid gap-6 sm:grid-cols-2">
                <flux:field>
                    <flux:label>{{ __('Subject Type') }} *</flux:label>
                    <flux:select wire:model="subject_type">
                        <option value="member">{{ __('Member') }}</option>
                        <option value="staff">{{ __('Staff') }}</option>
                    </flux:select>
                    <flux:error name="subject_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Select Subject') }} *</flux:label>
                    @if($subject_type === 'member')
                        <flux:select wire:model="subject_id">
                            <option value="">{{ __('Choose member...') }}</option>
                            @foreach($members as $member)
                                <option value="{{ $member->id }}">{{ $member->full_name ?? ($member->first_name . ' ' . $member->last_name) }}</option>
                            @endforeach
                        </flux:select>
                    @else
                        <flux:select wire:model="subject_id">
                            <option value="">{{ __('Choose staff...') }}</option>
                            @foreach($staff as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </flux:select>
                    @endif
                    <flux:error name="subject_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Device User ID') }} *</flux:label>
                    <flux:input
                        wire:model="device_user_id"
                        placeholder="{{ __('EmployeeNo / device user code') }}"
                        required
                    />
                    <flux:error name="device_user_id" />
                    <flux:description>{{ __('Must match the EmployeeNo configured on the device.') }}</flux:description>
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Card Number') }}</flux:label>
                    <flux:input
                        wire:model="card_number"
                        placeholder="{{ __('Optional card/RFID number') }}"
                    />
                    <flux:error name="card_number" />
                </flux:field>

                <flux:field>
                    <flux:checkbox wire:model="is_active">
                        <flux:label>{{ __('Active') }}</flux:label>
                        <flux:description>{{ __('Inactive identities will be ignored during log ingestion.') }}</flux:description>
                    </flux:checkbox>
                </flux:field>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route($route_prefix . '.index') }}" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEditing ? __('Update Identity') : __('Create Identity') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>
