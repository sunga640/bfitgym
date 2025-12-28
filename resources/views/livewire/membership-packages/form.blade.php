<div>
    @if($needs_branch_selection)
        {{-- Branch Selection Required --}}
        <div class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-8 text-center dark:border-amber-800 dark:bg-amber-900/20">
                <flux:icon name="building-office" class="mx-auto h-12 w-12 text-amber-500" />
                <h3 class="mt-4 text-lg font-semibold text-amber-800 dark:text-amber-200">
                    {{ __('Branch Selection Required') }}
                </h3>
                <p class="mt-2 text-sm text-amber-700 dark:text-amber-300">
                    {{ __('Please select a branch from the sidebar before creating a membership package.') }}
                </p>
                <div class="mt-6 flex items-center justify-center gap-3">
                    <flux:button variant="ghost" href="{{ route('membership-packages.index') }}" wire:navigate>
                        {{ __('Go Back') }}
                    </flux:button>
                    <flux:button variant="primary" href="{{ route('dashboard') }}" wire:navigate>
                        {{ __('Go to Dashboard') }}
                    </flux:button>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="save" class="mx-auto max-w-2xl">
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $is_editing ? __('Edit Membership Package') : __('Create Membership Package') }}
                    </h2>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ $is_editing ? __('Update the package details below.') : __('Define a new membership package with pricing and duration.') }}
                    </p>
                </div>

                <div class="space-y-6 p-6">
                    {{-- Package Name --}}
                    <flux:field>
                        <flux:label required>{{ __('Package Name') }}</flux:label>
                        <flux:input
                            wire:model="name"
                            type="text"
                            placeholder="{{ __('e.g., Monthly Standard, Annual Premium') }}"
                            required
                        />
                        <flux:error name="name" />
                        <flux:description>{{ __('A unique name to identify this package.') }}</flux:description>
                    </flux:field>

                    {{-- Description --}}
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea
                            wire:model="description"
                            placeholder="{{ __('Describe what this package includes...') }}"
                            rows="3"
                        />
                        <flux:error name="description" />
                    </flux:field>

                    {{-- Price --}}
                    <flux:field>
                        <flux:label required>{{ __('Price') }}</flux:label>
                        <div class="flex items-center gap-2">
                            <flux:input
                                wire:model="price"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                                required
                                class="flex-1"
                            />
                            <span class="text-sm font-medium text-zinc-500 dark:text-zinc-400">
                                {{ current_branch()?->currency ?? 'TZS' }}
                            </span>
                        </div>
                        <flux:error name="price" />
                    </flux:field>

                    {{-- Duration --}}
                    <div class="grid gap-4 sm:grid-cols-2">
                        <flux:field>
                            <flux:label required>{{ __('Duration Value') }}</flux:label>
                            <flux:input
                                wire:model="duration_value"
                                type="number"
                                min="1"
                                max="365"
                                placeholder="1"
                                required
                            />
                            <flux:error name="duration_value" />
                        </flux:field>

                        <flux:field>
                            <flux:label required>{{ __('Duration Type') }}</flux:label>
                            <flux:select wire:model="duration_type" required>
                                @foreach($duration_types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </flux:select>
                            <flux:error name="duration_type" />
                        </flux:field>
                    </div>

                    {{-- Renewable Checkbox --}}
                    <flux:field>
                        <div class="flex items-start gap-3">
                            <flux:checkbox
                                wire:model="is_renewable"
                                id="is_renewable"
                            />
                            <div>
                                <flux:label for="is_renewable" class="cursor-pointer">
                                    {{ __('Allow Renewals') }}
                                </flux:label>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('Members can renew this package when it expires.') }}
                                </p>
                            </div>
                        </div>
                    </flux:field>

                    {{-- Status --}}
                    <flux:field>
                        <flux:label required>{{ __('Status') }}</flux:label>
                        <flux:radio.group wire:model="status">
                            <flux:radio value="active">
                                <flux:label>{{ __('Active') }}</flux:label>
                                <flux:description>{{ __('Package is available for new subscriptions.') }}</flux:description>
                            </flux:radio>
                            <flux:radio value="inactive">
                                <flux:label>{{ __('Inactive') }}</flux:label>
                                <flux:description>{{ __('Package is hidden and cannot be assigned.') }}</flux:description>
                            </flux:radio>
                        </flux:radio.group>
                        <flux:error name="status" />
                    </flux:field>
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:button variant="ghost" href="{{ route('membership-packages.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>
                            {{ $is_editing ? __('Update Package') : __('Create Package') }}
                        </span>
                        <span wire:loading>
                            {{ __('Saving...') }}
                        </span>
                    </flux:button>
                </div>
            </div>
        </form>

        {{-- Preview Card --}}
        @if($name || $price)
            <div class="mx-auto mt-8 max-w-2xl">
                <h3 class="mb-3 text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Preview') }}</h3>
                <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $name ?: __('Package Name') }}
                            </h4>
                            @if($description)
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ $description }}
                                </p>
                            @endif
                        </div>
                        <flux:badge :color="$status === 'active' ? 'green' : 'zinc'" size="sm">
                            {{ ucfirst($status) }}
                        </flux:badge>
                    </div>

                    <div class="mt-4">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ $price ? number_format((float)$price, 0) : '0' }}
                        </span>
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            {{ current_branch()?->currency ?? 'TZS' }}
                        </span>
                    </div>

                    <div class="mt-3 flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
                        <flux:icon name="calendar" class="h-4 w-4 text-zinc-400" />
                        <span>{{ $duration_value }} {{ $duration_types[$duration_type] ?? $duration_type }}</span>
                    </div>

                    @if($is_renewable)
                        <div class="mt-3">
                            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                                <flux:icon name="arrow-path" class="h-3 w-3" />
                                {{ __('Renewable') }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endif
</div>
