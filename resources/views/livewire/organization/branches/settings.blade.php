<div>
    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif

    {{-- Page Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <flux:button variant="ghost" icon="arrow-left" href="{{ route('organization.branches.show', $branch) }}" wire:navigate />
            <div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Branch Settings') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $branch->name }} ({{ $branch->code }})
                </p>
            </div>
        </div>
        <flux:badge :color="$status === 'active' ? 'emerald' : 'zinc'" size="lg">
            {{ ucfirst($status) }}
        </flux:badge>
    </div>

    <form wire:submit="save">
        <div class="space-y-6">
            {{-- Basic Information --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Basic Information') }}</h2>
                </div>
                <div class="p-6">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="name" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Branch Name') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input
                                id="name"
                                wire:model="name"
                                type="text"
                                placeholder="{{ __('e.g., Downtown Gym') }}"
                            />
                            @error('name') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="code" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Branch Code') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input
                                id="code"
                                wire:model="code"
                                type="text"
                                placeholder="{{ __('e.g., DTN') }}"
                                :disabled="!$this->canChangeCode"
                                class="{{ !$this->canChangeCode ? 'opacity-50' : '' }}"
                            />
                            @error('code') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                            @if(!$this->canChangeCode)
                                <p class="mt-1 text-xs text-amber-600 dark:text-amber-400">
                                    {{ __('Branch code cannot be changed.') }}
                                </p>
                            @endif
                        </div>

                        <div class="sm:col-span-2">
                            <label for="address" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Address') }}
                            </label>
                            <flux:input
                                id="address"
                                wire:model="address"
                                type="text"
                                placeholder="{{ __('Street address') }}"
                            />
                            @error('address') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="city" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('City') }}
                            </label>
                            <flux:input
                                id="city"
                                wire:model="city"
                                type="text"
                                placeholder="{{ __('e.g., Dar es Salaam') }}"
                            />
                            @error('city') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="country" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Country') }}
                            </label>
                            <flux:input
                                id="country"
                                wire:model="country"
                                type="text"
                                placeholder="{{ __('e.g., Tanzania') }}"
                            />
                            @error('country') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="phone" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Phone') }}
                            </label>
                            <flux:input
                                id="phone"
                                wire:model="phone"
                                type="tel"
                                placeholder="{{ __('e.g., +255 123 456 789') }}"
                            />
                            @error('phone') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Email') }}
                            </label>
                            <flux:input
                                id="email"
                                wire:model="email"
                                type="email"
                                placeholder="{{ __('e.g., downtown@gym.com') }}"
                            />
                            @error('email') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Branch Settings --}}
            <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Configuration') }}</h2>
                </div>
                <div class="p-6">
                    <div class="grid gap-6 sm:grid-cols-2">
                        <div>
                            <label for="currency" class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-200">
                                {{ __('Currency') }} <span class="text-red-500">*</span>
                            </label>
                            <flux:input
                                id="currency"
                                wire:model="currency"
                                type="text"
                                maxlength="3"
                                placeholder="{{ __('TZS') }}"
                                class="uppercase"
                            />
                            @error('currency') <p class="mt-1 text-sm text-red-500">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-6">
                        <h3 class="mb-4 text-sm font-medium text-zinc-700 dark:text-zinc-200">{{ __('Enabled Modules') }}</h3>
                        <div class="space-y-3">
                            <label class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Point of Sale (POS)') }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable product sales and inventory management.') }}</p>
                                </div>
                                <input type="checkbox" wire:model="module_pos_enabled" class="h-5 w-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            </label>

                            <label class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Classes & Bookings') }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable class scheduling and member bookings.') }}</p>
                                </div>
                                <input type="checkbox" wire:model="module_classes_enabled" class="h-5 w-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            </label>

                            <label class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Insurance Integration') }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable insurer management and member policies.') }}</p>
                                </div>
                                <input type="checkbox" wire:model="module_insurance_enabled" class="h-5 w-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            </label>

                            <label class="flex items-center justify-between rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium text-zinc-900 dark:text-white">{{ __('Access Control') }}</p>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enable device integration and attendance tracking.') }}</p>
                                </div>
                                <input type="checkbox" wire:model="module_access_control_enabled" class="h-5 w-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Status Management --}}
            @if($this->canManageStatus)
                <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                        <h2 class="font-semibold text-zinc-900 dark:text-white">{{ __('Branch Status') }}</h2>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="font-medium text-zinc-900 dark:text-white">
                                    {{ $status === 'active' ? __('Branch is Active') : __('Branch is Inactive') }}
                                </p>
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                                    @if($status === 'active')
                                        {{ __('Users can switch to this branch and it appears in listings.') }}
                                    @else
                                        {{ __('Users cannot switch to this branch. It may still have data.') }}
                                    @endif
                                </p>
                            </div>
                            @if($status === 'active')
                                <flux:button type="button" variant="danger" icon="pause" wire:click="confirmDeactivate">
                                    {{ __('Deactivate') }}
                                </flux:button>
                            @else
                                <flux:button type="button" variant="primary" icon="play" wire:click="confirmActivate">
                                    {{ __('Activate') }}
                                </flux:button>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3">
                <flux:button type="button" variant="ghost" href="{{ route('organization.branches.show', $branch) }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button type="submit" variant="primary" icon="check" wire:loading.attr="disabled">
                    {{ __('Save Settings') }}
                </flux:button>
            </div>
        </div>
    </form>

    {{-- Deactivate Modal --}}
    <flux:modal wire:model="show_deactivate_modal" class="max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/50">
                    <flux:icon name="exclamation-triangle" class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Deactivate Branch') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Are you sure you want to deactivate this branch? Users will not be able to switch to it.') }}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="danger" wire:click="deactivateBranch">
                    {{ __('Deactivate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Activate Modal --}}
    <flux:modal wire:model="show_activate_modal" class="max-w-md">
        <div class="p-6">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/50">
                    <flux:icon name="check-circle" class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                </div>
                <div>
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Activate Branch') }}</h3>
                    <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('Are you sure you want to activate this branch? Users will be able to switch to it.') }}
                    </p>
                </div>
            </div>
            <div class="mt-6 flex justify-end gap-3">
                <flux:button variant="ghost" wire:click="cancelModal">
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" wire:click="activateBranch">
                    {{ __('Activate') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>

