<?php

namespace App\Livewire\AccessDevices;

use App\Models\AccessControlDevice;
use App\Models\Location;
use App\Services\AccessControl\AccessControlCommandService;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Form extends Component
{
    public ?AccessControlDevice $device = null;
    public bool $isEditing = false;

    // Basic Info
    public string $name = '';
    public string $device_model = AccessControlDevice::MODEL_DS_K1T808MFWX;
    public string $device_type = AccessControlDevice::TYPE_ENTRY;
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

    // Test connection result
    public string $connection_test_result = '';
    public bool $testing_connection = false;

    public function mount(?AccessControlDevice $device = null): void
    {
        $this->device = $device;
        $this->isEditing = $device && $device->exists;

        if ($this->isEditing) {
            $this->fill(Arr::only($device->toArray(), [
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
            // Don't fill password for security - show placeholder instead
        } else {
            // Set default capabilities based on selected model
            $this->updateCapabilities();
        }
    }

    protected function rules(): array
    {
        return [
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
            'ip_address' => ['required', 'ip'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['required', 'string', 'max:100'],
            'password' => [$this->isEditing ? 'nullable' : 'required', 'string', 'max:255'],
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
            'ip_address.required' => __('Device IP address is required.'),
            'ip_address.ip' => __('Please enter a valid IP address.'),
            'serial_number.unique' => __('A device with this serial number already exists.'),
            'password.required' => __('Password is required for new devices.'),
        ];
    }

    /**
     * Update capabilities when device model changes.
     */
    public function updatedDeviceModel(): void
    {
        $this->updateCapabilities();
    }

    /**
     * Set default capabilities based on model.
     */
    protected function updateCapabilities(): void
    {
        $capabilities = AccessControlDevice::defaultCapabilities($this->device_model);

        $this->supports_face_recognition = $capabilities['face_recognition'] ?? false;
        $this->supports_fingerprint = $capabilities['fingerprint'] ?? false;
        $this->supports_card = $capabilities['card'] ?? false;
    }

    /**
     * Test connection to device.
     */
    public function testConnection(): void
    {
        $this->testing_connection = true;
        $this->connection_test_result = '';

        try {
            if (!$this->isEditing || !$this->device?->exists) {
                $this->connection_test_result = 'error';
                session()->flash('error', __('Save the device first, then use “Test connection” from the devices list to enqueue an agent check.'));
                return;
            }

            $this->authorize('testConnection', $this->device);

            // Cloud MUST NOT talk to LAN devices directly. Enqueue agent-side check.
            app(AccessControlCommandService::class)->enqueueLogsPull($this->device, $this->device->logs_synced_until);

            $this->connection_test_result = 'queued';
            session()->flash('success', __('Connection check enqueued. The local agent will verify connectivity and update status.'));
        } catch (\Exception $e) {
            $this->connection_test_result = 'error';
            session()->flash('error', __('Failed to enqueue connection check: :message', ['message' => $e->getMessage()]));
        }

        $this->testing_connection = false;
    }

    /**
     * Save device.
     */
    public function save(): void
    {
        $data = $this->validate();
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        if (!$branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return;
        }

        DB::beginTransaction();

        try {
            $device_data = [
                'branch_id' => $branch_id,
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

            // Only update password if provided
            if (!empty($data['password'])) {
                $device_data['password'] = $data['password'];
            }

            if ($this->isEditing) {
                $this->authorize('update', $this->device);
                $this->device->update($device_data);
                $message = __('Device updated successfully.');
            } else {
                $this->authorize('create', AccessControlDevice::class);
                AccessControlDevice::create($device_data);
                $message = __('Device created successfully.');
            }

            DB::commit();
            session()->flash('success', $message);
            $this->redirect(route('access-devices.index'), navigate: true);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save access control device', [
                'error' => $e->getMessage(),
                'data' => $device_data ?? null,
            ]);
            session()->flash('error', __('Failed to save device. Please try again.'));
        }
    }

    public function render(): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $locations = Location::query()
            ->when($branch_id, fn($q) => $q->where('branch_id', $branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $device_models = AccessControlDevice::deviceModels();
        $device_types = AccessControlDevice::deviceTypes();

        return view('livewire.access-devices.form', [
            'locations' => $locations,
            'device_models' => $device_models,
            'device_types' => $device_types,
        ]);
    }
}

