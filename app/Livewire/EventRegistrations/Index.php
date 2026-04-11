<?php

namespace App\Livewire\EventRegistrations;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $event_filter = '';

    #[Url]
    public string $status_filter = '';

    public function mount(): void
    {
        $this->authorize('viewAny', EventRegistration::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEventFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->event_filter = '';
        $this->status_filter = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $registrations = EventRegistration::query()
            ->with(['event:id,title,start_datetime', 'member:id,first_name,last_name,member_no', 'paymentTransaction:id,amount,currency,status'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($nested_query) {
                    $nested_query
                        ->where('full_name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('phone', 'like', '%' . $this->search . '%')
                        ->orWhereHas('member', function ($member_query) {
                            $member_query
                                ->where('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%')
                                ->orWhere('member_no', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('event', fn($event_query) => $event_query->where('title', 'like', '%' . $this->search . '%'));
                });
            })
            ->when($this->event_filter !== '', fn($query) => $query->where('event_id', (int) $this->event_filter))
            ->when($this->status_filter !== '', fn($query) => $query->where('status', $this->status_filter))
            ->latest('registration_datetime')
            ->paginate(15);

        $events = Event::query()
            ->orderByDesc('start_datetime')
            ->limit(200)
            ->get(['id', 'title', 'start_datetime']);

        return view('livewire.event-registrations.index', [
            'registrations' => $registrations,
            'events' => $events,
        ]);
    }
}

