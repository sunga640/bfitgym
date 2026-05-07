<?php

namespace App\Livewire\Members;

use App\Models\Branch;
use App\Models\Insurer;
use App\Models\Member;
use App\Models\MemberInsurance;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?Member $member = null;
    public bool $isEditing = false;
    public ?int $branch_id = null;
    public string $first_name = '';
    public string $last_name = '';
    public string $phone = '';
    public string $email = '';
    public string $member_no = '';
    public string $gender = '';
    public ?string $dob = null;
    public string $address = '';
    public string $status = 'active';
    public bool $has_insurance = false;
    public string $notes = '';

    // Insurance fields
    public ?int $insurer_id = null;
    public ?string $insurance_start_date = null;
    public ?string $insurance_end_date = null;

    public function mount(?Member $member = null): void
    {
        $this->member = $member;
        $this->isEditing = $member && $member->exists;

        if ($this->isEditing) {
            $this->branch_id = $member->branch_id;
            $this->member_no = (string) ($member->member_no ?? '');
            $this->first_name = (string) ($member->first_name ?? '');
            $this->last_name = (string) ($member->last_name ?? '');
            $this->phone = (string) ($member->phone ?? '');
            $this->email = (string) ($member->email ?? '');
            $this->gender = (string) ($member->gender ?? '');
            $this->dob = $member->dob?->format('Y-m-d');
            $this->address = (string) ($member->address ?? '');
            $this->status = (string) ($member->status ?? 'active');
            $this->has_insurance = (bool) $member->has_insurance;
            $this->notes = (string) ($member->notes ?? '');

            // Load existing active insurance if present
            $active_insurance = $member->insurances()->active()->latest()->first();
            if ($active_insurance) {
                $this->insurer_id = $active_insurance->insurer_id;
                $this->insurance_start_date = $active_insurance->start_date?->format('Y-m-d');
                $this->insurance_end_date = $active_insurance->end_date?->format('Y-m-d');
            }
        } else {
            $this->status = 'active';
            // Default insurance start date to today
            $this->insurance_start_date = now()->format('Y-m-d');
        }

        if (Auth::user()?->hasRole('super-admin') && !$this->branch_id) {
            $this->branch_id = null;
        } else {
            $this->branch_id = $this->branch_id ?: (Auth::user()?->branch_id);
        }
    }

    public function updatedHasInsurance(): void
    {
        // Reset insurance fields when unchecked
        if (!$this->has_insurance) {
            $this->insurer_id = null;
            $this->insurance_start_date = null;
            $this->insurance_end_date = null;
        } else {
            // Set default start date to today when insurance is enabled
            $this->insurance_start_date = now()->format('Y-m-d');
        }
    }

    public function rules(): array
    {
        $rules = [
            'branch_id' => [
                Rule::requiredIf(fn() => Auth::user()?->hasRole('super-admin')),
                'nullable',
                'exists:branches,id'
            ],
            'member_no' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('members', 'member_no')->ignore($this->member?->id),
            ],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => [
                'required',
                'string',
                'size:12',
                'regex:/^255\d{9}$/',
                Rule::unique('members', 'phone')->ignore($this->member?->id),
            ],
            'email' => [
                'nullable',
                'string',
                'email',
                'max:200',
                Rule::unique('members', 'email')->ignore($this->member?->id),
            ],
            'gender' => [
                Rule::requiredIf(fn() => ! $this->isEditing),
                'nullable',
                Rule::in(['male', 'female', 'other']),
            ],
            'dob' => ['nullable', 'date'],
            'address' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],
            'has_insurance' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],

            // Insurance fields - required when has_insurance is true
            'insurer_id' => [
                Rule::requiredIf(fn() => $this->has_insurance),
                'nullable',
                'exists:insurers,id'
            ],
            'insurance_start_date' => [
                Rule::requiredIf(fn() => $this->has_insurance),
                'nullable',
                'date'
            ],
            'insurance_end_date' => [
                'nullable',
                'date',
                'after_or_equal:insurance_start_date'
            ],
        ];

        return $rules;
    }

    public function messages(): array
    {
        return [
            'phone.size' => __('Phone number must be 12 digits in the format 255XXXXXXXXX.'),
            'phone.regex' => __('Phone number must be in the format 255XXXXXXXXX.'),
            'phone.unique' => __('This phone number is already registered to another member.'),
            'email.unique' => __('This email is already registered to another member.'),
            'gender.required' => __('Please select gender.'),
            'insurer_id.required' => __('Please select an insurer.'),
            'insurance_start_date.required' => __('Please enter the insurance start date.'),
            'insurance_end_date.after_or_equal' => __('End date must be after or equal to start date.'),
        ];
    }

    public function save(): void
    {
        $this->authorize($this->isEditing ? 'update' : 'create', $this->member ?? Member::class);
        $this->normalizeContactFields();
        $data = $this->validate();
        $data['email'] = Member::normalizeEmail($data['email'] ?? null);

        if (!isset($data['branch_id']) || $data['branch_id'] === null) {
            $data['branch_id'] = $this->branch_id ?? Auth::user()?->branch_id;
        }

        DB::beginTransaction();

        try {
            if (!$this->isEditing) {
                if (empty($data['member_no'])) {
                    $data['member_no'] = $this->generateMemberNo($data['branch_id'] ?? null);
                }

                // Create member
                $member = Member::create(Arr::except($data, ['insurer_id', 'insurance_start_date', 'insurance_end_date']));

                // Create insurance record if has_insurance is true
                if ($this->has_insurance && $this->insurer_id) {
                    MemberInsurance::create([
                        'member_id' => $member->id,
                        'insurer_id' => $this->insurer_id,
                        'policy_number' => null, // Will be set later if needed
                        'start_date' => $this->insurance_start_date,
                        'end_date' => $this->insurance_end_date,
                        'status' => 'active',
                    ]);
                }

                DB::commit();

                $this->showCreatedMemberBranch($member);

                session()->flash('success', __('Member created successfully.'));
                $this->redirect(route('members.index'), navigate: true);
            } else {
                // Update member
                $this->member->update(Arr::except($data, ['insurer_id', 'insurance_start_date', 'insurance_end_date']));

                // Handle insurance update
                if ($this->has_insurance && $this->insurer_id) {
                    // Check if there's an existing active insurance
                    $existing_insurance = $this->member->insurances()->active()->latest()->first();

                    if ($existing_insurance) {
                        // Update existing insurance
                        $existing_insurance->update([
                            'insurer_id' => $this->insurer_id,
                            'start_date' => $this->insurance_start_date,
                            'end_date' => $this->insurance_end_date,
                        ]);
                    } else {
                        // Create new insurance record
                        MemberInsurance::create([
                            'member_id' => $this->member->id,
                            'insurer_id' => $this->insurer_id,
                            'policy_number' => null,
                            'start_date' => $this->insurance_start_date,
                            'end_date' => $this->insurance_end_date,
                            'status' => 'active',
                        ]);
                    }
                } elseif (!$this->has_insurance) {
                    // Cancel all active insurances if has_insurance is unchecked
                    $this->member->insurances()->active()->update(['status' => 'cancelled']);
                }

                DB::commit();
                session()->flash('success', __('Member updated successfully.'));
                $this->redirect(route('members.index'), navigate: true);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Member save failed: ' . $e->getMessage(), [
                'exception' => $e,
                'data' => $data ?? [],
                'isEditing' => $this->isEditing,
                'has_insurance' => $this->has_insurance,
            ]);
            session()->flash('error', __('An error occurred while saving the member. Please try again.'));
        }
    }

    private function normalizeContactFields(): void
    {
        $this->phone = Member::normalizePhone($this->phone) ?? '';
        $this->email = Member::normalizeEmail($this->email) ?? '';
    }

    public function render(): View
    {
        $branches = Auth::user()->hasRole('super-admin')
            ? Branch::orderBy('name')->get(['id', 'name'])
            : collect();

        $insurers = Insurer::active()->orderBy('name')->get(['id', 'name']);

        return view('livewire.members.form', [
            'branches' => $branches,
            'insurers' => $insurers,
        ]);
    }

    private function generateMemberNo(?int $branchId): string
    {
        $prefix = '455';

        if ($branchId) {
            $prefix .= '-' . str_pad((string) $branchId, 2, '0', STR_PAD_LEFT);
        }

        // Must bypass branch scope and include soft-deleted to find the true highest member_no
        // The member_no is globally unique, so we search for the pattern matching this branch prefix
        $searchPrefix = $prefix . '-';

        $highestNumber = 0;

        // Find all member_no matching this prefix pattern (including soft-deleted)
        $existing_member_nos = Member::withTrashed()
            ->withoutBranchScope()
            ->where('member_no', 'like', $searchPrefix . '%')
            ->whereNotNull('member_no')
            ->pluck('member_no');

        foreach ($existing_member_nos as $member_no) {
            if (preg_match('/(\d+)$/', $member_no, $m)) {
                $number = (int) $m[1];
                if ($number > $highestNumber) {
                    $highestNumber = $number;
                }
            }
        }

        $nextNumber = $highestNumber + 1;

        return $prefix . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function showCreatedMemberBranch(Member $member): void
    {
        $branch_id = $member->branch_id;

        if (! $branch_id) {
            return;
        }

        app(BranchContext::class)->setCurrentBranch((int) $branch_id);
    }
}
