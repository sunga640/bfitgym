<?php

namespace App\Livewire\Members;

use App\Models\Member;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $insurance_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingInsuranceFilter(): void
    {
        $this->resetPage();
    }

    public function deleteMember(int $member_id): void
    {
        try {
            $member = Member::findOrFail($member_id);

            $this->authorize('delete', $member);

            $member_name = $member->full_name;

            DB::beginTransaction();
            $member->delete();
            DB::commit();

            Log::info('Member deleted', [
                'member_id' => $member_id,
                'member_name' => $member_name,
                'branch_id' => $member->branch_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Member ":name" deleted successfully.', ['name' => $member_name]));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this member.'));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            session()->flash('error', __('Member not found.'));
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to delete member', [
                'member_id' => $member_id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            session()->flash('error', __('Failed to delete the member. Please try again.'));
        }
    }

    public function render(): View
    {
        $members = Member::query()
            ->with(['branch'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('member_no', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->insurance_filter !== '', fn($query) => $query->where('has_insurance', $this->insurance_filter === '1'))
            ->latest()
            ->paginate(12);

        $user = Auth::user();
        $showBranch = $user && $user->hasRole('super-admin');

        return view('livewire.members.index', [
            'members' => $members,
            'showBranch' => $showBranch,
        ]);
    }
}
