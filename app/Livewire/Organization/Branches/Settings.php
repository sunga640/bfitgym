<?php

namespace App\Livewire\Organization\Branches;

use App\Models\Branch;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Branch Settings')]
class Settings extends Component
{
    public Branch $branch;

    // Form fields
    public string $name = '';
    public string $code = '';
    public string $address = '';
    public string $city = '';
    public string $country = '';
    public string $phone = '';
    public string $email = '';
    public string $status = '';

    // Settings fields
    public string $currency = 'TZS';
    public bool $module_pos_enabled = true;
    public bool $module_classes_enabled = true;
    public bool $module_insurance_enabled = true;
    public bool $module_access_control_enabled = true;

    // Modals
    public bool $show_deactivate_modal = false;
    public bool $show_activate_modal = false;

    public function mount(Branch $branch): void
    {
        $this->branch = $branch->load(['setting']);
        $this->authorize('update', $branch);

        $this->fillFromBranch();
    }

    protected function fillFromBranch(): void
    {
        $this->name = $this->branch->name ?? '';
        $this->code = $this->branch->code ?? '';
        $this->address = $this->branch->address ?? '';
        $this->city = $this->branch->city ?? '';
        $this->country = $this->branch->country ?? '';
        $this->phone = $this->branch->phone ?? '';
        $this->email = $this->branch->email ?? '';
        $this->status = $this->branch->status ?? 'active';

        // Load settings
        $setting = $this->branch->setting;
        if ($setting) {
            $this->currency = $setting->currency ?? 'TZS';
            $this->module_pos_enabled = $setting->module_pos_enabled ?? true;
            $this->module_classes_enabled = $setting->module_classes_enabled ?? true;
            $this->module_insurance_enabled = $setting->module_insurance_enabled ?? true;
            $this->module_access_control_enabled = $setting->module_access_control_enabled ?? true;
        }
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:20',
                'alpha_dash',
                Rule::unique('branches', 'code')->ignore($this->branch->id),
            ],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'currency' => ['required', 'string', 'size:3'],
            'module_pos_enabled' => ['boolean'],
            'module_classes_enabled' => ['boolean'],
            'module_insurance_enabled' => ['boolean'],
            'module_access_control_enabled' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $this->authorize('update', $this->branch);

        $validated = $this->validate();

        // Check if code is being changed
        if ($this->code !== $this->branch->code) {
            if (!$this->canChangeCode()) {
                $this->addError('code', __('Branch code cannot be changed. This branch may have generated documents or the feature is restricted.'));
                return;
            }
        }

        // Update branch
        $this->branch->update([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'address' => $validated['address'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
        ]);

        // Update or create settings
        $this->branch->setting()->updateOrCreate(
            ['branch_id' => $this->branch->id],
            [
                'currency' => $validated['currency'],
                'module_pos_enabled' => $validated['module_pos_enabled'],
                'module_classes_enabled' => $validated['module_classes_enabled'],
                'module_insurance_enabled' => $validated['module_insurance_enabled'],
                'module_access_control_enabled' => $validated['module_access_control_enabled'],
            ]
        );

        Log::info('Branch settings updated', [
            'branch_id' => $this->branch->id,
            'user_id' => auth()->id(),
        ]);

        $this->branch->refresh()->load(['setting']);

        session()->flash('success', __('Branch settings saved successfully.'));
    }

    public function confirmDeactivate(): void
    {
        $this->authorize('manageStatus', $this->branch);
        $this->show_deactivate_modal = true;
    }

    public function confirmActivate(): void
    {
        $this->authorize('manageStatus', $this->branch);
        $this->show_activate_modal = true;
    }

    public function deactivateBranch(): void
    {
        $this->authorize('manageStatus', $this->branch);

        $this->branch->update(['status' => 'inactive']);
        $this->status = 'inactive';

        $this->show_deactivate_modal = false;

        Log::info('Branch deactivated', [
            'branch_id' => $this->branch->id,
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', __('Branch has been deactivated.'));
    }

    public function activateBranch(): void
    {
        $this->authorize('manageStatus', $this->branch);

        $this->branch->update(['status' => 'active']);
        $this->status = 'active';

        $this->show_activate_modal = false;

        Log::info('Branch activated', [
            'branch_id' => $this->branch->id,
            'user_id' => auth()->id(),
        ]);

        session()->flash('success', __('Branch has been activated.'));
    }

    public function cancelModal(): void
    {
        $this->show_deactivate_modal = false;
        $this->show_activate_modal = false;
    }

    /**
     * Check if code can be changed.
     * TODO: Implement document check when document generation is added.
     * Currently, code changes are always blocked unless config flag is enabled.
     */
    #[Computed]
    public function canChangeCode(): bool
    {
        // Check if explicit config flag allows code changes
        if (config('app.allow_branch_code_change', false)) {
            return true;
        }

        // TODO: When documents feature is implemented, add check:
        // if ($this->branch->hasGeneratedDocuments()) {
        //     return false;
        // }

        // By default, block code changes as a safeguard
        return false;
    }

    #[Computed]
    public function canManageStatus(): bool
    {
        return Auth::user()->can('manageStatus', $this->branch);
    }

    public function render(): View
    {
        return view('livewire.organization.branches.settings', [
            'branch' => $this->branch,
        ]);
    }
}

