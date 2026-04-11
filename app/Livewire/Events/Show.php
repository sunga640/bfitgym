<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Models\EventRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Event $event;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status_filter = '';

    public function mount(Event $event): void
    {
        $this->authorize('view', $event);

        $this->event = $event->loadCount([
            'registrations',
            'registrations as attending_registrations_count' => fn($query) => $query
                ->where('will_attend', true)
                ->whereIn('status', ['pending', 'confirmed', 'attended']),
        ]);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updateRegistrationStatus(int $registration_id, string $status): void
    {
        $registration = EventRegistration::query()->findOrFail($registration_id);
        $this->authorize('update', $registration);

        if ($registration->event_id !== $this->event->id) {
            abort(403);
        }

        $valid_statuses = ['pending', 'confirmed', 'cancelled', 'attended', 'no_show'];
        if (!in_array($status, $valid_statuses, true)) {
            return;
        }

        $registration->update(['status' => $status]);
        session()->flash('success', __('Registration status updated.'));
    }

    public function deleteRegistration(int $registration_id): void
    {
        $registration = EventRegistration::query()->findOrFail($registration_id);
        $this->authorize('delete', $registration);

        if ($registration->event_id !== $this->event->id) {
            abort(403);
        }

        DB::transaction(function () use ($registration) {
            $registration->delete();
        });

        session()->flash('success', __('Registration deleted.'));
    }

    public function render(): View
    {
        $fresh_event = $this->event->fresh();
        if ($fresh_event) {
            $this->event = $fresh_event->loadCount([
                'registrations',
                'registrations as attending_registrations_count' => fn($query) => $query
                    ->where('will_attend', true)
                    ->whereIn('status', ['pending', 'confirmed', 'attended']),
            ]);
        }

        $registrations = EventRegistration::query()
            ->with(['member:id,first_name,last_name,member_no', 'paymentTransaction:id,amount,currency,status,paid_at'])
            ->where('event_id', $this->event->id)
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
                        });
                });
            })
            ->when($this->status_filter !== '', fn($query) => $query->where('status', $this->status_filter))
            ->latest('registration_datetime')
            ->paginate(12);

        $payment_total = (float) EventRegistration::query()
            ->where('event_id', $this->event->id)
            ->whereHas('paymentTransaction', fn($query) => $query->where('status', 'paid'))
            ->with('paymentTransaction:id,amount,status')
            ->get()
            ->sum(fn($registration) => (float) ($registration->paymentTransaction?->amount ?? 0));

        return view('livewire.events.show', [
            'event' => $this->event,
            'registrations' => $registrations,
            'payment_total' => $payment_total,
        ]);
    }
}
