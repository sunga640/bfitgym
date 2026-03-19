<?php

namespace App\Livewire\CvSecurity\Connections;

use App\Models\CvSecurityConnection;
use App\Services\CvSecurity\ConnectionService;
use App\Services\CvSecurity\MemberSyncPlanner;
use App\Services\CvSecurity\PairingService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('CVSecurity Integration')]
class Show extends Component
{
    public CvSecurityConnection $connection;

    public ?string $generated_pairing_token = null;
    public ?string $generated_pairing_token_expires_at = null;

    public function mount(CvSecurityConnection $connection): void
    {
        abort_unless(auth()->user()->hasAnyPermission(['view zkteco', 'manage zkteco', 'manage zkteco settings']), 403);
        $this->authorize('view', $connection);
        $this->connection = $connection;
    }

    public function generatePairingToken(PairingService $pairing_service): void
    {
        $this->authorize('update', $this->connection);

        $token = $pairing_service->generateToken($this->connection, auth()->user(), 30);
        $this->generated_pairing_token = $token['plaintext_token'];
        $this->generated_pairing_token_expires_at = $token['token_model']->expires_at?->toDateTimeString();

        $this->connection->refresh();
        session()->flash('success', __('Pairing token generated.'));
    }

    public function testConnection(ConnectionService $connection_service): void
    {
        $this->authorize('update', $this->connection);
        $connection_service->requestAgentTest($this->connection, auth()->user());
        $this->connection->refresh();
        session()->flash('success', __('Connection test requested. Agent will run it on next heartbeat.'));
    }

    public function syncMembersNow(MemberSyncPlanner $planner): void
    {
        $this->authorize('update', $this->connection);

        $result = $planner->planForConnection($this->connection, auth()->user());
        $this->connection->refresh();

        session()->flash('success', __('Sync queued. :count item(s) created.', ['count' => $result['created']]));
    }

    public function pullLatestEvents(ConnectionService $connection_service): void
    {
        $this->authorize('update', $this->connection);
        $connection_service->requestAgentEventPull($this->connection, auth()->user());
        $this->connection->refresh();
        session()->flash('success', __('Event pull requested. Agent will pull events on next cycle.'));
    }

    public function disconnect(ConnectionService $connection_service): void
    {
        $this->authorize('update', $this->connection);
        $connection_service->disconnect($this->connection, auth()->user());
        $this->connection->refresh();
        session()->flash('success', __('Integration disconnected.'));
    }

    public function disable(ConnectionService $connection_service): void
    {
        $this->authorize('update', $this->connection);
        $connection_service->disable($this->connection, auth()->user());
        $this->connection->refresh();
        session()->flash('success', __('Integration disabled.'));
    }

    public function render(): View
    {
        $connection = CvSecurityConnection::query()
            ->with([
                'agents' => fn ($q) => $q->latest('last_seen_at'),
                'syncState',
                'activityLogs' => fn ($q) => $q->latest('id')->limit(15),
            ])
            ->withCount([
                'events as recent_events_count' => fn ($q) => $q->where('occurred_at', '>=', now()->subDay()),
                'syncItems as pending_sync_items_count' => fn ($q) => $q->whereIn('status', ['pending', 'retry']),
                'syncItems as failed_sync_items_count' => fn ($q) => $q->where('status', 'failed'),
            ])
            ->findOrFail($this->connection->id);

        $recent_events = $connection->events()
            ->with('member:id,first_name,last_name,member_no')
            ->latest('occurred_at')
            ->limit(20)
            ->get();

        return view('livewire.cvsecurity.connections.show', [
            'connection' => $connection,
            'latest_agent' => $connection->agents->first(),
            'recent_events' => $recent_events,
            'can_manage' => auth()->user()->hasAnyPermission(['manage zkteco', 'manage zkteco settings']),
        ]);
    }
}

