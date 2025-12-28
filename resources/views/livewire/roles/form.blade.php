<div>
    <form wire:submit="save" class="mx-auto max-w-4xl">
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $is_editing ? __('Edit Role') : __('Create Role') }}
                </h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                    {{ $is_editing ? __('Update role permissions.') : __('Create a new role with specific permissions.') }}
                </p>
            </div>

            <div class="space-y-6 p-6">
                {{-- Role Name --}}
                <flux:field>
                    <flux:label required>{{ __('Role Name') }}</flux:label>
                    <flux:input
                        wire:model="name"
                        type="text"
                        required
                        :disabled="$is_system_role"
                        placeholder="e.g., manager, trainer, receptionist"
                    />
                    <flux:error name="name" />
                    <flux:description>{{ __('Use lowercase letters, numbers, and dashes only (e.g., branch-manager).') }}</flux:description>
                    @if($is_system_role)
                        <div class="mt-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
                            {{ __('System role names cannot be changed.') }}
                        </div>
                    @endif
                </flux:field>

                {{-- Permissions --}}
                <div>
                    <div class="flex items-center justify-between">
                        <flux:label required>{{ __('Permissions') }}</flux:label>
                        <div class="flex items-center gap-2">
                            <flux:button variant="ghost" size="sm" wire:click="selectAll" type="button">
                                {{ __('Select All') }}
                            </flux:button>
                            <flux:button variant="ghost" size="sm" wire:click="deselectAll" type="button">
                                {{ __('Deselect All') }}
                            </flux:button>
                        </div>
                    </div>
                    <flux:error name="selected_permissions" />

                    <div class="mt-4 space-y-6">
                        @foreach($grouped_permissions as $group => $permissions)
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <div class="flex items-center justify-between border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-900/50">
                                    <h4 class="font-medium capitalize text-zinc-900 dark:text-white">
                                        {{ $group }}
                                    </h4>
                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            wire:click="selectGroup('{{ $group }}')"
                                            class="text-xs text-blue-600 hover:text-blue-700 dark:text-blue-400"
                                        >
                                            {{ __('Select All') }}
                                        </button>
                                        <span class="text-zinc-300 dark:text-zinc-600">|</span>
                                        <button
                                            type="button"
                                            wire:click="deselectGroup('{{ $group }}')"
                                            class="text-xs text-zinc-500 hover:text-zinc-700 dark:text-zinc-400"
                                        >
                                            {{ __('Clear') }}
                                        </button>
                                    </div>
                                </div>
                                <div class="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-3">
                                    @foreach($permissions as $permission)
                                        <label class="flex cursor-pointer items-center gap-2 rounded-lg p-2 transition hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                            <input
                                                type="checkbox"
                                                wire:model="selected_permissions"
                                                value="{{ $permission->name }}"
                                                class="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-blue-500 dark:border-zinc-600 dark:bg-zinc-700"
                                            >
                                            <span class="text-sm text-zinc-700 dark:text-zinc-300">
                                                {{ ucfirst(str_replace(' ' . $group, '', $permission->name)) }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Form Actions --}}
            <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                <div class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ trans_choice(':count permission selected|:count permissions selected', count($selected_permissions)) }}
                </div>
                <div class="flex items-center gap-3">
                    <flux:button variant="ghost" href="{{ route('roles.index') }}" wire:navigate>
                        {{ __('Cancel') }}
                    </flux:button>
                    <flux:button variant="primary" type="submit">
                        {{ $is_editing ? __('Update Role') : __('Create Role') }}
                    </flux:button>
                </div>
            </div>
        </div>
    </form>
</div>

