<?php

namespace App\Livewire\AccessControl\Agents;

use App\Models\AccessControlAgentEnrollment;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Agent Enrollment'])]
#[Title('Agent Enrollment')]
class Enroll extends Component
{
    public ?string $enrollment_code = null;
    public ?string $expires_at = null;

    public function generateEnrollmentCode(): void
    {
        $this->authorize('create', AccessControlAgentEnrollment::class);

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        if (!$branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return;
        }

        $code = Str::random(64);
        $expires_at = now()->addMinutes(30);

        AccessControlAgentEnrollment::create([
            'branch_id' => $branch_id,
            'code' => $code,
            'expires_at' => $expires_at,
            'created_by' => auth()->id(),
            'used_at' => null,
            'used_by_agent_id' => null,
        ]);

        $this->enrollment_code = $code;
        $this->expires_at = $expires_at->toIso8601String();

        session()->flash('success', __('Enrollment code generated.'));
    }

    public function render(): View
    {
        $this->authorize('viewAny', AccessControlAgentEnrollment::class);

        return view('livewire.access-control.agents.enroll');
    }
}
