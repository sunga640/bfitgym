<?php

namespace App\Livewire\Users;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role_filter = '';

    #[Url]
    public string $branch_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function deleteUser(int $user_id): void
    {
        $user = User::findOrFail($user_id);

        $this->authorize('delete', $user);

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            session()->flash('error', __('You cannot delete your own account.'));
            return;
        }

        $user->delete();

        session()->flash('success', __('User deleted successfully.'));
    }

    public function render(): View
    {
        $users = User::query()
            ->with(['branch', 'roles'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%");
                });
            })
            ->when($this->role_filter, function ($query) {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', $this->role_filter);
                });
            })
            ->when($this->branch_filter, function ($query) {
                $query->where('branch_id', $this->branch_filter);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.users.index', [
            'users' => $users,
            'roles' => Role::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }
}
