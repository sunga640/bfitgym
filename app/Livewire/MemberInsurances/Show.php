<?php

namespace App\Livewire\MemberInsurances;

use App\Models\AccessIdentity;
use App\Models\MemberInsurance;
use App\Services\AccessControl\AccessControlService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Policy Details'])]
#[Title('Policy Details')]
class Show extends Component
{
    public MemberInsurance $insurance;
    public bool $has_fingerprint_access = false;
    public bool $has_fingerprint_registered = false;
    public ?AccessIdentity $access_identity = null;

    public function mount(MemberInsurance $memberInsurance): void
    {
        $this->insurance = $memberInsurance->load(['member.branch', 'insurer']);
        
        // Check permission
        if (!Auth::user()?->hasPermissionTo('manage insurers') && !Auth::user()?->hasRole('super-admin')) {
            abort(403);
        }
        
        $this->loadFingerprintStatus();
    }

    protected function loadFingerprintStatus(): void
    {
        $service = app(AccessControlService::class);
        $this->access_identity = $service->getMemberIdentity($this->insurance->member);
        $this->has_fingerprint_registered = $this->access_identity !== null;
        $this->has_fingerprint_access = $this->access_identity?->is_active ?? false;
    }

    protected function checkPermission(): bool
    {
        return Auth::user()?->hasPermissionTo('manage insurers') || Auth::user()?->hasRole('super-admin');
    }

    public function addFingerprint(): void
    {
        if (!$this->checkPermission()) {
            session()->flash('error', __('You do not have permission to perform this action.'));
            return;
        }

        $service = app(AccessControlService::class);
        $result = $service->enrollMemberFingerprint(
            $this->insurance->member,
            'insurance',
            $this->insurance->id
        );

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function enableFingerprint(): void
    {
        if (!$this->checkPermission()) {
            session()->flash('error', __('You do not have permission to perform this action.'));
            return;
        }

        $service = app(AccessControlService::class);
        $result = $service->enableMemberFingerprint($this->insurance->member);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function disableFingerprint(): void
    {
        if (!$this->checkPermission()) {
            session()->flash('error', __('You do not have permission to perform this action.'));
            return;
        }

        $service = app(AccessControlService::class);
        $result = $service->disableMemberFingerprint($this->insurance->member);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function removeFingerprint(): void
    {
        if (!$this->checkPermission()) {
            session()->flash('error', __('You do not have permission to perform this action.'));
            return;
        }

        $service = app(AccessControlService::class);
        $result = $service->removeMemberFingerprint($this->insurance->member);

        if ($result['success']) {
            session()->flash('success', $result['message']);
            $this->loadFingerprintStatus();
        } else {
            session()->flash('error', $result['message']);
        }
    }

    public function render(): View
    {
        return view('livewire.member-insurances.show');
    }
}

