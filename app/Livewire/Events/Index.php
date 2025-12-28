<?php

namespace App\Livewire\Events;

use App\Models\Event;
use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app', ['title' => 'Events', 'description' => 'Manage gym events and activities.'])]
#[Title('Events')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $type_filter = '';

    #[Url]
    public string $status_filter = '';

    #[Url]
    public string $view_mode = 'list'; // list, calendar

    #[Url]
    public string $current_date = '';

    // Modal states for quick view
    public bool $show_event_modal = false;
    public ?int $selected_event_id = null;
    public array $selected_event_data = [];

    protected BranchContext $branch_context;

    public function boot(BranchContext $branch_context): void
    {
        $this->branch_context = $branch_context;
    }

    public function mount(): void
    {
        if (empty($this->current_date)) {
            $this->current_date = now()->format('Y-m-d');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setViewMode(string $mode): void
    {
        $this->view_mode = $mode;
    }

    public function previousMonth(): void
    {
        $date = Carbon::parse($this->current_date);
        $this->current_date = $date->subMonth()->format('Y-m-d');
    }

    public function nextMonth(): void
    {
        $date = Carbon::parse($this->current_date);
        $this->current_date = $date->addMonth()->format('Y-m-d');
    }

    public function goToToday(): void
    {
        $this->current_date = now()->format('Y-m-d');
    }

    public function showEventQuickView(int $event_id): void
    {
        $event = Event::withCount(['registrations' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->find($event_id);

        if (!$event) {
            return;
        }

        $this->selected_event_id = $event_id;
        $this->selected_event_data = [
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'start_date' => $event->start_datetime->format('l, F j, Y'),
            'start_time' => $event->start_datetime->format('g:i A'),
            'end_time' => $event->end_datetime?->format('g:i A'),
            'location' => $event->location,
            'capacity' => $event->capacity,
            'registered' => $event->registrations_count,
            'is_paid' => $event->is_paid,
            'price' => $event->price,
            'allow_non_members' => $event->allow_non_members,
            'status' => $event->status,
        ];
        $this->show_event_modal = true;
    }

    public function closeEventModal(): void
    {
        $this->show_event_modal = false;
        $this->selected_event_id = null;
        $this->selected_event_data = [];
    }

    public function cancelEvent(int $event_id): void
    {
        try {
            $event = Event::findOrFail($event_id);
            $this->authorize('update', $event);

            $event->update(['status' => 'cancelled']);

            Log::info('Event cancelled', [
                'event_id' => $event_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Event cancelled successfully.'));
            $this->closeEventModal();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to cancel this event.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to cancel the event. Please try again.'));
        }
    }

    public function deleteEvent(int $event_id): void
    {
        try {
            $event = Event::findOrFail($event_id);
            $this->authorize('delete', $event);

            // Check for registrations
            if ($event->registrations()->whereIn('status', ['pending', 'confirmed'])->exists()) {
                session()->flash('error', __('Cannot delete event with active registrations. Cancel the event instead.'));
                return;
            }

            DB::beginTransaction();
            $event->delete();
            DB::commit();

            Log::info('Event deleted', [
                'event_id' => $event_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Event deleted successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this event.'));
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to delete the event. Please try again.'));
        }
    }

    protected function getCalendarData(): array
    {
        $current = Carbon::parse($this->current_date);
        $start_date = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $end_date = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $days = [];
        $date = $start_date->copy();

        while ($date <= $end_date) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'day_name' => $date->format('D'),
                'is_today' => $date->isToday(),
                'is_current_month' => $date->month === $current->month,
                'is_weekend' => $date->isWeekend(),
            ];
            $date->addDay();
        }

        return [
            'days' => $days,
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
            'current_month' => $current->format('F Y'),
        ];
    }

    protected function getCalendarEvents(string $start_date, string $end_date): array
    {
        $events = Event::query()
            ->where('start_datetime', '>=', $start_date . ' 00:00:00')
            ->where('start_datetime', '<=', $end_date . ' 23:59:59')
            ->when($this->type_filter, fn($query) => $query->where('type', $this->type_filter))
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->withCount(['registrations' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->get();

        $calendar_events = [];

        foreach ($events as $event) {
            $date_str = $event->start_datetime->format('Y-m-d');
            $calendar_events[$date_str][] = [
                'id' => $event->id,
                'title' => $event->title,
                'start_time' => $event->start_datetime->format('H:i'),
                'end_time' => $event->end_datetime?->format('H:i'),
                'location' => $event->location,
                'type' => $event->type,
                'status' => $event->status,
                'registrations_count' => $event->registrations_count,
                'capacity' => $event->capacity,
                'is_paid' => $event->is_paid,
                'price' => $event->price,
            ];
        }

        // Sort events by start time within each day
        foreach ($calendar_events as $date => &$events) {
            usort($events, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
        }

        return $calendar_events;
    }

    public function render(): View
    {
        $user = Auth::user();

        if ($this->view_mode === 'calendar') {
            $calendar_data = $this->getCalendarData();
            $calendar_events = $this->getCalendarEvents($calendar_data['start_date'], $calendar_data['end_date']);

            return view('livewire.events.index', [
                'calendar_data' => $calendar_data,
                'calendar_events' => $calendar_events,
                'events' => null,
            ]);
        }

        // List view
        $events = Event::query()
            ->withCount(['registrations' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('title', 'like', "%{$this->search}%")
                        ->orWhere('description', 'like', "%{$this->search}%")
                        ->orWhere('location', 'like', "%{$this->search}%");
                });
            })
            ->when($this->type_filter, fn($query) => $query->where('type', $this->type_filter))
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->latest('start_datetime')
            ->paginate(12);

        return view('livewire.events.index', [
            'events' => $events,
            'calendar_data' => null,
            'calendar_events' => null,
        ]);
    }
}

