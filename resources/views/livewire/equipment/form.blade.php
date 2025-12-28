<div>
    <form wire:submit.prevent="save" class="mx-auto max-w-2xl space-y-8">
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

        {{-- Basic Information --}}
        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="border-b border-zinc-200 pb-4 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">{{ __('Equipment Information') }}</h2>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Enter the basic details for this equipment.') }}</p>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Name') }} <span class="text-red-500">*</span></flux:label>
                        <flux:input type="text" wire:model.live="name" placeholder="{{ __('e.g., Treadmill, Dumbbell Set, Rowing Machine') }}" required />
                        @error('name')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Description') }}</flux:label>
                        <flux:textarea wire:model.live="description" rows="3" placeholder="{{ __('Describe the equipment, its specifications, and any relevant details...') }}" />
                        @error('description')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Type') }}</flux:label>
                        <flux:input type="text" wire:model.live="type" placeholder="{{ __('e.g., Cardio, Strength, Free Weights') }}" list="equipment-types" />
                        <datalist id="equipment-types">
                            @foreach($existing_types as $existing_type)
                                <option value="{{ $existing_type }}">
                            @endforeach
                        </datalist>
                        @error('type')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div>
                    <flux:field>
                        <flux:label>{{ __('Brand') }}</flux:label>
                        <flux:input type="text" wire:model.live="brand" placeholder="{{ __('e.g., Technogym, Life Fitness') }}" />
                        @error('brand')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>

                <div class="sm:col-span-2">
                    <flux:field>
                        <flux:label>{{ __('Model') }}</flux:label>
                        <flux:input type="text" wire:model.live="model" placeholder="{{ __('e.g., Pro 9000, Elite Series') }}" />
                        @error('model')
                            <div class="mt-1 text-xs text-red-500">{{ $message }}</div>
                        @enderror
                    </flux:field>
                </div>
            </div>
        </div>

        {{-- Form Actions --}}
        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" href="{{ route('equipment.index') }}" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                <span wire:loading.remove>{{ $is_editing ? __('Update Equipment') : __('Create Equipment') }}</span>
                <span wire:loading>{{ __('Saving...') }}</span>
            </flux:button>
        </div>
    </form>
</div>

