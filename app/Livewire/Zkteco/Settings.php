<?php

namespace App\Livewire\Zkteco;

use App\Integrations\Zkteco\Repositories\ZktecoConnectionRepository;
use App\Integrations\Zkteco\Repositories\ZktecoDeviceRepository;
use App\Integrations\Zkteco\Services\ZktecoConnectionService;
use App\Integrations\Zkteco\Services\ZktecoDeviceDiscoveryService;
use App\Integrations\Zkteco\Services\ZktecoEventImportService;
use App\Integrations\Zkteco\Services\ZktecoHealthService;
use App\Integrations\Zkteco\Services\ZktecoMemberSyncService;
use App\Models\AccessControlDevice;
use App\Models\ZktecoConnection;
use App\Services\BranchContext;
use App\Support\Integrations\IntegrationPermission;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('ZKTeco Settings')]
class Settings extends Component
{
    public ?int $branch_id = null;
    public ?int $selected_branch_id = null;

    public ?string $base_url = null;
    public ?int $port = null;
    public ?string $username = null;
    public string $password = '';
    public string $api_key = '';
    public bool $ssl_enabled = true;
    public bool $allow_self_signed = false;
    public int $timeout_seconds = 10;
    public bool $clear_password = false;
    public bool $clear_api_key = false;

    /**
     * @var array<int, int>
     */
    public array $selected_device_ids = [];

    public bool $has_saved_password = false;
    public bool $has_saved_api_key = false;

    public function mount(
        BranchContext $branch_context,
        ZktecoConnectionRepository $connections,
        ZktecoDeviceRepository $devices
    ): void {
        if (!IntegrationPermission::canManageSettings(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $this->branch_id = $branch_context->getCurrentBranchId();
        $this->selected_branch_id = $this->branch_id;

        if (!$this->branch_id) {
            return;
        }

        $connection = $connections->forBranch($this->branch_id);
        $this->fillFromConnection($connection);

        if ($connection) {
            $this->selected_device_ids = $devices->mappedDeviceIds($this->selected_branch_id, $connection->id);
        }
    }

    public function save(ZktecoConnectionService $service): void
    {
        if (!$this->branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return;
        }

        $validated = $this->validate($this->rules());

        $connection = $service->saveConnection($this->branch_id, [
            ...$validated,
            'clear_password' => $this->clear_password,
            'clear_api_key' => $this->clear_api_key,
        ], auth()->user());

        $this->fillFromConnection($connection->fresh());
        $this->clear_password = false;
        $this->clear_api_key = false;
        $this->password = '';
        $this->api_key = '';

        session()->flash('success', __('ZKTeco connection settings saved.'));
    }

    public function testConnection(
        ZktecoConnectionService $connection_service,
        ZktecoConnectionRepository $connections
    ): void {
        $connection = $this->persistAndGetConnection($connection_service, $connections);
        if (!$connection) {
            return;
        }

        $result = $connection_service->testConnection($connection, auth()->user());
        $this->fillFromConnection($connection->fresh());

        if ($result->ok) {
            session()->flash('success', $result->message);
            return;
        }

        session()->flash('error', $result->message);
    }

    public function fetchDevices(
        ZktecoConnectionService $connection_service,
        ZktecoConnectionRepository $connections,
        ZktecoDeviceDiscoveryService $discovery
    ): void {
        $connection = $this->persistAndGetConnection($connection_service, $connections);
        if (!$connection) {
            return;
        }

        try {
            $result = $discovery->fetchAndStore($connection, auth()->user());
            $this->fillFromConnection($connection->fresh());

            session()->flash(
                'success',
                __('Fetched :count devices from ZKBio.', ['count' => $result['discovered']])
            );
        } catch (\Throwable $e) {
            session()->flash('error', __('Failed to fetch devices: :message', ['message' => $e->getMessage()]));
        }
    }

    public function syncPersonnel(
        ZktecoConnectionRepository $connections,
        ZktecoMemberSyncService $sync_service
    ): void {
        $connection = $this->currentConnection($connections);
        if (!$connection) {
            session()->flash('error', __('Save and test a connection first.'));
            return;
        }

        $result = $sync_service->syncBranch($connection, auth()->user());
        $this->fillFromConnection($connection->fresh());

        session()->flash(
            'success',
            __('Personnel sync completed. Total: :total, Success: :ok, Failed: :failed.', [
                'total' => $result['total'],
                'ok' => $result['success'],
                'failed' => $result['failed'],
            ])
        );
    }

    public function syncLogs(
        ZktecoConnectionRepository $connections,
        ZktecoEventImportService $event_import
    ): void {
        $connection = $this->currentConnection($connections);
        if (!$connection) {
            session()->flash('error', __('Save and test a connection first.'));
            return;
        }

        try {
            $result = $event_import->syncBranch($connection, actor: auth()->user());
            $this->fillFromConnection($connection->fresh());

            session()->flash(
                'success',
                __('Event sync completed. Imported: :imported, Skipped: :skipped, Failed: :failed.', [
                    'imported' => $result['imported'],
                    'skipped' => $result['skipped'],
                    'failed' => $result['failed'],
                ])
            );
        } catch (\Throwable $e) {
            session()->flash('error', __('Failed to sync events: :message', ['message' => $e->getMessage()]));
        }
    }

    public function saveBranchMapping(
        ZktecoConnectionRepository $connections,
        ZktecoDeviceRepository $devices
    ): void {
        $this->validate([
            'selected_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'selected_device_ids' => ['array'],
            'selected_device_ids.*' => ['integer', Rule::exists('zkteco_devices', 'id')],
        ]);

        $connection = $this->currentConnection($connections);
        if (!$connection) {
            session()->flash('error', __('Save and test a connection first.'));
            return;
        }

        $devices->saveBranchMappings(
            branch_id: $this->selected_branch_id,
            connection: $connection,
            device_ids: $this->selected_device_ids,
            actor_id: auth()->id()
        );

        session()->flash('success', __('Device mapping saved for the selected branch.'));
    }

    public function updatedSelectedBranchId(): void
    {
        if (!$this->selected_branch_id || !$this->branch_id) {
            return;
        }

        $connection = app(ZktecoConnectionRepository::class)->forBranch($this->branch_id);
        if (!$connection) {
            return;
        }

        $this->selected_device_ids = app(ZktecoDeviceRepository::class)->mappedDeviceIds(
            $this->selected_branch_id,
            $connection->id
        );
    }

    public function disconnect(
        ZktecoConnectionRepository $connections,
        ZktecoConnectionService $service
    ): void {
        $connection = $this->currentConnection($connections);
        if (!$connection) {
            session()->flash('error', __('No active ZKTeco connection found.'));
            return;
        }

        $service->disconnect($connection, auth()->user());
        $this->fillFromConnection($connection->fresh());

        session()->flash('success', __('ZKTeco connection disconnected.'));
    }

    protected function rules(): array
    {
        return [
            'base_url' => ['required', 'string', 'max:255'],
            'port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:120'],
            'password' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:255'],
            'ssl_enabled' => ['boolean'],
            'allow_self_signed' => ['boolean'],
            'timeout_seconds' => ['required', 'integer', 'min:1', 'max:120'],
        ];
    }

    public function render(
        BranchContext $branch_context,
        ZktecoConnectionRepository $connections,
        ZktecoHealthService $health_service
    ): View {
        $connection = $this->branch_id ? $connections->forBranch($this->branch_id) : null;

        $all_devices = $connection
            ? $connection->devices()->withoutBranchScope()->orderBy('remote_name')->get()
            : collect();

        $recent_runs = $connection
            ? $connection->syncRuns()->withoutBranchScope()->latest('started_at')->limit(10)->get()
            : collect();

        return view('livewire.zkteco.settings', [
            'connection' => $connection,
            'health' => $health_service->healthForBranch($this->branch_id),
            'devices' => $all_devices,
            'recent_runs' => $recent_runs,
            'branches' => $branch_context->getAccessibleBranches(auth()->user()),
        ]);
    }

    private function fillFromConnection(?ZktecoConnection $connection): void
    {
        if (!$connection) {
            $this->has_saved_password = false;
            $this->has_saved_api_key = false;
            return;
        }

        $this->base_url = $connection->base_url;
        $this->port = $connection->port;
        $this->username = $connection->username;
        $this->ssl_enabled = (bool) $connection->ssl_enabled;
        $this->allow_self_signed = (bool) $connection->allow_self_signed;
        $this->timeout_seconds = (int) $connection->timeout_seconds;
        $this->has_saved_password = !empty($connection->password);
        $this->has_saved_api_key = !empty($connection->api_key);
    }

    private function persistAndGetConnection(
        ZktecoConnectionService $connection_service,
        ZktecoConnectionRepository $connections
    ): ?ZktecoConnection {
        if (!$this->branch_id) {
            session()->flash('error', __('Please select a branch first.'));
            return null;
        }

        $validated = $this->validate($this->rules());

        $connection_service->saveConnection($this->branch_id, [
            ...$validated,
            'clear_password' => $this->clear_password,
            'clear_api_key' => $this->clear_api_key,
        ], auth()->user());

        $this->password = '';
        $this->api_key = '';
        $this->clear_password = false;
        $this->clear_api_key = false;

        return $connections->forBranch($this->branch_id);
    }

    private function currentConnection(ZktecoConnectionRepository $connections): ?ZktecoConnection
    {
        if (!$this->branch_id) {
            return null;
        }

        return $connections->forBranch($this->branch_id);
    }
}
