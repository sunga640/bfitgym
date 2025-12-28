<?php

namespace App\Services\Classes;

use App\Models\ClassBooking;
use App\Models\ClassSession;
use App\Models\Member;
use App\Models\PaymentTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ClassBookingService
{
    /**
     * Check if a session has available capacity.
     */
    public function hasAvailableCapacity(ClassSession $session): bool
    {
        $effective_capacity = $session->effective_capacity;

        // Unlimited capacity
        if ($effective_capacity === null) {
            return true;
        }

        $booked_count = $this->getConfirmedBookingsCount($session);

        return $booked_count < $effective_capacity;
    }

    /**
     * Get the number of confirmed bookings for a session.
     */
    public function getConfirmedBookingsCount(ClassSession $session): int
    {
        return $session->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'attended'])
            ->count();
    }

    /**
     * Get available spots for a session.
     */
    public function getAvailableSpots(ClassSession $session): ?int
    {
        $effective_capacity = $session->effective_capacity;

        if ($effective_capacity === null) {
            return null; // Unlimited
        }

        $booked_count = $this->getConfirmedBookingsCount($session);

        return max(0, $effective_capacity - $booked_count);
    }

    /**
     * Check if a member has already booked a session.
     */
    public function memberHasBooking(ClassSession $session, Member $member): bool
    {
        return $session->bookings()
            ->where('member_id', $member->id)
            ->whereIn('status', ['pending', 'confirmed'])
            ->exists();
    }

    /**
     * Create a booking for a member.
     *
     * @throws \Exception
     */
    public function createBooking(
        ClassSession $session,
        Member $member,
        ?array $payment_data = null
    ): ClassBooking {
        return DB::transaction(function () use ($session, $member, $payment_data) {
            // Check capacity
            if (!$this->hasAvailableCapacity($session)) {
                throw new \Exception(__('This class session is fully booked.'));
            }

            // Check for duplicate booking
            if ($this->memberHasBooking($session, $member)) {
                throw new \Exception(__('Member already has a booking for this session.'));
            }

            // Get booking fee from class type
            $class_type = $session->classType;
            $booking_fee = $class_type->has_booking_fee ? $class_type->booking_fee : null;

            // Create payment transaction if booking fee applies
            $payment_transaction = null;
            if ($booking_fee !== null && $booking_fee > 0) {
                $payment_transaction = $this->createBookingPayment(
                    $member,
                    $session,
                    $booking_fee,
                    $payment_data ?? []
                );
            }

            // Create the booking
            $booking = ClassBooking::create([
                'branch_id' => $session->branch_id,
                'class_session_id' => $session->id,
                'member_id' => $member->id,
                'payment_transaction_id' => $payment_transaction?->id,
                'booked_at' => Carbon::now(),
                'status' => $payment_transaction ? 'confirmed' : 'pending',
                'booking_fee_amount' => $booking_fee,
            ]);

            // Update payment transaction with payable reference
            if ($payment_transaction) {
                $payment_transaction->update([
                    'payable_type' => ClassBooking::class,
                    'payable_id' => $booking->id,
                ]);
            }

            return $booking;
        });
    }

    /**
     * Create a payment transaction for a booking fee.
     */
    protected function createBookingPayment(
        Member $member,
        ClassSession $session,
        float $amount,
        array $payment_data
    ): PaymentTransaction {
        return PaymentTransaction::create([
            'branch_id' => $member->branch_id,
            'payer_type' => PaymentTransaction::PAYER_MEMBER,
            'payer_member_id' => $member->id,
            'amount' => $amount,
            'currency' => $payment_data['currency'] ?? 'TZS',
            'payment_method' => $payment_data['payment_method'] ?? 'cash',
            'reference' => $payment_data['reference'] ?? null,
            'paid_at' => Carbon::now(),
            'status' => PaymentTransaction::STATUS_PAID,
            'revenue_type' => PaymentTransaction::REVENUE_TYPE_CLASS_BOOKING,
            'notes' => __('Booking fee for :class on :date', [
                'class' => $session->classType->name,
                'date' => $session->specific_date
                    ? $session->specific_date->format('M d, Y')
                    : $this->getDayName($session->day_of_week),
            ]),
        ]);
    }

    /**
     * Cancel a booking.
     */
    public function cancelBooking(ClassBooking $booking, ?string $reason = null): ClassBooking
    {
        $booking->update([
            'status' => 'cancelled',
        ]);

        return $booking;
    }

    /**
     * Mark a booking as attended.
     */
    public function markAttended(ClassBooking $booking): ClassBooking
    {
        $booking->update(['status' => 'attended']);

        return $booking;
    }

    /**
     * Mark a booking as no-show.
     */
    public function markNoShow(ClassBooking $booking): ClassBooking
    {
        $booking->update(['status' => 'no_show']);

        return $booking;
    }

    /**
     * Get the day name from day number.
     */
    protected function getDayName(int $day): string
    {
        $days = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        return $days[$day] ?? 'Unknown';
    }
}

