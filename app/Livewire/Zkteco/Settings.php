<?php

namespace App\Livewire\Zkteco;

use App\Models\AccessControlDevice;
use App\Models\AccessIntegrationConfig;
use App\Services\BranchContext;
use App\Services\Integrations\Zkteco\ZktecoProviderManager;
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
    public string $mode = AccessIntegrationConfig::MODE_PLATFORM;
    public string $provider = AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
    public bool $is_enabled = true;
    public bool $sync_enabled = true;
    public bool $agent_fallback_enabled = false;

    public ?string $platform_base_url = null;
    public ?string $platform_username = null;
    public ?string $platform_password = null;
    public ?string $platform_site_code = null;
    public ?string $platform_client_id = null;
    public ?string $platform_client_secret = null;

    public function mount(): void
    {
        if (!IntegrationPermission::canManageSettings(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        if (!$branch_id) {
            return;
        }

        $config = AccessIntegrationConfig::query()
            ->withoutBranchScope()
            ->where('branch_id', $branch_id)
            ->where('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
            ->first();

        if (!$config) {
            return;
        }

        $this->mode = $config->mode;
        $this->provider = $config->provider;
        $this->is_enabled = (bool) $config->is_enabled;
        $this->sync_enabled = (bool) $config->sync_enabled;
        $this->agent_fallback_enabled = (bool) $config->agent_fallback_enabled;
        $this->platform_base_url = $config->platform_base_url;
        $this->platform_username = $config->platform_username;
        $this->platform_password = $config->platform_password;
        $this->platform_site_code = $config->platform_site_code;
        $this->platform_client_id = $config->platform_client_id;
        $this->platform_client_secret = $config->platform_client_secret;
    }

    public function updatedMode(string $mode): void
    {
        if ($mode === AccessIntegrationConfig::MODE_AGENT) {
            $this->provider = AccessControlDevice::PROVIDER_ZKTECO_AGENT;
            return;
        }

        $this->provider = AccessControlDevice::PROVIDER_ZKBIO_PLATFORM;
    }

    public function save(): void
    {
        if (!IntegrationPermission::canManageSettings(auth()->user(), AccessControlDevice::INTEGRATION_ZKTECO)) {
            abort(403);
        }

        $branch_id = app(BranchContext::class)->getCurrentBranchId();
        if (!$branch_id) {
            session()->flash('error', __('Please select a branch before saving ZKTeco settings.'));
            return;
        }

        $validated = $this->validate($this->rules());

        AccessIntegrationConfig::query()
            ->withoutBranchScope()
            ->updateOrCreate(
                [
                    'branch_id' => $branch_id,
                    'integration_type' => AccessControlDevice::INTEGRATION_ZKTECO,
                ],
                [
                    'mode' => $validated['mode'],
                    'provider' => $validated['provider'],
                    'is_enabled' => $validated['is_enabled'],
                    'sync_enabled' => $validated['sync_enabled'],
                    'agent_fallback_enabled' => $validated['agent_fallback_enabled'],
                    'platform_base_url' => $validated['platform_base_url'] ?: null,
                    'platform_username' => $validated['platform_username'] ?: null,
                    'platform_password' => $validated['platform_password'] ?: null,
                    'platform_site_code' => $validated['platform_site_code'] ?: null,
                    'platform_client_id' => $validated['platform_client_id'] ?: null,
                    'platform_client_secret' => $validated['platform_client_secret'] ?: null,
                ]
            );

        session()->flash('success', __('ZKTeco integration settings saved successfully.'));
    }

    protected function rules(): array
    {
        return [
            'mode' => ['required', Rule::in([AccessIntegrationConfig::MODE_PLATFORM, AccessIntegrationConfig::MODE_AGENT])],
            'provider' => ['required', Rule::in([
                AccessControlDevice::PROVIDER_ZKBIO_PLATFORM,
                AccessControlDevice::PROVIDER_ZKTECO_AGENT,
            ])],
            'is_enabled' => ['boolean'],
            'sync_enabled' => ['boolean'],
            'agent_fallback_enabled' => ['boolean'],
            'platform_base_url' => ['nullable', 'url', 'max:255'],
            'platform_username' => ['nullable', 'string', 'max:120'],
            'platform_password' => ['nullable', 'string', 'max:255'],
            'platform_site_code' => ['nullable', 'string', 'max:120'],
            'platform_client_id' => ['nullable', 'string', 'max:120'],
            'platform_client_secret' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function render(ZktecoProviderManager $provider_manager): View
    {
        $branch_id = app(BranchContext::class)->getCurrentBranchId();

        $config = null;
        if ($branch_id) {
            $config = AccessIntegrationConfig::query()
                ->withoutBranchScope()
                ->where('branch_id', $branch_id)
                ->where('integration_type', AccessControlDevice::INTEGRATION_ZKTECO)
                ->first();
        }

        $provider_meta = null;
        if ($config) {
            $provider = $provider_manager->resolve($config);
            $provider_meta = $provider->health($config);
        }

        return view('livewire.zkteco.settings', [
            'provider_options' => $provider_manager->providerOptions(),
            'config' => $config,
            'provider_meta' => $provider_meta,
        ]);
    }
}

