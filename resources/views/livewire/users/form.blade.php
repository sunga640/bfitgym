<div>
    <form wire:submit="save" class="mx-auto max-w-2xl">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $is_editing ? __('Edit User') : __('Create User') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $is_editing ? __('Update user information and roles.') : __('Add a new staff member to the system.') }}
                </p>
            </div>

            <div class="space-y-6 p-6">
                {{-- Name --}}
                <flux:field>
                    <flux:label required>{{ __('Name') }}</flux:label>
                    <flux:input wire:model="name" type="text" required />
                    <flux:error name="name" />
                </flux:field>

                {{-- Email --}}
                <flux:field>
                    <flux:label required>{{ __('Email') }}</flux:label>
                    <flux:input wire:model="email" type="email" required />
                    <flux:error name="email" />
                </flux:field>

                {{-- Phone --}}
                <flux:field>
                    <flux:label>{{ __('Phone') }}</flux:label>
                    <flux:input wire:model="phone" type="tel" placeholder="+255" />
                    <flux:error name="phone" />
                </flux:field>

                {{-- Password --}}
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label :required="!$is_editing">{{ __('Password') }}</flux:label>
                        <flux:input wire:model="password" type="password" :required="!$is_editing" />
                        <flux:error name="password" />
                        @if($is_editing)
                            <flux:description>{{ __('Leave blank to keep current password.') }}</flux:description>
                        @endif
                    </flux:field>

                    <flux:field>
                        <flux:label :required="!$is_editing">{{ __('Confirm Password') }}</flux:label>
                        <flux:input wire:model="password_confirmation" type="password" :required="!$is_editing" />
                    </flux:field>
                </div>

                {{-- Branch --}}
                <flux:field>
                    <flux:label>{{ __('Branch') }}</flux:label>
                    <flux:select wire:model="branch_id">
                        <option value="">{{ __('All Branches (HQ/Super Admin)') }}</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="branch_id" />
                    <flux:description>{{ __('Select the branch this user belongs to. Leave empty for users who need access to all branches.') }}</flux:description>
                </flux:field>

                {{-- Roles --}}
                <flux:field>
                    <flux:label required>{{ __('Roles') }}</flux:label>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($roles as $role)
                            <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-zinc-200 p-3 transition hover:border-zinc-300 dark:border-zinc-700 dark:hover:border-zinc-600 {{ in_array($role->name, $selected_roles) ? 'border-blue-500 bg-blue-50 dark:border-blue-500 dark:bg-blue-900/20' : '' }}">
                                <input
                                    type="checkbox"
                                    wire:model="selected_roles"
                                    value="{{ $role->name }}"
                                    class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"
                                >
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-white">
                                        {{ ucwords(str_replace('-', ' ', $role->name)) }}
                                    </div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $role->permissions->count() }} {{ __('permissions') }}
                                    </div>
                                </div>
                            </label>
                        @endforeach
                    </div>
                    <flux:error name="selected_roles" />
                </flux:field>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <flux:button variant="ghost" href="{{ route('users.index') }}" wire:navigate>
                    {{ __('Cancel') }}
                </flux:button>
                <flux:button variant="primary" type="submit">
                    {{ $is_editing ? __('Update User') : __('Create User') }}
                </flux:button>
            </div>
        </div>
    </form>
</div>

