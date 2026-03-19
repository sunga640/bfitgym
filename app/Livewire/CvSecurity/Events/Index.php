<?php

namespace App\Livewire\CvSecurity\Events;

use App\Models\CvSecurityConnection;
use App\Models\CvSecurityEvent;
use App\Services\BranchContext;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
#[Title('CVSecurity Events')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $connection_id = '';

    #[Url]
    public string $event_type = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(BranchContext $branch_context): View
    {
        abort_unless(auth()->user()->hasAnyPermission(['view zkteco', 'manage zkteco', 'manage zkteco settings']), 403);

        $branch_id = $branch_context->getCurrentBranchId();

        $events = CvSecurityEvent::query()
            ->with(['connection:id,name', 'member:id,first_name,last_name,member_no'])
            ->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->when($this->connection_id !== '', fn ($q) => $q->where('cvsecurity_connection_id', (int) $this->connection_id))
            ->when($this->event_type !== '', fn ($q) => $q->where('event_type', $this->event_type))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('external_event_id', 'like', '%' . $this->search . '%')
                        ->orWhere('external_person_id', 'like', '%' . $this->search . '%')
                        ->orWhere('device_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($member_q) {
                            $member_q->where('member_no', 'like', '%' . $this->search . '%')
                                ->orWhere('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->latest('occurred_at')
            ->paginate(25);

        $connections = CvSecurityConnection::query()
            ->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->orderBy('name')
            ->get(['id', 'name']);

        $event_types = CvSecurityEvent::query()
            ->when($branch_id, fn ($q) => $q->where('branch_id', $branch_id))
            ->whereNotNull('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->pluck('event_type');

        return view('livewire.cvsecurity.events.index', [
            'events' => $events,
            'connections' => $connections,
            'event_types' => $event_types,
        ]);
    }
}

