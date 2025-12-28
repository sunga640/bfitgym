<?php

namespace App\Livewire\MemberInsurances;

use App\Models\Insurer;
use App\Models\Member;
use App\Models\MemberInsurance;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?MemberInsurance $policy = null;
    public bool $isEditing = false;

    public ?int $member_id = null;
    public ?int $insurer_id = null;
    public string $policy_number = '';
    public string $coverage_type = '';
    public string $start_date = '';
    public string $end_date = '';
    public string $status = 'active';
    public string $notes = '';

    public function mount(?MemberInsurance $memberInsurance = null): void
    {
        $this->policy = $memberInsurance;
        $this->isEditing = $memberInsurance && $memberInsurance->exists;

        if ($this->isEditing) {
            $this->fill(Arr::only($memberInsurance->toArray(), [
                'member_id',
                'insurer_id',
                'policy_number',
                'coverage_type',
                'start_date',
                'end_date',
                'status',
                'notes',
            ]));
        } else {
            $this->start_date = now()->toDateString();
            $this->end_date = now()->addYear()->toDateString();
        }
    }

    protected function rules(): array
    {
        return [
            'member_id' => ['required', 'exists:members,id'],
            'insurer_id' => ['required', 'exists:insurers,id'],
            'policy_number' => [
                'required',
                'string',
                'max:150',
                Rule::unique('member_insurances', 'policy_number')->ignore($this->policy?->id),
            ],
            'coverage_type' => ['nullable', 'string', 'max:150'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['required', Rule::in(['active', 'inactive', 'expired'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        // Ensure member belongs to branch
        if ($branch_id) {
            $member = Member::where('id', $data['member_id'])
                ->where('branch_id', $branch_id)
                ->first();

            if (!$member) {
                session()->flash('error', __('Member not found for current branch.'));
                return;
            }
        }

        DB::beginTransaction();

        try {
            $payload = [
                'member_id' => $data['member_id'],
                'insurer_id' => $data['insurer_id'],
                'policy_number' => $data['policy_number'],
                'coverage_type' => $data['coverage_type'] ?: null,
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'status' => $data['status'],
                'notes' => $data['notes'] ?: null,
            ];

            if ($this->isEditing) {
                $this->policy->update($payload);
                $message = __('Policy updated successfully.');
            } else {
                MemberInsurance::create($payload);
                $message = __('Policy created successfully.');
            }

            DB::commit();
            session()->flash('success', $message);
            $this->redirect(route('member-insurances.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to save policy. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $members = Member::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->active()
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $insurers = Insurer::orderBy('name')->get(['id', 'name']);

        return view('livewire.member-insurances.form', [
            'members' => $members,
            'insurers' => $insurers,
        ]);
    }
}

