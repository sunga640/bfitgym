<?php

namespace App\Livewire\CvSecurity\Connections;

use App\Models\Branch;
use App\Models\CvSecurityConnection;
use App\Services\BranchContext;
use App\Services\CvSecurity\ConnectionService;
use App\Services\CvSecurity\PairingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Form extends Component
{
    public ?CvSecurityConnection $connection = null;
    public bool $is_editing = false;

    public ?int $branch_id = null;
    public string $name = '';
    public string $agent_label = '';
    public string $cv_base_url = '';
    public ?int $cv_port = null;
    public string $cv_username = '';
    public string $cv_password = '';
    public string $cv_api_token = '';
    public bool $clear_cv_password = false;
    public bool $clear_cv_api_token = false;
    public int $poll_interval_seconds = 30;
    public string $timezone = '';
    public string $notes = '';

    public ?string $generated_pairing_token = null;
    public ?string $generated_pairing_token_expires_at = null;

    public function mount(?CvSecurityConnection $connection = null): void
    {
        abort_unless(auth()->user()->hasAnyPermission(['manage zkteco', 'manage zkteco settings']), 403);

        $this->connection = $connection;
        $this->is_editing = $connection?->exists === true;

        $branch_context = app(BranchContext::class);
        $this->branch_id = $branch_context->canSwitchBranches(Auth::user())
            ? $branch_context->getCurrentBranchId()
            : Auth::user()?->branch_id;

        $this->timezone = config('app.timezone', 'UTC');

        if ($this->is_editing && $connection) {
            $this->fill([
                'branch_id' => $connection->branch_id,
                'name' => $connection->name,
                'agent_label' => $connection->agent_label ?? '',
                'cv_base_url' => $connection->cv_base_url ?? '',
                'cv_port' => $connection->cv_port,
                'cv_username' => $connection->cv_username ?? '',
                'poll_interval_seconds' => $connection->poll_interval_seconds,
                'timezone' => $connection->timezone ?: config('app.timezone', 'UTC'),
                'notes' => $connection->notes ?? '',
            ]);
        }
    }

    #[Title('CVSecurity Setup')]
    public function title(): string
    {
        return $this->is_editing ? __('Edit CVSecurity Integration') : __('Create CVSecurity Integration');
    }

    public function save(ConnectionService $service): void
    {
        $validated = $this->validate($this->rules());

        $saved = $service->save(
            $validated,
            auth()->user(),
            $this->is_editing ? $this->connection : null,
        );

        session()->flash('success', $this->is_editing
            ? __('Integration settings updated.')
            : __('Integration created. Generate a pairing token to connect a local agent.'));

        $this->redirect(route('zkteco.connections.show', $saved), navigate: true);
    }

    public function generatePairingToken(PairingService $pairing_service): void
    {
        if (!$this->connection?->exists) {
            session()->flash('error', __('Save the integration before generating a pairing token.'));
            return;
        }

        $token = $pairing_service->generateToken($this->connection, auth()->user(), 30);

        $this->generated_pairing_token = $token['plaintext_token'];
        $this->generated_pairing_token_expires_at = $token['token_model']->expires_at?->toDateTimeString();
        session()->flash('success', __('Pairing token generated. Copy it now; it is shown only once.'));
    }

    protected function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'name' => [
                'required',
                'string',
                'max:160',
                Rule::unique('cvsecurity_connections', 'name')
                    ->where(fn ($q) => $q->where('branch_id', $this->branch_id))
                    ->ignore($this->connection?->id),
            ],
            'agent_label' => ['nullable', 'string', 'max:150'],
            'cv_base_url' => ['required', 'string', 'max:255'],
            'cv_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'cv_username' => ['nullable', 'string', 'max:120'],
            'cv_password' => ['nullable', 'string', 'max:255'],
            'cv_api_token' => ['nullable', 'string', 'max:255'],
            'clear_cv_password' => ['boolean'],
            'clear_cv_api_token' => ['boolean'],
            'poll_interval_seconds' => ['required', 'integer', 'min:5', 'max:3600'],
            'timezone' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function render(BranchContext $branch_context): View
    {
        $can_switch_branches = $branch_context->canSwitchBranches(Auth::user());

        $branches = $can_switch_branches
            ? Branch::active()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('livewire.cvsecurity.connections.form', [
            'branches' => $branches,
            'can_switch_branches' => $can_switch_branches,
        ]);
    }
}
