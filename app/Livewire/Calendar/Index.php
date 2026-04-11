<?php

namespace App\Livewire\Calendar;

use App\Models\ClassSession;
use App\Models\Event;
use App\Services\BranchContext;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app', ['title' => 'Calendar', 'description' => 'View class schedules and events.'])]
#[Title('Calendar')]
class Index extends Component
{
    #[Url]
    public string $view_mode = 'month'; // month, week

    #[Url]
    public string $current_date = '';

    #[Url]
    public string $filter_type = 'all'; // all, classes, events

    // Modal state
    public bool $show_detail_modal = false;
    public ?string $detail_type = null;
    public ?int $detail_id = null;
    public array $detail_data = [];

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

    public function previousPeriod(): void
    {
        $date = Carbon::parse($this->current_date);

        if ($this->view_mode === 'month') {
            $this->current_date = $date->subMonth()->format('Y-m-d');
        } else {
            $this->current_date = $date->subWeek()->format('Y-m-d');
        }
    }

    public function nextPeriod(): void
    {
        $date = Carbon::parse($this->current_date);

        if ($this->view_mode === 'month') {
            $this->current_date = $date->addMonth()->format('Y-m-d');
        } else {
            $this->current_date = $date->addWeek()->format('Y-m-d');
        }
    }

    public function goToToday(): void
    {
        $this->current_date = now()->format('Y-m-d');
    }

    public function setViewMode(string $mode): void
    {
        $this->view_mode = $mode;
    }

    public function showClassDetail(int $session_id, string $date): void
    {
        $session = ClassSession::with(['classType', 'location', 'mainInstructor', 'assistantStaff'])
            ->withCount(['bookings' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->find($session_id);

        if (!$session) {
            return;
        }

        $this->detail_type = 'class';
        $this->detail_id = $session_id;
        $this->detail_data = [
            'name' => $session->classType?->name ?? 'Unknown Class',
            'description' => $session->classType?->description,
            'date' => Carbon::parse($date)->format('l, F j, Y'),
            'start_time' => Carbon::parse($session->start_time)->format('g:i A'),
            'end_time' => Carbon::parse($session->end_time)->format('g:i A'),
            'location' => $session->location?->name,
            'instructor' => $session->mainInstructor?->name,
            'assistants' => $session->assistantStaff->pluck('name')->toArray(),
            'capacity' => $session->effective_capacity,
            'booked' => $session->bookings_count,
            'has_booking_fee' => $session->classType?->has_booking_fee ?? false,
            'booking_fee' => $session->classType?->booking_fee,
            'status' => $session->status,
            'is_recurring' => $session->is_recurring,
        ];
        $this->show_detail_modal = true;
    }

    public function showEventDetail(int $event_id): void
    {
        $event = Event::withCount(['registrations' => fn($q) => $q
            ->where('will_attend', true)
            ->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->find($event_id);

        if (!$event) {
            return;
        }

        $this->detail_type = 'event';
        $this->detail_id = $event_id;
        $this->detail_data = [
            'title' => $event->title,
            'description' => $event->description,
            'type' => $event->type,
            'date' => $event->start_datetime->format('l, F j, Y'),
            'start_time' => $event->start_datetime->format('g:i A'),
            'end_time' => $event->end_datetime?->format('g:i A'),
            'location' => $event->location,
            'capacity' => $event->capacity,
            'registered' => $event->registrations_count,
            'is_paid' => $event->is_paid,
            'payment_required' => (bool) $event->payment_required,
            'price' => $event->price,
            'allow_non_members' => $event->allow_non_members,
            'status' => $event->status,
        ];
        $this->show_detail_modal = true;
    }

    public function closeDetailModal(): void
    {
        $this->show_detail_modal = false;
        $this->detail_type = null;
        $this->detail_id = null;
        $this->detail_data = [];
    }

    protected function getCalendarData(): array
    {
        $current = Carbon::parse($this->current_date);

        if ($this->view_mode === 'month') {
            $start_date = $current->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
            $end_date = $current->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        } else {
            $start_date = $current->copy()->startOfWeek(Carbon::MONDAY);
            $end_date = $current->copy()->endOfWeek(Carbon::SUNDAY);
        }

        $days = [];
        $date = $start_date->copy();

        while ($date <= $end_date) {
            $days[] = [
                'date' => $date->format('Y-m-d'),
                'day' => $date->day,
                'day_name' => $date->format('D'),
                'is_today' => $date->isToday(),
                'is_current_month' => $date->month === $current->month,
                'day_of_week' => $date->dayOfWeekIso, // 1 = Monday, 7 = Sunday
            ];
            $date->addDay();
        }

        return [
            'days' => $days,
            'start_date' => $start_date->format('Y-m-d'),
            'end_date' => $end_date->format('Y-m-d'),
            'current_month' => $current->format('F Y'),
            'current_week' => $current->weekOfYear,
        ];
    }

    protected function getClassSessions(string $start_date, string $end_date): array
    {
        if ($this->filter_type === 'events') {
            return [];
        }

        $sessions = ClassSession::query()
            ->with(['classType', 'location', 'mainInstructor'])
            ->active()
            ->get();

        $calendar_items = [];
        $start = Carbon::parse($start_date);
        $end = Carbon::parse($end_date);

        foreach ($sessions as $session) {
            if ($session->is_recurring && $session->day_of_week) {
                // Generate occurrences for recurring sessions
                $date = $start->copy();
                while ($date <= $end) {
                    if ($date->dayOfWeekIso === $session->day_of_week) {
                        $date_str = $date->format('Y-m-d');
                        $calendar_items[$date_str][] = [
                            'id' => $session->id,
                            'type' => 'class',
                            'name' => $session->classType?->name ?? 'Class',
                            'start_time' => Carbon::parse($session->start_time)->format('H:i'),
                            'end_time' => Carbon::parse($session->end_time)->format('H:i'),
                            'location' => $session->location?->name,
                            'instructor' => $session->mainInstructor?->name,
                            'color' => $this->getClassColor($session->classType?->name ?? ''),
                        ];
                    }
                    $date->addDay();
                }
            } elseif ($session->specific_date) {
                // One-off session
                $date_str = $session->specific_date->format('Y-m-d');
                if ($date_str >= $start_date && $date_str <= $end_date) {
                    $calendar_items[$date_str][] = [
                        'id' => $session->id,
                        'type' => 'class',
                        'name' => $session->classType?->name ?? 'Class',
                        'start_time' => Carbon::parse($session->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($session->end_time)->format('H:i'),
                        'location' => $session->location?->name,
                        'instructor' => $session->mainInstructor?->name,
                        'color' => $this->getClassColor($session->classType?->name ?? ''),
                    ];
                }
            }
        }

        return $calendar_items;
    }

    protected function getEvents(string $start_date, string $end_date): array
    {
        if ($this->filter_type === 'classes') {
            return [];
        }

        $events = Event::query()
            ->scheduled()
            ->where(function ($query) use ($start_date, $end_date) {
                $query
                    ->whereBetween('start_datetime', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                    ->orWhereBetween('end_datetime', [$start_date . ' 00:00:00', $end_date . ' 23:59:59'])
                    ->orWhere(function ($nested_query) use ($start_date, $end_date) {
                        $nested_query
                            ->where('start_datetime', '<=', $start_date . ' 00:00:00')
                            ->where('end_datetime', '>=', $end_date . ' 23:59:59');
                    });
            })
            ->get();

        $calendar_items = [];

        foreach ($events as $event) {
            $event_start = $event->start_datetime->copy()->startOfDay();
            $event_end = $event->end_datetime?->copy()->endOfDay() ?? $event_start->copy();
            $display_date = $event_start->copy();

            while ($display_date->lte($event_end)) {
                $date_str = $display_date->format('Y-m-d');
                if ($date_str >= $start_date && $date_str <= $end_date) {
                    $visual_type = $event->payment_required ? 'paid' : $event->type;

                    $calendar_items[$date_str][] = [
                        'id' => $event->id,
                        'type' => 'event',
                        'name' => $event->title,
                        'start_time' => $event->start_datetime->format('H:i'),
                        'end_time' => $event->end_datetime?->format('H:i'),
                        'location' => $event->location,
                        'event_type' => $visual_type,
                        'color' => $this->getEventColor($visual_type),
                    ];
                }

                $display_date->addDay();
            }
        }

        // Keep each date bucket ordered by start time.
        foreach ($calendar_items as $date => &$items) {
            usort($items, fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
        }

        return $calendar_items;
    }

    protected function getClassColor(string $class_name): string
    {
        $colors = [
            'yoga' => 'emerald',
            'pilates' => 'teal',
            'zumba' => 'pink',
            'hiit' => 'orange',
            'spin' => 'blue',
            'boxing' => 'red',
            'strength' => 'purple',
            'cardio' => 'amber',
        ];

        $name_lower = strtolower($class_name);
        foreach ($colors as $keyword => $color) {
            if (str_contains($name_lower, $keyword)) {
                return $color;
            }
        }

        return 'blue';
    }

    protected function getEventColor(string $event_type): string
    {
        return match ($event_type) {
            'public' => 'violet',
            'paid' => 'amber',
            'internal' => 'slate',
            default => 'violet',
        };
    }

    public function render(): View
    {
        $calendar_data = $this->getCalendarData();
        $class_items = $this->getClassSessions($calendar_data['start_date'], $calendar_data['end_date']);
        $event_items = $this->getEvents($calendar_data['start_date'], $calendar_data['end_date']);

        // Merge calendar items
        $calendar_items = [];
        foreach (array_merge(array_keys($class_items), array_keys($event_items)) as $date) {
            $calendar_items[$date] = array_merge(
                $class_items[$date] ?? [],
                $event_items[$date] ?? []
            );

            // Sort by start time
            usort($calendar_items[$date], fn($a, $b) => strcmp($a['start_time'], $b['start_time']));
        }

        return view('livewire.calendar.index', [
            'calendar_data' => $calendar_data,
            'calendar_items' => $calendar_items,
        ]);
    }
}
