<?php

namespace App\Livewire\CvSecurity\Connections;

use App\Models\CvSecurityConnection;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('CVSecurity Integrations')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function render(BranchContext $branch_context): View
    {
        abort_unless(auth()->user()->hasAnyPermission(['view zkteco', 'manage zkteco', 'manage zkteco settings']), 403);

        $branch_id = $branch_context->getCurrentBranchId();

        $connections = CvSecurityConnection::query()
            ->withCount([
                'agents',
                'events',
                'syncItems as pending_sync_items_count' => fn ($q) => $q->whereIn('status', ['pending', 'retry']),
            ])
            ->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('agent_label', 'like', '%' . $this->search . '%')
                        ->orWhere('cv_base_url', 'like', '%' . $this->search . '%');
                });
            })
            ->latest('updated_at')
            ->paginate(12);

        return view('livewire.cvsecurity.connections.index', [
            'connections' => $connections,
            'statuses' => [
                CvSecurityConnection::STATUS_PENDING,
                CvSecurityConnection::STATUS_PAIRED,
                CvSecurityConnection::STATUS_CONNECTED,
                CvSecurityConnection::STATUS_ERROR,
                CvSecurityConnection::STATUS_DISCONNECTED,
                CvSecurityConnection::STATUS_DISABLED,
            ],
            'can_manage' => auth()->user()->hasAnyPermission(['manage zkteco', 'manage zkteco settings']),
        ]);
    }
}

