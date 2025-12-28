<?php

namespace App\Livewire\AccessIdentities;

use App\Models\AccessIdentity;
use App\Models\Member;
use App\Models\User;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Access Identities'])]
#[Title('Access Identities')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $subject_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSubjectFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $id): void
    {
        $identity = AccessIdentity::findOrFail($id);
        $this->authorize('update', $identity);

        $identity->update(['is_active' => !$identity->is_active]);

        $status = $identity->is_active ? __('activated') : __('deactivated');
        session()->flash('success', __('Identity :status successfully.', ['status' => $status]));
    }

    public function delete(int $id): void
    {
        $identity = AccessIdentity::findOrFail($id);
        $this->authorize('delete', $identity);

        $identity->delete();
        session()->flash('success', __('Identity deleted successfully.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', AccessIdentity::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $identities = AccessIdentity::query()
            ->with(['member', 'staff'])
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('device_user_id', 'like', "%{$this->search}%")
                        ->orWhere('card_number', 'like', "%{$this->search}%");
                });
            })
            ->when($this->subject_filter, fn($q) => $q->where('subject_type', $this->subject_filter))
            ->when($this->status_filter !== '', fn($q) => $q->where('is_active', $this->status_filter === '1'))
            ->latest()
            ->paginate(12);

        $members = Member::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $staff = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.access-identities.index', [
            'identities' => $identities,
            'members' => $members,
            'staff' => $staff,
        ]);
    }
}

