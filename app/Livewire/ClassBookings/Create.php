<?php

namespace App\Livewire\ClassBookings;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\ClassType;
use App\Models\Member;
use App\Services\Classes\ClassBookingService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Component;

class Create extends Component
{
    public ?int $class_session_id = null;
    public ?int $member_id = null;
    public string $payment_method = 'cash';
    public string $currency = 'TZS';
    public ?string $reference = null;

    // Search helpers
    public string $member_search = '';
    public string $session_search = '';

    protected ClassBookingService $booking_service;

    public function boot(ClassBookingService $booking_service): void
    {
        $this->booking_service = $booking_service;
    }

    public function rules(): array
    {
        return [
            'class_session_id' => ['required', 'exists:class_sessions,id'],
            'member_id' => ['required', 'exists:members,id'],
            'payment_method' => ['required', Rule::in(['cash', 'mobile_money', 'card', 'bank_transfer'])],
            'currency' => ['required', 'string', 'size:3'],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'class_session_id.required' => __('Please select a class session.'),
            'member_id.required' => __('Please select a member.'),
        ];
    }

    public function save(): void
    {
        $this->authorize('create', ClassBooking::class);
        $this->validate();

        try {
            $session = ClassSession::findOrFail($this->class_session_id);
            $member = Member::findOrFail($this->member_id);

            // Check if session is active
            if ($session->status !== 'active') {
                session()->flash('error', __('This session is not available for booking.'));
                return;
            }

            // Check capacity
            if (!$this->booking_service->hasAvailableCapacity($session)) {
                session()->flash('error', __('This class session is fully booked.'));
                return;
            }

            // Check for duplicate booking
            if ($this->booking_service->memberHasBooking($session, $member)) {
                session()->flash('error', __('This member already has a booking for this session.'));
                return;
            }

            // Prepare payment data if class has booking fee
            $payment_data = null;
            if ($session->classType->has_booking_fee) {
                $payment_data = [
                    'payment_method' => $this->payment_method,
                    'currency' => $this->currency,
                    'reference' => $this->reference,
                ];
            }

            // Create booking
            $booking = $this->booking_service->createBooking($session, $member, $payment_data);

            Log::info('Class booking created', [
                'booking_id' => $booking->id,
                'session_id' => $session->id,
                'member_id' => $member->id,
                'user_id' => auth()->id(),
            ]);

            session()->flash('success', __('Booking created successfully.'));
            $this->redirect(route('class-bookings.index'), navigate: true);
        } catch (\Exception $e) {
            Log::error('Failed to create booking', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);
            session()->flash('error', $e->getMessage());
        }
    }

    public function render(): View
    {
        // Get active class sessions
        $sessions = ClassSession::query()
            ->with(['classType', 'location', 'mainInstructor'])
            ->withCount(['bookings' => fn($q) => $q->whereIn('status', ['pending', 'confirmed', 'attended'])])
            ->active()
            ->when($this->session_search, function ($query) {
                $query->whereHas('classType', fn($q) => $q->where('name', 'like', "%{$this->session_search}%"))
                    ->orWhereHas('location', fn($q) => $q->where('name', 'like', "%{$this->session_search}%"));
            })
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->limit(20)
            ->get();

        // Add availability info to sessions
        foreach ($sessions as $session) {
            $session->available_spots = $this->booking_service->getAvailableSpots($session);
            $session->is_available = $this->booking_service->hasAvailableCapacity($session);
        }

        // Get active members
        $members = Member::query()
            ->active()
            ->when($this->member_search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->member_search}%")
                        ->orWhere('last_name', 'like', "%{$this->member_search}%")
                        ->orWhere('member_no', 'like', "%{$this->member_search}%")
                        ->orWhere('phone', 'like', "%{$this->member_search}%");
                });
            })
            ->orderBy('first_name')
            ->limit(20)
            ->get();

        // Get selected session for fee display
        $selected_session = $this->class_session_id
            ? $sessions->firstWhere('id', $this->class_session_id)
                ?? ClassSession::with('classType')->find($this->class_session_id)
            : null;

        // Check if selected member already has a booking for selected session
        $has_existing_booking = false;
        if ($selected_session && $this->member_id) {
            $member = Member::find($this->member_id);
            if ($member) {
                $has_existing_booking = $this->booking_service->memberHasBooking($selected_session, $member);
            }
        }

        $days = [
            1 => __('Monday'),
            2 => __('Tuesday'),
            3 => __('Wednesday'),
            4 => __('Thursday'),
            5 => __('Friday'),
            6 => __('Saturday'),
            7 => __('Sunday'),
        ];

        return view('livewire.class-bookings.create', [
            'sessions' => $sessions,
            'members' => $members,
            'selected_session' => $selected_session,
            'has_existing_booking' => $has_existing_booking,
            'days' => $days,
        ]);
    }
}

