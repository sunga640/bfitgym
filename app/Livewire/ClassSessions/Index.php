<?php

namespace App\Livewire\ClassSessions;

use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Location;
use App\Services\Classes\ClassBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $class_type_filter = '';

    #[Url]
    public string $day_filter = '';

    #[Url]
    public string $status_filter = '';

    protected ClassBookingService $booking_service;

    public function boot(ClassBookingService $booking_service): void
    {
        $this->booking_service = $booking_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingClassTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDayFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function cancelSession(int $session_id): void
    {
        try {
            $session = ClassSession::findOrFail($session_id);

            $this->authorize('update', $session);

            $session->update(['status' => 'cancelled']);

            Log::info('Class session cancelled', [
                'session_id' => $session_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Class session cancelled successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to cancel this session.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to cancel the session. Please try again.'));
        }
    }

    public function deleteSession(int $session_id): void
    {
        try {
            $session = ClassSession::findOrFail($session_id);

            $this->authorize('delete', $session);

            // Check for bookings
            if ($session->bookings()->whereIn('status', ['pending', 'confirmed'])->exists()) {
                session()->flash('error', __('Cannot delete session with active bookings. Cancel the session instead.'));
                return;
            }

            DB::beginTransaction();
            $session->delete();
            DB::commit();

            Log::info('Class session deleted', [
                'session_id' => $session_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Class session deleted successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to delete this session.'));
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', __('Failed to delete the session. Please try again.'));
        }
    }

    public function render(): View
    {
        $sessions = ClassSession::query()
            ->with(['classType', 'location', 'mainInstructor', 'assistantStaff'])
            ->withCount(['bookings' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->when($this->search, function ($query) {
                $query->whereHas('classType', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('location', fn($q) => $q->where('name', 'like', "%{$this->search}%"))
                    ->orWhereHas('mainInstructor', fn($q) => $q->where('name', 'like', "%{$this->search}%"));
            })
            ->when($this->class_type_filter, fn($query) => $query->where('class_type_id', $this->class_type_filter))
            ->when($this->day_filter, fn($query) => $query->where('day_of_week', $this->day_filter))
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->latest()
            ->paginate(12);

        // Add available spots to each session
        foreach ($sessions as $session) {
            $session->available_spots = $this->booking_service->getAvailableSpots($session);
        }

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        $class_types = ClassType::active()->orderBy('name')->get(['id', 'name']);
        $days = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday'),
        ];

        return view('livewire.class-sessions.index', [
            'sessions' => $sessions,
            'show_branch' => $show_branch,
            'class_types' => $class_types,
            'days' => $days,
        ]);
    }
}

