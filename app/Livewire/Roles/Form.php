<?php

namespace App\Livewire\Roles;

use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Form extends Component
{
    public ?Role $role = null;

    public string $name = '';
    public array $selected_permissions = [];

    public bool $is_editing = false;
    public bool $is_system_role = false;

    public function mount(?Role $role = null): void
    {
        if ($role && $role->exists) {
            $this->role = $role;
            $this->is_editing = true;
            $this->name = $role->name;
            $this->selected_permissions = $role->permissions->pluck('name')->toArray();

            // Check if it's a system role
            $this->is_system_role = in_array($role->name, ['super-admin', 'branch-admin']);
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:125',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->ignore($this->role?->id),
            ],
            'selected_permissions' => ['required', 'array', 'min:1'],
            'selected_permissions.*' => ['exists:permissions,name'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('Role name must be lowercase and may only contain letters, numbers, and dashes.'),
        ];
    }

    public function save(): void
    {
        // Prevent editing system role names
        if ($this->is_system_role && $this->is_editing) {
            $validated = $this->validate([
                'selected_permissions' => ['required', 'array', 'min:1'],
                'selected_permissions.*' => ['exists:permissions,name'],
            ]);
        } else {
            $validated = $this->validate();
        }

        if ($this->is_editing) {
            $this->updateRole($validated);
        } else {
            $this->createRole($validated);
        }
    }

    protected function createRole(array $validated): void
    {
        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web',
        ]);

        $role->syncPermissions($validated['selected_permissions']);

        session()->flash('success', __('Role created successfully.'));

        $this->redirect(route('roles.index'), navigate: true);
    }

    protected function updateRole(array $validated): void
    {
        if (!$this->is_system_role) {
            $this->role->update([
                'name' => $validated['name'],
            ]);
        }

        $this->role->syncPermissions($validated['selected_permissions']);

        session()->flash('success', __('Role updated successfully.'));

        $this->redirect(route('roles.index'), navigate: true);
    }

    public function selectAll(): void
    {
        $this->selected_permissions = Permission::pluck('name')->toArray();
    }

    public function deselectAll(): void
    {
        $this->selected_permissions = [];
    }

    public function selectGroup(string $group): void
    {
        $group_permissions = $this->getGroupedPermissions()[$group] ?? [];
        $this->selected_permissions = array_unique(
            array_merge($this->selected_permissions, $group_permissions->pluck('name')->toArray())
        );
    }

    public function deselectGroup(string $group): void
    {
        $group_permissions = $this->getGroupedPermissions()[$group] ?? [];
        $this->selected_permissions = array_diff(
            $this->selected_permissions,
            $group_permissions->pluck('name')->toArray()
        );
    }

    protected function getGroupedPermissions()
    {
        return Permission::all()->groupBy(function ($permission) {
            $parts = explode(' ', $permission->name);
            return end($parts);
        });
    }

    public function render(): View
    {
        $permissions = Permission::all()->groupBy(function ($permission) {
            // Group by the last word (e.g., "view members" -> "members")
            $parts = explode(' ', $permission->name);
            return end($parts);
        })->sortKeys();

        return view('livewire.roles.form', [
            'grouped_permissions' => $permissions,
        ]);
    }
}
