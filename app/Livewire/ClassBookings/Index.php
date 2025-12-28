<?php

namespace App\Livewire\ClassBookings;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Services\Classes\ClassBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
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
    public string $status_filter = '';

    #[Url]
    public string $class_type_filter = '';

    protected ClassBookingService $booking_service;

    public function boot(ClassBookingService $booking_service): void
    {
        $this->booking_service = $booking_service;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingClassTypeFilter(): void
    {
        $this->resetPage();
    }

    public function confirmBooking(int $booking_id): void
    {
        try {
            $booking = ClassBooking::findOrFail($booking_id);
            $this->authorize('update', $booking);

            $booking->update(['status' => 'confirmed']);

            session()->flash('success', __('Booking confirmed successfully.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to confirm booking.'));
        }
    }

    public function cancelBooking(int $booking_id): void
    {
        try {
            $booking = ClassBooking::findOrFail($booking_id);
            $this->authorize('cancel', $booking);

            $this->booking_service->cancelBooking($booking);

            Log::info('Class booking cancelled', [
                'booking_id' => $booking_id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Booking cancelled successfully.'));
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            session()->flash('error', __('You do not have permission to cancel this booking.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to cancel booking.'));
        }
    }

    public function markAttended(int $booking_id): void
    {
        try {
            $booking = ClassBooking::findOrFail($booking_id);
            $this->authorize('update', $booking);

            $this->booking_service->markAttended($booking);

            session()->flash('success', __('Booking marked as attended.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to update booking.'));
        }
    }

    public function markNoShow(int $booking_id): void
    {
        try {
            $booking = ClassBooking::findOrFail($booking_id);
            $this->authorize('update', $booking);

            $this->booking_service->markNoShow($booking);

            session()->flash('success', __('Booking marked as no-show.'));
        } catch (\Exception $e) {
            session()->flash('error', __('Failed to update booking.'));
        }
    }

    public function render(): View
    {
        $bookings = ClassBooking::query()
            ->with(['classSession.classType', 'classSession.location', 'member', 'paymentTransaction'])
            ->when($this->search, function ($query) {
                $query->whereHas('member', function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('member_no', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status_filter, fn($query) => $query->where('status', $this->status_filter))
            ->when($this->class_type_filter, function ($query) {
                $query->whereHas('classSession', fn($q) => $q->where('class_type_id', $this->class_type_filter));
            })
            ->latest()
            ->paginate(15);

        $user = Auth::user();
        $show_branch = $user && $user->hasRole('super-admin');

        $class_types = ClassType::active()->orderBy('name')->get(['id', 'name']);

        return view('livewire.class-bookings.index', [
            'bookings' => $bookings,
            'show_branch' => $show_branch,
            'class_types' => $class_types,
        ]);
    }
}

