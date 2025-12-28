<?php

namespace App\Livewire\Users;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?User $user = null;

    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';
    public ?int $branch_id = null;
    public array $selected_roles = [];

    public bool $is_editing = false;

    public function mount(?User $user = null): void
    {
        if ($user && $user->exists) {
            $this->user = $user;
            $this->is_editing = true;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->branch_id = $user->branch_id;
            $this->selected_roles = $user->roles->pluck('name')->toArray();

            $this->authorize('update', $user);
        } else {
            $this->authorize('create', User::class);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user?->id),
            ],
            'phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'selected_roles' => ['required', 'array', 'min:1'],
            'selected_roles.*' => ['exists:roles,name'],
        ];

        if (!$this->is_editing) {
            $rules['password'] = ['required', 'confirmed', Password::defaults()];
        } else {
            $rules['password'] = ['nullable', 'confirmed', Password::defaults()];
        }

        return $rules;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->is_editing) {
            $this->updateUser($validated);
        } else {
            $this->createUser($validated);
        }
    }

    protected function createUser(array $validated): void
    {
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'password' => Hash::make($validated['password']),
            'branch_id' => $validated['branch_id'],
            'email_verified_at' => now(),
        ]);

        $user->syncRoles($validated['selected_roles']);

        session()->flash('success', __('User created successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }

    protected function updateUser(array $validated): void
    {
        $this->user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?: null,
            'branch_id' => $validated['branch_id'],
        ]);

        if (!empty($validated['password'])) {
            $this->user->update([
                'password' => Hash::make($validated['password']),
            ]);
        }

        $this->user->syncRoles($validated['selected_roles']);

        session()->flash('success', __('User updated successfully.'));

        $this->redirect(route('users.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.users.form', [
            'roles' => Role::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
