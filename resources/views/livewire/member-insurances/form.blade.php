<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
    <div class="flex items-center gap-4">
        <flux:button href="{{ route('member-insurances.index') }}" wire:navigate variant="ghost" icon="arrow-left" size="sm" />
        <div>
            <flux:heading size="xl">
                {{ $isEditing ? __('Edit Policy') : __('Add Policy') }}
            </flux:heading>
            <flux:subheading>
                {{ __('Manage insurance coverage for a member.') }}
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
                    <flux:label>{{ __('Member') }} *</flux:label>
                    <flux:select wire:model="member_id" required>
                        <option value="">{{ __('Select member...') }}</option>
                        @foreach($members as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name ?? ($member->first_name . ' ' . $member->last_name) }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="member_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Insurer') }} *</flux:label>
                    <flux:select wire:model="insurer_id" required>
                        <option value="">{{ __('Select insurer...') }}</option>
                        @foreach($insurers as $insurer)
                            <option value="{{ $insurer->id }}">{{ $insurer->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="insurer_id" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Policy Number') }} *</flux:label>
                    <flux:input wire:model="policy_number" required />
                    <flux:error name="policy_number" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Coverage Type') }}</flux:label>
                    <flux:input wire:model="coverage_type" placeholder="{{ __('e.g., Outpatient, Inpatient') }}" />
                    <flux:error name="coverage_type" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Start Date') }} *</flux:label>
                    <flux:input type="date" wire:model="start_date" required />
                    <flux:error name="start_date" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('End Date') }} *</flux:label>
                    <flux:input type="date" wire:model="end_date" required />
                    <flux:error name="end_date" />
                </flux:field>

                <flux:field>
                    <flux:label>{{ __('Status') }} *</flux:label>
                    <flux:select wire:model="status">
                        <option value="active">{{ __('Active') }}</option>
                        <option value="inactive">{{ __('Inactive') }}</option>
                        <option value="expired">{{ __('Expired') }}</option>
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('Notes') }}</flux:label>
                    <flux:textarea wire:model="notes" rows="3" />
                    <flux:error name="notes" />
                </flux:field>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3">
            <flux:button href="{{ route('member-insurances.index') }}" wire:navigate variant="ghost">
                {{ __('Cancel') }}
            </flux:button>
            <flux:button type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="save">{{ $isEditing ? __('Update Policy') : __('Create Policy') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

