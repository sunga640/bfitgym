<?php

namespace App\Livewire\Roles;

use Illuminate\Contracts\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    public string $search = '';

    public function deleteRole(int $role_id): void
    {
        $role = Role::findOrFail($role_id);

        // Prevent deletion of system roles
        if (in_array($role->name, ['super-admin', 'branch-admin'])) {
            session()->flash('error', __('System roles cannot be deleted.'));
            return;
        }

        // Check if role has users
        if ($role->users()->count() > 0) {
            session()->flash('error', __('Cannot delete role with assigned users. Remove users first.'));
            return;
        }

        $role->delete();

        session()->flash('success', __('Role deleted successfully.'));
    }

    public function render(): View
    {
        $roles = Role::query()
            ->withCount(['users', 'permissions'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })
            ->orderBy('name')
            ->get();

        return view('livewire.roles.index', [
            'roles' => $roles,
        ]);
    }
}
