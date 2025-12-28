<?php

namespace App\Livewire\MemberInsurances;

use App\Models\Insurer;
use App\Models\MemberInsurance;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Member Policies'])]
#[Title('Member Policies')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $insurer_filter = '';

    #[Url]
    public string $status_filter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingInsurerFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateStatus(int $insurance_id, string $new_status): void
    {
        $insurance = MemberInsurance::findOrFail($insurance_id);

        // Check permission
        if (!Auth::user()?->hasPermissionTo('manage insurers') && !Auth::user()?->hasRole('super-admin')) {
            session()->flash('error', __('You do not have permission to update insurance status.'));
            return;
        }

        $valid_statuses = ['active', 'expired', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            session()->flash('error', __('Invalid status.'));
            return;
        }

        $old_status = $insurance->status;

        $insurance->update(['status' => $new_status]);

        // Also update member's has_insurance flag if needed
        if ($new_status !== 'active') {
            $member = $insurance->member;
            $has_other_active = $member->insurances()->active()->where('id', '!=', $insurance_id)->exists();
            if (!$has_other_active) {
                $member->update(['has_insurance' => false]);
            }
        } else {
            $insurance->member->update(['has_insurance' => true]);
        }

        Log::info('Member insurance status updated', [
            'insurance_id' => $insurance_id,
            'member_id' => $insurance->member_id,
            'old_status' => $old_status,
            'new_status' => $new_status,
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', __('Insurance status updated to :status.', ['status' => ucfirst($new_status)]));
    }

    public function deleteInsurance(int $insurance_id): void
    {
        $insurance = MemberInsurance::findOrFail($insurance_id);

        // Check permission
        if (!Auth::user()?->hasPermissionTo('manage insurers') && !Auth::user()?->hasRole('super-admin')) {
            session()->flash('error', __('You do not have permission to delete insurance records.'));
            return;
        }

        $member = $insurance->member;
        $insurance->delete();

        // Update member's has_insurance flag
        $has_other_active = $member->insurances()->active()->exists();
        if (!$has_other_active) {
            $member->update(['has_insurance' => false]);
        }

        Log::info('Member insurance deleted', [
            'insurance_id' => $insurance_id,
            'member_id' => $member->id,
            'user_id' => Auth::id(),
        ]);

        session()->flash('success', __('Insurance record deleted successfully.'));
    }

    public function render(): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $insurances = MemberInsurance::query()
            ->with(['member', 'insurer'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('member_no', 'like', "%{$this->search}%");
                })->orWhereHas('insurer', function ($q) {
                    $q->where('name', 'like', "%{$this->search}%");
                })->orWhere('policy_number', 'like', "%{$this->search}%");
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->insurer_filter, fn($query) => $query->where('insurer_id', $this->insurer_filter))
            ->when($branch_id, fn($q) => $q->whereHas('member', fn($m) => $m->where('branch_id', $branch_id)))
            ->latest()
            ->paginate(15);

        $insurers = Insurer::orderBy('name')->get(['id', 'name']);

        $can_manage = Auth::user()?->hasPermissionTo('manage insurers') || Auth::user()?->hasRole('super-admin');

        return view('livewire.member-insurances.index', [
            'insurances' => $insurances,
            'insurers' => $insurers,
            'canManage' => $can_manage,
        ]);
    }
}
