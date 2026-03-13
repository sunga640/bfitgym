<?php

namespace App\Livewire\AccessControl\Devices;

use App\Models\AccessControlDevice;
use App\Models\Branch;
use App\Models\Location;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public string $integration_type = AccessControlDevice::INTEGRATION_HIKVISION;
    public string $integration_label = 'HIKVision';
    public string $route_prefix = 'hikvision.devices';
    public string $route_base = 'hikvision';

    public ?AccessControlDevice $device = null;
    public bool $is_editing = false;

    // Branch selection
    public ?int $branch_id = null;

    // Basic Info
    public string $name = '';
    public string $device_model = AccessControlDevice::MODEL_DS_K1T808MFWX;
    public string $device_type = AccessControlDevice::TYPE_ENTRY;
    public string $provider = AccessControlDevice::PROVIDER_HIKVISION_AGENT;
    public string $serial_number = '';
    public ?int $location_id = null;
    public string $status = AccessControlDevice::STATUS_ACTIVE;

    // Connection Settings
    public string $ip_address = '';
    public int $port = 80;
    public string $username = 'admin';
    public string $password = '';

    // Capabilities
    public bool $supports_face_recognition = true;
    public bool $supports_fingerprint = true;
    public bool $supports_card = true;

    // Sync Settings
    public bool $auto_sync_enabled = true;
    public int $sync_interval_minutes = 5;

    // Notes
    public string $notes = '';

    public function mount(?AccessControlDevice $device = null): void
    {
        if (!IntegrationPermission::canManage(auth()->user(), $this->integration_type)) {
            abort(403);
        }

        $this->device = $device;
        $this->is_editing = $device && $device->exists;

        if ($this->is_editing) {
            if ($device->integration_type !== $this->integration_type) {
                abort(404);
            }

            $this->authorize('update', $device);

            $this->fill(Arr::only($device->toArray(), [
                'branch_id',
                'integration_type',
                'provider',
                'name',
                'device_model',
                'device_type',
                'serial_number',
                'location_id',
                'status',
                'ip_address',
                'port',
                'username',
                'supports_face_recognition',
                'supports_fingerprint',
                'supports_card',
                'auto_sync_enabled',
                'sync_interval_minutes',
            ]));
            $this->notes = $device->notes ?? '';
        } else {
            $this->authorize('create', AccessControlDevice::class);
            $this->provider = $this->defaultProviderForIntegration();
            $this->updateCapabilities();
        }

        // Set branch_id based on user permissions
        $branch_context = app(BranchContext::class);
        if ($branch_context->canSwitchBranches(Auth::user())) {
            // Super-admin or users with branch switch permission: keep selected or default to current
            if (!$this->branch_id) {
                $this->branch_id = $branch_context->getCurrentBranchId();
            }
        } else {
            // Regular branch user: always use their branch
            $this->branch_id = Auth::user()?->branch_id;
        }
    }

    #[Title('Access Control Device')]
    public function getTitle(): string
    {
        return $this->is_editing ? __('Edit Device') : __('Add Device');
    }

    protected function rules(): array
    {
        $ip_rules = ['nullable', 'ip'];
        $username_rules = ['nullable', 'string', 'max:100'];
        $password_rules = ['nullable', 'string', 'max:255'];

        if ($this->requiresDeviceCredentials()) {
            $ip_rules = ['required', 'ip'];
            $username_rules = ['required', 'string', 'max:100'];
            $password_rules = [$this->is_editing ? 'nullable' : 'required', 'string', 'max:255'];
        }

        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'provider' => ['required', Rule::in(AccessControlDevice::providersForIntegration($this->integration_type))],
            'name' => ['required', 'string', 'max:150'],
            'device_model' => ['required', 'string', 'max:100'],
            'device_type' => ['required', Rule::in(['entry', 'exit', 'bidirectional'])],
            'serial_number' => [
                'required',
                'string',
                'max:100',
                Rule::unique('access_control_devices', 'serial_number')->ignore($this->device?->id),
            ],
            'location_id' => ['nullable', 'exists:locations,id'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'ip_address' => $ip_rules,
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => $username_rules,
            'password' => $password_rules,
            'supports_face_recognition' => ['boolean'],
            'supports_fingerprint' => ['boolean'],
            'supports_card' => ['boolean'],
            'auto_sync_enabled' => ['boolean'],
            'sync_interval_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'branch_id.required' => __('Please select a branch.'),
            'branch_id.exists' => __('The selected branch is invalid.'),
            'ip_address.required' => __('Device IP address is required.'),
            'ip_address.ip' => __('Please enter a valid IP address.'),
            'serial_number.unique' => __('A device with this serial number already exists.'),
            'password.required' => __('Password is required for new devices.'),
        ];
    }

    public function updatedDeviceModel(): void
    {
        $this->updateCapabilities();
    }

    public function updatedProvider(): void
    {
        $this->resetErrorBag(['ip_address', 'username', 'password']);
    }

    protected function updateCapabilities(): void
    {
        $capabilities = AccessControlDevice::defaultCapabilities($this->device_model);

        $this->supports_face_recognition = $capabilities['face_recognition'] ?? false;
        $this->supports_fingerprint = $capabilities['fingerprint'] ?? false;
        $this->supports_card = $capabilities['card'] ?? false;
    }

    public function updatedBranchId(): void
    {
        // Reset location when branch changes (locations are branch-scoped)
        $this->location_id = null;
    }

    public function save(): void
    {
        $data = $this->validate();

        DB::beginTransaction();

        try {
            $device_data = [
                'branch_id' => $data['branch_id'],
                'integration_type' => $this->integration_type,
                'provider' => $data['provider'],
                'name' => $data['name'],
                'device_model' => $data['device_model'],
                'device_type' => $data['device_type'],
                'serial_number' => $data['serial_number'],
                'location_id' => $data['location_id'],
                'status' => $data['status'],
                'ip_address' => $data['ip_address'],
                'port' => $data['port'],
                'username' => $data['username'],
                'supports_face_recognition' => $data['supports_face_recognition'],
                'supports_fingerprint' => $data['supports_fingerprint'],
                'supports_card' => $data['supports_card'],
                'auto_sync_enabled' => $data['auto_sync_enabled'],
                'sync_interval_minutes' => $data['sync_interval_minutes'],
                'notes' => $data['notes'] ?: null,
                'capabilities' => AccessControlDevice::defaultCapabilities($data['device_model']),
            ];

            if (!empty($data['password'])) {
                $device_data['password'] = $data['password'];
            }

            if ($this->is_editing) {
                $this->device->update($device_data);
                $message = __('Device updated successfully.');
            } else {
                AccessControlDevice::create($device_data);
                $message = __('Device created successfully.');
            }

            DB::commit();
            session()->flash('success', $message);
            $this->redirect(route($this->route_prefix . '.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save access control device', [
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', __('Failed to save device. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_context = app(BranchContext::class);
        $can_switch_branches = $branch_context->canSwitchBranches(Auth::user());

        // Load branches for dropdown if user can switch branches
        $branches = $can_switch_branches
            ? Branch::active()->orderBy('name')->get(['id', 'name'])
            : collect();

        // Load locations for the selected branch
        $locations = Location::query()
            ->when($this->branch_id, fn($q) => $q->where('branch_id', $this->branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $device_models = AccessControlDevice::deviceModels();
        $device_types = AccessControlDevice::deviceTypes();

        return view('livewire.access-control.devices.form', [
            'branches' => $branches,
            'can_switch_branches' => $can_switch_branches,
            'locations' => $locations,
            'device_models' => $device_models,
            'device_types' => $device_types,
            'provider_options' => $this->providerOptions(),
            'integration_label' => $this->integration_label,
            'route_prefix' => $this->route_prefix,
            'route_base' => $this->route_base,
            'show_provider_selector' => $this->integration_type === AccessControlDevice::INTEGRATION_ZKTECO,
            'requires_credentials' => $this->requiresDeviceCredentials(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    protected function providerOptions(): array
    {
        if ($this->integration_type === AccessControlDevice::INTEGRATION_ZKTECO) {
            return [
                AccessControlDevice::PROVIDER_ZKBIO_PLATFORM => __('ZKBio Platform (Preferred)'),
                AccessControlDevice::PROVIDER_ZKTECO_AGENT => __('Local Agent (Fallback)'),
            ];
        }

        return [
            AccessControlDevice::PROVIDER_HIKVISION_AGENT => __('Local Agent'),
        ];
    }

    protected function defaultProviderForIntegration(): string
    {
        if ($this->integration_type === AccessControlDevice::INTEGRATION_ZKTECO) {
            return AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
        }

        return AccessControlDevice::PROVIDER_HIKVISION_AGENT;
    }

    protected function requiresDeviceCredentials(): bool
    {
        if ($this->integration_type === AccessControlDevice::INTEGRATION_HIKVISION) {
            return true;
        }

        return $this->provider === AccessControlDevice::PROVIDER_ZKTECO_AGENT;
    }
}
