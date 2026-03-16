<?php

namespace App\Livewire\AccessIdentities;

use App\Models\AccessIdentity;
use App\Models\AccessControlDevice;
use App\Models\Member;
use App\Models\User;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $integration_label = 'HIKVision';
    public string $route_prefix = 'hikvision.identities';

    public ?AccessIdentity $identity = null;
    public bool $isEditing = false;

    public string $subject_type = AccessIdentity::SUBJECT_MEMBER;
    public ?int $subject_id = null;
    public string $device_user_id = '';
    public string $card_number = '';
    public bool $is_active = true;
    public string $provider = AccessControlDevice::PROVIDER_HIKVISION_AGENT;

    public function mount(?AccessIdentity $identity = null): void
    {
        if (!IntegrationPermission::canManage(auth()->user(), $this->integration_type)) {
            abort(403);
        }

        $this->identity = $identity;
        $this->isEditing = $identity && $identity->exists;

        if ($this->isEditing) {
            if ($identity->integration_type !== $this->integration_type) {
                abort(404);
            }

            $this->fill(Arr::only($identity->toArray(), [
                'subject_type',
                'subject_id',
                'device_user_id',
                'card_number',
                'is_active',
                'provider',
            ]));
        } else {
            $this->provider = $this->defaultProviderForIntegration();
        }
    }

    protected function rules(): array
    {
        return [
            'subject_type' => ['required', Rule::in([AccessIdentity::SUBJECT_MEMBER, AccessIdentity::SUBJECT_STAFF])],
            'subject_id' => ['required', 'integer'],
            'device_user_id' => [
                'required',
                'string',
                'max:100',
                Rule::unique('access_identities', 'device_user_id')
                    ->where(fn($q) => $q
                        ->where('branch_id', app(BranchContext::class)->getCurrentBranchId())
                        ->where('integration_type', $this->integration_type))
                    ->ignore($this->identity?->id),
            ],
            'card_number' => ['nullable', 'string', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        DB::beginTransaction();

        try {
            $payload = [
                'branch_id' => $branch_id,
                'integration_type' => $this->integration_type,
                'provider' => $this->provider,
                'subject_type' => $data['subject_type'],
                'subject_id' => $data['subject_id'],
                'device_user_id' => $data['device_user_id'],
                'card_number' => $data['card_number'] ?: null,
                'is_active' => $data['is_active'],
            ];

            if ($this->isEditing) {
                $this->authorize('update', $this->identity);
                $this->identity->update($payload);
                $message = __('Identity updated successfully.');
            } else {
                $this->authorize('create', AccessIdentity::class);
                AccessIdentity::create($payload);
                $message = __('Identity created successfully.');
            }

            DB::commit();
            session()->flash('success', $message);
            $this->redirect(route($this->route_prefix . '.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to save identity. Please try again.'));
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

        $staff = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.access-identities.form', [
            'members' => $members,
            'staff' => $staff,
            'integration_label' => $this->integration_label,
            'route_prefix' => $this->route_prefix,
        ]);
    }

    protected function defaultProviderForIntegration(): string
    {
        if ($this->integration_type === AccessControlDevice::INTEGRATION_ZKTECO) {
            return AccessControlDevice::PROVIDER_ZKTECO_ZKBIO;
        }

        return AccessControlDevice::PROVIDER_HIKVISION_AGENT;
    }
}
