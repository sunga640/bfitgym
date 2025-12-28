<?php

namespace App\Livewire\AccessControl\Enrollments;

use App\Models\AccessControlAgentEnrollment;
use App\Models\AccessControlDevice;
use App\Models\Branch;
use App\Services\AccessControl\AgentEnrollmentService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('Agent Enrollments')]
class Index extends Component
{
    use WithPagination;

    // Search/Filter
    public string $search = '';
    public string $status_filter = '';

    // Generate enrollment modal
    public bool $show_generate_modal = false;
    public string $enrollment_label = '';
    public int $expires_in_minutes = 30;

    /** @var array<int> */
    public array $selected_device_ids = [];

    // View-once plaintext code (transient)
    public ?string $generated_code = null;
    public ?string $generated_agent_uuid = null;
    public bool $show_code_modal = false;
    public bool $code_copied = false;

    // Revoke modal
    public bool $show_revoke_modal = false;
    public ?int $revoke_enrollment_id = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    // -------------------------------------------------------------------------
    // Generate Enrollment
    // -------------------------------------------------------------------------

    public function openGenerateModal(): void
    {
        $this->authorize('create', AccessControlAgentEnrollment::class);

        $this->reset(['enrollment_label', 'selected_device_ids', 'generated_code', 'generated_agent_uuid', 'code_copied']);
        $this->expires_in_minutes = 30;
        $this->show_generate_modal = true;
    }

    public function closeGenerateModal(): void
    {
        $this->show_generate_modal = false;
        $this->reset(['enrollment_label', 'selected_device_ids']);
    }

    public function generateEnrollment(): void
    {
        $this->authorize('create', AccessControlAgentEnrollment::class);

        $this->validate([
            'enrollment_label' => ['nullable', 'string', 'max:255'],
            'expires_in_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'selected_device_ids' => ['array'],
            'selected_device_ids.*' => ['integer'],
        ]);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        if (!$branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return;
        }

        $branch = Branch::findOrFail($branch_id);
        $service = app(AgentEnrollmentService::class);

        try {
            $result = $service->createEnrollment(
                branch: $branch,
                actor: auth()->user(),
                device_ids: $this->selected_device_ids,
                label: $this->enrollment_label ?: null,
                expires_in_minutes: $this->expires_in_minutes
            );

            // Store the plaintext code for view-once display
            $this->generated_code = $result['plaintext_code'];
            $this->generated_agent_uuid = $result['agent_uuid'];
            $this->code_copied = false;

            $this->closeGenerateModal();
            $this->show_code_modal = true;

            session()->flash('success', __('Enrollment code generated successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to generate enrollment: :message', ['message' => $e->getMessage()]));
        }
    }

    public function closeCodeModal(): void
    {
        // Clear the plaintext code - it's view-once
        $this->generated_code = null;
        $this->generated_agent_uuid = null;
        $this->code_copied = false;
        $this->show_code_modal = false;
    }

    public function markCodeCopied(): void
    {
        $this->code_copied = true;
    }

    // -------------------------------------------------------------------------
    // Revoke Enrollment
    // -------------------------------------------------------------------------

    public function confirmRevoke(int $enrollment_id): void
    {
        $enrollment = AccessControlAgentEnrollment::findOrFail($enrollment_id);
        $this->authorize('update', $enrollment);

        if ($enrollment->status === AccessControlAgentEnrollment::STATUS_USED) {
            session()->flash('error', __('Cannot revoke an already used enrollment.'));
            return;
        }

        $this->revoke_enrollment_id = $enrollment_id;
        $this->show_revoke_modal = true;
    }

    public function closeRevokeModal(): void
    {
        $this->show_revoke_modal = false;
        $this->revoke_enrollment_id = null;
    }

    public function revokeEnrollment(): void
    {
        if (!$this->revoke_enrollment_id) {
            return;
        }

        $enrollment = AccessControlAgentEnrollment::findOrFail($this->revoke_enrollment_id);
        $this->authorize('update', $enrollment);

        $service = app(AgentEnrollmentService::class);

        try {
            $service->revokeEnrollment($enrollment, auth()->user());
            session()->flash('success', __('Enrollment revoked successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to revoke enrollment: :message', ['message' => $e->getMessage()]));
        }

        $this->closeRevokeModal();
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render(): View
    {
        $this->authorize('viewAny', AccessControlAgentEnrollment::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        // Get enrollments
        $enrollments = AccessControlAgentEnrollment::query()
            ->with(['createdBy', 'agent', 'usedByAgent', 'devices'])
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->when($this->search, fn($q) => $q->where(function ($q) {
                $q->where('label', 'like', "%{$this->search}%")
                    ->orWhereHas('agent', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            }))
            ->when($this->status_filter, fn($q) => $q->where('status', $this->status_filter))
            ->latest()
            ->paginate(15);

        // Compute status for each enrollment (considering expiry)
        foreach ($enrollments as $enrollment) {
            $enrollment->computed_status = $enrollment->computed_status;
        }

        // Available devices for selection
        $available_devices = AccessControlDevice::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->where('status', AccessControlDevice::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('livewire.access-control.enrollments.index', [
            'enrollments' => $enrollments,
            'available_devices' => $available_devices,
        ]);
    }
}
